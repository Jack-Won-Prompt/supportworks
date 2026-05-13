<?php

namespace App\Services\PromptBuilder;

use App\Services\PromptBuilder\Generators\ClaudePromptGenerator;
use App\Services\PromptBuilder\Generators\CursorPromptGenerator;
use App\Services\PromptBuilder\Generators\OpenAiPromptGenerator;

class PromptGenerationService
{
    public function __construct(
        private CursorPromptGenerator $cursorGenerator,
        private ClaudePromptGenerator $claudeGenerator,
        private OpenAiPromptGenerator $openAiGenerator,
    ) {}

    public function generate(
        string $aiType,
        array $context,
        array $purpose,
        array $analysis,
        array $inputSources,
    ): string {
        $generator = match ($aiType) {
            'cursor' => $this->cursorGenerator,
            'claude' => $this->claudeGenerator,
            'openai' => $this->openAiGenerator,
            default  => throw new \InvalidArgumentException("Unknown 웍스 type: {$aiType}"),
        };

        return $generator->generate(
            context: $context,
            purpose: $purpose,
            analysis: $analysis,
            inputSources: $inputSources,
        );
    }

    public function estimateTokens(string $prompt): int
    {
        return (int) ceil(mb_strlen($prompt) / 3);
    }
}
