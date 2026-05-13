<?php

namespace App\Services\Analysis;

use App\Models\AnalysisSession;
use App\Models\AnalysisSessionFile;
use App\Services\Analysis\FileExtractor\FileExtractorFactory;
use App\Services\Analysis\Llm\LlmClientFactory;
use App\Services\Analysis\Llm\Prompts\RequirementAnalysisPrompt;
use App\Services\Analysis\Validators\AiOutputValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AnalysisService
{
    public function __construct(
        private FileExtractorFactory $extractorFactory,
        private LlmClientFactory     $llmFactory,
        private AiOutputValidator    $validator,
    ) {}

    // Primary: Claude sonnet, fallback: GPT-4o
    private const PRIMARY_PROVIDER  = 'anthropic';
    private const PRIMARY_MODEL     = 'claude-sonnet-4-6';
    private const FALLBACK_PROVIDER = 'openai';
    private const FALLBACK_MODEL    = 'gpt-4o';

    public function run(AnalysisSession $session): void
    {
        try {
            $session->update(['status' => 'processing', 'started_at' => now()]);

            $documentText = $this->extractAllFiles($session);
            $inputText    = $this->buildInputText($session, $documentText);

            $session->update(['input_text' => $inputText]);

            $existingTitles = $session->project
                ->requirements()
                ->pluck('title')
                ->toArray();

            $systemPrompt = RequirementAnalysisPrompt::system();
            $userMessage  = RequirementAnalysisPrompt::user($inputText, null, $existingTitles);

            [$provider, $model, $llmResponse] = $this->callWithFallback(
                $session, $systemPrompt, $userMessage
            );

            $structured = $this->validator->validate($llmResponse->content);

            $costPer1k = $this->estimateCostPer1k($provider, $model);
            $cost      = (($llmResponse->inputTokens + $llmResponse->outputTokens) / 1000) * $costPer1k;

            $session->update([
                'status'                => 'review',
                'llm_provider'          => $provider,
                'llm_model'             => $llmResponse->model,
                'ai_raw_output'         => ['text' => $llmResponse->content],
                'ai_structured_output'  => $structured,
                'system_prompt_version' => RequirementAnalysisPrompt::VERSION,
                'token_input'           => $llmResponse->inputTokens,
                'token_output'          => $llmResponse->outputTokens,
                'cost_estimated'        => round($cost, 4),
                'completed_at'          => now(),
            ]);

        } catch (\Throwable $e) {
            Log::channel('ai_analysis')->error('Analysis failed', [
                'session_id' => $session->id,
                'error'      => $e->getMessage(),
            ]);

            $session->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);

            throw $e;
        }
    }

    private function callWithFallback(AnalysisSession $session, string $system, string $user): array
    {
        try {
            $client      = $this->llmFactory->make(self::PRIMARY_PROVIDER);
            $llmResponse = $client->complete($system, $user, ['model' => self::PRIMARY_MODEL]);

            Log::channel('ai_analysis')->info('LLM response (primary)', [
                'session_id' => $session->id,
                'provider'   => self::PRIMARY_PROVIDER,
                'model'      => $llmResponse->model,
                'tokens_in'  => $llmResponse->inputTokens,
                'tokens_out' => $llmResponse->outputTokens,
            ]);

            return [self::PRIMARY_PROVIDER, self::PRIMARY_MODEL, $llmResponse];

        } catch (\Throwable $primaryErr) {
            Log::channel('ai_analysis')->warning('Primary LLM failed, falling back to OpenAI', [
                'session_id' => $session->id,
                'error'      => $primaryErr->getMessage(),
            ]);

            $client      = $this->llmFactory->make(self::FALLBACK_PROVIDER);
            $llmResponse = $client->complete($system, $user, ['model' => config('services.openai.model', self::FALLBACK_MODEL)]);

            Log::channel('ai_analysis')->info('LLM response (fallback)', [
                'session_id' => $session->id,
                'provider'   => self::FALLBACK_PROVIDER,
                'model'      => $llmResponse->model,
                'tokens_in'  => $llmResponse->inputTokens,
                'tokens_out' => $llmResponse->outputTokens,
            ]);

            return [self::FALLBACK_PROVIDER, config('services.openai.model', self::FALLBACK_MODEL), $llmResponse];
        }
    }

    private function extractAllFiles(AnalysisSession $session): string
    {
        $parts = [];

        foreach ($session->files as $file) {
            try {
                $absolutePath = Storage::disk('local')->path($file->stored_path);
                $extension    = strtolower(pathinfo($file->original_filename, PATHINFO_EXTENSION));

                $extractor = $this->extractorFactory->make($file->mime_type, $extension);
                $text      = $extractor->extract($absolutePath);

                $file->update([
                    'extracted_text'    => $text,
                    'extraction_status' => 'done',
                ]);

                $parts[] = "### 파일: {$file->original_filename}\n\n{$text}";

            } catch (\Throwable $e) {
                $file->update([
                    'extraction_status' => 'failed',
                    'extraction_error'  => $e->getMessage(),
                ]);

                Log::channel('ai_analysis')->warning('File extraction failed', [
                    'session_id' => $session->id,
                    'file_id'    => $file->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return implode("\n\n---\n\n", $parts);
    }

    private function buildInputText(AnalysisSession $session, string $documentText): string
    {
        $parts = [];

        if ($session->input_text) {
            $parts[] = "## 추가 컨텍스트\n" . $session->input_text;
        }

        if ($documentText) {
            $parts[] = $documentText;
        }

        return implode("\n\n", $parts);
    }

    private function estimateCostPer1k(string $provider, string $model): float
    {
        return match (true) {
            $provider === 'anthropic' && str_contains($model, 'opus')   => 0.015,
            $provider === 'anthropic' && str_contains($model, 'sonnet') => 0.003,
            $provider === 'anthropic' && str_contains($model, 'haiku')  => 0.00025,
            $provider === 'openai'    && str_contains($model, 'gpt-4o') => 0.005,
            $provider === 'openai'    && str_contains($model, 'gpt-4')  => 0.01,
            default                                                      => 0.002,
        };
    }
}
