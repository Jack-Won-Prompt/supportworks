<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\User;
use App\Services\Agent\Figma\FigmaClientFactory;
use Illuminate\Support\Facades\Cache;

class DesignReviewService
{
    private const CACHE_PREFIX = 'ai-agent:design-review:';
    private const CACHE_TTL    = 3600;

    public function __construct(
        private readonly ReviewContextLoader  $contextLoader,
        private readonly AiDesignReviewer     $aiReviewer,
        private readonly FigmaClientFactory   $clientFactory,
        private readonly TraceabilityService  $traceability,
    ) {}

    // ── Session / SSE ──────────────────────────────────────────────────────────

    public static function cacheKey(string $sessionId): string
    {
        return self::CACHE_PREFIX . $sessionId;
    }

    public function createSession(Project $project, User $user, string $sessionId): void
    {
        Cache::put(self::cacheKey($sessionId), [
            'project_id' => $project->id,
            'user_id'    => $user->id,
            'status'     => 'STARTING',
            'created_at' => now()->toIso8601String(),
        ], self::CACHE_TTL);
    }

    public function getSession(string $sessionId): ?array
    {
        return Cache::get(self::cacheKey($sessionId));
    }

    /**
     * Main review loop — called inline from the SSE endpoint.
     * Calls $onEvent for each significant milestone.
     *
     * @param  callable(string $event, array $data): void  $onEvent
     */
    public function runReview(
        Project  $project,
        User     $user,
        string   $sessionId,
        callable $onEvent,
    ): void {
        $context = $this->contextLoader->load($project);
        $screens = AiAgentScreen::where('project_id', $project->id)
            ->whereNull('archived_at')
            ->whereNotNull('figma_frame_id')
            ->orderBy('screen_id')
            ->get();

        $total          = $screens->count();
        $screenResults  = [];
        $tokensInTotal  = 0;
        $tokensOutTotal = 0;
        $errorCount     = 0;

        $onEvent('start', ['total' => $total, 'progress' => 5]);

        // Group screens by figma_file_key to batch image fetching
        $byFileKey = $screens->groupBy('figma_file_key');

        // Pre-fetch all preview images grouped by file
        $imageUrls = [];
        foreach ($byFileKey as $fileKey => $fileScreens) {
            if (!$fileKey) continue;
            try {
                $client    = $this->clientFactory->forUser($user);
                $nodeIds   = $fileScreens->pluck('figma_frame_id')->filter()->values()->all();
                $fetched   = $client->getImages($fileKey, $nodeIds, 'png', 0.75);
                $imageUrls = array_merge($imageUrls, $fetched);
            } catch (\Exception) {
                // image fetch fail — continue without images for this file
            }
        }

        $onEvent('images_loaded', ['count' => count($imageUrls), 'progress' => 10]);

        foreach ($screens as $i => $screen) {
            $progressStart = 10 + (int) (($i / $total) * 80);

            $onEvent('screen_start', [
                'screen_id'   => $screen->screen_id,
                'screen_name' => $screen->title,
                'index'       => $i + 1,
                'total'       => $total,
                'progress'    => $progressStart,
            ]);

            try {
                $imageUrl = $imageUrls[$screen->figma_frame_id] ?? null;

                $reviewResult = $this->aiReviewer->reviewScreen(
                    screen:        $screen,
                    context:       $context,
                    userId:        $user->id,
                    projectId:     $project->id,
                    figmaImageUrl: $imageUrl,
                );

                $screenResults[$screen->screen_id] = array_merge(
                    $reviewResult['result'],
                    [
                        'screen_name'     => $screen->title,
                        'figma_frame_name'=> $screen->figma_frame_name,
                        'figma_node_id'   => $screen->figma_frame_id,
                        'figma_url'       => $screen->figma_url,
                        'screen_db_id'    => $screen->id,
                        'has_image'       => $imageUrl !== null,
                    ]
                );

                $tokensInTotal  += $reviewResult['tokensIn'];
                $tokensOutTotal += $reviewResult['tokensOut'];

                $onEvent('screen_done', [
                    'screen_id'   => $screen->screen_id,
                    'screen_name' => $screen->title,
                    'score'       => $reviewResult['result']['compliance_score'] ?? 0,
                    'violations'  => count($reviewResult['result']['violations'] ?? []),
                    'index'       => $i + 1,
                    'total'       => $total,
                    'progress'    => $progressStart + (int) (80 / $total),
                    'tokens_in'   => $tokensInTotal,
                    'tokens_out'  => $tokensOutTotal,
                ]);
            } catch (\Throwable $e) {
                $errorCount++;
                $screenResults[$screen->screen_id] = [
                    'screen_id'       => $screen->screen_id,
                    'screen_name'     => $screen->title,
                    'compliance_score'=> 0,
                    'category_scores' => ['color' => 0, 'typography' => 0, 'component' => 0, 'layout' => 0],
                    'violations'      => [],
                    'strengths'       => [],
                    'error'           => $e->getMessage(),
                ];
                $onEvent('screen_error', [
                    'screen_id'   => $screen->screen_id,
                    'screen_name' => $screen->title,
                    'error'       => $e->getMessage(),
                    'index'       => $i + 1,
                    'total'       => $total,
                ]);
            }
        }

        $onEvent('aggregating', ['progress' => 92, 'message' => '종합 리포트 생성 중...']);

        // Build aggregate report
        $report   = $this->buildReport($screenResults, $project, $tokensInTotal, $tokensOutTotal);
        $artifact = $this->saveArtifact($project, $report, $user);

        // Traceability links
        try {
            $this->linkTraceability($project, $artifact, $screens->all());
        } catch (\Exception) {}

        $onEvent('complete', [
            'status'       => 'COMPLETED',
            'artifact_id'  => $artifact->id,
            'progress'     => 100,
            'score'        => $report['$metadata']['stats']['compliance_score'],
            'violations'   => $report['$metadata']['stats']['total_violations'],
            'tokens_in'    => $tokensInTotal,
            'tokens_out'   => $tokensOutTotal,
            'error_count'  => $errorCount,
        ]);
    }

