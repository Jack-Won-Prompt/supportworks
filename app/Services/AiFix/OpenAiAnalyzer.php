<?php

namespace App\Services\AiFix;

use App\Models\SystemErrorLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI Chat Completions API 를 호출해 SystemErrorLog 를 분석하는 AiAnalyzer.
 *
 * - response_format=json_object 강제 → AnalysisResult 필드와 1:1 매핑
 * - API 실패 / JSON 파싱 실패 시 unknown + unsure=true 로 안전 fallback
 *   (orchestrator 의 EscalationEvaluator 가 그것을 보고 escalate 결정)
 * - API key 는 AiSetting::current()->openaiKey() 에서 받아서 생성자에 전달 (binding 단계)
 *
 * 활성화: AI_FIX_ANALYZER_DRIVER=openai
 */
final class OpenAiAnalyzer implements AiAnalyzer
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gpt-4o-mini',
        private readonly int    $timeout = 60,
    ) {}

    public function analyze(SystemErrorLog $errorLog): AnalysisResult
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'           => $this->model,
                    'response_format' => ['type' => 'json_object'],
                    'temperature'     => 0.2,
                    'messages'        => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user',   'content' => $this->userPrompt($errorLog)],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('[OpenAiAnalyzer] HTTP failed: ' . $e->getMessage());
            return $this->fallback($errorLog, 'http error: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            return $this->fallback($errorLog, 'HTTP ' . $response->status() . ': ' . $response->body());
        }

        $content = (string) $response->json('choices.0.message.content', '');
        $data    = json_decode($content, true);
        if (!is_array($data)) {
            return $this->fallback($errorLog, 'invalid JSON: ' . mb_substr($content, 0, 200));
        }

        return new AnalysisResult(
            category:     (string) ($data['category']   ?? 'unknown'),
            confidence:   (float)  ($data['confidence'] ?? 0.3),
            changedFiles: array_values(array_filter((array) ($data['changed_files'] ?? []), 'is_string')),
            summary:      mb_substr((string) ($data['summary'] ?? '[no summary]'), 0, 1000),
            unsure:       (bool)   ($data['unsure'] ?? false),
        );
    }

    private function systemPrompt(): string
    {
        return 'You are a senior PHP/Laravel engineer analyzing runtime errors. '
            . 'Identify the root cause and the minimum set of source files that likely need '
            . 'modification to fix it. Respond with strict JSON only (no markdown, no commentary).';
    }

    private function userPrompt(SystemErrorLog $errorLog): string
    {
        return implode("\n", [
            'Analyze this PHP/Laravel runtime error.',
            '',
            'Exception: ' . ($errorLog->exception ?? 'Throwable'),
            'Message: '   . mb_substr((string) $errorLog->message, 0, 500),
            'File: '      . ($errorLog->file ?? '-') . ':' . ($errorLog->line ?? 0),
            '',
            'Stack trace (truncated):',
            $this->truncateTrace((string) ($errorLog->trace ?? ''), 30),
            '',
            'Respond with this JSON shape ONLY:',
            '{',
            '  "category": "<null_check|type_mismatch|undefined_var|syntax_error|db_query|validation|external_api|permission|unknown>",',
            '  "confidence": <number 0.0 to 1.0>,',
            '  "changed_files": [<relative path strings>],',
            '  "summary": "<root cause + proposed fix, 1-2 sentences>",',
            '  "unsure": <true if confidence < 0.5 or analysis ambiguous>',
            '}',
        ]);
    }

    private function truncateTrace(string $trace, int $maxLines): string
    {
        $lines = explode("\n", $trace);
        if (count($lines) <= $maxLines) {
            return $trace;
        }
        return implode("\n", array_slice($lines, 0, $maxLines)) . "\n...(truncated)";
    }

    private function fallback(SystemErrorLog $errorLog, string $note): AnalysisResult
    {
        $relative = $errorLog->file
            ? str_replace(str_replace('\\', '/', base_path()) . '/', '', str_replace('\\', '/', $errorLog->file))
            : null;
        return new AnalysisResult(
            category:     'unknown',
            confidence:   0.1,
            changedFiles: $relative ? [$relative] : [],
            summary:      '[openai analyzer failed — fallback] ' . $note,
            unsure:       true,
        );
    }
}
