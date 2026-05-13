<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\Message;
use App\Models\MessageAnalysis;
use App\Services\ClaudeService;
use App\Services\ManusService;
use App\Services\OpenAiService;
use Illuminate\Http\Request;

class MessageAnalyzeController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate(['message_id' => 'required|integer|exists:messages,id']);

        $msg  = Message::with('sender', 'conversation.participants')->findOrFail($request->message_id);
        $conv = $msg->conversation;

        // 권한 확인: 참여자 또는 관리자
        $isAdmin = auth('admin')->check();
        $user    = auth()->user() ?? auth('admin')->user();
        if (!$user) abort(401);
        if (!$isAdmin && auth()->check() && !$conv->participants->contains('id', auth()->id())) {
            abort(403);
        }

        // 캐시된 분석 결과 확인
        $cached = MessageAnalysis::where('message_id', $msg->id)->first();
        if ($cached) {
            return response()->json(['ok' => true, 'result' => $cached->result]);
        }

        // 컨텍스트: 해당 메시지 이전/이후 각 6개
        $context = Message::with('sender')
            ->where('conversation_id', $conv->id)
            ->orderBy('created_at')
            ->get();

        $msgIndex = $context->search(fn($m) => $m->id === $msg->id);
        $start    = max(0, $msgIndex - 6);
        $slice    = $context->slice($start, 13)->values();

        $lines = $slice->map(function ($m) use ($msg) {
            $name   = $m->sender?->name ?? '알수없음';
            $body   = $m->body ?: ($m->file_name ? "[파일: {$m->file_name}]" : '');
            $marker = $m->id === $msg->id ? ' ★(분석 대상)' : '';
            return "[{$name}]{$marker}: {$body}";
        })->join("\n");

        $system = <<<PROMPT
당신은 대화 분석 전문가입니다.
주어진 채팅 대화에서 ★(분석 대상) 으로 표시된 메시지를 분석해주세요.
반드시 아래 JSON 형식으로만 응답하세요. 다른 텍스트는 포함하지 마세요.

{
  "summary": "메시지의 핵심 내용 한 문장 요약",
  "intent": "작성자의 의도 또는 목적",
  "tone": "어조 (예: 요청, 답변, 불만, 감사, 확인, 질문 등)",
  "keywords": ["핵심", "키워드", "최대3개"],
  "context_note": "앞뒤 문맥과의 관계 또는 특이사항 (없으면 null)"
}
PROMPT;

        $setting  = AiSetting::current();
        $msgs     = [['role' => 'user', 'content' => "다음 대화를 분석해주세요:\n\n{$lines}"]];
        $lastErr  = null;

        // 순서: Claude → GPT → Manus
        $providers = [
            'Claude' => fn() => ($k = $setting->anthropicKey())
                ? (new ClaudeService($k))->chatRaw($msgs, $system)
                : null,
            'GPT'    => fn() => ($k = $setting->openaiKey())
                ? (new OpenAiService($k))->chatRaw($msgs, $system)
                : null,
            'Manus'  => fn() => ($k = $setting->manusKey())
                ? (new ManusService($k, $setting->manusEndpoint()))->chatRaw($msgs, $system)
                : null,
        ];

        foreach ($providers as $name => $call) {
            try {
                $raw = $call();
                if ($raw === null) continue; // 키 없음, 다음으로
                preg_match('/\{[\s\S]*\}/', $raw, $m2);
                $data = $m2 ? json_decode($m2[0], true) : null;
                if (!$data) { $lastErr = "{$name}: 응답 파싱 실패"; continue; }
                MessageAnalysis::create(['message_id' => $msg->id, 'result' => $data]);
                return response()->json(['ok' => true, 'result' => $data]);
            } catch (\Throwable $e) {
                $lastErr = "{$name} API 오류: " . $e->getMessage();
                \App\Models\SystemErrorLog::record($e, 'warning');
            }
        }

        return response()->json([
            'error' => $lastErr ?? '웍스 API 키가 설정되지 않았습니다.',
        ], 503);
    }
}
