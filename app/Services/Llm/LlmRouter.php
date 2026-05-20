<?php

namespace App\Services\Llm;

use App\Services\Llm\Exceptions\AllProvidersFailedException;
use App\Services\Llm\Exceptions\LlmFatalException;
use App\Services\Llm\Exceptions\LlmRetryableException;
use Illuminate\Support\Facades\Log;

class LlmRouter
{
    public function __construct(
        private ClaudeProvider $claude,
        private OpenAiProvider $openai,
    ) {}

    public function execute(LlmRequest $request, ?string $requestId = null): LlmResponse
    {
        $primary         = config('services.llm_router.primary', 'claude');
        $fallback        = config('services.llm_router.fallback', 'openai');
        $fallbackEnabled = (bool) config('services.llm_router.fallback_enabled', true);

        $providers = [
            'claude' => $this->claude,
            'openai' => $this->openai,
        ];

        // Primary attempt
        try {
            return $providers[$primary]->generate($request);
        } catch (LlmFatalException $e) {
            Log::error('llm_fatal', [
                'request_id' => $requestId,
                'provider'   => $primary,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        } catch (LlmRetryableException $primaryError) {
            Log::warning('llm_primary_failed_attempting_fallback', [
                'request_id' => $requestId,
                'primary'    => $primary,
                'error'      => $primaryError->getMessage(),
            ]);

            if (!$fallbackEnabled) {
                throw new AllProvidersFailedException(
                    "Primary {$primary} failed and fallback disabled: " . $primaryError->getMessage()
                );
            }

            // Fallback attempt
            try {
                $response = $providers[$fallback]->generate($request);

                Log::info('llm_fallback_succeeded', [
                    'request_id' => $requestId,
                    'fallback'   => $fallback,
                    'reason'     => $primaryError->getMessage(),
                ]);

                return new LlmResponse(
                    content:        $response->content,
                    providerUsed:   $response->providerUsed,
                    modelUsed:      $response->modelUsed,
                    totalTokens:    $response->totalTokens,
                    elapsedMs:      $response->elapsedMs,
                    fallbackReason: substr($primaryError->getMessage(), 0, 200),
                );
            } catch (\Exception $fallbackError) {
                Log::error('llm_all_providers_failed', [
                    'request_id'     => $requestId,
                    'primary_error'  => $primaryError->getMessage(),
                    'fallback_error' => $fallbackError->getMessage(),
                ]);

                throw new AllProvidersFailedException(
                    "All LLM providers failed. Primary({$primary}): {$primaryError->getMessage()} | "
                    . "Fallback({$fallback}): {$fallbackError->getMessage()}"
                );
            }
        }
    }
}
