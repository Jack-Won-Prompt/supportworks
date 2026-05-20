<?php

namespace App\Services\WorksBuilder\Ai\Providers;

use App\Services\WorksBuilder\Ai\AiAttemptException;
use App\Services\WorksBuilder\Ai\AiResult;

interface AiProviderInterface
{
    public function name(): string;

    /**
     * @throws AiAttemptException
     */
    public function generate(string $systemPrompt, string $userPrompt): AiResult;
}
