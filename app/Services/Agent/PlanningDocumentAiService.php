<?php

namespace App\Services\Agent;

use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;
use App\Models\AiSetting;
use App\Models\SystemErrorLog;
use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact as Artifact;

class PlanningDocumentAiService
{
    /** 웍스 생성 섹션 정의 — key: task_type suffix, title: 진행 표시용 */
    public const AI_SECTIONS = [
        'section_1_3_objectives' => [
            'title'       => '1.3 프로젝트 목표',
            'task_type'   => 'planning_doc_section_1_3_objectives',
            'max_tokens'  => 700,
        ],
        'section_5_1_priority_actions' => [
            'title'      => '5.1 우선순위 액션',
            'task_type'  => 'planning_doc_section_5_1_priority_actions',
            'max_tokens' => 900,
        ],
        'section_5_2_phasing_strategy' => [
            'title'      => '5.2 단계적 접근 방안',
            'task_type'  => 'planning_doc_section_5_2_phasing_strategy',
            'max_tokens' => 700,
        ],
        'section_5_3_csf' => [
            'title'      => '5.3 핵심 성공 요인',
            'task_type'  => 'planning_doc_section_5_3_csf',
            'max_tokens' => 700,
        ],
        'section_5_4_risk_strategy' => [
            'title'      => '5.4 리스크 대응 전략',
            'task_type'  => 'planning_doc_section_5_4_risk_strategy',
            'max_tokens' => 800,
        ],
        'section_8_1_glossary' => [
            'title'      => '8.1 용어 정의',
            'task_type'  => 'planning_doc_section_8_1_glossary',
            'max_tokens' => 900,
        ],
    ];

    private const SCREEN_TASK_TYPE = 'planning_doc_screen_detail';
    private const SCREEN_MAX_TOKENS = 500;

    public function __construct(
        private readonly AgentUsageLogService      $usageLog,
        private readonly PromptLibraryService      $prompts,
        private readonly PlanningDocumentDataContext $dataContext,
        private readonly PlanningTemplateService   $templateService,
        private readonly TraceabilityService       $traceability,
    ) {}

    // ── 전체 생성 + 저장 ──────────────────────────────────────────────────────

