<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\FrontendStack;
use App\Enums\Agent\StageStatus;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentScreen;
use App\Models\Agent\AiAgentStackStandard;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\AiSetting;
use App\Models\SystemErrorLog;

class FrontendCodeAiService
{
    private const MAX_TOKENS = 16000;
    private const TIMEOUT    = 300;

    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
        private readonly TraceabilityService  $traceability,
    ) {}

    /**
     * @return array{artifact: AiAgentArtifact, files_count: int, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generateForScreen(int $projectId, AiAgentScreen $screen, int $userId): array
    {
        $provider = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $stack    = $this->resolveStack($projectId);
        $context  = $this->buildContext($projectId, $screen, $stack);

        $taskType     = $this->selectTaskType($stack);
        $systemPrompt = $this->prompts->render('dev', $taskType)
            ?? $this->getFallbackSystemPrompt($stack);
        $userContent  = $this->buildUserMessage($context);

        $response = $this->usageLog->callAndLog(
            provider:   $provider,
            call:       fn() => $provider->generateWithTools(
                systemPrompt: $systemPrompt,
                messages:     [['role' => 'user', 'content' => $userContent]],
                tools:        [$this->getFrontendCodeTool($stack)],
                options:      ['max_tokens' => self::MAX_TOKENS, 'timeout' => self::TIMEOUT],
            )->toAIResponse(),
            userId:     $userId,
            projectId:  $projectId,
            stage:      'dev',
            taskType:   $taskType,
        );

        $toolInput  = json_decode($response->text, true) ?? [];
        $files      = $toolInput['files'] ?? [];
        $cost       = $this->usageLog->calculateCost($response->model, $response->inputTokens, $response->outputTokens);

        $artifactContent = [
            '$metadata' => [
                'screen_id'    => $screen->screen_id,
                'stack'        => $stack->value,
                'generated_at' => now()->toIso8601String(),
                'model'        => $response->model,
                'tokens'       => ['input' => $response->inputTokens, 'output' => $response->outputTokens],
                'cost'         => $cost,
            ],
            'files'                => $files,
            'dependencies'         => $toolInput['dependencies'] ?? [],
            'implementation_notes' => $toolInput['implementation_notes'] ?? [],
            'todo_items'           => $toolInput['todo_items'] ?? [],
            'main_file_path'       => $toolInput['main_file_path'] ?? ($files[0]['path'] ?? ''),
        ];

        $stage    = $this->resolveDevelopmentStage($projectId);
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $projectId,
            stageId:   $stage->id,
            type:      ArtifactType::FRONTEND_CODE,
            scopeType: 'screen',
            scopeId:   $screen->id,
            title:     "[{$screen->screen_id}] {$screen->title} Frontend Code ({$stack->label()})",
            content:   json_encode($artifactContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:    $userId,
            meta: [
                'change_type'  => 'ai_generated',
                'stack'        => $stack->value,
                'files_count'  => count($files),
                'model'        => $response->model,
                'tokens_in'    => $response->inputTokens,
                'tokens_out'   => $response->outputTokens,
                'cost_usd'     => $cost,
                'generated_at' => now()->toIso8601String(),
            ],
        );

        $this->createTraceabilityLinks($projectId, $artifact, $screen, $context);

        return [
            'artifact'   => $artifact,
            'files_count'=> count($files),
            'tokens_in'  => $response->inputTokens,
            'tokens_out' => $response->outputTokens,
            'cost'       => $cost,
            'model'      => $response->model,
        ];
    }

    /**
     * @param  int[]|null $screenIds  null = 전체
     * @return array{total: int, done: int, failed_count: int, failed: array, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generateBatch(
        int      $projectId,
        ?array   $screenIds,
        bool     $onlyMissing,
        int      $userId,
        callable $onProgress,
    ): array {
        $query = AiAgentScreen::where('project_id', $projectId)->active()->orderBy('order');
        if ($screenIds !== null) {
            $query->whereIn('id', $screenIds);
        }
        $screens = $query->get();

        if ($onlyMissing) {
            $existingIds = AiAgentArtifact::where('project_id', $projectId)
                ->where('type', ArtifactType::FRONTEND_CODE->value)
                ->where('scope_type', 'screen')
                ->pluck('scope_id')
                ->map(fn($id) => (int) $id)
                ->toArray();
            $screens = $screens->filter(fn($s) => !in_array($s->id, $existingIds))->values();
        }

        $total     = $screens->count();
        $done      = 0;
        $failed    = [];
        $totalIn   = 0;
        $totalOut  = 0;
        $totalCost = 0.0;
        $lastModel = '';

        foreach ($screens as $screen) {
            $onProgress([
                'done'      => $done,
                'total'     => $total,
                'screen_id' => $screen->screen_id,
                'title'     => $screen->title,
                'status'    => 'processing',
                'progress'  => $total > 0 ? round(($done / $total) * 90) : 0,
            ]);

            try {
                $result     = $this->generateForScreen($projectId, $screen, $userId);
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
                'progress'  => $total > 0 ? round(($done / $total) * 90) : 90,
            ]);
        }

        return [
            'total'        => $total,
            'done'         => $done,
            'failed_count' => count($failed),
            'failed'       => $failed,
            'tokens_in'    => $totalIn,
            'tokens_out'   => $totalOut,
            'cost'         => $totalCost,
            'model'        => $lastModel,
        ];
    }

    // ── Context ───────────────────────────────────────────────────────────────

    public function buildContext(int $projectId, AiAgentScreen $screen, ?FrontendStack $stack = null): array
    {
        $stack ??= $this->resolveStack($projectId);

        $stackInfo = [];
        if ($stack) {
            $stackInfo = [
                'label'            => $stack->label(),
                'folder_structure' => AiAgentStackStandard::getFolderStructure($stack),
                'code_template'    => AiAgentStackStandard::getCodeTemplate($stack),
            ];
        }

        $codePromptArtifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::CODE_GEN_PROMPT->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->latest('id')->first();

        $erdArtifact  = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::ERD->value)->latest('id')->first();
        $apiArtifact  = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::API_SPEC->value)->latest('id')->first();
        $rbacArtifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::RBAC_MODEL->value)->latest('id')->first();

        return [
            'screen'              => $screen,
            'stack'               => $stack,
            'stack_info'          => $stackInfo,
            'code_prompt'         => $codePromptArtifact,
            'erd_artifact'        => $erdArtifact,
            'api_artifact'        => $apiArtifact,
            'rbac_artifact'       => $rbacArtifact,
            'code_prompt_exists'  => $codePromptArtifact !== null,
        ];
    }

    public function resolveStack(int $projectId): FrontendStack
    {
        $config = ProjectAiAgentConfig::forProject($projectId);
        return $config?->frontend_stack ?? FrontendStack::REACT;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildUserMessage(array $ctx): string
    {
        $screen = $ctx['screen'];
        $stack  = $ctx['stack'];
        $parts  = [];

        // Screen info
        $figmaLine = $screen->figma_url ? "\n- Figma URL: {$screen->figma_url}" : '';
        $parts[] = "# 화면 정보\n"
            . "- ID: {$screen->screen_id}\n"
            . "- 이름: {$screen->title}\n"
            . "- 설명: " . ($screen->description ?: '(없음)')
            . $figmaLine;

        // Stack info
        if (!empty($ctx['stack_info'])) {
            $si      = $ctx['stack_info'];
            $folders = empty($si['folder_structure'])
                ? '(없음)'
                : json_encode($si['folder_structure'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $parts[] = "# 기술 스택: {$si['label']}\n폴더 구조:\n{$folders}";
        }

        // T39 code generation prompt (primary input)
        if ($ctx['code_prompt']?->content) {
            $parts[] = "# 코드 생성 프롬프트 (T39 산출물)\n" . $ctx['code_prompt']->content;
        } else {
            $parts[] = "# 코드 생성 프롬프트\n(없음 — 화면 정보와 다른 명세를 기반으로 최선을 다해 생성하세요)";
        }

        // ERD
        if ($ctx['erd_artifact']?->content) {
            $parts[] = "# ERD 테이블 (발췌)\n" . $this->extractErdExcerpt($ctx['erd_artifact']->content);
        }

        // API spec
        if ($ctx['api_artifact']?->content) {
            $parts[] = "# API 엔드포인트 (발췌)\n" . $this->extractApiExcerpt($ctx['api_artifact']->content);
        }

        // RBAC
        if ($ctx['rbac_artifact']?->content) {
            $parts[] = "# 권한 모델 (발췌)\n" . $this->extractRbacExcerpt($ctx['rbac_artifact']->content);
        }

        return implode("\n\n---\n\n", $parts);
    }

    private function extractErdExcerpt(string $content): string
    {
        $data   = json_decode($content, true);
        $tables = $data['tables'] ?? [];
        if (empty($tables)) return mb_substr($content, 0, 1500);

        $lines = [];
        foreach ($tables as $name => $table) {
            $lines[] = "- **{$name}**: " . ($table['description'] ?? '');
            foreach (array_slice($table['columns'] ?? [], 0, 4) as $col => $def) {
                $type    = is_array($def) ? ($def['type'] ?? '') : (string) $def;
                $lines[] = "  - {$col}: {$type}";
            }
        }
        return mb_substr(implode("\n", $lines), 0, 2000);
    }

    private function extractApiExcerpt(string $content): string
    {
        $data  = json_decode($content, true);
        $paths = $data['spec']['paths'] ?? [];
        if (empty($paths)) return mb_substr($content, 0, 2000);

        $lines = [];
        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $op) {
                $summary = $op['summary'] ?? '';
                $lines[] = strtoupper($method) . " {$path}" . ($summary ? ": {$summary}" : '');
            }
        }
        return mb_substr(implode("\n", $lines), 0, 2000);
    }

    private function extractRbacExcerpt(string $content): string
    {
        $data  = json_decode($content, true);
        $roles = $data['roles'] ?? [];
        if (empty($roles)) return mb_substr($content, 0, 1000);

        $lines = ['## 역할 및 권한'];
        foreach ($roles as $role) {
            $perms   = implode(', ', array_slice($role['permissions'] ?? [], 0, 8));
            $lines[] = "- **{$role['name']}** (`{$role['key']}`): {$perms}";
        }
        return mb_substr(implode("\n", $lines), 0, 1000);
    }

    private function selectTaskType(FrontendStack $stack): string
    {
        return match($stack) {
            FrontendStack::HTML  => 'code_html_v1',
            FrontendStack::REACT => 'code_react_v1',
            FrontendStack::VUE   => 'code_vue_v1',
        };
    }

    private function getFrontendCodeTool(FrontendStack $stack): array
    {
        $stackDesc = match($stack) {
            FrontendStack::HTML  => 'HTML/CSS/JS 파일들',
            FrontendStack::REACT => 'React TypeScript 파일들 (pages/, components/, hooks/, types/)',
            FrontendStack::VUE   => 'Vue 3 TypeScript 파일들 (pages/, components/, composables/, types/)',
        };

        return [
            'name'         => 'create_frontend_code',
            'description'  => "프론트엔드 코드를 다중 파일로 생성합니다. {$stackDesc}",
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'files' => [
                        'type'        => 'array',
                        'description' => '생성된 파일 목록',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'path'    => ['type' => 'string', 'description' => '프로젝트 내 상대 경로 예: pages/Login.tsx'],
                                'content' => ['type' => 'string', 'description' => '파일 전체 내용'],
                                'purpose' => ['type' => 'string', 'description' => '이 파일의 역할/목적'],
                            ],
                            'required' => ['path', 'content', 'purpose'],
                        ],
                    ],
                    'dependencies' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'name'    => ['type' => 'string'],
                                'version' => ['type' => 'string'],
                                'purpose' => ['type' => 'string'],
                            ],
                            'required' => ['name'],
                        ],
                    ],
                    'implementation_notes' => [
                        'type'  => 'array',
                        'items' => ['type' => 'string'],
                        'description' => '구현 시 주의사항 및 핵심 결정 사항',
                    ],
                    'todo_items' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'type'        => [
                                    'type' => 'string',
                                    'enum' => ['review_required', 'env_var_needed', 'manual_test', 'security_check'],
                                ],
                                'description' => ['type' => 'string'],
                                'file'        => ['type' => 'string'],
                                'line'        => ['type' => 'integer'],
                            ],
                            'required' => ['type', 'description'],
                        ],
                    ],
                    'main_file_path' => [
                        'type'        => 'string',
                        'description' => '미리보기 진입점 파일 경로 (예: pages/Login.tsx 또는 index.html)',
                    ],
                ],
                'required' => ['files', 'main_file_path'],
            ],
        ];
    }

    private function resolveDevelopmentStage(int $projectId): AiAgentProjectStage
    {
        return AiAgentProjectStage::where('project_id', $projectId)
            ->where('type', StageType::DEVELOPMENT)
            ->first()
            ?? AiAgentProjectStage::create([
                'project_id' => $projectId,
                'type'       => StageType::DEVELOPMENT,
                'name'       => '개발',
                'status'     => StageStatus::IN_PROGRESS,
                'order'      => 4,
            ]);
    }

    private function createTraceabilityLinks(
        int             $projectId,
        AiAgentArtifact $artifact,
        AiAgentScreen   $screen,
        array           $context,
    ): void {
        try {
            $this->traceability->link(
                $projectId,
                'screen', $screen->id, $screen->screen_id,
                'artifact', $artifact->id, "FRONTEND_CODE#{$artifact->id}",
                'has_code',
            );
            if ($context['code_prompt']) {
                $this->traceability->link(
                    $projectId,
                    'artifact', $artifact->id, "FRONTEND_CODE#{$artifact->id}",
                    'artifact', $context['code_prompt']->id, "CODE_PROMPT#{$context['code_prompt']->id}",
                    'derived_from',
                );
            }
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
        }
    }

    private function getFallbackSystemPrompt(FrontendStack $stack): string
    {
        return match($stack) {
            FrontendStack::HTML  => $this->htmlSystemPrompt(),
            FrontendStack::REACT => $this->reactSystemPrompt(),
            FrontendStack::VUE   => $this->vueSystemPrompt(),
        };
    }

    private function htmlSystemPrompt(): string
    {
        return <<<'PROMPT'
당신은 시니어 Frontend 개발자입니다.
주어진 통합 명세에 따라 프로덕션급 HTML/CSS/JS 코드를 작성하세요.

기술 요구사항:
- 다중 파일 구조: index.html, style.css, script.js (필요 시 추가 파일)
- ES6+ Vanilla JavaScript (jQuery 금지)
- 시맨틱 HTML5 + ARIA 접근성
- Tailwind CSS via CDN
- 실제 API fetch() 호출 (명세된 엔드포인트 사용)
- 폼 검증, 로딩 상태, 에러 처리 완비
- 반응형 레이아웃

API 연계:
- 명세된 엔드포인트를 정확히 사용
- 응답 schema 준수
- 에러 코드별 처리 (401, 403, 422, 500)
- API_BASE_URL은 환경변수 또는 상수로 분리

권한 검증:
- RBAC에 명세된 권한 확인
- 비권한 시 적절한 처리 (리다이렉트 또는 에러 표시)

create_frontend_code 도구로 응답하세요.
PROMPT;
    }

    private function reactSystemPrompt(): string
    {
        return <<<'PROMPT'
당신은 시니어 React/TypeScript 개발자입니다.
주어진 통합 명세에 따라 프로덕션급 React 컴포넌트를 작성하세요.

기술 요구사항:
- TypeScript (모든 타입 명시, any 금지)
- 함수형 컴포넌트 + Hooks 전용
- 파일 분리: pages/ScreenName.tsx, components/PartName.tsx, hooks/useFeatureName.ts, types/feature.ts
- 상태 관리: useState, useReducer (Context 필요 시 명시)
- 사이드 이펙트: useEffect with cleanup
- API 호출: axios (별도 서비스 파일 권장)
- 폼: 네이티브 React state 또는 react-hook-form
- 에러 처리: try/catch + 사용자 피드백

API 연계:
- 명세된 엔드포인트만 사용
- 요청/응답 타입 정의 (interface)
- 에러 처리 (401, 403, 422 등 응답 코드 분기)
- API_BASE_URL 환경변수 (process.env.REACT_APP_API_URL)

권한 검증:
- 보호 라우트에서 RBAC 권한 체크
- 비권한 시 처리 (Navigate to /forbidden 또는 조건부 렌더링)

컴포넌트 명명:
- 컴포넌트: PascalCase
- 훅: useXxx
- Props: interface XxxProps

create_frontend_code 도구로 응답하세요.
PROMPT;
    }

    private function vueSystemPrompt(): string
    {
        return <<<'PROMPT'
당신은 시니어 Vue 3/TypeScript 개발자입니다.
주어진 통합 명세에 따라 프로덕션급 Vue 3 컴포넌트를 작성하세요.

기술 요구사항:
- Composition API (<script setup lang="ts">)
- TypeScript (모든 타입 명시)
- SFC 구조: pages/ScreenName.vue, components/PartName.vue, composables/useFeatureName.ts, types/feature.ts
- 반응형 상태: ref, reactive, computed
- 비동기: async/await + axios
- Vue Router 사용 (useRouter, useRoute)
- defineProps / defineEmits 타입 명시

API 연계:
- 명세된 엔드포인트만 사용
- 요청/응답 타입 정의 (interface)
- 에러 처리 (401, 403, 422 응답 코드 분기)
- API_BASE_URL 환경변수 (import.meta.env.VITE_API_URL)

권한 검증:
- 라우트 가드 또는 컴포넌트 내 권한 체크
- 비권한 시 처리 (router.push('/forbidden') 또는 조건부 렌더링)

create_frontend_code 도구로 응답하세요.
PROMPT;
    }
}
