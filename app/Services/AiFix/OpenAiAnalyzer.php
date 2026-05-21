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
        private readonly string  $apiKey,
        private readonly string  $model = 'gpt-4.1',
        private readonly ?string $fallbackModel = 'gpt-4o',
        private readonly int     $timeout = 60,
    ) {}

    public function analyze(SystemErrorLog $errorLog): AnalysisResult
    {
        $result = $this->tryAnalyze($errorLog, $this->model);
        if ($result !== null) return $result;

        if ($this->fallbackModel !== null && $this->fallbackModel !== $this->model) {
            Log::info("[OpenAiAnalyzer] primary {$this->model} failed — falling back to {$this->fallbackModel}");
            $result = $this->tryAnalyze($errorLog, $this->fallbackModel);
            if ($result !== null) return $result;
        }

        return $this->fallback($errorLog, "all models failed (primary={$this->model}, fallback="
            . ($this->fallbackModel ?? 'none') . ')');
    }

    /** 한 모델로 호출 시도. 성공 시 AnalysisResult, 실패(HTTP/JSON) 시 null. */
    private function tryAnalyze(SystemErrorLog $errorLog, string $model): ?AnalysisResult
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'           => $model,
                    'response_format' => ['type' => 'json_object'],
                    'temperature'     => 0.2,
                    'messages'        => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user',   'content' => $this->userPrompt($errorLog)],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning("[OpenAiAnalyzer:$model] HTTP threw: " . $e->getMessage());
            return null;
        }

        if (!$response->successful()) {
            Log::warning("[OpenAiAnalyzer:$model] HTTP " . $response->status() . ': ' . mb_substr($response->body(), 0, 300));
            return null;
        }

        $content = (string) $response->json('choices.0.message.content', '');
        $data    = json_decode($content, true);
        if (!is_array($data)) {
            Log::warning("[OpenAiAnalyzer:$model] invalid JSON: " . mb_substr($content, 0, 200));
            return null;
        }

        return new AnalysisResult(
            category:     (string) ($data['category']   ?? 'unknown'),
            confidence:   (float)  ($data['confidence'] ?? 0.3),
            changedFiles: self::filterApplicationFiles((array) ($data['changed_files'] ?? [])),
            summary:      mb_substr((string) ($data['summary'] ?? '[no summary]'), 0, 1000),
            unsure:       (bool)   ($data['unsure'] ?? false),
        );
    }

    /**
     * changed_files 화이트리스트 — application 소스만 통과.
     * AI 가 무시하더라도 여기서 한 번 더 거른다.
     */
    private static function filterApplicationFiles(array $files): array
    {
        $allowedPrefixes = ['app/', 'resources/', 'routes/', 'database/', 'config/', 'tests/'];
        $out = [];
        foreach ($files as $f) {
            if (!is_string($f) || $f === '') continue;
            // 절대경로 / 외부 path 제외
            if ($f[0] === '/' || $f[0] === '\\') continue;
            if (str_starts_with($f, 'vendor/') || str_starts_with($f, 'node_modules/')) continue;
            if (str_starts_with($f, 'storage/') || str_starts_with($f, 'bootstrap/cache/')) continue;
            // 허용 prefix 중 하나라도 매칭
            foreach ($allowedPrefixes as $p) {
                if (str_starts_with($f, $p)) {
                    $out[] = $f;
                    continue 2;
                }
            }
        }
        return array_values(array_unique($out));
    }

    private function systemPrompt(): string
    {
        return 'You are a senior PHP/Laravel engineer analyzing runtime errors. '
            . 'Identify the root cause and the minimum set of source files that likely need '
            . 'modification to fix it. Respond with strict JSON only (no markdown, no commentary). '
            . 'CRITICAL: changed_files MUST contain only application source files using RELATIVE paths '
            . 'under these prefixes: app/, resources/, routes/, database/, config/, tests/. '
            . 'NEVER include: vendor/, node_modules/, storage/, bootstrap/cache/, /tmp/, /var/, /home/, '
            . 'any absolute path, or any third-party library file. If the error originates inside '
            . 'vendor code, instead point to the application file that calls into it.';
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