    /**
     * 모든 웍스 섹션을 순차 생성하고 최종 기획서를 저장.
     * onProgress(array): void — 각 섹션 처리 전후 호출 (SSE용)
     *
     * @return array{total: int, failed_count: int, failed: array, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generateAllAndSave(
        AiAgentArtifact $artifact,
        array           $context,
        int             $userId,
        callable        $onProgress,
    ): array {
        $provider  = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $aiSections = [];
        $failed     = [];
        $totalIn    = 0;
        $totalOut   = 0;
        $totalCost  = 0.0;
        $lastModel  = '';

        $screens = $context['screens'];
        $total   = count(self::AI_SECTIONS) + count($screens);
        $done    = 0;

        // ── 일반 섹션 ────────────────────────────────────────────────────────
        foreach (self::AI_SECTIONS as $key => $def) {
            $onProgress(['done' => $done, 'total' => $total, 'key' => $key, 'title' => $def['title'], 'status' => 'processing']);

            try {
                $result = $this->generateSection($provider, $artifact->project_id, $key, $def, $context, $artifact->id, $userId);
                $aiSections[$key] = $result['content'];
                $totalIn   += $result['tokens_in'];
                $totalOut  += $result['tokens_out'];
                $totalCost += $result['cost'];
                $lastModel  = $result['model'];
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $failed[$key]     = $e->getMessage();
                $aiSections[$key] = '';
            }

            $done++;
            $onProgress(['done' => $done, 'total' => $total, 'key' => $key, 'title' => $def['title'], 'status' => isset($failed[$key]) ? 'failed' : 'done']);
        }

        // ── 화면별 섹션 ──────────────────────────────────────────────────────
        foreach ($screens as $screen) {
            $key   = 'screen_' . $screen->screen_id;
            $title = $screen->title;
            $onProgress(['done' => $done, 'total' => $total, 'key' => $key, 'title' => $title, 'status' => 'processing']);

            try {
                $result = $this->generateScreenDetail($provider, $artifact->project_id, $screen, $context, $artifact->id, $userId);
                $aiSections[$key] = $result['content'];
                $totalIn   += $result['tokens_in'];
                $totalOut  += $result['tokens_out'];
                $totalCost += $result['cost'];
                $lastModel  = $result['model'];
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $failed[$key]     = $e->getMessage();
                $aiSections[$key] = '';
            }

            $done++;
            $onProgress(['done' => $done, 'total' => $total, 'key' => $key, 'title' => $title, 'status' => isset($failed[$key]) ? 'failed' : 'done']);
        }

        // ── 템플릿 렌더링 ─────────────────────────────────────────────────────
        $context['ai_sections']      = $aiSections;
        $context['document_version'] = 'v' . ($artifact->version + 1);

        $template     = $this->templateService->getActive();
        $templateFile = resource_path('templates/' . ($template?->template_path ?? 'planning/standard_v1.md.blade.php'));
        $markdown     = view()->file($templateFile, $context)->render();

        // ── 산출물 저장 ───────────────────────────────────────────────────────
        $artifact->updateWithVersion(
            content: $markdown,
            userId:  $userId,
            meta: [
                'change_type'      => 'ai_generated',
                'model'            => $lastModel,
                'tokens_in'        => $totalIn,
                'tokens_out'       => $totalOut,
                'cost_usd'         => $totalCost,
                'ai_sections'      => $aiSections,
                'failed_sections'  => $failed,
                'template_id'      => $template?->id,
                'template_version' => $template?->version,
                'generated_at'     => now()->toIso8601String(),
            ],
        );

        // ── 추적성 링크 ───────────────────────────────────────────────────────
        $this->createTraceabilityLinks($artifact->project_id, $artifact);

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

    // ── 단일 섹션 재생성 ─────────────────────────────────────────────────────

    /**
     * 특정 섹션만 재생성하여 기존 artifact meta.ai_sections[key]를 업데이트하고
     * 전체 마크다운을 재렌더링하여 새 버전으로 저장.
     *
     * @return array{content: string, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function regenerateSection(
        AiAgentArtifact $artifact,
        string          $sectionKey,
        int             $userId,
    ): array {
        $projectId = $artifact->project_id;
        $provider  = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $context   = $this->dataContext->build($projectId);

        $result = $this->generateOneSection($provider, $projectId, $sectionKey, $context, $artifact->id, $userId);

        // 기존 ai_sections에서 해당 key만 교체
        $meta       = $artifact->meta ?? [];
        $aiSections = $meta['ai_sections'] ?? [];
        $aiSections[$sectionKey] = $result['content'];

        // 실패 목록에서 제거 (성공했으므로)
        $failedSections = $meta['failed_sections'] ?? [];
        unset($failedSections[$sectionKey]);

        // 재렌더링
        $context['ai_sections']      = $aiSections;
        $context['document_version'] = 'v' . ($artifact->version + 1);
        $template     = $this->templateService->getActive();
        $templateFile = resource_path('templates/' . ($template?->template_path ?? 'planning/standard_v1.md.blade.php'));
        $markdown     = view()->file($templateFile, $context)->render();

        $artifact->updateWithVersion(
            content: $markdown,
            userId:  $userId,
            meta: array_merge($meta, [
                'ai_sections'     => $aiSections,
                'failed_sections' => $failedSections,
                'change_type'     => 'section_regenerated',
                'regenerated_key' => $sectionKey,
                'updated_at'      => now()->toIso8601String(),
            ]),
        );

        return $result;
    }

    // ── 내부 생성 메서드 ─────────────────────────────────────────────────────

    private function generateSection(
        AnthropicProvider $provider,
        int               $projectId,
        string            $key,
        array             $def,
        array             $context,
        int               $artifactId,
        int               $userId,
    ): array {
        $systemPrompt = $this->prompts->render('planning', $def['task_type'])
            ?? $this->getFallbackPrompt($key);

        $userContent = $this->buildSectionInput($key, $context);

        $response = $this->usageLog->callAndLog(
            provider:   $provider,
            call:       fn() => $provider->generate(
                systemPrompt: $systemPrompt,
                messages:     [['role' => 'user', 'content' => $userContent]],
                options:      ['max_tokens' => $def['max_tokens']],
            ),
            userId:     $userId,
            projectId:  $projectId,
            artifactId: $artifactId,
            stage:      'planning',
            taskType:   $def['task_type'],
        );

        return [
            'content'    => trim($response->text),
            'tokens_in'  => $response->inputTokens,
            'tokens_out' => $response->outputTokens,
            'cost'       => $this->usageLog->calculateCost($response->model, $response->inputTokens, $response->outputTokens),
            'model'      => $response->model,
        ];
    }

    private function generateScreenDetail(
        AnthropicProvider $provider,
        int               $projectId,
        AiAgentScreen     $screen,
        array             $context,
        int               $artifactId,
        int               $userId,
    ): array {
        $systemPrompt = $this->prompts->render('planning', self::SCREEN_TASK_TYPE)
            ?? <<<'PROMPT'
당신은 UX/UI 기획 전문가입니다.
화면 정보와 관련 요구사항을 바탕으로 기획 의도, 주요 기능, UX 고려사항을 마크다운으로 작성하세요.
응답에는 마크다운 콘텐츠만 포함하세요.
PROMPT;

        $userContent = $this->buildScreenInput($screen, $context);

        $response = $this->usageLog->callAndLog(
            provider:   $provider,
            call:       fn() => $provider->generate(
                systemPrompt: $systemPrompt,
                messages:     [['role' => 'user', 'content' => $userContent]],
                options:      ['max_tokens' => self::SCREEN_MAX_TOKENS],
            ),
            userId:     $userId,
            projectId:  $projectId,
            artifactId: $artifactId,
            stage:      'planning',
            taskType:   self::SCREEN_TASK_TYPE,
        );

        return [
            'content'    => trim($response->text),
            'tokens_in'  => $response->inputTokens,
            'tokens_out' => $response->outputTokens,
            'cost'       => $this->usageLog->calculateCost($response->model, $response->inputTokens, $response->outputTokens),
            'model'      => $response->model,
        ];
    }

    /**
     * 단일 섹션 재생성용 — key가 screen_* 이면 screen 분기, 아니면 일반 섹션
     */
    private function generateOneSection(
        AnthropicProvider $provider,
        int               $projectId,
        string            $key,
        array             $context,
        int               $artifactId,
        int               $userId,
    ): array {
        if (str_starts_with($key, 'screen_')) {
            $screenId = substr($key, 7);
            $screen   = $context['screens']->first(fn($s) => $s->screen_id === $screenId);
            if (!$screen) {
                throw new \RuntimeException("화면 '{$screenId}'을 찾을 수 없습니다.");
            }
            return $this->generateScreenDetail($provider, $projectId, $screen, $context, $artifactId, $userId);
        }

        $def = self::AI_SECTIONS[$key] ?? null;
        if (!$def) {
            throw new \InvalidArgumentException("알 수 없는 섹션 키: {$key}");
        }

        return $this->generateSection($provider, $projectId, $key, $def, $context, $artifactId, $userId);
    }

