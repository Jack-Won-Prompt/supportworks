<?php

namespace App\Services\Agent;

use App\Models\Agent\AiAgentScreen;
use App\Services\Agent\Contracts\AIResponse;
use Illuminate\Support\Facades\Http;

/**
 * Calls Claude (Tool Use) to review a single Figma screen for design consistency.
 * Optionally fetches the frame image for visual review.
 */
class AiDesignReviewer
{
    public function __construct(
        private readonly AnthropicProvider    $provider,
        private readonly AgentUsageLogService $usageLog,
    ) {}

    /**
     * Review a single screen. Returns [toolInput array, tokensIn, tokensOut, costEstimate].
     *
     * @param  array  $context  from ReviewContextLoader::load()
     * @param  string|null  $figmaImageUrl  pre-fetched Figma image URL (or null)
     */
    public function reviewScreen(
        AiAgentScreen $screen,
        array         $context,
        int           $userId,
        int           $projectId,
        ?string       $figmaImageUrl = null,
    ): array {
        $imageBase64 = null;

        if ($figmaImageUrl) {
            try {
                $bytes       = Http::withOptions(['verify' => false])->timeout(30)->get($figmaImageUrl)->body();
                $imageBase64 = base64_encode($bytes);
            } catch (\Exception) {
                // proceed without image
            }
        }

        $contentBlocks = [];

        if ($imageBase64) {
            $contentBlocks[] = [
                'type'   => 'image',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => 'image/png',
                    'data'       => $imageBase64,
                ],
            ];
        }

        $contentBlocks[] = ['type' => 'text', 'text' => $this->buildUserPrompt($screen, $context, $imageBase64 !== null)];

        $messages = [['role' => 'user', 'content' => $contentBlocks]];

        $toolResponse = $this->usageLog->callAndLog(
            provider:  $this->provider,
            call:      fn() => $this->provider->generateWithTools(
                systemPrompt: $context['system_prompt'],
                messages:     $messages,
                tools:        [$this->getReviewTool()],
                options:      ['max_tokens' => 4096, 'timeout' => 180],
            )->toAIResponse(),
            userId:    $userId,
            projectId: $projectId,
            stage:     'design',
            taskType:  'design_review',
        );

        $toolInput = json_decode($toolResponse->text, true) ?? [];

        // Ensure required fields
        $toolInput['screen_id']         ??= $screen->screen_id;
        $toolInput['figma_node_id']     ??= $screen->figma_frame_id;
        $toolInput['compliance_score']  ??= 0;
        $toolInput['category_scores']   ??= ['color' => 0, 'typography' => 0, 'component' => 0, 'layout' => 0];
        $toolInput['violations']        ??= [];
        $toolInput['strengths']         ??= [];

        return [
            'result'      => $toolInput,
            'tokensIn'    => $toolResponse->inputTokens,
            'tokensOut'   => $toolResponse->outputTokens,
        ];
    }

    // ── Private ────────────────────────────────────────────────────────────────

    private function buildUserPrompt(AiAgentScreen $screen, array $context, bool $hasImage): string
    {
        $imageNote = $hasImage
            ? '위 이미지가 해당 화면의 Figma 프레임입니다. 시각적으로도 검수해주세요.'
            : '(이미지 없음 — 화면 이름/ID만으로 검수합니다.)';

        $mappedAt = $screen->figma_mapped_at?->format('Y.m.d') ?? '—';

        return <<<PROMPT
## 검수 대상 화면

- **화면 ID**: {$screen->screen_id}
- **화면 이름**: {$screen->title}
- **Figma 프레임**: {$screen->figma_frame_name}
- **노드 ID**: {$screen->figma_frame_id}
- **매핑일**: {$mappedAt}

{$imageNote}

위 디자인 표준을 기준으로 이 화면을 검수하고, `record_screen_review` 도구로 결과를 기록해주세요.
- compliance_score: 0~100 (100=완전 준수)
- category_scores: 색상/타이포/컴포넌트/레이아웃 각 0~100
- violations: 발견된 위반 사항 (없으면 빈 배열)
- strengths: 잘된 점 1~3개
PROMPT;
    }

    private function getReviewTool(): array
    {
        return [
            'name'         => 'record_screen_review',
            'description'  => '화면 디자인 검수 결과를 기록합니다.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'screen_id'        => ['type' => 'string', 'description' => '화면 ID (SCR-XXX)'],
                    'figma_node_id'    => ['type' => 'string'],
                    'compliance_score' => [
                        'type'        => 'integer',
                        'minimum'     => 0,
                        'maximum'     => 100,
                        'description' => '0~100 디자인 표준 준수 점수',
                    ],
                    'category_scores'  => [
                        'type'       => 'object',
                        'properties' => [
                            'color'      => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                            'typography' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                            'component'  => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                            'layout'     => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                        ],
                        'required' => ['color', 'typography', 'component', 'layout'],
                    ],
                    'strengths' => [
                        'type'  => 'array',
                        'items' => ['type' => 'string'],
                        'description' => '잘된 점 1~3개',
                    ],
                    'violations' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'category'        => ['type' => 'string', 'enum' => ['color', 'typography', 'component', 'layout']],
                                'severity'        => ['type' => 'string', 'enum' => ['critical', 'warning', 'info']],
                                'title'           => ['type' => 'string'],
                                'description'     => ['type' => 'string'],
                                'current_value'   => ['type' => 'string'],
                                'suggested_value' => ['type' => 'string'],
                                'location'        => ['type' => 'string'],
                            ],
                            'required' => ['category', 'severity', 'title', 'description'],
                        ],
                    ],
                ],
                'required' => ['screen_id', 'compliance_score', 'category_scores', 'violations'],
            ],
        ];
    }
}