    /**
     * Save review results from CLI or other non-SSE callers.
     */
    public function saveReviewResults(
        Project $project,
        User    $user,
        string  $sessionId,
        array   $results,
        int     $tokensIn,
        int     $tokensOut,
    ): AiAgentArtifact {
        $report   = $this->buildReport($results, $project, $tokensIn, $tokensOut);
        $artifact = $this->saveArtifact($project, $report, $user);

        $screens = AiAgentScreen::where('project_id', $project->id)
            ->whereIn('screen_id', array_keys($results))
            ->get()
            ->all();

        try {
            $this->linkTraceability($project, $artifact, $screens);
        } catch (\Exception) {}

        Cache::forget(self::cacheKey($sessionId));

        return $artifact;
    }

    // ── Data access ────────────────────────────────────────────────────────────

    public function getCurrent(Project $project): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::DESIGN_REVIEW->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();
    }

    public function getScreenResult(Project $project, AiAgentScreen $screen): ?array
    {
        $artifact = $this->getCurrent($project);
        if (!$artifact) return null;

        $data = is_array($artifact->content)
            ? $artifact->content
            : json_decode($artifact->content, true);

        return $data['violations_by_screen'][$screen->screen_id] ?? null;
    }

    public function updateIgnoredViolations(AiAgentArtifact $artifact, string $screenId, array $ignoredIds, int $userId): void
    {
        $data = is_array($artifact->content)
            ? $artifact->content
            : json_decode($artifact->content, true);

        if (isset($data['violations_by_screen'][$screenId])) {
            foreach ($data['violations_by_screen'][$screenId]['violations'] as &$v) {
                $v['ignored'] = in_array($v['id'] ?? null, $ignoredIds);
            }
        }

        $artifact->updateWithVersion(
            content: json_encode($data, JSON_UNESCAPED_UNICODE),
            userId:  $userId,
            meta:    ['change_type' => 'user_ignored_violations'],
        );
    }

    // ── Private ────────────────────────────────────────────────────────────────

    private function buildReport(array $screenResults, Project $project, int $tokensIn, int $tokensOut): array
    {
        $totalViolations = 0;
        $critical = $warning = $info = 0;
        $passedScreens   = 0;
        $colorScores     = [];
        $typoScores      = [];
        $compScores      = [];
        $layoutScores    = [];
        $allScores       = [];

        foreach ($screenResults as $result) {
            $violations = $result['violations'] ?? [];
            $vCount     = count($violations);
            $totalViolations += $vCount;

            foreach ($violations as $v) {
                match ($v['severity'] ?? 'info') {
                    'critical' => $critical++,
                    'warning'  => $warning++,
                    default    => $info++,
                };
            }

            if ($vCount === 0 && ($result['compliance_score'] ?? 0) >= 80) $passedScreens++;

            $catScores = $result['category_scores'] ?? [];
            if (isset($catScores['color']))      $colorScores[]  = $catScores['color'];
            if (isset($catScores['typography'])) $typoScores[]   = $catScores['typography'];
            if (isset($catScores['component']))  $compScores[]   = $catScores['component'];
            if (isset($catScores['layout']))     $layoutScores[] = $catScores['layout'];
            if (isset($result['compliance_score'])) $allScores[] = $result['compliance_score'];
        }

        $avg     = fn(array $arr) => empty($arr) ? 0 : (int) round(array_sum($arr) / count($arr));
        $score   = $avg($allScores);

        // Violations by category
        $byCategory = ['color' => [], 'typography' => [], 'component' => [], 'layout' => []];
        foreach ($screenResults as $scrId => $result) {
            foreach ($result['violations'] ?? [] as $v) {
                $cat = $v['category'] ?? 'color';
                if (isset($byCategory[$cat])) {
                    $byCategory[$cat][] = array_merge($v, ['screen_id' => $scrId]);
                }
            }
        }

        // Recommendations
        $recommendations = $this->generateRecommendations($screenResults, $byCategory);

        return [
            '$metadata' => [
                'version'          => '1.0',
                'reviewed_at'      => now()->toIso8601String(),
                'scope'            => 'project',
                'project_id'       => $project->id,
                'screens_reviewed' => count($screenResults),
                'tokens_in'        => $tokensIn,
                'tokens_out'       => $tokensOut,
                'stats'            => [
                    'total_violations' => $totalViolations,
                    'critical'         => $critical,
                    'warning'          => $warning,
                    'info'             => $info,
                    'passed_screens'   => $passedScreens,
                    'compliance_score' => $score,
                ],
            ],
            'summary' => [
                'executive'            => $this->buildExecutiveSummary($score, $totalViolations, count($screenResults)),
                'compliance_breakdown' => [
                    'color'      => $avg($colorScores),
                    'typography' => $avg($typoScores),
                    'component'  => $avg($compScores),
                    'layout'     => $avg($layoutScores),
                ],
            ],
            'violations_by_screen'   => $screenResults,
            'violations_by_category' => $byCategory,
            'recommendations'        => $recommendations,
        ];
    }

    private function buildExecutiveSummary(int $score, int $violations, int $screensCount): string
    {
        if ($score >= 90) {
            return "디자인 일관성이 매우 높습니다 (종합 {$score}점). {$screensCount}개 화면 검수 결과 위반 {$violations}건이 발견되었으나 전반적으로 표준을 잘 준수하고 있습니다.";
        }
        if ($score >= 70) {
            return "디자인 일관성이 양호합니다 (종합 {$score}점). {$screensCount}개 화면에서 위반 {$violations}건이 발견되었습니다. 일부 화면에서 개선이 필요합니다.";
        }
        return "디자인 일관성 개선이 필요합니다 (종합 {$score}점). {$screensCount}개 화면에서 위반 {$violations}건이 발견되었습니다. 디자인 표준 적용을 우선적으로 검토해 주세요.";
    }

    private function generateRecommendations(array $screenResults, array $byCategory): array
    {
        $recs = [];

        // Find worst screens
        $worstScreens = collect($screenResults)
            ->filter(fn($r) => ($r['compliance_score'] ?? 100) < 70)
            ->sortBy('compliance_score')
            ->take(3)
            ->keys()
            ->all();

        if (!empty($worstScreens)) {
            $recs[] = implode(', ', $worstScreens) . '에서 표준 색상 및 컴포넌트 적용을 우선적으로 진행하세요.';
        }

        foreach (['color', 'component', 'layout'] as $cat) {
            $criticals = collect($byCategory[$cat])->filter(fn($v) => ($v['severity'] ?? '') === 'critical');
            if ($criticals->count() >= 2) {
                $labels = ['color' => '색상 토큰', 'component' => '표준 컴포넌트', 'layout' => '레이아웃 그리드'];
                $recs[] = "{$labels[$cat]} 적용이 여러 화면에서 누락되어 있습니다. 디자인 가이드 공유를 권장합니다.";
            }
        }

        if (empty($recs)) {
            $recs[] = '전반적으로 디자인 표준이 잘 준수되고 있습니다. Warning 수준 이슈를 점진적으로 개선해주세요.';
        }

        return array_values(array_unique($recs));
    }

    private function saveArtifact(Project $project, array $report, User $user): AiAgentArtifact
    {
        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::DESIGN)
            ->first();

        return AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage?->id ?? 0,
            type:      ArtifactType::DESIGN_REVIEW,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "디자인 일관성 검수 — {$project->name}",
            content:   json_encode($report, JSON_UNESCAPED_UNICODE),
            userId:    $user->id,
            meta: [
                'reviewed_at'      => now()->toIso8601String(),
                'compliance_score' => $report['$metadata']['stats']['compliance_score'],
                'screens_reviewed' => $report['$metadata']['screens_reviewed'],
                'total_violations' => $report['$metadata']['stats']['total_violations'],
            ],
        );
    }

    private function linkTraceability(Project $project, AiAgentArtifact $artifact, array $screens): void
    {
        foreach ($screens as $screen) {
            $this->traceability->link(
                projectId:  $project->id,
                sourceType: 'artifact',
                sourceId:   $artifact->id,
                sourceRef:  ArtifactType::DESIGN_REVIEW->value,
                targetType: 'screen',
                targetId:   $screen->id,
                targetRef:  $screen->screen_id,
                linkType:   'reviews',
            );
        }
    }
}
