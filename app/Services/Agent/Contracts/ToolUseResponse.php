<?php

namespace App\Services\Agent\Contracts;

readonly class ToolUseResponse
{
    public function __construct(
        public string $toolName,
        public array  $toolInput,
        public int    $inputTokens,
        public int    $outputTokens,
        public string $model,
    ) {}

    public function toAIResponse(): AIResponse
    {
        return new AIResponse(
            text:         json_encode($this->toolInput, JSON_UNESCAPED_UNICODE),
            inputTokens:  $this->inputTokens,
            outputTokens: $this->outputTokens,
            model:        $this->model,
            stopReason:   'tool_use',
        );
    }
}
