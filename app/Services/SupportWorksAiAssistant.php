<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\AiSetting;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\SystemErrorLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 방문 상담/문의 채팅에서 관리자가 답변하기 전 AI가 SupportWorks 기능·사용법 한정으로 자동 응답.
 * Claude → OpenAI 폴백.
 */
class SupportWorksAiAssistant
{
    public const BODY_PREFIX     = '[관리자 AI 도우미] ';
    public const ADMIN_PREFIX    = '[관리자';
    private const HISTORY_LIMIT  = 12;

    // 토큰 소모 방지 가드레일
    private const MAX_AI_REPLIES_PER_CONV     = 5;     // 한 대화당 AI 자동 응답 횟수 상한
    private const MIN_AI_COOLDOWN_SECONDS     = 4;     // 직전 AI 응답 후 최소 대기 시간
    private const MIN_USER_MSG_CHARS          = 2;     // 너무 짧은 메시지는 무시
    private const MAX_AI_REPLIES_PER_USER_HR  = 30;    // 사용자(또는 게스트 user 레코드)당 시간당 상한

    /**
     * 응답을 비동기(요청 종료 후)로 생성·저장·브로드캐스트.
     * 사용자 응답 지연을 방지하기 위해 app()->terminating() 으로 큐잉.
     */
    public function scheduleReply(Conversation $conversation): void
    {
        $convId = $conversation->id;

        app()->terminating(function () use ($convId) {
            set_time_limit(0);
            try {
                $this->generateAndStore($convId);
            } catch (\Throwable $e) {
                SystemErrorLog::record($e, 'warning');
            }
        });
    }

