<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\SystemErrorLog;

/**
 * 웍스 요청 오케스트레이터 — 모든 기능에 OpenAI → Anthropic(Claude) → Manus 폴백 적용.
 * Anthropic 결제 이슈 우회 정책에 따라 OpenAI 가 primary. 결제 복구 시 swap 또는 config 플래그화 검토.
 */
class AiOrchestrator
{
    public const PROVIDER_CLAUDE = 'claude';
    public const PROVIDER_OPENAI = 'openai';
    public const PROVIDER_MANUS  = 'manus';

    private const NO_KEY_MESSAGE = '사용 가능한 웍스 API 키가 없습니다. 설정에서 OpenAI 또는 Anthropic API 키를 등록하세요.';

    public function __construct(
        private ?string $anthropicKey,
        private ?string $openaiKey,
        private ?string $manusKey      = null,
        private ?string $manusEndpoint = null,
    ) {}

    /** Log::warning + 관리자 시스템 에러 페이지에도 기록 */
    private static function warnAndRecord(string $message): void
    {
        Log::warning($message);
        SystemErrorLog::log('warning', $message, ['source' => 'AiOrchestrator']);
    }

    /**
     * 코드 생성용. OpenAI → Claude → Manus 순으로 시도합니다.
     *
     * @return array{result: array, provider: string}
     */
    public function chat(array $messages, ?string $figmaContext = null, ?string $systemOverride = null): array
    {
        $lastException = null;

        if ($this->openaiKey) {
            try {
                $result = (new OpenAiService($this->openaiKey))->chat($messages, $figmaContext, $systemOverride);
                return ['result' => $result, 'provider' => self::PROVIDER_OPENAI];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] OpenAI chat 실패, Claude 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->anthropicKey) {
            try {
                $result = (new ClaudeService($this->anthropicKey))->chat($messages, $figmaContext, $systemOverride);
                return ['result' => $result, 'provider' => self::PROVIDER_CLAUDE];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Claude chat 실패, Manus 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->manusKey) {
            try {
                $endpoint   = $this->manusEndpoint ?: 'https://api.manus.ai/v2';
                $systemPart = $systemOverride ?? '';
                $userMsg    = collect($messages)->filter(fn($m) => ($m['role'] ?? '') === 'user')->last()['content'] ?? '';
                $text       = (new ManusService($this->manusKey, $endpoint))->chatRaw([['role' => 'user', 'content' => $userMsg]], $systemPart);
                return ['result' => ['type' => 'text', 'content' => $text], 'provider' => self::PROVIDER_MANUS];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Manus chat 실패: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        throw $lastException ?? new \RuntimeException(self::NO_KEY_MESSAGE);
    }

    /**
     * 프롬프트 정제용. OpenAI → Claude → Manus 순으로 시도합니다.
     *
     * @return array{result: array, provider: string}
     */
    public function refinePrompt(string $userInput, ?array $existing = null): array
    {
        $lastException = null;

        if ($this->openaiKey) {
            try {
                $result = (new OpenAiService($this->openaiKey))->refinePrompt($userInput, $existing);
                return ['result' => $result, 'provider' => self::PROVIDER_OPENAI];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] OpenAI refine 실패, Claude 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->anthropicKey) {
            try {
                $result = (new ClaudeService($this->anthropicKey))->refinePrompt($userInput, $existing);
                return ['result' => $result, 'provider' => self::PROVIDER_CLAUDE];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Claude refine 실패, Manus 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->manusKey) {
            try {
                $endpoint = $this->manusEndpoint ?: 'https://api.manus.ai/v2';
                $text     = (new ManusService($this->manusKey, $endpoint))->chatRaw(
                    [['role' => 'user', 'content' => $userInput]],
                    AiPrompts::refineSystem()
                );
                return ['result' => ['refined' => $text], 'provider' => self::PROVIDER_MANUS];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Manus refine 실패: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        throw $lastException ?? new \RuntimeException(self::NO_KEY_MESSAGE);
    }

    /**
     * 대용량 HTML 생성용. OpenAI → Claude → Manus 순으로 시도합니다. (16000 tokens)
     *
     * @return array{text: string, provider: string}
     */
    public function chatRawLarge(array $messages, string $systemPrompt): array
    {
        $lastException = null;

        if ($this->openaiKey) {
            try {
                $text = (new OpenAiService($this->openaiKey))->chatRawLarge($messages, $systemPrompt);
                return ['text' => $text, 'provider' => self::PROVIDER_OPENAI];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] OpenAI chatRawLarge 실패, Claude 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->anthropicKey) {
            try {
                $text = (new ClaudeService($this->anthropicKey))->chatRawLarge($messages, $systemPrompt);
                return ['text' => $text, 'provider' => self::PROVIDER_CLAUDE];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Claude chatRawLarge 실패, Manus 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->manusKey) {
            try {
                $endpoint = $this->manusEndpoint ?: 'https://api.manus.ai/v2';
                $text     = (new ManusService($this->manusKey, $endpoint))->chatRawLarge($messages, $systemPrompt);
                return ['text' => $text, 'provider' => self::PROVIDER_MANUS];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Manus chatRawLarge 실패: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        throw $lastException ?? new \RuntimeException(
            '사용 가능한 웍스 API 키가 없습니다. 설정에서 OpenAI, Anthropic 또는 Manus API 키를 등록하세요.'
        );
    }

    /**
     * 텍스트 생성용. OpenAI → Claude → Manus 순으로 시도합니다.
     *
     * @return array{text: string, provider: string}
     */
    public function chatRawDirect(array $messages, string $systemPrompt): array
    {
        $lastException = null;

        if ($this->openaiKey) {
            try {
                $text = (new OpenAiService($this->openaiKey))->chatRaw($messages, $systemPrompt);
                return ['text' => $text, 'provider' => self::PROVIDER_OPENAI];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] OpenAI chatRawDirect 실패, Claude 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->anthropicKey) {
            try {
                $text = (new ClaudeService($this->anthropicKey))->chatRaw($messages, $systemPrompt);
                return ['text' => $text, 'provider' => self::PROVIDER_CLAUDE];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Claude chatRawDirect 실패, Manus 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->manusKey) {
            try {
                $endpoint = $this->manusEndpoint ?: 'https://api.manus.ai/v2';
                $text     = (new ManusService($this->manusKey, $endpoint))->chatRaw($messages, $systemPrompt);
                return ['text' => $text, 'provider' => self::PROVIDER_MANUS];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Manus chatRawDirect 실패: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        throw $lastException ?? new \RuntimeException(self::NO_KEY_MESSAGE);
    }

    /**
     * 빠른 정제·요약용 — OpenAI gpt-4o-mini → Claude Haiku 폴백.
     */
    public function chatRawFast(array $messages, string $systemPrompt): array
    {
        $lastException = null;

        if ($this->openaiKey) {
            try {
                $text = (new OpenAiService($this->openaiKey))->chatRawFast($messages, $systemPrompt);
                return ['text' => $text, 'provider' => self::PROVIDER_OPENAI];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] OpenAI chatRawFast 실패, Claude 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->anthropicKey) {
            try {
                $text = (new ClaudeService($this->anthropicKey))->chatRawFast($messages, $systemPrompt);
                return ['text' => $text, 'provider' => self::PROVIDER_CLAUDE];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Claude chatRawFast 실패: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        throw $lastException ?? new \RuntimeException(self::NO_KEY_MESSAGE);
    }

    /**
     * 산출물 단계 초안 생성 — OpenAI → Claude → Manus 순으로 시도합니다.
     *
     * @param  array  $fieldSchema  JSON Schema {type, properties, required}
     * @return array{fields: array, provider: string}
     */
    public function generateDraft(string $systemPrompt, string $userPrompt, array $fieldSchema): array
    {
        $lastException = null;

        if ($this->openaiKey) {
            try {
                $fields = (new OpenAiService($this->openaiKey))
                    ->generateDraftFields($systemPrompt, $userPrompt, $fieldSchema);
                return ['fields' => $fields, 'provider' => self::PROVIDER_OPENAI];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] OpenAI generateDraft 실패, Claude 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->anthropicKey) {
            try {
                $tools = [[
                    'name'         => 'fill_draft_fields',
                    'description'  => '산출물 단계 초안을 필드별로 작성합니다.',
                    'input_schema' => $fieldSchema,
                ]];
                $response = (new \App\Services\Agent\AnthropicProvider($this->anthropicKey))
                    ->generateWithTools(
                        $systemPrompt,
                        [['role' => 'user', 'content' => $userPrompt]],
                        $tools,
                        ['max_tokens' => 4000, 'timeout' => 120]
                    );
                return ['fields' => $response->toolInput, 'provider' => self::PROVIDER_CLAUDE];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Claude generateDraft 실패, Manus 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->manusKey) {
            try {
                $endpoint  = $this->manusEndpoint ?: 'https://api.manus.ai/v2';
                $fieldKeys = array_keys($fieldSchema['properties'] ?? []);
                $jsonGuide = json_encode(array_fill_keys($fieldKeys, '...내용...'), JSON_UNESCAPED_UNICODE);
                $manusSystem = $systemPrompt . "\n\n## 응답 형식\n반드시 아래 JSON 형식으로만 응답하세요. 설명 없이 순수 JSON만 반환하세요:\n{$jsonGuide}";

                $text = (new ManusService($this->manusKey, $endpoint))
                    ->chatRaw([['role' => 'user', 'content' => $userPrompt]], $manusSystem);

                if (preg_match('/\{[\s\S]*\}/u', $text, $matches)) {
                    $parsed = json_decode($matches[0], true);
                    if (is_array($parsed)) {
                        return ['fields' => $parsed, 'provider' => self::PROVIDER_MANUS];
                    }
                }
                throw new \RuntimeException('Manus 응답에서 JSON을 파싱할 수 없습니다: ' . mb_substr($text, 0, 200));
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Manus generateDraft 실패: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        throw $lastException ?? new \RuntimeException(self::NO_KEY_MESSAGE);
    }

    /**
     * 문서/텍스트 생성용. OpenAI → Claude → Manus 순으로 시도합니다.
     *
     * @return array{text: string, provider: string}
     */
    public function chatRaw(array $messages, string $systemPrompt): array
    {
        $lastException = null;

        if ($this->openaiKey) {
            try {
                $text = (new OpenAiService($this->openaiKey))->chatRaw($messages, $systemPrompt);
                return ['text' => $text, 'provider' => self::PROVIDER_OPENAI];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] OpenAI chatRaw 실패, Claude 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->anthropicKey) {
            try {
                $text = (new ClaudeService($this->anthropicKey))->chatRaw($messages, $systemPrompt);
                return ['text' => $text, 'provider' => self::PROVIDER_CLAUDE];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Claude chatRaw 실패, Manus 폴백: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        if ($this->manusKey) {
            try {
                $endpoint = $this->manusEndpoint ?: 'https://api.manus.ai/v2';
                $text     = (new ManusService($this->manusKey, $endpoint))->chatRaw($messages, $systemPrompt);
                return ['text' => $text, 'provider' => self::PROVIDER_MANUS];
            } catch (\Throwable $e) {
                self::warnAndRecord('[AiOrchestrator] Manus chatRaw 실패: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        throw $lastException ?? new \RuntimeException(
            '사용 가능한 웍스 API 키가 없습니다. 설정에서 OpenAI, Anthropic 또는 Manus API 키를 등록하세요.'
        );
    }
}
