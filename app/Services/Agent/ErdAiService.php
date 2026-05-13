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

class ErdAiService
{
    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
        private readonly TraceabilityService  $traceability,
    ) {}

    /**
     * ERD를 웍스(Tool Use)로 생성하고 산출물을 저장한다.
     *
     * @param  callable(array): void  $onProgress  SSE 진행 콜백
     * @return array{artifact: AiAgentArtifact, tables_count: int, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generate(
        int      $projectId,
        int      $userId,
        callable $onProgress,
    ): array {
        $provider = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $context  = $this->buildContext($projectId);

        $onProgress(['status' => 'CONTEXT_READY', 'progress' => 15,
            'message' => "컨텍스트 로드 완료 (화면 {$context['screen_count']}건, 요구사항 {$context['requirements_count']}건)."]);

        $systemPrompt = $this->prompts->render('dev_prep', 'erd_generation_v1')
            ?? $this->getFallbackSystemPrompt();

        $userContent = $this->buildUserContent($context);

        $onProgress(['status' => 'CALLING_AI', 'progress' => 20,
            'message' => '웍스에 ERD 설계를 요청하고 있습니다. 잠시 기다려 주세요...']);

        $response = $this->usageLog->callAndLog(
            provider:  $provider,
            call:      fn() => $provider->generateWithTools(
                systemPrompt: $systemPrompt,
                messages:     [['role' => 'user', 'content' => $userContent]],
                tools:        [$this->getErdTool()],
                options:      ['max_tokens' => 8000, 'timeout' => 240],
            )->toAIResponse(),
            userId:    $userId,
            projectId: $projectId,
            stage:     'dev_prep',
            taskType:  'erd_generation_v1',
        );

        $toolInput = json_decode($response->text, true) ?? [];

        $onProgress(['status' => 'AI_DONE', 'progress' => 80,
            'message' => '설계 완료. 산출물을 저장하는 중...']);

        // Build normalised ERD document
        $tables        = $this->normalizeTables($toolInput['tables'] ?? []);
        $relationships = $toolInput['relationships'] ?? [];
        $mermaid       = $toolInput['mermaid_diagram'] ?? $this->buildFallbackMermaid($tables);
        $designNotes   = $toolInput['design_notes'] ?? '';

        $relatedReqs     = $this->collectDerivedRefs($tables, 'REQ-');
        $relatedScreens  = $this->collectDerivedRefs($tables, 'SCR-');

        $erdJson = [
            '$metadata' => [
                'version'            => '1.0',
                'generated_at'       => now()->toIso8601String(),
                'based_on'           => [
                    'planning_doc_id'    => $context['planning_doc_id'],
                    'requirements_count' => $context['requirements_count'],
                    'screens_count'      => $context['screen_count'],
                ],
            ],
            'tables'                => $tables,
            'relationships'         => $relationships,
            'mermaid_diagram'       => $mermaid,
            'design_notes'          => $designNotes,
            'related_requirements'  => $relatedReqs,
            'related_screens'       => $relatedScreens,
        ];

        // Resolve or create dev_prep stage
        $stage = $this->resolveDevPrepStage($projectId);

        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $projectId,
            stageId:   $stage->id,
            type:      ArtifactType::ERD,
            scopeType: 'project',
            scopeId:   $projectId,
            title:     'ERD (데이터 모델)',
            content:   json_encode($erdJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:    $userId,
            meta: [
                'change_type'   => 'ai_generated',
                'model'         => $response->model,
                'tokens_in'     => $response->inputTokens,
                'tokens_out'    => $response->outputTokens,
                'tables_count'  => count($tables),
                'generated_at'  => now()->toIso8601String(),
            ],
        );

        $this->createTraceabilityLinks($projectId, $artifact, $context);

        $onProgress(['status' => 'SAVED', 'progress' => 100,
            'message' => '저장 완료.']);

        return [
            'artifact'     => $artifact,
            'tables_count' => count($tables),
            'tokens_in'    => $response->inputTokens,
            'tokens_out'   => $response->outputTokens,
            'cost'         => $this->usageLog->calculateCost($response->model, $response->inputTokens, $response->outputTokens),
            'model'        => $response->model,
        ];
    }

    // ── Context ───────────────────────────────────────────────────────────────

    public function buildContext(int $projectId): array
    {
        $planningDoc = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::PLANNING_DOC->value)
            ->latest('id')->first();

        $requirements = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::TO_BE_REQUIREMENTS->value)
            ->latest('id')->first();

        $screens      = AiAgentScreen::where('project_id', $projectId)->active()->get();
        $reqCount     = $this->countRequirements($requirements?->content ?? '');

        return [
            'planning_doc_id'      => $planningDoc?->id,
            'planning_doc_content' => $this->truncate($planningDoc?->content ?? '', 8000),
            'requirements_content' => $this->truncate($requirements?->content ?? '', 4000),
            'requirements_count'   => $reqCount,
            'screens'              => $screens,
            'screen_count'         => $screens->count(),
        ];
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildUserContent(array $context): string
    {
        $parts = [];

        if ($context['planning_doc_content']) {
            $parts[] = "# 기획서 내용\n\n{$context['planning_doc_content']}";
        }

        if ($context['requirements_content']) {
            $parts[] = "# TO-BE 요구사항\n\n{$context['requirements_content']}";
        }

        if ($context['screens']->isNotEmpty()) {
            $lines = $context['screens']->map(fn($s) => "- {$s->screen_id}: {$s->title}" . ($s->description ? " ({$s->description})" : ''))->join("\n");
            $parts[] = "# 화면 목록\n\n{$lines}";
        }

        return implode("\n\n---\n\n", $parts)
            ?: '프로젝트 기획 정보가 없습니다. 기본적인 데이터 모델을 설계해주세요.';
    }

    private function normalizeTables(array $tables): array
    {
        $result = [];
        foreach ($tables as $table) {
            $name = $table['name'] ?? '';
            if (!$name) continue;

            $result[$name] = [
                'name'         => $name,
                'description'  => $table['description'] ?? '',
                'columns'      => $table['columns'] ?? [],
                'indexes'      => $table['indexes'] ?? [],
                'foreign_keys' => $table['foreign_keys'] ?? [],
                'derived_from' => $table['derived_from'] ?? [],
            ];
        }
        return $result;
    }

    private function collectDerivedRefs(array $tables, string $prefix): array
    {
        $refs = [];
        foreach ($tables as $table) {
            foreach ($table['derived_from'] ?? [] as $ref) {
                if (str_starts_with($ref, $prefix)) {
                    $refs[] = $ref;
                }
            }
        }
        return array_values(array_unique($refs));
    }

    private function buildFallbackMermaid(array $tables): string
    {
        if (empty($tables)) return 'erDiagram';
        $lines = ['erDiagram'];
        foreach ($tables as $name => $table) {
            $lines[] = "    {$name} {";
            foreach (array_slice($table['columns'] ?? [], 0, 6) as $col) {
                $type = strtolower(explode(' ', $col['type'] ?? 'varchar')[0]);
                $name2 = $col['name'] ?? '';
                $pk   = !empty($col['primary_key']) ? ' PK' : '';
                $uk   = !empty($col['unique'])      ? ' UK' : '';
                $lines[] = "        {$type} {$name2}{$pk}{$uk}";
            }
            $lines[] = '    }';
        }
        return implode("\n", $lines);
    }

    private function countRequirements(string $content): int
    {
        preg_match_all('/REQ-\d+/i', $content, $m);
        return count(array_unique($m[0] ?? []));
    }

    private function truncate(string $text, int $maxChars): string
    {
        return mb_strlen($text) > $maxChars ? mb_substr($text, 0, $maxChars) . "\n...(이하 생략)" : $text;
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

    private function createTraceabilityLinks(int $projectId, AiAgentArtifact $erd, array $context): void
    {
        try {
            // Link ERD → planning doc
            if ($context['planning_doc_id']) {
                $this->traceability->link(
                    projectId: $projectId,
                    sourceType: 'artifact', sourceId: $erd->id,    sourceRef: "ERD#{$erd->id}",
                    targetType: 'artifact', targetId: $context['planning_doc_id'],
                    targetRef: "PLANNING_DOC#{$context['planning_doc_id']}",
                    linkType: 'derived_from',
                );
            }

            // Link ERD → screens
            foreach ($context['screens'] as $screen) {
                $this->traceability->link(
                    projectId: $projectId,
                    sourceType: 'artifact', sourceId: $erd->id, sourceRef: "ERD#{$erd->id}",
                    targetType: 'screen',   targetId: $screen->id, targetRef: $screen->screen_id,
                    linkType: 'documents',
                );
            }
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
        }
    }

    private function getErdTool(): array
    {
        return [
            'name'        => 'create_data_model',
            'description' => 'ERD(데이터 모델)를 구조화된 형태로 기록합니다.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'tables' => [
                        'type'  => 'array',
                        'description' => '데이터베이스 테이블 목록',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'name'        => ['type' => 'string', 'description' => '테이블명 (snake_case, 영문)'],
                                'description' => ['type' => 'string', 'description' => '테이블 설명 (한국어 가능)'],
                                'columns'     => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'name'           => ['type' => 'string'],
                                            'type'           => ['type' => 'string', 'description' => 'VARCHAR(255), BIGINT UNSIGNED 등'],
                                            'nullable'       => ['type' => 'boolean'],
                                            'primary_key'    => ['type' => 'boolean'],
                                            'auto_increment' => ['type' => 'boolean'],
                                            'unique'         => ['type' => 'boolean'],
                                            'default'        => ['type' => 'string'],
                                            'comment'        => ['type' => 'string'],
                                        ],
                                        'required' => ['name', 'type'],
                                    ],
                                ],
                                'indexes' => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'name'    => ['type' => 'string'],
                                            'columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                                            'unique'  => ['type' => 'boolean'],
                                        ],
                                    ],
                                ],
                                'foreign_keys' => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'column'     => ['type' => 'string'],
                                            'references' => [
                                                'type'       => 'object',
                                                'properties' => [
                                                    'table'  => ['type' => 'string'],
                                                    'column' => ['type' => 'string'],
                                                ],
                                            ],
                                            'on_delete'  => ['type' => 'string', 'enum' => ['CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION']],
                                        ],
                                    ],
                                ],
                                'derived_from' => [
                                    'type'  => 'array',
                                    'items' => ['type' => 'string'],
                                    'description' => '출처 ID 목록. 예: ["REQ-001", "SCR-003"]',
                                ],
                            ],
                            'required' => ['name', 'columns'],
                        ],
                    ],
                    'relationships' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'from'  => ['type' => 'string', 'description' => '예: orders.user_id'],
                                'to'    => ['type' => 'string', 'description' => '예: users.id'],
                                'type'  => ['type' => 'string', 'enum' => ['one_to_one', 'one_to_many', 'many_to_one', 'many_to_many']],
                                'label' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'mermaid_diagram' => [
                        'type'        => 'string',
                        'description' => 'Mermaid erDiagram 문법으로 작성된 다이어그램 (erDiagram 키워드 포함)',
                    ],
                    'design_notes' => [
                        'type'        => 'string',
                        'description' => '설계 결정사항, 정규화 수준, 의도적 비정규화 이유 등',
                    ],
                ],
                'required' => ['tables', 'relationships', 'mermaid_diagram'],
            ],
        ];
    }

    private function getFallbackSystemPrompt(): string
    {
        return <<<'PROMPT'
당신은 시니어 데이터베이스 아키텍트입니다.

주어진 프로젝트의 기획서, 요구사항, 화면 정보를 분석하여 정규화된 데이터 모델(ERD)을 설계해주세요.

설계 원칙:
- 3NF 이상 정규화 (필요한 경우 의도적 비정규화 명시)
- 명확한 명명 규칙 (snake_case, 영문)
- 모든 테이블에 id (BIGINT UNSIGNED PK AUTO_INCREMENT), created_at, updated_at 포함
- 적절한 인덱스 (검색 자주 발생하는 컬럼)
- 외래키 제약 명시 (ON DELETE 정책 포함)
- BIGINT UNSIGNED PK 사용 (Laravel 호환)

각 테이블의 derived_from 필드에 어느 요구사항(REQ-XXX) 또는 화면(SCR-XXX)에서 도출됐는지 기록하세요.

create_data_model 도구를 사용해 응답하세요.
PROMPT;
    }
}
