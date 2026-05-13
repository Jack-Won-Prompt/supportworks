<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\AiSetting;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentGap;
use App\Models\Agent\AiAgentRequirement;
use Illuminate\Support\Facades\DB;

class GapAnalysisAiService
{
    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
        private readonly TraceabilityService  $traceability,
    ) {}

    /**
     * @return array{result: array, tokensIn: int, tokensOut: int, costUsd: float, model: string}
     */
    public function analyzeWithStats(AiAgentArtifact $artifact, int $userId): array
    {
        $projectId = $artifact->project_id;

        $messages     = $this->buildMessages($projectId);
        $systemPrompt = $this->buildSystemPrompt($projectId);
        $tools        = [$this->getAnalysisTool()];

        $apiKey   = AiSetting::current()->anthropicKey();
        $provider = new AnthropicProvider($apiKey);

        $toolResponse = $this->usageLog->callAndLog(
            provider:   $provider,
            call:       fn() => $provider->generateWithTools(
                systemPrompt: $systemPrompt,
                messages:     $messages,
                tools:        $tools,
                options:      ['max_tokens' => 8000],
            )->toAIResponse(),
            userId:     $userId,
            projectId:  $projectId,
            artifactId: $artifact->id,
            stage:      'planning',
            taskType:   'gap_analysis',
        );

        $result = json_decode($toolResponse->text, true) ?? [];

        $this->persistResult($artifact, $result, $toolResponse, $userId);

        return [
            'result'    => $result,
            'tokensIn'  => $toolResponse->inputTokens,
            'tokensOut' => $toolResponse->outputTokens,
            'costUsd'   => $this->usageLog->calculateCost($toolResponse->model, $toolResponse->inputTokens, $toolResponse->outputTokens),
            'model'     => $toolResponse->model,
        ];
    }

    /**
     * Build text-only messages from AS-IS + TO-BE artifacts.
     */
    private function buildMessages(int $projectId): array
    {
        $asIsArtifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::AS_IS_ANALYSIS->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();

        $toBeArtifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::TO_BE_REQUIREMENTS->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();

        $asIsContent = $asIsArtifact ? (json_decode($asIsArtifact->content ?? '{}', true) ?? []) : [];
        $toBeContent = $toBeArtifact ? (json_decode($toBeArtifact->content ?? '{}', true) ?? []) : [];

        $requirements = AiAgentRequirement::where('project_id', $projectId)
            ->orderBy('req_id')
            ->get();

        $text  = "# AS-IS 분석 결과\n\n";
        $text .= "## 현황 요약\n{$this->safe($asIsContent['summary'] ?? '(없음)')}\n\n";

        $issues = $asIsContent['issues'] ?? [];
        if ($issues) {
            $text .= "## 주요 이슈 (" . count($issues) . "건)\n";
            foreach ($issues as $i => $issue) {
                $severity = strtoupper($issue['severity'] ?? 'medium');
                $text .= "### 이슈 {$i}: [{$severity}][{$issue['category']}] {$issue['title']}\n";
                $text .= "{$issue['description']}\n\n";
            }
        }

        $text .= "\n---\n\n# TO-BE 요구사항\n\n";
        $text .= "## 개요\n{$this->safe($toBeContent['overview'] ?? '(없음)')}\n\n";

        if ($requirements->isNotEmpty()) {
            $text .= "## 요구사항 목록 ({$requirements->count()}건)\n";
            foreach ($requirements as $req) {
                $priority = strtoupper($req->priority->value ?? $req->priority ?? 'should');
                $text .= "### {$req->req_id} [{$priority}][{$req->category}] {$req->title}\n";
                if ($req->description) {
                    $text .= "{$req->description}\n";
                }
                if ($req->rationale) {
                    $text .= "도출 근거: {$req->rationale}\n";
                }
                $text .= "\n";
            }
        }

        return [['role' => 'user', 'content' => $text]];
    }

    private function buildSystemPrompt(int $projectId): string
    {
        $asIsArtifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::AS_IS_ANALYSIS->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();

        $issueCount = 0;
        if ($asIsArtifact) {
            $content    = json_decode($asIsArtifact->content ?? '{}', true) ?? [];
            $issueCount = count($content['issues'] ?? []);
        }

        $reqCount = AiAgentRequirement::where('project_id', $projectId)->count();

        $rendered = $this->prompts->render('planning', 'gap_analysis', [
            'as_is_summary' => "AS-IS 이슈 {$issueCount}건",
            'issue_count'   => $issueCount,
            'req_count'     => $reqCount,
        ]);

        return $rendered ?? <<<PROMPT
당신은 시스템 개선 전략 분석 전문가입니다.
AS-IS 분석 결과와 TO-BE 요구사항을 비교하여 Gap을 분석하고,
record_gap_analysis 도구를 사용하여 구조화된 형태로 반환해주세요.
PROMPT;
    }

    private function getAnalysisTool(): array
    {
        return [
            'name'         => 'record_gap_analysis',
            'description'  => 'AS-IS와 TO-BE를 비교한 Gap 분석 결과를 기록합니다.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'executive_summary' => [
                        'type'        => 'string',
                        'description' => 'Gap 분석 종합 요약 (2~3문단)',
                    ],
                    'gaps' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'title'                  => ['type' => 'string'],
                                'current_state'          => ['type' => 'string'],
                                'target_state'           => ['type' => 'string'],
                                'category'               => ['type' => 'string', 'enum' => ['보안', '기능', 'UX', '성능', '데이터', '인프라', '기타']],
                                'severity'               => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                                'estimated_effort'       => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                                'related_requirement_ids'=> ['type' => 'array', 'items' => ['type' => 'string'], 'description' => '예: ["REQ-001", "REQ-003"]'],
                                'related_issue_indices'  => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'AS-IS 이슈 배열 인덱스'],
                                'recommended_actions'    => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                            'required' => ['title', 'current_state', 'target_state', 'category', 'severity'],
                        ],
                    ],
                    'improvement_opportunities' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'title'           => ['type' => 'string'],
                                'description'     => ['type' => 'string'],
                                'expected_benefit'=> ['type' => 'string'],
                            ],
                        ],
                    ],
                    'risks' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'title'       => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                                'likelihood'  => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                                'impact'      => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                                'mitigation'  => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'recommendations' => [
                        'type'       => 'object',
                        'properties' => [
                            'priority_actions'  => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => '가장 우선해야 할 3~5개 액션'],
                            'phasing_strategy'  => ['type' => 'string'],
                        ],
                    ],
                ],
                'required' => ['executive_summary', 'gaps', 'risks', 'recommendations'],
            ],
        ];
    }

    private function persistResult(
        AiAgentArtifact $artifact,
        array           $result,
        object          $toolResponse,
        int             $userId
    ): void {
        $projectId = $artifact->project_id;

        $asIsArtifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::AS_IS_ANALYSIS->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();

        DB::transaction(function () use ($artifact, $result, $toolResponse, $userId, $projectId, $asIsArtifact) {
            // Delete previous gaps for this artifact (reanalysis policy)
            AiAgentGap::where('project_id', $projectId)
                ->where('artifact_id', $artifact->id)
                ->delete();

            foreach ($result['gaps'] ?? [] as $aiGap) {
                $gapId = AiAgentGap::nextGapId($projectId);

                $gap = AiAgentGap::create([
                    'gap_id'                  => $gapId,
                    'project_id'              => $projectId,
                    'artifact_id'             => $artifact->id,
                    'title'                   => $aiGap['title'],
                    'current_state'           => $aiGap['current_state'] ?? null,
                    'target_state'            => $aiGap['target_state'] ?? null,
                    'category'                => $aiGap['category'] ?? '기타',
                    'severity'                => $aiGap['severity'] ?? 'medium',
                    'estimated_effort'        => $aiGap['estimated_effort'] ?? null,
                    'recommended_actions'     => $aiGap['recommended_actions'] ?? null,
                    'related_requirement_ids' => $aiGap['related_requirement_ids'] ?? null,
                    'source'                  => 'ai',
                    'created_by'              => $userId,
                ]);

                // Gap ← artifact (identified_in)
                $this->traceability->link(
                    projectId:  $projectId,
                    sourceType: 'artifact',
                    sourceId:   $artifact->id,
                    sourceRef:  "GAP-ANALYSIS#{$artifact->id}",
                    targetType: 'gap',
                    targetId:   $gap->id,
                    targetRef:  $gapId,
                    linkType:   'identified_in',
                );

                // Gap → requirements (addresses)
                foreach ($aiGap['related_requirement_ids'] ?? [] as $reqId) {
                    $req = AiAgentRequirement::where('project_id', $projectId)
                        ->where('req_id', $reqId)
                        ->first();
                    if ($req) {
                        $this->traceability->link(
                            projectId:  $projectId,
                            sourceType: 'gap',
                            sourceId:   $gap->id,
                            sourceRef:  $gapId,
                            targetType: 'requirement',
                            targetId:   $req->id,
                            targetRef:  $reqId,
                            linkType:   'addresses',
                        );
                    }
                }

                // Gap → AS-IS artifact (derived_from, when related issues exist)
                if ($asIsArtifact && !empty($aiGap['related_issue_indices'])) {
                    $this->traceability->link(
                        projectId:  $projectId,
                        sourceType: 'gap',
                        sourceId:   $gap->id,
                        sourceRef:  $gapId,
                        targetType: 'artifact',
                        targetId:   $asIsArtifact->id,
                        targetRef:  "AS-IS#{$asIsArtifact->id}",
                        linkType:   'derived_from',
                    );
                }
            }

            $meta = [
                'change_type' => 'ai_generated',
                'model'       => $toolResponse->model,
                'tokens_in'   => $toolResponse->inputTokens,
                'tokens_out'  => $toolResponse->outputTokens,
                'analyzed_at' => now()->toIso8601String(),
                'gap_count'   => count($result['gaps'] ?? []),
                'risk_count'  => count($result['risks'] ?? []),
            ];

            $artifact->updateWithVersion(
                content: json_encode([
                    'executive_summary'         => $result['executive_summary'] ?? '',
                    'improvement_opportunities' => $result['improvement_opportunities'] ?? [],
                    'risks'                     => $result['risks'] ?? [],
                    'recommendations'           => $result['recommendations'] ?? [],
                ], JSON_UNESCAPED_UNICODE),
                userId:  $userId,
                meta:    $meta,
            );
        });
    }

    private function safe(string $text): string
    {
        return trim($text);
    }
}
