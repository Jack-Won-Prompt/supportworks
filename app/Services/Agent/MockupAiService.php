<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\FrontendStack;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;
use App\Models\Agent\AiAgentStackStandard;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\AiSetting;
use App\Models\SystemErrorLog;

class MockupAiService
{
    private const MAX_TOKENS = 4096;

    private const STACK_TASK_TYPE = [
        'HTML'  => 'mockup_html_generator',
        'REACT' => 'mockup_react_generator',
        'VUE'   => 'mockup_vue_generator',
    ];

    private const CREATE_MOCKUP_TOOL = [
        'name'        => 'create_mockup',
        'description' => '화면 목업 코드를 생성합니다.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'main_file' => [
                    'type'       => 'object',
                    'properties' => [
                        'name'    => ['type' => 'string', 'description' => '파일명 (예: Login.html / Login.tsx / Login.vue)'],
                        'content' => ['type' => 'string', 'description' => '전체 코드'],
                    ],
                    'required' => ['name', 'content'],
                ],
                'description'   => ['type' => 'string', 'description' => '구현된 화면 요약 (무엇이 가능한지)'],
                'features'      => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => '구현된 기능 목록'],
                'dependencies'  => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => '사용된 외부 의존성 (CDN 등)'],
                'preview_notes' => ['type' => 'string', 'description' => '미리보기 주의사항'],
            ],
            'required' => ['main_file', 'description'],
        ],
    ];

    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
        private readonly TraceabilityService  $traceability,
    ) {}

    /**
     * 단일 화면 목업 생성 및 저장.
     *
     * @return array{artifact: AiAgentArtifact, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generateForScreen(
        int           $projectId,
        int           $stageId,
        AiAgentScreen $screen,
        int           $userId,
    ): array {
        $provider = new AnthropicProvider(AiSetting::current()->anthropicKey());

        $config   = ProjectAiAgentConfig::forProject($projectId);
        $stack    = $config?->frontend_stack ?? FrontendStack::HTML;
        $taskType = self::STACK_TASK_TYPE[$stack->value] ?? 'mockup_html_generator';

        $systemPrompt = $this->prompts->render('planning', $taskType)
            ?? $this->getFallbackSystemPrompt($stack);

        $screenPrompt = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::SCREEN_PROMPTS->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->latest()
            ->first();

        $stackInfo = $this->buildStackInfo($stack);
        $messages  = [['role' => 'user', 'content' => $this->buildUserMessage($screen, $screenPrompt, $stack, $stackInfo)]];

        $toolResponse = null;
        $response     = $this->usageLog->callAndLog(
            provider:  $provider,
            call:      function () use ($provider, $systemPrompt, $messages, &$toolResponse) {
                $toolResponse = $provider->generateWithTools(
                    systemPrompt: $systemPrompt,
                    messages:     $messages,
                    tools:        [self::CREATE_MOCKUP_TOOL],
                    options:      ['max_tokens' => self::MAX_TOKENS],
                );
                return $toolResponse->toAIResponse();
            },
            userId:    $userId,
            projectId: $projectId,
            stage:     'planning',
            taskType:  $taskType,
        );

        $toolInput          = $toolResponse->toolInput;
        $toolInput['stack'] = $stack->value;

        $cost     = $this->usageLog->calculateCost($response->model, $response->inputTokens, $response->outputTokens);
        $artifact = $this->persistMockup($projectId, $stageId, $screen, $toolInput, [
            'model'      => $response->model,
            'tokens_in'  => $response->inputTokens,
            'tokens_out' => $response->outputTokens,
            'cost'       => $cost,
            'stack'      => $stack->value,
        ], $userId, $screenPrompt);

        return [
            'artifact'   => $artifact,
            'tokens_in'  => $response->inputTokens,
            'tokens_out' => $response->outputTokens,
            'cost'       => $cost,
            'model'      => $response->model,
        ];
    }

    /**
     * 일괄 생성 — 지정된 screen ID 목록을 순차 처리.
     *
     * @param  int[]     $screenIds
     * @return array{total: int, failed_count: int, failed: array, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generateBatch(
        int      $projectId,
        int      $stageId,
        array    $screenIds,
        int      $userId,
        callable $onProgress,
    ): array {
        $screens   = AiAgentScreen::whereIn('id', $screenIds)->orderBy('order')->get();
        $total     = $screens->count();
        $done      = 0;
        $failed    = [];
        $totalIn   = 0;
        $totalOut  = 0;
        $totalCost = 0.0;
        $lastModel = '';

        foreach ($screens as $screen) {
            $onProgress(['done' => $done, 'total' => $total, 'screen_id' => $screen->screen_id, 'title' => $screen->title, 'status' => 'processing']);

            try {
                $result     = $this->generateForScreen($projectId, $stageId, $screen, $userId);
                $totalIn   += $result['tokens_in'];
                $totalOut  += $result['tokens_out'];
                $totalCost += $result['cost'];
                $lastModel  = $result['model'];
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $failed[$screen->screen_id] = $e->getMessage();
            }

            $done++;
            $onProgress([
                'done'      => $done,
                'total'     => $total,
                'screen_id' => $screen->screen_id,
                'title'     => $screen->title,
                'status'    => isset($failed[$screen->screen_id]) ? 'failed' : 'done',
                'cost_so_far' => round($totalCost, 4),
            ]);
        }

        return [
            'total'        => $total,
            'failed_count' => count($failed),
            'failed'       => $failed,
            'tokens_in'    => $totalIn,
            'tokens_out'   => $totalOut,
            'cost'         => $totalCost,
            'model'        => $lastModel,
        ];
    }

    // ── 컨텍스트 빌더 ─────────────────────────────────────────────────────────

    private function buildStackInfo(FrontendStack $stack): array
    {
        $folderStructure = AiAgentStackStandard::getFolderStructure($stack);
        $codeTemplate    = AiAgentStackStandard::getCodeTemplate($stack);

        return [
            'label'            => $stack->label(),
            'folder_structure' => $folderStructure,
            'code_template'    => $codeTemplate,
        ];
    }

    private function buildUserMessage(
        AiAgentScreen      $screen,
        ?AiAgentArtifact   $screenPrompt,
        FrontendStack      $stack,
        array              $stackInfo,
    ): string {
        $parts = [];

        $parts[] = "# 화면 정보\n"
            . "- ID: {$screen->screen_id}\n"
            . "- 이름: {$screen->title}\n"
            . "- 설명: " . ($screen->description ?: '(없음)');

        if ($screenPrompt?->content) {
            $parts[] = "# 화면 명세 (T24 자동 생성)\n" . $screenPrompt->content;
        } elseif ($screen->generation_prompt) {
            $parts[] = "# 화면 명세\n" . $screen->generation_prompt;
        }

        $parts[] = "# 프론트엔드 스택: {$stackInfo['label']}\n"
            . "명명 규칙 및 컴포넌트 패턴:\n"
            . (empty($stackInfo['code_template'])
                ? '(없음)'
                : json_encode($stackInfo['code_template'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $parts[] = "위 명세에 따라 완전히 동작하는 목업을 create_mockup 도구로 생성해주세요.\n"
            . "더미 데이터를 사용하여 실제처럼 보이게 만들어주세요.\n"
            . "백엔드 없이 브라우저에서 즉시 동작해야 합니다.";

        return implode("\n\n---\n\n", $parts);
    }

    // ── 저장 + 추적성 ─────────────────────────────────────────────────────────

    private function persistMockup(
        int               $projectId,
        int               $stageId,
        AiAgentScreen     $screen,
        array             $toolInput,
        array             $usage,
        int               $userId,
        ?AiAgentArtifact  $screenPrompt,
    ): AiAgentArtifact {
        $content = json_encode($toolInput, JSON_UNESCAPED_UNICODE);

        /** @var AiAgentArtifact $artifact */
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $projectId,
            stageId:   $stageId,
            type:      ArtifactType::MOCKUP,
            scopeType: 'screen',
            scopeId:   $screen->id,
            title:     "[{$screen->screen_id}] {$screen->title} 목업",
            content:   $content,
            userId:    $userId,
            meta: [
                'change_type'  => 'ai_generated',
                'model'        => $usage['model'],
                'tokens_in'    => $usage['tokens_in'],
                'tokens_out'   => $usage['tokens_out'],
                'cost_usd'     => $usage['cost'],
                'stack'        => $usage['stack'],
                'generated_at' => now()->toIso8601String(),
            ],
        );

        // Quick-access field on screen
        $screen->update(['mockup_content' => $toolInput['main_file']['content'] ?? '']);

        // Traceability: screen → mockup artifact
        $this->traceability->link(
            $projectId,
            'screen', $screen->id, $screen->screen_id,
            'artifact', $artifact->id, "MOCKUP#{$artifact->id}",
            'mockup_for'
        );

        // Traceability: mockup → screen prompt (derived_from)
        if ($screenPrompt) {
            $this->traceability->link(
                $projectId,
                'artifact', $artifact->id, "MOCKUP#{$artifact->id}",
                'artifact', $screenPrompt->id, "SCREEN_PROMPT#{$screenPrompt->id}",
                'derived_from'
            );
        }

        return $artifact;
    }

    // ── 폴백 프롬프트 ─────────────────────────────────────────────────────────

    private function getFallbackSystemPrompt(FrontendStack $stack): string
    {
        return match($stack) {
            FrontendStack::REACT => <<<'PROMPT'
당신은 시니어 React 개발자입니다. 주어진 화면 명세를 기반으로 동작하는 React + TypeScript 목업을 작성하세요.
단일 .tsx 파일, 함수형 컴포넌트, Hooks, Tailwind CSS 사용. create_mockup 도구로 응답하세요.
PROMPT,
            FrontendStack::VUE => <<<'PROMPT'
당신은 시니어 Vue 3 개발자입니다. 주어진 화면 명세를 기반으로 동작하는 Vue 3 SFC 목업을 작성하세요.
<script setup lang="ts"> Composition API, Tailwind CSS 사용. create_mockup 도구로 응답하세요.
PROMPT,
            default => <<<'PROMPT'
당신은 시니어 프론트엔드 개발자입니다. 주어진 화면 명세를 기반으로 동작하는 HTML 목업을 작성하세요.
단일 HTML 파일, Tailwind CSS CDN, Vanilla JS 사용. create_mockup 도구로 응답하세요.
PROMPT,
        };
    }
}
