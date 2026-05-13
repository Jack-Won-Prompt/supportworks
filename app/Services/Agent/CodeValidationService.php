<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\SystemErrorLog;

class CodeValidationService
{
    private const CACHE_PREFIX = 'ai-agent:code-validation:batch:';
    private const CACHE_TTL    = 3600;
    private const COST_PER_SCREEN = 0.30;

    public function __construct(
        private readonly CodeStaticAnalyzer  $staticAnalyzer,
        private readonly AiCodeReviewer      $aiReviewer,
        private readonly TraceabilityService $traceability,
    ) {}

    // ── Single screen ─────────────────────────────────────────────────────────

    /**
     * Validate a single screen (static + 웍스).
     *
     * @return array{artifact:AiAgentArtifact, compliance_score:int, violations_count:int, tokensIn:int, tokensOut:int, model:string}
     */
    public function validateScreen(
        Project       $project,
        AiAgentScreen $screen,
        int           $userId,
    ): array {
        // Load T40 code artifact
        $codeArtifact = $this->loadFrontendCode($project->id, $screen->id);
        if (!$codeArtifact) {
            throw new \RuntimeException("[{$screen->screen_id}] Frontend 코드 산출물이 없습니다. T40을 먼저 실행하세요.");
        }

        $codeContent  = json_decode($codeArtifact->content, true) ?? [];
        $files        = $codeContent['files'] ?? [];
        $stack        = $this->resolveStack($project->id);

        // 1. Static analysis
        $staticResult = $this->staticAnalyzer->analyze($files, $stack);

        // 2. 웍스 review
        $context    = $this->buildContext($project->id, $screen);
        $aiResult   = $this->aiReviewer->reviewScreen(
            screen:        $screen,
            codeContent:   $codeContent,
            staticResult:  $staticResult,
            context:       $context,
            userId:        $userId,
            projectId:     $project->id,
        );

        // 3. Merge: static issues + 웍스 violations
        $merged     = $this->mergeResults($staticResult, $aiResult['result']);
        $stage      = $this->resolveDevelopmentStage($project->id);

        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage->id,
            type:      ArtifactType::CODE_VALIDATION,
            scopeType: 'screen',
            scopeId:   $screen->id,
            title:     "[{$screen->screen_id}] {$screen->title} — Output 검증",
            content:   json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:    $userId,
            meta: [
                'validated_at'         => now()->toIso8601String(),
                'compliance_score'     => $merged['compliance_score'],
                'total_violations'     => count($merged['violations']),
                'static_available'     => $staticResult['available'],
                'model'                => $aiResult['model'],
                'tokens_in'            => $aiResult['tokensIn'],
                'tokens_out'           => $aiResult['tokensOut'],
            ],
        );

        // Traceability: validation → code artifact
        try {
            $this->traceability->link(
                projectId:  $project->id,
                sourceType: 'artifact',
                sourceId:   $artifact->id,
                sourceRef:  ArtifactType::CODE_VALIDATION->value,
                targetType: 'artifact',
                targetId:   $codeArtifact->id,
                targetRef:  ArtifactType::FRONTEND_CODE->value,
                linkType:   'validates',
            );
        } catch (\Exception) {}

