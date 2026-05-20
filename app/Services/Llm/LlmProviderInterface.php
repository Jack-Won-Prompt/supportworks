<?php

namespace App\Services\Llm;

use App\Services\Llm\Exceptions\LlmFatalException;
use App\Services\Llm\Exceptions\LlmRetryableException;

interface LlmProviderInterface
{
    /**
     * @throws LlmRetryableException  fallback 가능한 실패 (5xx, 429, timeout, invalid JSON)
     * @throws LlmFatalException       fallback 불가 실패 (4xx)
     */
    public function generate(LlmRequest $request): LlmResponse;

    public function name(): string;
}
