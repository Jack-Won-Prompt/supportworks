<?php

namespace Tests\Unit;

use App\Services\PromptRefiner\Llm\ClaudeProvider;
use App\Services\PromptRefiner\Llm\Exceptions\AllProvidersFailedException;
use App\Services\PromptRefiner\Llm\Exceptions\LlmFatalException;
use App\Services\PromptRefiner\Llm\Exceptions\LlmRetryableException;
use App\Services\PromptRefiner\Llm\LlmRequest;
use App\Services\PromptRefiner\Llm\LlmResponse;
use App\Services\PromptRefiner\Llm\LlmRouter;
use App\Services\PromptRefiner\Llm\OpenAiProvider;
use Tests\TestCase;

class LlmRouterTest extends TestCase
{
    private LlmRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new LlmRequest(
            systemPrompt: 'system',
            userMessage:  '{"user_input":"test"}',
        );
    }

    private function makeRouter(ClaudeProvider $claude, OpenAiProvider $openai): LlmRouter
    {
        return new LlmRouter($claude, $openai);
    }

    private function okResponse(string $provider = 'claude'): LlmResponse
    {
        return new LlmResponse(
            content:      '{"status":"refined","task_type":"code_generation","refined_prompt":"p","metadata":{}}',
            providerUsed: $provider,
            modelUsed:    $provider === 'claude' ? 'claude-opus-4-7' : 'gpt-4o',
            totalTokens:  100,
            elapsedMs:    500,
        );
    }

    /** Claude 성공 → Claude 결과, fallbackReason=null */
    public function test_claude_success_returns_claude_response(): void
    {
        $claude = $this->createMock(ClaudeProvider::class);
        $openai = $this->createMock(OpenAiProvider::class);

        $claude->method('generate')->willReturn($this->okResponse('claude'));
        $openai->expects($this->never())->method('generate');

        $response = $this->makeRouter($claude, $openai)->execute($this->request);

        $this->assertSame('claude', $response->providerUsed);
        $this->assertNull($response->fallbackReason);
    }

    /** Claude 5xx → OpenAI fallback → OpenAI 결과, fallbackReason 채워짐 */
    public function test_claude_5xx_triggers_openai_fallback(): void
    {
        $claude = $this->createMock(ClaudeProvider::class);
        $openai = $this->createMock(OpenAiProvider::class);

        $claude->method('generate')->willThrowException(new LlmRetryableException('claude_http_503: overloaded'));
        $openai->method('generate')->willReturn($this->okResponse('openai'));

        $response = $this->makeRouter($claude, $openai)->execute($this->request);

        $this->assertSame('openai', $response->providerUsed);
        $this->assertNotNull($response->fallbackReason);
        $this->assertStringContainsString('claude_http_503', $response->fallbackReason);
    }

    /** Claude 429 → OpenAI fallback 동작 */
    public function test_claude_429_triggers_openai_fallback(): void
    {
        $claude = $this->createMock(ClaudeProvider::class);
        $openai = $this->createMock(OpenAiProvider::class);

        $claude->method('generate')->willThrowException(new LlmRetryableException('claude_http_429: rate limit'));
        $openai->method('generate')->willReturn($this->okResponse('openai'));

        $response = $this->makeRouter($claude, $openai)->execute($this->request);

        $this->assertSame('openai', $response->providerUsed);
    }

    /** Claude 4xx → 즉시 LlmFatalException (fallback 없음) */
    public function test_claude_4xx_throws_fatal_exception_immediately(): void
    {
        $claude = $this->createMock(ClaudeProvider::class);
        $openai = $this->createMock(OpenAiProvider::class);

        $claude->method('generate')->willThrowException(new LlmFatalException('claude_http_401: unauthorized'));
        $openai->expects($this->never())->method('generate');

        $this->expectException(LlmFatalException::class);
        $this->makeRouter($claude, $openai)->execute($this->request);
    }

    /** 양쪽 모두 실패 → AllProvidersFailedException */
    public function test_both_providers_fail_throws_all_providers_failed(): void
    {
        $claude = $this->createMock(ClaudeProvider::class);
        $openai = $this->createMock(OpenAiProvider::class);

        $claude->method('generate')->willThrowException(new LlmRetryableException('claude_http_503'));
        $openai->method('generate')->willThrowException(new LlmRetryableException('openai_http_503'));

        $this->expectException(AllProvidersFailedException::class);
        $this->makeRouter($claude, $openai)->execute($this->request);
    }

    /** LLM_FALLBACK_ENABLED=false → fallback 불가 */
    public function test_fallback_disabled_skips_openai(): void
    {
        config(['services.llm_router.fallback_enabled' => false]);

        $claude = $this->createMock(ClaudeProvider::class);
        $openai = $this->createMock(OpenAiProvider::class);

        $claude->method('generate')->willThrowException(new LlmRetryableException('claude_http_503'));
        $openai->expects($this->never())->method('generate');

        $this->expectException(AllProvidersFailedException::class);
        $this->makeRouter($claude, $openai)->execute($this->request);

        config(['services.llm_router.fallback_enabled' => true]);
    }
}
