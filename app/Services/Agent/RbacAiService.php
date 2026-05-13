<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageStatus;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentScreen;
use App\Models\AiSetting;
use App\Models\SystemErrorLog;

class RbacAiService
{
    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
        private readonly TraceabilityService  $traceability,
    ) {}

    /**
     * @param  callable(array): void  $onProgress
     * @return array{artifact: AiAgentArtifact, roles_count: int, permissions_count: int, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generate(int $projectId, int $userId, callable $onProgress): array
    {
        $provider = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $context  = $this->buildContext($projectId);

        $onProgress(['status' => 'CONTEXT_READY', 'progress' => 15,
            'message' => "컨텍스트 로드 완료 (테이블 {$context['tables_count']}개, 엔드포인트 {$context['endpoints_count']}개)."]);

        $systemPrompt = $this->prompts->render('dev_prep', 'rbac_generation_v1')
            ?? $this->getFallbackSystemPrompt();

        $userContent = $this->buildUserContent($context);

        $onProgress(['status' => 'CALLING_AI', 'progress' => 20,
            'message' => '웍스에 RBAC 권한 모델 설계를 요청하고 있습니다. 잠시 기다려 주세요...']);

        $response = $this->usageLog->callAndLog(
            provider:  $provider,
            call:      fn() => $provider->generateWithTools(
                systemPrompt: $systemPrompt,
                messages:     [['role' => 'user', 'content' => $userContent]],
                tools:        [$this->getRbacTool()],
                options:      ['max_tokens' => 8000, 'timeout' => 240],
            )->toAIResponse(),
            userId:    $userId,
            projectId: $projectId,
            stage:     'dev_prep',
            taskType:  'rbac_generation_v1',
        );

        $toolInput = json_decode($response->text, true) ?? [];

        $onProgress(['status' => 'AI_DONE', 'progress' => 80,
            'message' => '설계 완료. 산출물을 저장하는 중...']);

        $roles       = $this->normalizeRoles($toolInput['roles'] ?? []);
        $permissions = $this->normalizePermissions($toolInput['permissions'] ?? []);
        $policies    = $toolInput['policies'] ?? [];

        $artifactContent = [
            '$metadata' => [
                'version'      => '1.0',
                'generated_at' => now()->toIso8601String(),
                'based_on'     => [
                    'erd_artifact_id'     => $context['erd_artifact_id'],
                    'api_spec_artifact_id'=> $context['api_spec_artifact_id'],
                    'tables_count'        => $context['tables_count'],
                    'endpoints_count'     => $context['endpoints_count'],
                ],
            ],
            'roles'       => $roles,
            'permissions' => $permissions,
            'policies'    => $policies,
        ];

        $stage    = $this->resolveDevPrepStage($projectId);
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $projectId,
            stageId:   $stage->id,
            type:      ArtifactType::RBAC_MODEL,
            scopeType: 'project',
            scopeId:   $projectId,
            title:     '권한 모델 (RBAC)',
            content:   json_encode($artifactContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:    $userId,
            meta: [
                'change_type'       => 'ai_generated',
                'model'             => $response->model,
                'tokens_in'         => $response->inputTokens,
                'tokens_out'        => $response->outputTokens,
                'roles_count'       => count($roles),
                'permissions_count' => count($permissions),
                'generated_at'      => now()->toIso8601String(),
            ],
        );

        $this->createTraceabilityLinks($projectId, $artifact, $context);

        $onProgress(['status' => 'SAVED', 'progress' => 100, 'message' => '저장 완료.']);

        return [
            'artifact'         => $artifact,
            'roles_count'      => count($roles),
            'permissions_count'=> count($permissions),
            'tokens_in'        => $response->inputTokens,
            'tokens_out'       => $response->outputTokens,
            'cost'             => $this->usageLog->calculateCost($response->model, $response->inputTokens, $response->outputTokens),
            'model'            => $response->model,
        ];
    }

    // ── Context ───────────────────────────────────────────────────────────────

    public function buildContext(int $projectId): array
    {
        $erdArtifact     = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::ERD->value)
            ->latest('id')->first();

        $apiSpecArtifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::API_SPEC->value)
            ->latest('id')->first();

        $screens         = AiAgentScreen::where('project_id', $projectId)->active()->get();

        $erdSummary      = $this->extractErdSummary($erdArtifact?->content ?? '');
        $apiSummary      = $this->extractApiSummary($apiSpecArtifact?->content ?? '');

        return [
            'erd_artifact_id'      => $erdArtifact?->id,
            'api_spec_artifact_id' => $apiSpecArtifact?->id,
            'erd_content'          => $erdSummary['text'],
            'tables_count'         => $erdSummary['count'],
            'api_content'          => $apiSummary['text'],
            'endpoints_count'      => $apiSummary['count'],
            'screens'              => $screens,
            'screen_count'         => $screens->count(),
        ];
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildUserContent(array $context): string
    {
        $parts = [];

        if ($context['erd_content']) {
            $parts[] = "# ERD (데이터 모델)\n\n{$context['erd_content']}";
        }

        if ($context['api_content']) {
            $parts[] = "# API 엔드포인트 목록\n\n{$context['api_content']}";
        }

        if ($context['screens']->isNotEmpty()) {
            $lines   = $context['screens']->map(
                fn($s) => "- {$s->screen_id}: {$s->title}" . ($s->description ? " ({$s->description})" : '')
            )->join("\n");
            $parts[] = "# 화면 목록\n\n{$lines}";
        }

        return implode("\n\n---\n\n", $parts)
            ?: '프로젝트 기획 정보가 없습니다. 기본적인 RBAC 권한 모델을 설계해주세요.';
    }

    private function normalizeRoles(array $roles): array
    {
        $result = [];
        foreach ($roles as $role) {
            $key = $role['key'] ?? '';
            if (!$key) continue;
            $result[] = [
                'key'         => $key,
                'name'        => $role['name']        ?? $key,
                'description' => $role['description'] ?? '',
                'permissions' => array_values(array_unique($role['permissions'] ?? [])),
            ];
        }
        return $result;
    }

    private function normalizePermissions(array $permissions): array
    {
        $result = [];
        foreach ($permissions as $perm) {
            $key = $perm['key'] ?? '';
            if (!$key) continue;
            $result[] = [
                'key'          => $key,
                'name'         => $perm['name']          ?? $key,
                'resource'     => $perm['resource']      ?? '',
                'action'       => $perm['action']        ?? '',
                'api_endpoints'=> $perm['api_endpoints'] ?? [],
            ];
        }
        return $result;
    }

    private function extractErdSummary(string $content): array
    {
        if (!$content) return ['text' => '', 'count' => 0];
        $data   = json_decode($content, true);
        $tables = $data['tables'] ?? [];
        if (empty($tables)) return ['text' => '', 'count' => 0];

        $lines = ['## 테이블 목록 (ERD)'];
        foreach ($tables as $name => $table) {
            $lines[] = "- **{$name}**: " . ($table['description'] ?? '');
        }
        return ['text' => implode("\n", $lines), 'count' => count($tables)];
    }

    private function extractApiSummary(string $content): array
    {
        if (!$content) return ['text' => '', 'count' => 0];
        $data  = json_decode($content, true);
        $paths = $data['spec']['paths'] ?? [];
        if (empty($paths)) return ['text' => '', 'count' => 0];

        $count = 0;
        $lines = ['## API 엔드포인트 목록'];
        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $op) {
                $lines[] = "- {$method} {$path}: " . ($op['summary'] ?? '');
                $count++;
            }
        }
        return ['text' => implode("\n", $lines), 'count' => $count];
    }

    private function resolveDevPrepStage(int $projectId): AiAgentProjectStage
    {
        return AiAgentProjectStage::where('project_id', $projectId)
            ->where('type', StageType::DEV_PREP)
            ->first()
            ?? AiAgentProjectStage::create([
                'project_id' => $projectId,
                'type'       => StageType::DEV_PREP,
                'name'       => '개발 준비',
                'status'     => StageStatus::IN_PROGRESS,
                'order'      => 3,
            ]);
    }

    private function createTraceabilityLinks(int $projectId, AiAgentArtifact $artifact, array $context): void
    {
        try {
            if ($context['erd_artifact_id']) {
                $this->traceability->link(
                    projectId:  $projectId,
                    sourceType: 'artifact', sourceId: $artifact->id,
                    sourceRef:  "RBAC#{$artifact->id}",
                    targetType: 'artifact', targetId: $context['erd_artifact_id'],
                    targetRef:  "ERD#{$context['erd_artifact_id']}",
                    linkType:   'derived_from',
                );
            }
            if ($context['api_spec_artifact_id']) {
                $this->traceability->link(
                    projectId:  $projectId,
                    sourceType: 'artifact', sourceId: $artifact->id,
                    sourceRef:  "RBAC#{$artifact->id}",
                    targetType: 'artifact', targetId: $context['api_spec_artifact_id'],
                    targetRef:  "API_SPEC#{$context['api_spec_artifact_id']}",
                    linkType:   'derived_from',
                );
            }
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
        }
    }

    private function getRbacTool(): array
    {
        return [
            'name'         => 'create_rbac_model',
            'description'  => 'RBAC 권한 모델(역할·권한·정책)을 구조화된 형태로 기록합니다.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'roles' => [
                        'type'        => 'array',
                        'description' => '시스템 역할 목록',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'key'         => ['type' => 'string', 'description' => '영문 소문자, 예: admin, pm, developer, viewer'],
                                'name'        => ['type' => 'string', 'description' => '한글명, 예: 관리자'],
                                'description' => ['type' => 'string'],
                                'permissions' => [
                                    'type'        => 'array',
                                    'items'       => ['type' => 'string'],
                                    'description' => '이 역할이 가진 permission key 목록',
                                ],
                            ],
                            'required' => ['key', 'name', 'permissions'],
                        ],
                    ],
                    'permissions' => [
                        'type'        => 'array',
                        'description' => '권한 목록. 각 ERD 테이블의 CRUD + API 엔드포인트 기반으로 정의',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'key'          => ['type' => 'string', 'description' => '{resource}.{action} 형식, 예: project.view'],
                                'name'         => ['type' => 'string', 'description' => '한글명, 예: 프로젝트 조회'],
                                'resource'     => ['type' => 'string', 'description' => '리소스명 (ERD 테이블명 기준)'],
                                'action'       => [
                                    'type' => 'string',
                                    'enum' => ['view', 'create', 'edit', 'delete', 'approve', 'export', 'manage'],
                                ],
                                'api_endpoints'=> [
                                    'type'  => 'array',
                                    'items' => ['type' => 'string'],
                                    'description' => '보호하는 API 엔드포인트 목록, 예: ["GET /api/v1/projects"]',
                                ],
                            ],
                            'required' => ['key', 'name', 'resource', 'action'],
                        ],
                    ],
                    'policies' => [
                        'type'        => 'array',
                        'description' => '조건부 권한 정책 후보 (비즈니스 규칙 기반 — 사용자 검토 권장)',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'name'           => ['type' => 'string', 'description' => '예: ProjectPolicy'],
                                'model'          => ['type' => 'string', 'description' => '대상 Eloquent 모델명, 예: Project'],
                                'description'    => ['type' => 'string'],
                                'methods'        => [
                                    'type'                 => 'object',
                                    'description'          => '메서드명 → 조건 설명 (string)',
                                    'additionalProperties' => ['type' => 'string'],
                                ],
                                'requires_review'=> ['type' => 'boolean', 'description' => '비즈니스 규칙 검토 필요 여부'],
                            ],
                            'required' => ['name', 'model'],
                        ],
                    ],
                ],
                'required' => ['roles', 'permissions'],
            ],
        ];
    }

    private function getFallbackSystemPrompt(): string
    {
        return <<<'PROMPT'
당신은 시스템 보안 아키텍트입니다.

주어진 데이터 모델(ERD)과 API 명세를 분석하여 RBAC 권한 모델을 설계해주세요.

설계 원칙:
1. 역할(Role) 정의:
   - 기본 4개: admin(관리자), manager/pm(매니저), member(일반 멤버), viewer(읽기 전용)
   - 프로젝트 특성에 따라 추가 역할 식별 (예: developer, analyst, client 등)
2. 권한(Permission) 명명: {resource}.{action} (예: project.view, user.create)
3. 각 ERD 테이블에 대해 CRUD + 필요 시 approve/export/manage 권한 생성
4. 각 API 엔드포인트에 적합한 권한 매핑
5. 조건부 권한(Policy) 후보 식별:
   - "본인 데이터만 수정", "프로젝트 멤버만 접근" 등
   - 웍스 추정 비즈니스 규칙은 requires_review: true로 표시

create_rbac_model 도구로 응답하세요.
PROMPT;
    }
}