        return [
            'artifact'         => $artifact,
            'compliance_score' => $merged['compliance_score'],
            'violations_count' => count($merged['violations']),
            'tokensIn'         => $aiResult['tokensIn'],
            'tokensOut'        => $aiResult['tokensOut'],
            'model'            => $aiResult['model'],
        ];
    }

    // ── Batch (SSE) ───────────────────────────────────────────────────────────

    /**
     * Validate all screens with code. Calls $onEvent for SSE progress.
     *
     * @param  callable(string $event, array $data): void  $onEvent
     */
    public function runBatch(
        Project  $project,
        int      $userId,
        callable $onEvent,
        ?array   $screenIds = null,
    ): void {
        $query = AiAgentScreen::where('project_id', $project->id)
            ->active()->orderBy('order');

        if ($screenIds !== null) {
            $query->whereIn('id', $screenIds);
        }

        $allScreens = $query->get();

        // Only validate screens that have frontend code
        $codeExists = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::FRONTEND_CODE->value)
            ->where('scope_type', 'screen')
            ->pluck('scope_id')
            ->map(fn($id) => (int) $id)
            ->flip()
            ->all();

        $screens        = $allScreens->filter(fn($s) => isset($codeExists[$s->id]))->values();
        $total          = $screens->count();
        $done           = 0;
        $failedCount    = 0;
        $totalTokensIn  = 0;
        $totalTokensOut = 0;
        $lastModel      = '';

        $onEvent('start', ['total' => $total, 'progress' => 0, 'message' => '검증 시작...']);

        foreach ($screens as $screen) {
            $onEvent('screen_start', [
                'screen_id' => $screen->screen_id,
                'title'     => $screen->title,
                'done'      => $done,
                'total'     => $total,
                'progress'  => $total > 0 ? (int) round(($done / $total) * 90) : 0,
            ]);

            try {
                $result          = $this->validateScreen($project, $screen, $userId);
                $totalTokensIn  += $result['tokensIn'];
                $totalTokensOut += $result['tokensOut'];
                $lastModel       = $result['model'];

                $onEvent('screen_done', [
                    'screen_id'        => $screen->screen_id,
                    'title'            => $screen->title,
                    'compliance_score' => $result['compliance_score'],
                    'violations_count' => $result['violations_count'],
                    'done'             => $done + 1,
                    'total'            => $total,
                    'progress'         => $total > 0 ? (int) round((($done + 1) / $total) * 90) : 90,
                    'status'           => 'done',
                ]);
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $failedCount++;
                $onEvent('screen_error', [
                    'screen_id' => $screen->screen_id,
                    'title'     => $screen->title,
                    'error'     => $e->getMessage(),
                    'done'      => $done + 1,
                    'total'     => $total,
                    'progress'  => $total > 0 ? (int) round((($done + 1) / $total) * 90) : 90,
                    'status'    => 'failed',
                ]);
            }

            $done++;
        }

        $onEvent('complete', [
            'status'      => 'COMPLETED',
            'total'       => $total,
            'done'        => $done,
            'failed'      => $failedCount,
            'tokens_in'   => $totalTokensIn,
            'tokens_out'  => $totalTokensOut,
            'model'       => $lastModel,
            'progress'    => 100,
        ]);
    }

    // ── Auto-fix ──────────────────────────────────────────────────────────────

    /**
     * Apply 웍스 auto-fix for a single violation.
     *
     * @return array{success:bool, explanation:string, new_version:int}
     */
    public function applyAutoFix(
        Project       $project,
        AiAgentScreen $screen,
        string        $violationId,
        int           $userId,
    ): array {
        $validationArtifact = $this->loadValidation($project->id, $screen->id);
        if (!$validationArtifact) {
            throw new \RuntimeException('검증 산출물을 찾을 수 없습니다.');
        }

        $codeArtifact = $this->loadFrontendCode($project->id, $screen->id);
        if (!$codeArtifact) {
            throw new \RuntimeException('Frontend 코드 산출물을 찾을 수 없습니다.');
        }

        $validationData = json_decode($validationArtifact->content, true) ?? [];
        $violation      = $this->findViolation($validationData['violations'] ?? [], $violationId);

        if (!$violation) {
            throw new \RuntimeException("위반 ID {$violationId}를 찾을 수 없습니다.");
        }
        if (empty($violation['auto_fixable'])) {
            throw new \RuntimeException('이 위반은 자동 수정이 불가능합니다.');
        }

        $codeData    = json_decode($codeArtifact->content, true) ?? [];
        $targetFile  = $violation['file'] ?? '';
        $fileContent = '';
        foreach ($codeData['files'] ?? [] as $f) {
            if ($f['path'] === $targetFile) {
                $fileContent = $f['content'];
                break;
            }
        }

        $fixResult = $this->aiReviewer->generateAutoFix(
            screen:      $screen,
            violation:   $violation,
            fileContent: $fileContent,
            userId:      $userId,
            projectId:   $project->id,
        );

        // Update code artifact with fixed content
        foreach ($codeData['files'] as &$f) {
            if ($f['path'] === $targetFile) {
                $f['content'] = $fixResult['fixed_content'];
                $f['lines']   = substr_count($fixResult['fixed_content'], "\n") + 1;
                break;
            }
        }
        unset($f);

        $codeArtifact->updateWithVersion(
            content: json_encode($codeData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  $userId,
            meta:    ['change_type' => 'auto_fix', 'violation_id' => $violationId, 'file' => $targetFile],
        );

        // Mark violation as fixed in validation artifact
        foreach ($validationData['violations'] as &$v) {
            if (($v['id'] ?? '') === $violationId) {
                $v['fixed']    = true;
                $v['fixed_at'] = now()->toIso8601String();
                break;
            }
        }
        unset($v);

        $validationArtifact->updateWithVersion(
            content: json_encode($validationData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  $userId,
            meta:    ['change_type' => 'auto_fix_applied', 'violation_id' => $violationId],
        );

        return [
            'success'      => true,
            'explanation'  => $fixResult['explanation'],
            'new_version'  => $codeArtifact->fresh()->version,
        ];
    }

    // ── Ignore violation ──────────────────────────────────────────────────────

    public function ignoreViolation(
        Project       $project,
        AiAgentScreen $screen,
        string        $violationId,
        int           $userId,
    ): void {
        $artifact = $this->loadValidation($project->id, $screen->id);
        if (!$artifact) {
            throw new \RuntimeException('검증 산출물을 찾을 수 없습니다.');
        }

        $data = json_decode($artifact->content, true) ?? [];
        $found = false;

        foreach ($data['violations'] as &$v) {
            if (($v['id'] ?? '') === $violationId) {
                $v['ignored']    = true;
                $v['ignored_at'] = now()->toIso8601String();
                $found           = true;
                break;
            }
        }
        unset($v);

        if (!$found) {
            throw new \RuntimeException("위반 ID {$violationId}를 찾을 수 없습니다.");
        }

        $artifact->updateWithVersion(
            content: json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  $userId,
            meta:    ['change_type' => 'violation_ignored', 'violation_id' => $violationId],
        );
    }

    // ── Export ────────────────────────────────────────────────────────────────

    /**
     * Build a Markdown export of all validation results.
     */
    public function exportMarkdown(Project $project): string
    {
        $artifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::CODE_VALIDATION->value)
            ->where('scope_type', 'screen')
            ->get();

        if ($artifacts->isEmpty()) {
            return "# Output 검증 결과\n\n검증 결과가 없습니다.\n";
        }

        $md = "# {$project->name} — Output 검증 결과\n\n생성일: " . now()->format('Y-m-d H:i') . "\n\n---\n\n";

        foreach ($artifacts as $artifact) {
            $data  = json_decode($artifact->content, true) ?? [];
            $score = $data['compliance_score'] ?? 0;
            $sid   = $data['screen_id'] ?? '?';
            $title = $data['screen_title'] ?? '';

            $md .= "## [{$sid}] {$title}\n\n";
            $md .= "**종합 점수**: {$score}/100\n\n";

            if (!empty($data['violations'])) {
                $md .= "### 위반 사항\n\n";
                foreach ($data['violations'] as $v) {
                    if (!empty($v['ignored'])) continue;
                    $icon = match($v['severity']) { 'critical' => '🔴', 'warning' => '🟡', default => '🔵' };
                    $md .= "{$icon} **[{$v['category']}] {$v['title']}**\n";
                    $md .= "- 파일: `{$v['file']}`" . (!empty($v['line']) ? ":{$v['line']}" : '') . "\n";
                    $md .= "- 설명: {$v['description']}\n";
                    $md .= "- 제안: {$v['suggestion']}\n\n";
                }
            }

            if (!empty($data['strengths'])) {
                $md .= "### 잘된 점\n\n";
                foreach ($data['strengths'] as $s) {
                    $md .= "✅ {$s}\n";
                }
                $md .= "\n";
            }

            $md .= "---\n\n";
        }

        return $md;
    }

    // ── Data access ───────────────────────────────────────────────────────────

    public function loadValidation(int $projectId, int $screenId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::CODE_VALIDATION->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screenId)
            ->latest('id')->first();
    }

    public function loadFrontendCode(int $projectId, int $screenId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::FRONTEND_CODE->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screenId)
            ->latest('id')->first();
    }

    public function estimatedCost(int $screenCount): float
    {
        return round($screenCount * self::COST_PER_SCREEN, 2);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildContext(int $projectId, AiAgentScreen $screen): array
    {
        $context = ['stack' => $this->resolveStack($projectId)->label()];

        // T36 ERD
        $erd = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::ERD->value)
            ->where('scope_type', 'project')
            ->latest('id')->first();
        if ($erd) {
            $context['erd'] = json_decode($erd->content, true) ?? null;
        }

        // T37 API spec
        $api = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::API_SPEC->value)
            ->where('scope_type', 'project')
            ->latest('id')->first();
        if ($api) {
            $context['api_spec'] = json_decode($api->content, true) ?? null;
        }

        // T38 RBAC
        $rbac = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::RBAC_MODEL->value)
            ->where('scope_type', 'project')
            ->latest('id')->first();
        if ($rbac) {
            $context['rbac'] = json_decode($rbac->content, true) ?? null;
        }

        return $context;
    }

    private function mergeResults(array $staticResult, array $aiResult): array
    {
        $aiViolations     = $aiResult['violations'] ?? [];
        $staticIssues     = $staticResult['issues'] ?? [];
        $allViolations    = $aiViolations;

        // De-duplicate: add static issues not already covered by 웍스
        foreach ($staticIssues as $si) {
            $duplicate = false;
            foreach ($aiViolations as $av) {
                if (($av['file'] ?? '') === $si['file'] && abs(($av['line'] ?? 0) - ($si['line'] ?? 0)) <= 2) {
                    $duplicate = true;
                    break;
                }
            }
            if (!$duplicate) {
                $allViolations[] = $si;
            }
        }

        $score          = $aiResult['compliance_score'] ?? 50;
        $categoryScores = $aiResult['category_scores']  ?? [];

        return [
            'screen_id'          => '', // filled by caller if needed
            'compliance_score'   => $score,
            'category_scores'    => $categoryScores,
            'violations'         => $allViolations,
            'strengths'          => $aiResult['strengths'] ?? [],
            'static_available'   => $staticResult['available'] ?? false,
            'static_summary'     => $staticResult['summary'] ?? [],
            'validated_at'       => now()->toIso8601String(),
        ];
    }

    private function findViolation(array $violations, string $id): ?array
    {
        foreach ($violations as $v) {
            if (($v['id'] ?? '') === $id) return $v;
        }
        return null;
    }

    private function resolveStack(int $projectId): \App\Enums\Agent\FrontendStack
    {
        $config = \App\Models\Agent\ProjectAiAgentConfig::where('project_id', $projectId)->first();
        $value  = $config?->frontend_stack ?? 'HTML';
        return \App\Enums\Agent\FrontendStack::tryFrom(strtoupper($value)) ?? \App\Enums\Agent\FrontendStack::HTML;
    }

    private function resolveDevelopmentStage(int $projectId): AiAgentProjectStage
    {
        return AiAgentProjectStage::firstOrCreate(
            ['project_id' => $projectId, 'type' => StageType::DEVELOPMENT],
            ['order' => 4, 'status' => \App\Enums\Agent\StageStatus::IN_PROGRESS],
        );
    }
}