    private function generateAndStore(int $conversationId): void
    {
        $conversation = Conversation::with(['messages' => fn($q) => $q->orderBy('id')])->find($conversationId);
        if (!$conversation || $conversation->type !== 'inquiry' || $conversation->status === 'closed') {
            return;
        }

        if ($this->humanAdminReplied($conversation)) {
            return;
        }

        if ($skipReason = $this->guardrailSkipReason($conversation)) {
            Log::info('[SupportWorksAiAssistant] skip: ' . $skipReason . ' (conv ' . $conversation->id . ')');
            return;
        }

        $settings = AiSetting::current();
        $anthropic = $settings->anthropicKey();
        $openai    = $settings->openaiKey();
        if (!$anthropic && !$openai) {
            return;
        }

        $messages = $this->buildMessages($conversation);
        if (empty($messages)) return;

        $orchestrator = new AiOrchestrator($anthropic, $openai);

        try {
            $result = $orchestrator->chatRaw($messages, $this->systemPrompt());
            $text   = trim((string) ($result['text'] ?? ''));
        } catch (\Throwable $e) {
            Log::warning('[SupportWorksAiAssistant] AI 호출 실패: ' . $e->getMessage());
            return;
        }

        if ($text === '') return;

        if ($this->humanAdminReplied($conversation->refresh())) {
            return;
        }

        $senderId = $this->resolveSenderId($conversation);
        if (!$senderId) return;

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $senderId,
            'body'            => self::BODY_PREFIX . $text,
        ]);

        if ($conversation->status === 'open') {
            $conversation->update(['status' => 'active']);
        } else {
            $conversation->touch();
        }

        broadcast(new MessageSent($msg));
    }

    private function humanAdminReplied(Conversation $conv): bool
    {
        return $conv->messages()
            ->where('body', 'like', self::ADMIN_PREFIX . '%')
            ->where('body', 'not like', self::BODY_PREFIX . '%')
            ->exists();
    }

    private function guardrailSkipReason(Conversation $conv): ?string
    {
        // 1) 한 대화당 AI 응답 횟수 상한
        $aiCount = $conv->messages()
            ->where('body', 'like', self::BODY_PREFIX . '%')
            ->count();
        if ($aiCount >= self::MAX_AI_REPLIES_PER_CONV) {
            return 'per_conv_cap';
        }

        // 2) 직전 AI 응답 후 최소 대기 시간
        $lastAi = $conv->messages()
            ->where('body', 'like', self::BODY_PREFIX . '%')
            ->latest('id')->first();
        if ($lastAi && $lastAi->created_at && $lastAi->created_at->diffInSeconds(now()) < self::MIN_AI_COOLDOWN_SECONDS) {
            return 'cooldown';
        }

        // 3) 마지막 사용자 메시지 검증 (너무 짧거나 비어있음)
        $lastUser = $conv->messages()
            ->where('body', 'not like', self::ADMIN_PREFIX . '%')
            ->latest('id')->first();
        if (!$lastUser) return 'no_user_msg';

        $lastBody = trim(preg_replace('/\s+/u', ' ', (string) $lastUser->body));
        if (mb_strlen($lastBody) < self::MIN_USER_MSG_CHARS) {
            return 'too_short';
        }

        // 4) 직전 사용자 메시지와 본문이 동일하면 중복
        $priorUser = $conv->messages()
            ->where('body', 'not like', self::ADMIN_PREFIX . '%')
            ->where('id', '<', $lastUser->id)
            ->latest('id')->first();
        if ($priorUser) {
            $priorBody = trim(preg_replace('/\s+/u', ' ', (string) $priorUser->body));
            if (mb_strtolower($priorBody) === mb_strtolower($lastBody)) {
                return 'duplicate_message';
            }
        }

        // 5) 사용자(또는 게스트 user 레코드) 단위 시간당 상한
        $userId = $conv->messages()->orderBy('id')->value('sender_id');
        if ($userId) {
            $convIds = DB::table('conversation_user')->where('user_id', $userId)->pluck('conversation_id');
            if ($convIds->isNotEmpty()) {
                $perUserHour = Message::whereIn('conversation_id', $convIds)
                    ->where('body', 'like', self::BODY_PREFIX . '%')
                    ->where('created_at', '>=', now()->subHour())
                    ->count();
                if ($perUserHour >= self::MAX_AI_REPLIES_PER_USER_HR) {
                    return 'per_user_hour_cap';
                }
            }
        }

        return null;
    }

    private function buildMessages(Conversation $conv): array
    {
        $rows = $conv->messages->reverse()->take(self::HISTORY_LIMIT)->reverse()->values();

        $out = [];
        foreach ($rows as $m) {
            $isAi = str_starts_with($m->body, self::BODY_PREFIX);
            if ($isAi) {
                $out[] = ['role' => 'assistant', 'content' => substr($m->body, strlen(self::BODY_PREFIX))];
            } elseif (str_starts_with($m->body, self::ADMIN_PREFIX)) {
                // 사람 관리자 응답이 있으면 AI 응답 자체를 보내지 않도록 위에서 이미 차단
                $out[] = ['role' => 'assistant', 'content' => preg_replace('/^\[관리자 .+?\] /', '', $m->body)];
            } else {
                $out[] = ['role' => 'user', 'content' => $m->body];
            }
        }

        if (empty($out) || ($out[count($out) - 1]['role'] ?? '') !== 'user') {
            return [];
        }

        return $out;
    }

    private function resolveSenderId(Conversation $conv): ?int
    {
        if ($conv->assigned_agent_id) return $conv->assigned_agent_id;

        $customerId = $conv->messages()->orderBy('id')->value('sender_id');
        $other = $conv->participants()->where('users.id', '!=', $customerId)->value('users.id');
        if ($other) return $other;

        return User::where('role', 'admin')->value('id');
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
당신은 SupportWorks 안내 도우미입니다. 한국어로 친절하고 간결하게(2~5문장, 필요 시 짧은 글머리표) 답변하세요.

답변 범위: SupportWorks 제품의 기능 설명과 사용 방법에 한정합니다. 그 외(개인 정보, 가격 협상, 계약, 환불, 결제, 법률·의료·금융 상담, 일반 시사·잡담 등)는
"해당 문의는 담당자가 직접 답변드릴 예정입니다. 잠시만 기다려주세요."처럼 정중히 안내하고 답변을 멈추세요.

SupportWorks 핵심 기능 요약:
- 1:1 문의 채팅(이미지 첨부·드로잉·댓글 가능), 실시간 알림(Pusher 기반)
- 프로젝트 보드(Kanban·마일스톤·이슈·작업/하위작업 관리)
- 요구사항 관리(URS, 변경 이력, 산출물 승인)
- 기획 문서 및 디스커션(파일 댓글 → 디스커션 변환, 반영 워크플로)
- AI 분석: 메시지·회의록에서 액션 아이템 자동 추출, 주간 AI 요약 리포트
- AI 에이전트 세션(Figma 분석, IA/와이어프레임/디자인 시스템·코드 생성 등)
- 파일 버전 관리·미리보기(PDF 변환), 외부 공유 링크
- 사용자/팀/회사 그룹 관리, 관리자 권한(super_admin/operator/support_agent)
- 모바일 앱, 데스크탑/IDE 연동

답변 톤·규칙:
- 답변 첫 줄에 결론을 먼저 제시하고, 필요 시 단계나 메뉴 경로를 보여주세요.
- 명령어·코드는 백틱(`)으로 표시하지 마세요. 본문은 일반 텍스트로 작성합니다.
- 제품에 없는 기능을 단정해서 약속하지 말고 "담당자에게 확인 후 안내" 식으로 처리하세요.
- 답변 마지막 줄에 항상 "추가 도움이 필요하시면 담당자가 곧 이어서 답변드립니다." 와 같은 안내 한 줄을 덧붙이세요.
PROMPT;
    }
}
