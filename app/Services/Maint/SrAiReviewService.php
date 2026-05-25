<?php

namespace App\Services\Maint;

use App\Models\AiSetting;
use App\Models\Maint\MaintRequest;
use App\Services\AiOrchestrator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 신규 SR 등록 직후 자동 AI 분석 (요청자 확인용).
 *
 * SrSummaryService(담당자용 정리)와 별도. 본 서비스는:
 *   - 요청자가 자기 의도가 맞는지 다시 확인하도록 "재정리본" 생성
 *   - 예상 난이도(1~5)·작업량(자연어) 추정
 *   - 모호한 부분이 있으면 "확인 질문" 자동 추출
 * 결과는 maint_requests.ai_review_* 컬럼에 저장.
 */
class SrAiReviewService
{
    private const CONTENT_CAP = 4000;

    /**
     * 분석 실행 후 모델을 직접 갱신한다.
     * 예외는 던지지 않고 ai_review_status='failed' / ai_review_error 에 기록.
     */
    public function analyze(MaintRequest $req): void
    {
        $req->forceFill([
            'ai_review_status' => 'analyzing',
            'ai_review_at'     => now(),
        ])->save();

        try {
            $settings = AiSetting::current();
            $orch = new AiOrchestrator(
                $settings->anthropicKey(),
                $settings->openaiKey(),
                $settings->manusKey(),
                $settings->manusEndpoint(),
            );

            $result = $orch->generateDraft(
                $this->systemPrompt(),
                $this->buildUserPrompt($req),
                $this->fieldSchema(),
            );

            $fields = $result['fields'] ?? [];

            $questions = [];
            foreach ((array) ($fields['questions'] ?? []) as $q) {
                $text = is_string($q) ? trim($q) : trim((string) ($q['q'] ?? ''));
                if ($text === '') continue;
                $questions[] = ['q' => $text, 'a' => null];
            }

            $difficulty = isset($fields['difficulty']) ? (int) $fields['difficulty'] : null;
            if ($difficulty !== null && ($difficulty < 1 || $difficulty > 5)) {
                $difficulty = null;
            }

            $req->forceFill([
                'ai_review_summary'    => trim((string) ($fields['summary'] ?? '')) ?: null,
                'ai_review_difficulty' => $difficulty,
                'ai_review_effort'     => Str::limit(trim((string) ($fields['effort'] ?? '')), 50, '') ?: null,
                'ai_review_questions'  => $questions ?: null,
                'ai_review_status'     => 'ready',
                'ai_review_error'      => null,
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('[SrAiReviewService] 분석 실패 SR #' . $req->id . ': ' . $e->getMessage());
            $req->forceFill([
                'ai_review_status' => 'failed',
                'ai_review_error'  => Str::limit($e->getMessage(), 1000, ''),
            ])->save();
        }
    }

    private function systemPrompt(): string
    {
        return <<<SYS
당신은 SupportWorks 의 SR(유지보수 요청) 사전 검토 보조입니다.

방금 요청자가 작성·제출한 SR을 받아, **요청자 본인이 자기 의도가 맞는지 다시 확인할 수 있도록** 다음 4가지를 산출합니다.

1. summary  — 요청을 평이한 한국어 업무체로 재정리한 본문 (markdown). 누락된 정보는 추측하지 말 것.
2. difficulty — 예상 난이도 정수 1~5 (1=매우 쉬움, 5=매우 어려움). 정보가 너무 부족하면 null.
3. effort — 예상 소요 작업량 자연어 (예: "0.5일", "1~2일", "반나절"). 모르면 빈 문자열.
4. questions — 모호하거나 누락된 정보가 있을 때 요청자에게 다시 물어볼 짧은 질문 배열 (0~5개). 추측 가능하면 빈 배열.

## summary 작성 규칙
- 핵심을 위로: 무엇을(목적) → 어디서(메뉴/화면) → 왜(배경) → 기대 결과.
- 모호한 표현은 명확히 ("그거", "거기" → 구체 메뉴/필드).
- 추측·확장 금지. 요청자가 명시한 내용만 다듬는다.

## questions 작성 규칙
- 한 줄 1문장. "예/아니오"로 답할 수 있게 구체적으로.
- 정보 누락 또는 동음이의어 등 실제로 결정이 어려운 항목만.
- 의미 없는 확인 질문(예: "맞습니까?") 금지.
SYS;
    }

    private function buildUserPrompt(MaintRequest $req): string
    {
        $lines = [];
        $lines[] = '## 새 SR (분석 대상)';
        $lines[] = '- 메뉴: ' . ($req->menu?->name ?? '—');
        $lines[] = '- 구분: ' . ($req->category ?? '—');
        $lines[] = '- 우선순위: ' . ($req->priority ?? '—');
        $lines[] = '- 요약 (사용자 입력): ' . trim((string) $req->summary);
        $lines[] = '';
        $lines[] = '### 상세 내용 (원본)';
        $lines[] = Str::limit((string) $req->content, self::CONTENT_CAP);
        return implode("\n", $lines);
    }

    private function fieldSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['summary', 'difficulty', 'effort', 'questions'],
            'properties' => [
                'summary'    => ['type' => 'string', 'description' => '재정리된 SR 본문 (markdown).'],
                'difficulty' => ['type' => ['integer', 'null'], 'minimum' => 1, 'maximum' => 5, 'description' => '예상 난이도 1~5 또는 null.'],
                'effort'     => ['type' => 'string', 'description' => '예상 작업량 자연어. 모르면 빈 문자열.'],
                'questions'  => [
                    'type'        => 'array',
                    'description' => '요청자에게 다시 물어볼 짧은 질문 (0~5개, 없으면 빈 배열).',
                    'items'       => ['type' => 'string'],
                ],
            ],
        ];
    }
}
