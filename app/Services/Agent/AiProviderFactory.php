<?php

namespace App\Services\Agent;

use App\Models\AiSetting;
use App\Services\Agent\Contracts\AIProvider;

/**
 * Agent Session에서 사용할 AIProvider를 provider 키워드 기준으로 해석한다.
 *
 *   'anthropic' → AnthropicProvider (DB AiSetting > env)
 *   'openai'    → OpenAiProvider
 *   'auto'      → config('ai-agent.sessions.default_provider')
 *
 * API 키가 없고 ai-agent.sessions.mock_when_unconfigured = true 인 경우 MockAiProvider 반환.
 */
class AiProviderFactory
{
    public const ANTHROPIC = 'anthropic';
    public const OPENAI    = 'openai';
    public const AUTO      = 'auto';

    /**
     * @return array<int, string>
     */
    public static function available(): array
    {
        return [self::ANTHROPIC, self::OPENAI];
    }

    public function make(string $provider = self::AUTO): AIProvider
    {
        $resolved = $this->resolveProvider($provider);

        return match ($resolved) {
            self::ANTHROPIC => $this->makeAnthropic(),
            self::OPENAI    => $this->makeOpenAi(),
            default         => $this->makeAnthropic(),
        };
    }

    private function resolveProvider(string $provider): string
    {
        if ($provider === self::AUTO) {
            return (string) config('ai-agent.sessions.default_provider', self::ANTHROPIC);
        }
        return $provider;
    }

    private function makeAnthropic(): AIProvider
    {
        $key = $this->anthropicKey();

        if ($key === null || $key === '') {
            return $this->mockIfAllowed('mock-claude')
                ?? throw new \RuntimeException('ANTHROPIC_API_KEY 미설정 — AI Agent provider를 사용할 수 없습니다.');
        }

        return new AnthropicProvider($key);
    }

    private function makeOpenAi(): AIProvider
    {
        $key = $this->openaiKey();

        if ($key === null || $key === '') {
            return $this->mockIfAllowed('mock-openai')
                ?? throw new \RuntimeException('OPENAI_API_KEY 미설정 — AI Agent provider를 사용할 수 없습니다.');
        }

        return new OpenAiProvider(
            apiKey:  $key,
            model:   (string) config('ai-agent.openai.model', 'gpt-4o'),
            baseUrl: (string) config('ai-agent.openai.base_url', 'https://api.openai.com/v1'),
        );
    }

    private function anthropicKey(): ?string
    {
        try {
            $fromDb = AiSetting::current()->anthropicKey();
            if ($fromDb) {
                return $fromDb;
            }
        } catch (\Throwable) {
            // 마이그레이션 전이거나 DB 미설정 — env fallback
        }

        return (string) config('ai-agent.anthropic.api_key', '') ?: null;
    }

    private function openaiKey(): ?string
    {
        try {
            $fromDb = AiSetting::current()->openaiKey();
            if ($fromDb) {
                return $fromDb;
            }
        } catch (\Throwable) {
            // ignore
        }

        return (string) config('ai-agent.openai.api_key', '') ?: null;
    }

    private function mockIfAllowed(string $label): ?AIProvider
    {
        if (config('ai-agent.sessions.mock_when_unconfigured', true)) {
            return new MockAiProvider($label);
        }
        return null;
    }
}
