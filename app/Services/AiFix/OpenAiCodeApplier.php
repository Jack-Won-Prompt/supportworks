<?php

namespace App\Services\AiFix;

use App\Models\AiFixJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * OpenAI Chat Completions 를 호출해 worktree 의 changed_files 를 실제 수정하는
 * AiCodeApplier. 각 파일별로 AI 호출 → 전체 새 내용 받음 → PHP syntax check 통과
 * 시만 write.
 *
 * 안전 장치:
 *  - response_format=json_object 강제 (parsing 실패 시 false)
 *  - 모든 파일 write 전 php -l 로 lint (syntax 에러 시 그 파일 skip + 전체 false)
 *  - primary 모델 실패 시 fallback chain (AiAnalyzer 와 같은 패턴)
 *  - 한 파일이라도 실패하면 apply() 가 false 반환 → ApplyAiFixJob 가 tests_failed 분기
 *
 * 활성화: AI_FIX_APPLIER_DRIVER=openai
 */
final class OpenAiCodeApplier implements AiCodeApplier
{
    public function __construct(
        private readonly string  $apiKey,
        private readonly string  $model = 'gpt-4.1',
        private readonly ?string $fallbackModel = 'gpt-4o',
        private readonly int     $timeout = 120,
    ) {}

    public function apply(AiFixJob $job, string $worktreePath): bool
    {
        $changedFiles = (array) ($job->changed_files ?? []);
        if (empty($changedFiles)) {
            Log::warning("[OpenAiCodeApplier] job #{$job->id} has no changed_files");
            return false;
        }

        $errorLog = $job->systemErrorLog;
        if (!$errorLog) {
            Log::warning("[OpenAiCodeApplier] job #{$job->id} has no systemErrorLog");
            return false;
        }

        $summary = (string) ($job->proposed_fix_summary ?? '');
        $allOk   = true;

        foreach ($changedFiles as $relPath) {
            $absPath = rtrim($worktreePath, '/') . '/' . ltrim($relPath, '/');
            if (!is_file($absPath)) {
                Log::warning("[OpenAiCodeApplier] file not found: $absPath");
                $allOk = false;
                continue;
            }

            $original   = file_get_contents($absPath);
            $newContent = $this->generateFix($errorLog, $summary, $relPath, $original);

            if ($newContent === null) {
                Log::warning("[OpenAiCodeApplier] no valid AI response for $relPath");
                $allOk = false;
                continue;
            }

            // PHP syntax check 통과한 경우에만 write
            $tmpPath = $absPath . '.aifix-tmp';
            file_put_contents($tmpPath, $newContent);
            if (!$this->phpSyntaxOk($tmpPath)) {
                @unlink($tmpPath);
                Log::warning("[OpenAiCodeApplier] syntax error in proposed fix for $relPath — skipped");
                $allOk = false;
                continue;
            }
            rename($tmpPath, $absPath);
            Log::info("[OpenAiCodeApplier] applied fix to $relPath (job #{$job->id})");
        }

        return $allOk;
    }

    /** 한 파일에 대해 AI 가 새 내용을 생성. fallback chain 포함. */
    private function generateFix(\App\Models\SystemErrorLog $errorLog, string $summary, string $relPath, string $original): ?string
    {
        $result = $this->tryGenerate($errorLog, $summary, $relPath, $original, $this->model);
        if ($result !== null) return $result;

        if ($this->fallbackModel !== null && $this->fallbackModel !== $this->model) {
            Log::info("[OpenAiCodeApplier] primary {$this->model} failed for $relPath — falling back to {$this->fallbackModel}");
            $result = $this->tryGenerate($errorLog, $summary, $relPath, $original, $this->fallbackModel);
            if ($result !== null) return $result;
        }
        return null;
    }

    private function tryGenerate(\App\Models\SystemErrorLog $errorLog, string $summary, string $relPath, string $original, string $model): ?string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'           => $model,
                    'response_format' => ['type' => 'json_object'],
                    'temperature'     => 0.1,
                    'messages'        => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user',   'content' => $this->userPrompt($errorLog, $summary, $relPath, $original)],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning("[OpenAiCodeApplier:$model] HTTP threw: " . $e->getMessage());
            return null;
        }

        if (!$response->successful()) {
            Log::warning("[OpenAiCodeApplier:$model] HTTP " . $response->status() . ': ' . mb_substr($response->body(), 0, 300));
            return null;
        }

        $content = (string) $response->json('choices.0.message.content', '');
        $data    = json_decode($content, true);
        if (!is_array($data) || !isset($data['new_content']) || !is_string($data['new_content'])) {
            Log::warning("[OpenAiCodeApplier:$model] invalid JSON or missing new_content");
            return null;
        }
        return $data['new_content'];
    }

    private function systemPrompt(): string
    {
        return 'You are a senior PHP/Laravel engineer. Fix the runtime error by modifying ONE source file. '
            . 'Return strict JSON: {"new_content": "<full new file content>", "explanation": "<1-2 sentences>"}. '
            . 'CRITICAL RULES: '
            . '(1) Return the COMPLETE file content as new_content (not a diff, not a snippet). '
            . '(2) Preserve all unrelated code exactly — same namespace, use statements, class structure. '
            . '(3) Make the MINIMAL change necessary to fix the error. '
            . '(4) Do not introduce new dependencies or feature flags. '
            . '(5) Do not add unnecessary comments. '
            . '(6) Output valid PHP only (must pass php -l).';
    }

    private function userPrompt(\App\Models\SystemErrorLog $errorLog, string $summary, string $relPath, string $original): string
    {
        return implode("\n", [
            'Fix this PHP/Laravel runtime error by modifying the source file below.',
            '',
            'Exception: ' . ($errorLog->exception ?? 'Throwable'),
            'Message:   ' . mb_substr((string) $errorLog->message, 0, 500),
            'Location:  ' . ($errorLog->file ?? '-') . ':' . ($errorLog->line ?? 0),
            '',
            'Analyzer summary: ' . $summary,
            '',
            "File to modify: $relPath",
            '--- BEGIN FILE ---',
            $original,
            '--- END FILE ---',
            '',
            'Return JSON: {"new_content": "<full new file>", "explanation": "<what+why>"}.',
        ]);
    }

    private function phpSyntaxOk(string $path): bool
    {
        $proc = new Process(['php', '-l', $path]);
        $proc->setTimeout(30);
        $proc->run();
        return $proc->isSuccessful();
    }
}
