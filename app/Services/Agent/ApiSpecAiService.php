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

class ApiSpecAiService
{
    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
        private readonly TraceabilityService  $traceability,
    ) {}

    /**
     * @param  callable(array): void  $onProgress
     * @return array{artifact: AiAgentArtifact, endpoints_count: int, schemas_count: int, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generate(int $projectId, int $userId, callable $onProgress): array
    {
        $provider = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $context  = $this->buildContext($projectId);

        $onProgress(['status' => 'CONTEXT_READY', 'progress' => 15,
            'message' => "컨텍스트 로드 완료 (화면 {$context['screen_count']}건, 요구사항 {$context['requirements_count']}건)."]);

        $systemPrompt = $this->prompts->render('dev_prep', 'api_spec_generation_v1')
            ?? $this->getFallbackSystemPrompt();

        $userContent = $this->buildUserContent($context);

        $onProgress(['status' => 'CALLING_AI', 'progress' => 20,
            'message' => '웍스에 API 명세서 설계를 요청하고 있습니다. 잠시 기다려 주세요...']);

        $response = $this->usageLog->callAndLog(
            provider:  $provider,
            call:      fn() => $provider->generateWithTools(
                systemPrompt: $systemPrompt,
                messages:     [['role' => 'user', 'content' => $userContent]],
                tools:        [$this->getApiSpecTool()],
                options:      ['max_tokens' => 8000, 'timeout' => 240],
            )->toAIResponse(),
            userId:    $userId,
            projectId: $projectId,
            stage:     'dev_prep',
            taskType:  'api_spec_generation_v1',
        );

        $toolInput = json_decode($response->text, true) ?? [];

        $onProgress(['status' => 'AI_DONE', 'progress' => 80,
            'message' => '설계 완료. 산출물을 저장하는 중...']);

        $openApiSpec    = $this->buildOpenApiSpec($toolInput);
        $relatedReqs    = $this->collectDerivedRefs($toolInput['paths'] ?? [], 'REQ-');
        $relatedScreens = $this->collectDerivedRefs($toolInput['paths'] ?? [], 'SCR-');
        $endpointsCount = count($toolInput['paths'] ?? []);
        $schemasCount   = count($toolInput['schemas'] ?? []);

        $artifactContent = [
            '$metadata' => [
                'version'      => '1.0',
                'generated_at' => now()->toIso8601String(),
                'based_on'     => [
                    'erd_artifact_id'    => $context['erd_artifact_id'],
                    'requirements_count' => $context['requirements_count'],
                    'screens_count'      => $context['screen_count'],
                ],
            ],
            'spec'                 => $openApiSpec,
            'design_notes'         => $toolInput['design_notes'] ?? '',
            'related_requirements' => $relatedReqs,
            'related_screens'      => $relatedScreens,
        ];

        $stage    = $this->resolveDevPrepStage($projectId);
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $projectId,
            stageId:   $stage->id,
            type:      ArtifactType::API_SPEC,
            scopeType: 'project',
            scopeId:   $projectId,
            title:     'API 명세서 (OpenAPI 3.0)',
            content:   json_encode($artifactContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:    $userId,
            meta: [
                'change_type'     => 'ai_generated',
                'model'           => $response->model,
                'tokens_in'       => $response->inputTokens,
                'tokens_out'      => $response->outputTokens,
                'endpoints_count' => $endpointsCount,
                'schemas_count'   => $schemasCount,
                'generated_at'    => now()->toIso8601String(),
            ],
        );

        $this->createTraceabilityLinks($projectId, $artifact, $context);

        $onProgress(['status' => 'SAVED', 'progress' => 100, 'message' => '저장 완료.']);

        return [
            'artifact'        => $artifact,
            'endpoints_count' => $endpointsCount,
            'schemas_count'   => $schemasCount,
            'tokens_in'       => $response->inputTokens,
            'tokens_out'      => $response->outputTokens,
            'cost'            => $this->usageLog->calculateCost($response->model, $response->inputTokens, $response->outputTokens),
            'model'           => $response->model,
        ];
    }

    // ── Context ───────────────────────────────────────────────────────────────

    public function buildContext(int $projectId): array
    {
        $erdArtifact  = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::ERD->value)
            ->latest('id')->first();

        $requirements = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::TO_BE_REQUIREMENTS->value)
            ->latest('id')->first();

        $planningDoc  = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::PLANNING_DOC->value)
            ->latest('id')->first();

        $screens  = AiAgentScreen::where('project_id', $projectId)->active()->get();
        $reqCount = $this->countRequirements($requirements?->content ?? '');

        return [
            'erd_artifact_id'      => $erdArtifact?->id,
            'erd_content'          => $this->extractErdSummary($erdArtifact?->content ?? ''),
            'planning_doc_content' => $this->truncate($planningDoc?->content ?? '', 4000),
            'requirements_content' => $this->truncate($requirements?->content ?? '', 3000),
            'requirements_count'   => $reqCount,
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

        if ($context['planning_doc_content']) {
            $parts[] = "# 기획서 내용\n\n{$context['planning_doc_content']}";
        }

        if ($context['requirements_content']) {
            $parts[] = "# TO-BE 요구사항\n\n{$context['requirements_content']}";
        }

        if ($context['screens']->isNotEmpty()) {
            $lines   = $context['screens']->map(
                fn($s) => "- {$s->screen_id}: {$s->title}" . ($s->description ? " ({$s->description})" : '')
            )->join("\n");
            $parts[] = "# 화면 목록\n\n{$lines}";
        }

        return implode("\n\n---\n\n", $parts)
            ?: '프로젝트 기획 정보가 없습니다. 기본적인 REST API 명세를 설계해주세요.';
    }

    private function buildOpenApiSpec(array $toolInput): array
    {
        $info    = $toolInput['info'] ?? ['title' => 'API', 'version' => '1.0.0'];
        $secType = $toolInput['security_type'] ?? 'bearer';

        // --- paths ---
        $paths = [];
        foreach ($toolInput['paths'] ?? [] as $item) {
            $path   = $item['path']   ?? '';
            $method = strtolower($item['method'] ?? 'get');
            if (!$path || !$method) continue;

            $operation = [
                'summary'     => $item['summary']      ?? '',
                'operationId' => $item['operation_id'] ?? '',
                'tags'        => $item['tags']          ?? [],
            ];

            if ($item['description'] ?? '') {
                $operation['description'] = $item['description'];
            }

            $params = [];
            foreach ($item['parameters'] ?? [] as $p) {
                $params[] = [
                    'name'        => $p['name']        ?? '',
                    'in'          => $p['in']          ?? 'query',
                    'required'    => $p['required']    ?? false,
                    'description' => $p['description'] ?? '',
                    'schema'      => ['type' => $p['schema_type'] ?? 'string'],
                ];
            }
            if ($params) $operation['parameters'] = $params;

            if ($ref = ($item['request_body_ref'] ?? '')) {
                $operation['requestBody'] = [
                    'required' => true,
                    'content'  => ['application/json' => ['schema' => ['$ref' => "#/components/schemas/{$ref}"]]],
                ];
            }

            $responses = [];
            if ($ref = ($item['response_200_ref'] ?? '')) {
                $responses['200'] = [
                    'description' => 'Success',
                    'content'     => ['application/json' => ['schema' => ['$ref' => "#/components/schemas/{$ref}"]]],
                ];
            }
            if ($ref = ($item['response_201_ref'] ?? '')) {
                $responses['201'] = [
                    'description' => 'Created',
                    'content'     => ['application/json' => ['schema' => ['$ref' => "#/components/schemas/{$ref}"]]],
                ];
            }
            if (empty($responses)) {
                $responses['200'] = ['description' => 'Success'];
            }
            $responses['400'] = ['description' => 'Bad Request'];
            $responses['401'] = ['description' => 'Unauthorized'];
            $operation['responses'] = $responses;

            if ($item['requires_auth'] ?? false) {
                $operation['security'] = [['bearerAuth' => []]];
            }

            $paths[$path][$method] = $operation;
        }

        // --- schemas ---
        $schemas = [];
        foreach ($toolInput['schemas'] ?? [] as $schema) {
            $name = $schema['name'] ?? '';
            if (!$name) continue;

            $properties = [];
            foreach ($schema['properties'] ?? [] as $prop) {
                $pName = $prop['name'] ?? '';
                if (!$pName) continue;
                $pDef = ['type' => $prop['type'] ?? 'string'];
                if ($prop['format']      ?? '') $pDef['format']      = $prop['format'];
                if ($prop['nullable']    ?? false) $pDef['nullable'] = true;
                if ($prop['description'] ?? '') $pDef['description'] = $prop['description'];
                if ($prop['example']     ?? '') $pDef['example']     = $prop['example'];
                $properties[$pName] = $pDef;
            }

            $schemaDef = ['type' => 'object', 'properties' => $properties];
            if ($schema['description']   ?? '') $schemaDef['description'] = $schema['description'];
            if ($schema['required_fields'] ?? []) $schemaDef['required']  = $schema['required_fields'];
            $schemas[$name] = $schemaDef;
        }

        // --- security schemes ---
        $securitySchemes = [];
        if ($secType === 'bearer') {
            $securitySchemes['bearerAuth'] = ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT'];
        } elseif ($secType === 'api_key') {
            $securitySchemes['apiKeyAuth'] = ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-API-Key'];
        }

        // --- servers ---
        $servers = [];
        foreach ($toolInput['servers'] ?? [] as $s) {
            $servers[] = ['url' => $s['url'] ?? '/api', 'description' => $s['description'] ?? ''];
        }
        if (empty($servers)) {
            $servers = [['url' => '/api/v1', 'description' => '개발 서버']];
        }

        $spec = [
            'openapi'    => '3.0.3',
            'info'       => [
                'title'       => $info['title']       ?? 'API',
                'version'     => $info['version']     ?? '1.0.0',
                'description' => $info['description'] ?? '',
            ],
            'servers'    => $servers,
            'paths'      => $paths,
            'components' => [
                'schemas'         => $schemas,
                'securitySchemes' => $securitySchemes,
            ],
        ];

        if ($toolInput['tags'] ?? []) {
            $spec['tags'] = $toolInput['tags'];
        }

        return $spec;
    }

    private function extractErdSummary(string $content): string
    {
        if (!$content) return '';
        $data = json_decode($content, true);
        if (!$data) return $this->truncate($content, 3000);

        $tables = $data['tables'] ?? [];
        if (empty($tables)) return '';

        $lines = ['## 테이블 목록 (ERD)'];
        foreach ($tables as $name => $table) {
            $desc     = $table['description'] ?? '';
            $colNames = array_column($table['columns'] ?? [], 'name');
            $cols     = implode(', ', array_slice($colNames, 0, 8));
            if (count($colNames) > 8) $cols .= ', ...';
            $lines[] = "- **{$name}**" . ($desc ? " ({$desc})" : '') . ": {$cols}";
        }
        return implode("\n", $lines);
    }

    private function collectDerivedRefs(array $paths, string $prefix): array
    {
        $refs = [];
        foreach ($paths as $path) {
            foreach ($path['derived_from'] ?? [] as $ref) {
                if (str_starts_with($ref, $prefix)) $refs[] = $ref;
            }
        }
        return array_values(array_unique($refs));
    }

    private function countRequirements(string $content): int
    {
        preg_match_all('/REQ-\d+/i', $content, $m);
        return count(array_unique($m[0] ?? []));
    }

    private function truncate(string $text, int $maxChars): string
    {
        return mb_strlen($text) > $maxChars
            ? mb_substr($text, 0, $maxChars) . "\n...(이하 생략)"
            : $text;
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
                    sourceRef:  "API_SPEC#{$artifact->id}",
                    targetType: 'artifact', targetId: $context['erd_artifact_id'],
                    targetRef:  "ERD#{$context['erd_artifact_id']}",
                    linkType:   'derived_from',
                );
            }

            foreach ($context['screens'] as $screen) {
                $this->traceability->link(
                    projectId:  $projectId,
                    sourceType: 'artifact', sourceId: $artifact->id,
                    sourceRef:  "API_SPEC#{$artifact->id}",
                    targetType: 'screen',   targetId: $screen->id,
                    targetRef:  $screen->screen_id,
                    linkType:   'documents',
                );
            }
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
        }
    }

    private function getApiSpecTool(): array
    {
        return [
            'name'         => 'create_api_spec',
            'description'  => 'RESTful API 명세서를 OpenAPI 3.0 구조로 기록합니다.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'info' => [
                        'type'       => 'object',
                        'properties' => [
                            'title'       => ['type' => 'string', 'description' => 'API 이름'],
                            'version'     => ['type' => 'string', 'description' => '예: 1.0.0'],
                            'description' => ['type' => 'string'],
                        ],
                        'required' => ['title', 'version'],
                    ],
                    'servers' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'url'         => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'tags' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'name'        => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'paths' => [
                        'type'        => 'array',
                        'description' => 'API 엔드포인트 목록',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'path'             => ['type' => 'string', 'description' => '예: /api/v1/users/{id}'],
                                'method'           => ['type' => 'string', 'enum' => ['get', 'post', 'put', 'patch', 'delete']],
                                'summary'          => ['type' => 'string'],
                                'description'      => ['type' => 'string'],
                                'operation_id'     => ['type' => 'string', 'description' => 'camelCase. 예: getUserById'],
                                'tags'             => ['type' => 'array', 'items' => ['type' => 'string']],
                                'parameters'       => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'name'        => ['type' => 'string'],
                                            'in'          => ['type' => 'string', 'enum' => ['query', 'path', 'header', 'cookie']],
                                            'required'    => ['type' => 'boolean'],
                                            'schema_type' => ['type' => 'string', 'description' => '예: string, integer, boolean'],
                                            'description' => ['type' => 'string'],
                                        ],
                                        'required' => ['name', 'in'],
                                    ],
                                ],
                                'request_body_ref' => ['type' => 'string', 'description' => 'components/schemas 참조명. 예: CreateUserRequest'],
                                'response_200_ref' => ['type' => 'string', 'description' => '200 응답 스키마 참조명'],
                                'response_201_ref' => ['type' => 'string', 'description' => '201 응답 스키마 참조명'],
                                'requires_auth'    => ['type' => 'boolean', 'description' => '인증 필요 여부'],
                                'derived_from'     => [
                                    'type'        => 'array',
                                    'items'       => ['type' => 'string'],
                                    'description' => '출처 ID 목록. 예: ["REQ-001", "SCR-003"]',
                                ],
                            ],
                            'required' => ['path', 'method', 'summary', 'operation_id'],
                        ],
                    ],
                    'schemas' => [
                        'type'        => 'array',
                        'description' => 'Request/Response 스키마 정의',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'name'            => ['type' => 'string', 'description' => 'PascalCase. 예: UserResponse'],
                                'description'     => ['type' => 'string'],
                                'properties'      => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'name'        => ['type' => 'string'],
                                            'type'        => ['type' => 'string', 'description' => 'string, integer, boolean, array, object'],
                                            'format'      => ['type' => 'string', 'description' => 'date-time, email, uuid 등'],
                                            'nullable'    => ['type' => 'boolean'],
                                            'description' => ['type' => 'string'],
                                            'example'     => ['type' => 'string'],
                                        ],
                                        'required' => ['name', 'type'],
                                    ],
                                ],
                                'required_fields' => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                            'required' => ['name'],
                        ],
                    ],
                    'security_type' => [
                        'type'        => 'string',
                        'enum'        => ['bearer', 'api_key', 'basic', 'none'],
                        'description' => '인증 방식 (bearer = JWT Bearer Token)',
                    ],
                    'design_notes' => [
                        'type'        => 'string',
                        'description' => 'API 설계 원칙, 버전 관리 전략, 인증 방식, 주의사항 등',
                    ],
                ],
                'required' => ['info', 'paths', 'schemas'],
            ],
        ];
    }

    private function getFallbackSystemPrompt(): string
    {
        return <<<'PROMPT'
당신은 시니어 백엔드 아키텍트입니다.

주어진 프로젝트의 ERD, 기획서, 요구사항, 화면 정보를 분석하여 RESTful API 명세서(OpenAPI 3.0)를 설계해주세요.

설계 원칙:
- RESTful 설계 원칙 준수 (명사 기반 리소스, HTTP 메서드 의미에 맞게 사용)
- URL 패턴: /api/v1/{resource} 및 /api/v1/{resource}/{id}
- 표준 CRUD 패턴: 목록(GET), 상세(GET /{id}), 생성(POST), 수정(PUT /{id}), 삭제(DELETE /{id})
- 응답 형식: { "data": ..., "message": "...", "status": 200 }
- 페이지네이션: GET 목록에 ?page=1&per_page=20 파라미터
- 검색/필터: ?q=keyword, ?status=active 등
- 인증: Bearer Token (JWT) — 인증 필요한 엔드포인트에 requires_auth: true
- 각 엔드포인트 derived_from에 요구사항(REQ-XXX) 또는 화면(SCR-XXX) 출처 기록

스키마 정의 원칙:
- Request 스키마: CreateXxxRequest, UpdateXxxRequest
- Response 스키마: XxxResponse, XxxListResponse, PaginatedXxxResponse
- 공통: ErrorResponse, SuccessResponse

create_api_spec 도구를 사용해 응답하세요.
PROMPT;
    }
}
