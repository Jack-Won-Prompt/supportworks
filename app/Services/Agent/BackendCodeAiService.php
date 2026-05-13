<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageStatus;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\AiSetting;
use App\Models\SystemErrorLog;

class BackendCodeAiService
{
    private const MAX_TOKENS = 16000;
    private const TIMEOUT    = 300;

    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
        private readonly TraceabilityService  $traceability,
    ) {}

    /**
     * ERD artifact から全テーブル一覧を返す
     * @return array<array{name: string, table: string, description: string, columns: array}>
     */
    public function getResources(int $projectId): array
    {
        $erd = $this->loadArtifact($projectId, ArtifactType::ERD);
        if (!$erd || empty($erd->content)) return [];

        $data   = json_decode($erd->content, true) ?? [];
        $tables = $data['tables'] ?? [];

        $resources = [];
        foreach ($tables as $tableName => $tableData) {
            $name = $tableData['model'] ?? $this->tableToModel($tableName);
            $resources[] = [
                'name'        => $name,
                'table'       => $tableName,
                'description' => $tableData['description'] ?? '',
                'columns'     => $tableData['columns'] ?? [],
                'relations'   => $tableData['relations'] ?? [],
            ];
        }

        return $resources;
    }

    /**
     * 단일 리소스(테이블) 백엔드 코드 생성
     *
     * @return array{artifact: AiAgentArtifact, files_count: int, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generateForResource(int $projectId, string $tableName, int $userId): array
    {
        $provider = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $context  = $this->buildContext($projectId, $tableName);
        $taskType = 'code_backend_laravel_v1';

        $systemPrompt = $this->prompts->render('dev', $taskType)
            ?? $this->getDefaultSystemPrompt();
        $userContent  = $this->buildUserMessage($context);

        $response = $this->usageLog->callAndLog(
            provider:  $provider,
            call:      fn() => $provider->generateWithTools(
                systemPrompt: $systemPrompt,
                messages:     [['role' => 'user', 'content' => $userContent]],
                tools:        [$this->getBackendCodeTool()],
                options:      ['max_tokens' => self::MAX_TOKENS, 'timeout' => self::TIMEOUT],
            )->toAIResponse(),
            userId:    $userId,
            projectId: $projectId,
            stage:     'dev',
            taskType:  $taskType,
        );

        $toolInput = json_decode($response->text, true) ?? [];
        $files     = $toolInput['files'] ?? [];
        $cost      = $this->usageLog->calculateCost($response->model, $response->inputTokens, $response->outputTokens);

        $resourceName  = $toolInput['resource'] ?? $this->tableToModel($tableName);
        $laravelVersion = $this->getLaravelVersion();

        $artifactContent = [
            '$metadata' => [
                'resource'     => $resourceName,
                'table'        => $tableName,
                'stack'        => 'LARAVEL',
                'version'      => $laravelVersion,
                'generated_at' => now()->toIso8601String(),
                'model'        => $response->model,
                'tokens'       => ['input' => $response->inputTokens, 'output' => $response->outputTokens],
                'cost'         => $cost,
            ],
            'files'        => $files,
            'routes'       => $toolInput['routes'] ?? [],
            'dependencies' => $toolInput['dependencies'] ?? [],
            'todo_items'   => $toolInput['todo_items'] ?? [],
        ];

        $stage    = $this->resolveDevelopmentStage($projectId);
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $projectId,
            stageId:   $stage->id,
            type:      ArtifactType::BACKEND_CODE,
            scopeType: 'resource',
            scopeId:   $this->getScopeId($tableName),
            title:     "{$resourceName} Backend Code (Laravel {$laravelVersion})",
            content:   json_encode($artifactContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:    $userId,
            meta: [
                'change_type'  => 'ai_generated',
                'resource'     => $resourceName,
                'table'        => $tableName,
                'files_count'  => count($files),
                'model'        => $response->model,
                'tokens_in'    => $response->inputTokens,
                'tokens_out'   => $response->outputTokens,
                'cost_usd'     => $cost,
                'generated_at' => now()->toIso8601String(),
            ],
        );

        $this->createTraceabilityLinks($projectId, $artifact, $tableName, $context);

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
     * 전체 또는 선택 리소스 일괄 생성
     *
     * @param  string[]|null $tableNames  null = 전체
     * @return array{total: int, done: int, failed_count: int, failed: array, tokens_in: int, tokens_out: int, cost: float}
     */
    public function generateBatch(
        int      $projectId,
        ?array   $tableNames,
        bool     $onlyMissing,
        int      $userId,
        callable $onProgress,
    ): array {
        $allResources = $this->getResources($projectId);
        if ($tableNames !== null) {
            $allResources = array_filter($allResources, fn($r) => in_array($r['table'], $tableNames));
        }

        if ($onlyMissing) {
            $existingScopes = AiAgentArtifact::where('project_id', $projectId)
                ->where('type', ArtifactType::BACKEND_CODE->value)
                ->where('scope_type', 'resource')
                ->pluck('scope_id')
                ->map(fn($id) => (int) $id)
                ->toArray();
            $allResources = array_filter(
                $allResources,
                fn($r) => !in_array($this->getScopeId($r['table']), $existingScopes)
            );
        }

        $resources = array_values($allResources);
        $total     = count($resources);
        $done      = 0;
        $failed    = [];
        $totalIn   = 0;
        $totalOut  = 0;
        $totalCost = 0.0;
        $lastModel = '';

        foreach ($resources as $resource) {
            $onProgress([
                'done'     => $done,
                'total'    => $total,
                'table'    => $resource['table'],
                'resource' => $resource['name'],
                'status'   => 'processing',
                'progress' => $total > 0 ? round(($done / $total) * 90) : 0,
            ]);

            try {
                $result     = $this->generateForResource($projectId, $resource['table'], $userId);
                $totalIn   += $result['tokens_in'];
                $totalOut  += $result['tokens_out'];
                $totalCost += $result['cost'];
                $lastModel  = $result['model'];
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $failed[$resource['table']] = $e->getMessage();
            }

            $done++;
            $onProgress([
                'done'     => $done,
                'total'    => $total,
                'table'    => $resource['table'],
                'resource' => $resource['name'],
                'status'   => isset($failed[$resource['table']]) ? 'failed' : 'done',
                'progress' => $total > 0 ? round(($done / $total) * 90) : 90,
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

    public function buildContext(int $projectId, string $tableName): array
    {
        $erdArtifact  = $this->loadArtifact($projectId, ArtifactType::ERD);
        $apiArtifact  = $this->loadArtifact($projectId, ArtifactType::API_SPEC);
        $rbacArtifact = $this->loadArtifact($projectId, ArtifactType::RBAC_MODEL);

        $erdData     = $erdArtifact ? (json_decode($erdArtifact->content, true) ?? []) : [];
        $tableData   = $erdData['tables'][$tableName] ?? [];
        $resourceName = $tableData['model'] ?? $this->tableToModel($tableName);

        // Related tables (foreign key targets + reverse)
        $relatedTables = $this->extractRelatedTables($tableName, $erdData);

        return [
            'table_name'      => $tableName,
            'resource_name'   => $resourceName,
            'table_data'      => $tableData,
            'related_tables'  => $relatedTables,
            'erd_artifact'    => $erdArtifact,
            'api_artifact'    => $apiArtifact,
            'rbac_artifact'   => $rbacArtifact,
            'laravel_version' => $this->getLaravelVersion(),
        ];
    }

    public function getScopeId(string $tableName): int
    {
        return abs(crc32($tableName)) % 2000000000;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildUserMessage(array $ctx): string
    {
        $parts = [];

        // Resource / table info
        $tableName    = $ctx['table_name'];
        $resourceName = $ctx['resource_name'];
        $tableData    = $ctx['table_data'];

        $columnLines = [];
        foreach ($tableData['columns'] ?? [] as $col => $def) {
            $type       = is_array($def) ? ($def['type'] ?? '') : (string) $def;
            $nullable   = is_array($def) && !empty($def['nullable']) ? '(nullable)' : '';
            $unique     = is_array($def) && !empty($def['unique']) ? '(unique)' : '';
            $columnLines[] = "  - {$col}: {$type} {$nullable} {$unique}";
        }

        $parts[] = "# 대상 리소스\n"
            . "- 리소스명: {$resourceName}\n"
            . "- 테이블: {$tableName}\n"
            . "- 설명: " . ($tableData['description'] ?? '(없음)') . "\n"
            . "- 컬럼:\n" . implode("\n", $columnLines);

        // Related tables
        if (!empty($ctx['related_tables'])) {
            $relLines = [];
            foreach ($ctx['related_tables'] as $rel) {
                $relLines[] = "  - {$rel['table']}: {$rel['type']} ({$rel['key']})";
            }
            $parts[] = "# 연관 테이블\n" . implode("\n", $relLines);
        }

        // Full ERD for context
        if ($ctx['erd_artifact']?->content) {
            $erdData = json_decode($ctx['erd_artifact']->content, true) ?? [];
            $erdLines = [];
            foreach ($erdData['tables'] ?? [] as $tbl => $tdata) {
                $erdLines[] = "- {$tbl}: " . ($tdata['description'] ?? '');
            }
            $parts[] = "# 전체 ERD 테이블 목록\n" . mb_substr(implode("\n", $erdLines), 0, 1500);
        }

        // API endpoints for this resource
        if ($ctx['api_artifact']?->content) {
            $parts[] = "# API 명세 (이 리소스 관련)\n" . $this->extractApiForResource($ctx['api_artifact']->content, $ctx['table_name']);
        }

        // RBAC
        if ($ctx['rbac_artifact']?->content) {
            $parts[] = "# 권한 모델 (RBAC)\n" . $this->extractRbacExcerpt($ctx['rbac_artifact']->content);
        }

        // Laravel version
        $parts[] = "# Laravel 버전\nLaravel " . $ctx['laravel_version'];

        return implode("\n\n---\n\n", $parts);
    }

    private function extractRelatedTables(string $tableName, array $erdData): array
    {
        $relations = [];
        $allTables = $erdData['tables'] ?? [];

        foreach ($allTables[$tableName]['relations'] ?? [] as $rel) {
            $target = is_array($rel) ? ($rel['table'] ?? '') : '';
            if ($target) {
                $relations[] = [
                    'table' => $target,
                    'type'  => $rel['type'] ?? 'hasMany',
                    'key'   => $rel['foreign_key'] ?? $tableName . '_id',
                ];
            }
        }

        // Reverse: find tables that reference this one
        foreach ($allTables as $tbl => $tdata) {
            if ($tbl === $tableName) continue;
            foreach ($tdata['columns'] ?? [] as $col => $def) {
                if (is_array($def) && isset($def['references']) && $def['references'] === $tableName) {
                    $relations[] = ['table' => $tbl, 'type' => 'hasMany', 'key' => $col];
                }
            }
        }

        return $relations;
    }

    private function extractApiForResource(string $content, string $tableName): string
    {
        $data   = json_decode($content, true) ?? [];
        $paths  = $data['spec']['paths'] ?? [];
        $lines  = [];
        $needle = strtolower($tableName);

        foreach ($paths as $path => $methods) {
            if (!str_contains(strtolower($path), rtrim($needle, 's'))) continue;
            foreach ($methods as $method => $op) {
                $summary = $op['summary'] ?? '';
                $lines[] = strtoupper($method) . " {$path}" . ($summary ? ": {$summary}" : '');
            }
        }

        return $lines ? mb_substr(implode("\n", $lines), 0, 2000) : mb_substr($content, 0, 1500);
    }

    private function extractRbacExcerpt(string $content): string
    {
        $data  = json_decode($content, true) ?? [];
        $roles = $data['roles'] ?? [];
        if (empty($roles)) return mb_substr($content, 0, 800);

        $lines = [];
        foreach ($roles as $role) {
            $perms   = implode(', ', array_slice($role['permissions'] ?? [], 0, 6));
            $lines[] = "- **{$role['name']}** (`{$role['key']}`): {$perms}";
        }
        return mb_substr(implode("\n", $lines), 0, 800);
    }

    private function getBackendCodeTool(): array
    {
        return [
            'name'        => 'create_backend_code',
            'description' => 'Laravel 백엔드 코드를 리소스 단위로 생성합니다. Model, Migration, Controller, FormRequest, Policy를 포함합니다.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'resource' => [
                        'type'        => 'string',
                        'description' => 'PascalCase 모델명 (예: User, ProductOrder)',
                    ],
                    'table' => [
                        'type'        => 'string',
                        'description' => 'snake_case 테이블명 (예: users, product_orders)',
                    ],
                    'files' => [
                        'type'  => 'array',
                        'description' => '생성된 파일 목록',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'path'    => ['type' => 'string', 'description' => 'Laravel 표준 상대 경로 (예: app/Models/User.php)'],
                                'content' => ['type' => 'string', 'description' => '파일 전체 내용'],
                                'purpose' => ['type' => 'string', 'description' => '이 파일의 역할'],
                            ],
                            'required' => ['path', 'content', 'purpose'],
                        ],
                    ],
                    'routes' => [
                        'type'  => 'array',
                        'description' => '이 리소스의 API 라우트 목록',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'method'     => ['type' => 'string', 'enum' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']],
                                'uri'        => ['type' => 'string', 'description' => '예: /api/users'],
                                'controller' => ['type' => 'string', 'description' => '예: UserController@index'],
                                'middleware' => [
                                    'type'  => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                            'required' => ['method', 'uri', 'controller'],
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
                    'todo_items' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'type'        => [
                                    'type' => 'string',
                                    'enum' => ['security_check', 'manual_test', 'review_required', 'env_var_needed'],
                                ],
                                'description' => ['type' => 'string'],
                                'file'        => ['type' => 'string'],
                            ],
                            'required' => ['type', 'description'],
                        ],
                    ],
                ],
                'required' => ['resource', 'table', 'files', 'routes'],
            ],
        ];
    }

    private function getDefaultSystemPrompt(): string
    {
        return <<<'PROMPT'
당신은 시니어 Laravel 백엔드 개발자입니다.

주어진 리소스(테이블)에 대해 프로덕션급 Laravel 코드를 생성하세요.

기술 요구사항:
- Laravel 11 (최신)
- Eloquent Model, Migration, Controller, FormRequest, Policy 모두 생성
- API Resource 컨트롤러 (RESTful)
- Form Request로 유효성 검증 분리
- Policy로 권한 검증 (RBAC 매핑)
- 적절한 인덱스, 외래키 (ERD 기준)
- Sanctum 인증

폴더 구조:
- app/Models/{Resource}.php
- app/Http/Controllers/{Resource}Controller.php
- app/Http/Requests/Store{Resource}Request.php
- app/Http/Requests/Update{Resource}Request.php
- app/Policies/{Resource}Policy.php
- database/migrations/{timestamp}_create_{table}_table.php

코드 작성 규칙:
- PSR-12
- 타입 힌트 + 반환 타입 명시
- 강타입 활용 (Cast, Attribute)
- N+1 회피 (with() eager loading)
- 마이그레이션 타임스탬프: 2025_01_01_000000 형태

T37 API 명세에 정의된 엔드포인트만 구현.
T38 RBAC 정책을 Policy 메서드로 정확히 변환.

create_backend_code 도구로 응답하세요.
PROMPT;
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
        string          $tableName,
        array           $context,
    ): void {
        try {
            if ($context['erd_artifact']) {
                $this->traceability->link(
                    $projectId,
                    'artifact', $artifact->id, "BACKEND_CODE#{$artifact->id}",
                    'artifact', $context['erd_artifact']->id, "ERD#{$context['erd_artifact']->id}",
                    'derived_from',
                );
            }
            if ($context['api_artifact']) {
                $this->traceability->link(
                    $projectId,
                    'artifact', $artifact->id, "BACKEND_CODE#{$artifact->id}",
                    'artifact', $context['api_artifact']->id, "API_SPEC#{$context['api_artifact']->id}",
                    'implements',
                );
            }
            if ($context['rbac_artifact']) {
                $this->traceability->link(
                    $projectId,
                    'artifact', $artifact->id, "BACKEND_CODE#{$artifact->id}",
                    'artifact', $context['rbac_artifact']->id, "RBAC_MODEL#{$context['rbac_artifact']->id}",
                    'enforces',
                );
            }
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
        }
    }

    private function loadArtifact(int $projectId, ArtifactType $type): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', $type->value)
            ->latest('id')->first();
    }

    private function tableToModel(string $tableName): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', rtrim($tableName, 's'))));
    }

    private function getLaravelVersion(): string
    {
        return defined('LARAVEL_VERSION') ? LARAVEL_VERSION : app()->version();
    }

    private function extractRelatedTablesFromErd(int $projectId, string $tableName): array
    {
        $erd = $this->loadArtifact($projectId, ArtifactType::ERD);
        if (!$erd) return [];
        $data = json_decode($erd->content, true) ?? [];
        return $this->extractRelatedTables($tableName, $data);
    }
}