    // ── 입력 텍스트 빌더 ────────────────────────────────────────────────────

    private function buildSectionInput(string $key, array $context): string
    {
        $asis         = $context['asis'] ?? [];
        $tobe         = $context['tobe'] ?? [];
        $requirements = $context['requirements'] ?? collect();
        $gap          = $context['gap'] ?? [];
        $gaps         = $context['gaps'] ?? collect();
        $project      = $context['project'];

        return match ($key) {
            'section_1_3_objectives' => $this->text([
                "# AS-IS 현황 요약\n" . ($asis['summary'] ?? '(없음)'),
                "# TO-BE 요구사항 개요\n" . ($tobe['overview'] ?? '(없음)'),
                "# 프로젝트명\n" . ($project?->name ?? ''),
            ]),

            'section_5_1_priority_actions' => $this->text([
                "# Gap 분석 권장 사항\n" . json_encode($gap['recommendations'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                "# MUST 요구사항\n" . $this->formatRequirements($requirements->filter(fn($r) => strtoupper($r->priority->value ?? $r->priority ?? '') === 'MUST')),
            ]),

            'section_5_2_phasing_strategy' => $this->text([
                "# Gap 권장 단계 전략\n" . ($gap['recommendations']['phasing_strategy'] ?? '(없음)'),
                "# 요구사항 목록\n" . $this->formatRequirements($requirements),
            ]),

            'section_5_3_csf' => $this->text([
                "# Gap 목록\n" . $this->formatGaps($gaps),
                "# 리스크 목록\n" . json_encode($gap['risks'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                "# MUST 요구사항\n" . $this->formatRequirements($requirements->filter(fn($r) => strtoupper($r->priority->value ?? $r->priority ?? '') === 'MUST')),
            ]),

            'section_5_4_risk_strategy' => $this->text([
                "# 리스크 목록\n" . json_encode($gap['risks'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ]),

            'section_8_1_glossary' => $this->text([
                "# 요구사항 목록 (제목)\n" . $requirements->map(fn($r) => "- [{$r->req_id}] {$r->title}: {$r->description}")->join("\n"),
                "# Gap 목록 (제목)\n"       . $gaps->map(fn($g) => "- [{$g->gap_id}] {$g->title}")->join("\n"),
                "# AS-IS 이슈 카테고리\n"   . implode(', ', array_keys($asis['categories'] ?? [])),
            ]),

            default => '(입력 데이터 없음)',
        };
    }

    private function buildScreenInput(AiAgentScreen $screen, array $context): string
    {
        $requirements = $context['requirements'] ?? collect();

        // 화면 screen_id와 관련된 요구사항 (있으면 첨부)
        $relatedReqs = collect();
        if (!empty($screen->title)) {
            $keyword = strtolower($screen->title);
            $relatedReqs = $requirements->filter(fn($r) => str_contains(strtolower($r->title . ' ' . $r->description), $keyword));
        }

        return $this->text([
            "# 화면 정보\n- ID: {$screen->screen_id}\n- 제목: {$screen->title}\n- 설명: {$screen->description}",
            "# 관련 요구사항\n" . ($relatedReqs->isNotEmpty()
                ? $relatedReqs->map(function ($r) {
                    $pri = $r->priority->value ?? $r->priority ?? '';
                    return "- [{$r->req_id}][{$pri}] {$r->title}: {$r->description}";
                })->join("\n")
                : '(관련 요구사항 없음)'),
        ]);
    }

    // ── 추적성 링크 자동 생성 ────────────────────────────────────────────────

    private function createTraceabilityLinks(int $projectId, AiAgentArtifact $doc): void
    {
        // 기획서 → AS-IS 산출물
        $asIs = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::AS_IS_ANALYSIS->value)
            ->where('scope_type', 'project')->latest()->first();
        if ($asIs) {
            $this->traceability->link(
                projectId: $projectId, sourceType: 'artifact', sourceId: $doc->id,
                sourceRef: "PLANNING_DOC#{$doc->id}", targetType: 'artifact', targetId: $asIs->id,
                targetRef: "AS_IS#{$asIs->id}", linkType: 'derived_from',
            );
        }

        // 기획서 → TO-BE 산출물
        $toBe = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::TO_BE_REQUIREMENTS->value)
            ->where('scope_type', 'project')->latest()->first();
        if ($toBe) {
            $this->traceability->link(
                projectId: $projectId, sourceType: 'artifact', sourceId: $doc->id,
                sourceRef: "PLANNING_DOC#{$doc->id}", targetType: 'artifact', targetId: $toBe->id,
                targetRef: "TO_BE#{$toBe->id}", linkType: 'derived_from',
            );
        }

        // 기획서 → Gap 산출물
        $gapArtifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::GAP_ANALYSIS->value)
            ->where('scope_type', 'project')->latest()->first();
        if ($gapArtifact) {
            $this->traceability->link(
                projectId: $projectId, sourceType: 'artifact', sourceId: $doc->id,
                sourceRef: "PLANNING_DOC#{$doc->id}", targetType: 'artifact', targetId: $gapArtifact->id,
                targetRef: "GAP#{$gapArtifact->id}", linkType: 'derived_from',
            );
        }

        // 기획서 → 모든 Requirements
        foreach (\App\Models\Agent\AiAgentRequirement::where('project_id', $projectId)->get() as $req) {
            $this->traceability->link(
                projectId: $projectId, sourceType: 'artifact', sourceId: $doc->id,
                sourceRef: "PLANNING_DOC#{$doc->id}", targetType: 'requirement', targetId: $req->id,
                targetRef: $req->req_id, linkType: 'documents',
            );
        }

        // 기획서 → 모든 Gaps
        foreach (\App\Models\Agent\AiAgentGap::where('project_id', $projectId)->get() as $gap) {
            $this->traceability->link(
                projectId: $projectId, sourceType: 'artifact', sourceId: $doc->id,
                sourceRef: "PLANNING_DOC#{$doc->id}", targetType: 'gap', targetId: $gap->id,
                targetRef: $gap->gap_id, linkType: 'documents',
            );
        }

        // 기획서 → 모든 Screens
        foreach (AiAgentScreen::where('project_id', $projectId)->whereNull('archived_at')->get() as $screen) {
            $this->traceability->link(
                projectId: $projectId, sourceType: 'artifact', sourceId: $doc->id,
                sourceRef: "PLANNING_DOC#{$doc->id}", targetType: 'screen', targetId: $screen->id,
                targetRef: $screen->screen_id, linkType: 'documents',
            );
        }
    }

    // ── 유틸리티 ─────────────────────────────────────────────────────────────

    private function text(array $parts): string
    {
        return implode("\n\n---\n\n", array_filter($parts));
    }

    private function formatRequirements(\Illuminate\Support\Collection $reqs): string
    {
        return $reqs->map(fn($r) => "- [{$r->req_id}][" . strtoupper($r->priority->value ?? $r->priority ?? '') . "] {$r->title}: {$r->description}")->join("\n") ?: '(없음)';
    }

    private function formatGaps(\Illuminate\Support\Collection $gaps): string
    {
        return $gaps->map(fn($g) => "- [{$g->gap_id}][{$g->severity}][{$g->category}] {$g->title}: {$g->current_state} → {$g->target_state}")->join("\n") ?: '(없음)';
    }

    private function getFallbackPrompt(string $key): string
    {
        return match ($key) {
            'section_1_3_objectives'       => '당신은 IT 기획 전문가입니다. AS-IS와 TO-BE 데이터를 바탕으로 프로젝트 목표를 마크다운으로 작성하세요.',
            'section_5_1_priority_actions' => '당신은 전략 컨설턴트입니다. Gap 분석 권장 사항을 바탕으로 우선 액션 5-7개를 마크다운 리스트로 작성하세요.',
            'section_5_2_phasing_strategy' => '당신은 프로젝트 관리자입니다. 요구사항을 3단계로 나누어 단계적 접근 방안을 마크다운으로 작성하세요.',
            'section_5_3_csf'              => '당신은 프로젝트 성공 요인 분석가입니다. Gap과 리스크를 바탕으로 CSF 5-7개를 마크다운 리스트로 작성하세요.',
            'section_5_4_risk_strategy'    => '당신은 리스크 관리 전문가입니다. 각 리스크에 대한 대응 전략을 마크다운으로 작성하세요.',
            'section_8_1_glossary'         => '당신은 IT 문서 편집자입니다. 주요 용어를 마크다운 표(용어|설명|출처)로 정리하세요.',
            default                        => '당신은 IT 기획 전문가입니다. 제공된 데이터를 바탕으로 해당 섹션을 마크다운으로 작성하세요.',
        };
    }
}
