<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageStatus;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentScreen;
use App\Models\AiSetting;
use App\Models\Project;
use App\Models\SystemErrorLog;

class CodeReviewService
{
    private const COST_PER_SCREEN  = 0.40;
    private const COST_SYSTEM      = 0.20;
    private const MAX_TOKENS       = 6000;
    private const TIMEOUT          = 180;
    private const CACHE_PREFIX     = 'ai-agent:code-review:batch:';
    private const CACHE_TTL        = 3600;

    private const FALLBACK_SCREEN_PROMPT = <<<'PROMPT'
당신은 시니어 풀스택 코드 리뷰어입니다.

주어진 화면의 Frontend 코드와 Backend 엔드포인트를 통합 검토합니다.

T41이 이미 발견한 Frontend 위반(`from_t41`)이 입력에 포함됩니다.
중복 보고는 피하고, T41이 못 잡은 것 + 통합 관점 위반에 집중하세요.

검증 영역 (7개):
1. spec_compliance: 명세 부합도
2. code_quality: 코드 품질
3. security: 보안
4. best_practices: 베스트 프랙티스
5. performance: 성능
6. data_flow: 데이터 흐름 (Frontend 입력 → Backend 처리)
7. integration: Frontend-Backend 통합

각 위반:
- severity: critical/warning/info
- 구체적 위치
- 수정 제안

record_screen_review 도구로 응답하세요.
PROMPT;

    private const FALLBACK_SYSTEM_PROMPT = <<<'PROMPT'
당신은 시니어 시스템 아키텍트이자 코드 리뷰어입니다.

주어진 프로젝트의 모든 Frontend 화면과 Backend 리소스를 종합하여 시스템 전체 관점에서 평가하세요.

영역:
1. 아키텍처 평가 (계층 분리, 책임 분배)
2. 일관성 (명명 규칙, 에러 처리, 인증 방식 등)
3. 데이터 흐름 (전체 시스템 관점)
4. 교차 관심사 (cross-cutting concerns)
5. 잘된 점 (strengths) - 1~3개

화면별 리뷰 결과(`screen_reviews`)가 입력에 포함됩니다.
이를 종합하여 시스템 전체 평가를 작성하세요.

record_system_review 도구로 응답하세요.
PROMPT;

    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
        private readonly TraceabilityService  $traceability,
    ) {}

    // ── Single-screen review ──────────────────────────────────────────────────

    /**
     * @return array{artifact:AiAgentArtifact, compliance_score:int, findings_count:int, tokensIn:int, tokensOut:int, model:string}
     */
    public function reviewScreen(
        Project       $project,
        AiAgentScreen $screen,
        int           $userId,
    ): array {
        $feArtifact = $this->loadFrontendCode($project->id, $screen->id);
        if (!$feArtifact) {
            throw new \RuntimeException("[{$screen->screen_id}] Frontend 코드 산출물이 없습니다. T40을 먼저 실행하세요.");
        }

        $feContent  = json_decode($feArtifact->content, true) ?? [];
        $t41Findings = $this->loadT41Findings($project->id, $screen->id);
        $beRoutes    = $this->loadBackendRoutesForScreen($project->id, $screen);
        $t44Match    = $this->loadT44MatchForScreen($project->id, $screen);
        $context     = $this->buildScreenContext($project->id, $screen);

        $provider     = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $systemPrompt = $this->prompts->render('dev', 'code_review_screen_v1')
            ?? self::FALLBACK_SCREEN_PROMPT;

        $userMessage = $this->buildScreenUserMessage($screen, $feContent, $t41Findings, $beRoutes, $t44Match, $context);

        $response = $this->usageLog->callAndLog(
            provider:  $provider,
            call:      fn() => $provider->generateWithTools(
                systemPrompt: $systemPrompt,
                messages:     [['role' => 'user', 'content' => $userMessage]],
                tools:        [$this->getScreenReviewTool()],
                options:      ['max_tokens' => self::MAX_TOKENS, 'timeout' => self::TIMEOUT],
            )->toAIResponse(),
            userId:    $userId,
            projectId: $project->id,
            stage:     'dev',
            taskType:  'code_review_screen_v1',
        );

        $toolInput = json_decode($response->text, true) ?? [];

        $toolInput['compliance_score']    ??= 50;
        $toolInput['category_scores']     ??= $this->zeroScores();
        $toolInput['additional_findings'] ??= [];

        foreach ($toolInput['additional_findings'] as &$f) {
            $f['id']           ??= substr(md5(($f['title'] ?? '') . ($f['frontend_file'] ?? '') . ($f['backend_file'] ?? '')), 0, 12);
            $f['auto_fixable'] ??= false;
            $f['ignored']      ??= false;
            $f['source']       ??= 't45';
        }
        unset($f);

        $allViolations = array_merge(
            array_map(fn($v) => array_merge($v, ['source' => 't41']), $t41Findings),
            $toolInput['additional_findings'],
        );

        $stage = $this->resolveDevelopmentStage($project->id);

        $content = [
            '$metadata' => [
                'screen_id'    => $screen->screen_id,
                'screen_title' => $screen->title,
                'reviewed_at'  => now()->toIso8601String(),
                'model'        => $response->model,
                'tokens_in'    => $response->inputTokens,
                'tokens_out'   => $response->outputTokens,
            ],
            'screen_id'            => $screen->screen_id,
            'screen_title'         => $screen->title,
            'compliance_score'     => $toolInput['compliance_score'],
            'category_scores'      => $toolInput['category_scores'],
            'from_t41'             => $t41Findings,
            'additional_findings'  => $toolInput['additional_findings'],
            'all_violations'       => $allViolations,
        ];

        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage->id,
            type:      ArtifactType::CODE_REVIEW,
            scopeType: 'screen',
            scopeId:   $screen->id,
            title:     "[{$screen->screen_id}] {$screen->title} — 웍스 코드 리뷰",
            content:   json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:    $userId,
            meta: [
                'reviewed_at'      => now()->toIso8601String(),
                'compliance_score' => $toolInput['compliance_score'],
                'findings_count'   => count($toolInput['additional_findings']),
                'model'            => $response->model,
                'tokens_in'        => $response->inputTokens,
                'tokens_out'       => $response->outputTokens,
            ],
        );

        try {
            $this->traceability->link(
                projectId:  $project->id,
                sourceType: 'artifact',
                sourceId:   $artifact->id,
                sourceRef:  ArtifactType::CODE_REVIEW->value,
                targetType: 'artifact',
                targetId:   $feArtifact->id,
                targetRef:  ArtifactType::FRONTEND_CODE->value,
                linkType:   'reviews',
            );
        } catch (\Exception) {}

        return [
            'artifact'        => $artifact,
            'compliance_score'=> $toolInput['compliance_score'],
            'findings_count'  => count($toolInput['additional_findings']),
            'tokensIn'        => $response->inputTokens,
            'tokensOut'       => $response->outputTokens,
            'model'           => $response->model,
        ];
    }

    // ── System review ─────────────────────────────────────────────────────────

    /**
     * @return array{artifact:AiAgentArtifact, overall_score:int, tokensIn:int, tokensOut:int, model:string}
     */
    public function reviewSystem(
        Project $project,
        array   $screenReviews,
        int     $userId,
    ): array {
        $provider     = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $systemPrompt = $this->prompts->render('dev', 'code_review_system_v1')
            ?? self::FALLBACK_SYSTEM_PROMPT;

        $userMessage = $this->buildSystemUserMessage($project, $screenReviews);

        $response = $this->usageLog->callAndLog(
            provider:  $provider,
            call:      fn() => $provider->generateWithTools(
                systemPrompt: $systemPrompt,
                messages:     [['role' => 'user', 'content' => $userMessage]],
                tools:        [$this->getSystemReviewTool()],
                options:      ['max_tokens' => self::MAX_TOKENS, 'timeout' => self::TIMEOUT],
            )->toAIResponse(),
            userId:    $userId,
            projectId: $project->id,
            stage:     'dev',
            taskType:  'code_review_system_v1',
        );

        $toolInput = json_decode($response->text, true) ?? [];

        $toolInput['overall_score']            ??= 50;
        $toolInput['executive_summary']        ??= '';
        $toolInput['architecture_assessment']  ??= '';
        $toolInput['data_flow_issues']         ??= [];
        $toolInput['cross_cutting_concerns']   ??= [];
        $toolInput['strengths']                ??= [];

        $avgScore = $this->computeAverageScore($screenReviews);
        $stage    = $this->resolveDevelopmentStage($project->id);

        $content = array_merge($toolInput, [
            '$metadata' => [
                'reviewed_at'  => now()->toIso8601String(),
                'model'        => $response->model,
                'tokens_in'    => $response->inputTokens,
                'tokens_out'   => $response->outputTokens,
                'screen_count' => count($screenReviews),
                'avg_screen_score' => $avgScore,
            ],
        ]);

        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage->id,
            type:      ArtifactType::CODE_REVIEW,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "{$project->name} — 시스템 종합 코드 리뷰",
            content:   json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:    $userId,
            meta: [
                'reviewed_at'    => now()->toIso8601String(),
                'overall_score'  => $toolInput['overall_score'],
                'screen_count'   => count($screenReviews),
                'model'          => $response->model,
                'tokens_in'      => $response->inputTokens,
                'tokens_out'     => $response->outputTokens,
            ],
        );

        return [
            'artifact'      => $artifact,
            'overall_score' => $toolInput['overall_score'],
            'tokensIn'      => $response->inputTokens,
            'tokensOut'     => $response->outputTokens,
            'model'         => $response->model,
        ];
    }

    // ── Batch (SSE) ───────────────────────────────────────────────────────────

    /**
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
        $screenReviews  = [];

        $onEvent('start', ['total' => $total + 1, 'progress' => 0, 'message' => '코드 리뷰 시작...']);

        foreach ($screens as $screen) {
            $onEvent('screen_start', [
                'screen_id' => $screen->screen_id,
                'title'     => $screen->title,
                'done'      => $done,
                'total'     => $total + 1,
                'progress'  => $total > 0 ? (int) round(($done / ($total + 1)) * 90) : 0,
            ]);

            try {
                $result          = $this->reviewScreen($project, $screen, $userId);
                $totalTokensIn  += $result['tokensIn'];
                $totalTokensOut += $result['tokensOut'];
                $lastModel       = $result['model'];

                $reviewData = json_decode($result['artifact']->content, true) ?? [];
                $screenReviews[] = $reviewData;

                $onEvent('screen_done', [
                    'screen_id'        => $screen->screen_id,
                    'title'            => $screen->title,
                    'compliance_score' => $result['compliance_score'],
                    'findings_count'   => $result['findings_count'],
                    'done'             => $done + 1,
                    'total'            => $total + 1,
                    'progress'         => (int) round((($done + 1) / ($total + 1)) * 90),
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
                    'total'     => $total + 1,
                    'progress'  => (int) round((($done + 1) / ($total + 1)) * 90),
                    'status'    => 'failed',
                ]);
            }

            $done++;
        }

        // System review
        if (!empty($screenReviews)) {
            $onEvent('system_start', [
                'message'  => '시스템 종합 리뷰 중...',
                'done'     => $done,
                'total'    => $total + 1,
                'progress' => 92,
            ]);

            try {
                $sysResult      = $this->reviewSystem($project, $screenReviews, $userId);
                $totalTokensIn  += $sysResult['tokensIn'];
                $totalTokensOut += $sysResult['tokensOut'];
                $lastModel       = $sysResult['model'];

                $onEvent('system_done', [
                    'overall_score' => $sysResult['overall_score'],
                    'done'          => $done + 1,
                    'total'         => $total + 1,
                    'progress'      => 98,
                    'status'        => 'done',
                ]);
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $onEvent('system_error', ['error' => $e->getMessage(), 'progress' => 98]);
            }
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

    public function applyAutoFix(
        Project       $project,
        AiAgentScreen $screen,
        string        $findingId,
        int           $userId,
    ): array {
        $reviewArtifact = $this->loadScreenReview($project->id, $screen->id);
        if (!$reviewArtifact) {
            throw new \RuntimeException('리뷰 산출물을 찾을 수 없습니다.');
        }

        $feArtifact = $this->loadFrontendCode($project->id, $screen->id);
        if (!$feArtifact) {
            throw new \RuntimeException('Frontend 코드 산출물을 찾을 수 없습니다.');
        }

        $reviewData = json_decode($reviewArtifact->content, true) ?? [];
        $finding    = $this->findFinding($reviewData['additional_findings'] ?? [], $findingId);

        if (!$finding) {
            throw new \RuntimeException("Finding ID {$findingId}를 찾을 수 없습니다.");
        }
        if (empty($finding['auto_fixable'])) {
            throw new \RuntimeException('이 항목은 자동 수정이 불가능합니다.');
        }

        $feData      = json_decode($feArtifact->content, true) ?? [];
        $targetFile  = $finding['frontend_file'] ?? '';
        $fileContent = '';
        foreach ($feData['files'] ?? [] as $f) {
            if ($f['path'] === $targetFile) {
                $fileContent = $f['content'];
                break;
            }
        }

        $provider = new AnthropicProvider(AiSetting::current()->anthropicKey());

        $fixMsg = <<<MSG
다음 코드 파일에서 발견된 위반 사항을 자동 수정해주세요.

## 화면: [{$screen->screen_id}] {$screen->title}
## 파일: {$targetFile}

## 위반 내용
- 카테고리: {$finding['category']}
- 심각도: {$finding['severity']}
- 설명: {$finding['description']}
- 제안: {$finding['suggestion']}

## 현재 파일 내용
```
{$fileContent}
```

apply_code_fix 도구로 응답하세요.
MSG;

        $fixTool = [
            'name'         => 'apply_code_fix',
            'description'  => '코드 파일의 위반 사항을 수정하고 결과를 반환합니다.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'fixed_content' => ['type' => 'string'],
                    'explanation'   => ['type' => 'string'],
                ],
                'required' => ['fixed_content', 'explanation'],
            ],
        ];

        $response = $this->usageLog->callAndLog(
            provider:  $provider,
            call:      fn() => $provider->generateWithTools(
                systemPrompt: '당신은 코드 수정 전문가입니다. 지시된 위반 사항만 최소한으로 수정하고 나머지 코드는 그대로 유지하세요.',
                messages:     [['role' => 'user', 'content' => $fixMsg]],
                tools:        [$fixTool],
                options:      ['max_tokens' => 8000, 'timeout' => 120],
            )->toAIResponse(),
            userId:    $userId,
            projectId: $project->id,
            stage:     'dev',
            taskType:  'code_review_auto_fix',
        );

        $fixResult = json_decode($response->text, true) ?? [];

        if (!empty($targetFile) && !empty($fixResult['fixed_content'])) {
            foreach ($feData['files'] as &$f) {
                if ($f['path'] === $targetFile) {
                    $f['content'] = $fixResult['fixed_content'];
                    $f['lines']   = substr_count($fixResult['fixed_content'], "\n") + 1;
                    break;
                }
            }
            unset($f);

            $feArtifact->updateWithVersion(
                content: json_encode($feData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                userId:  $userId,
                meta:    ['change_type' => 'auto_fix_t45', 'finding_id' => $findingId, 'file' => $targetFile],
            );
        }

        foreach ($reviewData['additional_findings'] as &$f) {
            if (($f['id'] ?? '') === $findingId) {
                $f['fixed']    = true;
                $f['fixed_at'] = now()->toIso8601String();
                break;
            }
        }
        unset($f);

        $reviewData['all_violations'] = array_merge(
            array_map(fn($v) => array_merge($v, ['source' => 't41']), $reviewData['from_t41'] ?? []),
            $reviewData['additional_findings'],
        );

        $reviewArtifact->updateWithVersion(
            content: json_encode($reviewData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  $userId,
            meta:    ['change_type' => 'auto_fix_applied', 'finding_id' => $findingId],
        );

        return [
            'success'     => true,
            'explanation' => $fixResult['explanation'] ?? '자동 수정이 적용되었습니다.',
            'new_version' => $feArtifact->fresh()->version,
        ];
    }

    // ── Ignore finding ────────────────────────────────────────────────────────

    public function ignoreFinding(
        Project       $project,
        AiAgentScreen $screen,
        string        $findingId,
        int           $userId,
    ): void {
        $artifact = $this->loadScreenReview($project->id, $screen->id);
        if (!$artifact) {
            throw new \RuntimeException('리뷰 산출물을 찾을 수 없습니다.');
        }

        $data  = json_decode($artifact->content, true) ?? [];
        $found = false;

        foreach ($data['additional_findings'] as &$f) {
            if (($f['id'] ?? '') === $findingId) {
                $f['ignored']    = true;
                $f['ignored_at'] = now()->toIso8601String();
                $found           = true;
                break;
            }
        }
        unset($f);

        if (!$found) {
            throw new \RuntimeException("Finding ID {$findingId}를 찾을 수 없습니다.");
        }

        $data['all_violations'] = array_merge(
            array_map(fn($v) => array_merge($v, ['source' => 't41']), $data['from_t41'] ?? []),
            $data['additional_findings'],
        );

        $artifact->updateWithVersion(
            content: json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  $userId,
            meta:    ['change_type' => 'finding_ignored', 'finding_id' => $findingId],
        );
    }

    // ── Export ────────────────────────────────────────────────────────────────

    public function exportMarkdown(Project $project): string
    {
        $systemArtifact = $this->loadSystemReview($project->id);
        $screenArtifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::CODE_REVIEW->value)
            ->where('scope_type', 'screen')
            ->get();

        $md = "# {$project->name} — 웍스 코드 리뷰 결과\n\n";
        $md .= "생성일: " . now()->format('Y-m-d H:i') . "\n\n---\n\n";

        if ($systemArtifact) {
            $sys = json_decode($systemArtifact->content, true) ?? [];
            $md .= "## 시스템 종합 평가\n\n";
            $md .= "**종합 점수**: {$sys['overall_score']}/100\n\n";
            $md .= "### 요약\n\n{$sys['executive_summary']}\n\n";

            if (!empty($sys['data_flow_issues'])) {
                $md .= "### 데이터 흐름 이슈\n\n";
                foreach ($sys['data_flow_issues'] as $issue) {
                    $md .= "- **{$issue['title']}** ({$issue['severity']})\n  {$issue['description']}\n";
                }
                $md .= "\n";
            }

            if (!empty($sys['strengths'])) {
                $md .= "### 잘된 점\n\n";
                foreach ($sys['strengths'] as $s) {
                    $md .= "✅ {$s}\n";
                }
                $md .= "\n";
            }

            $md .= "---\n\n";
        }

        foreach ($screenArtifacts as $artifact) {
            $data  = json_decode($artifact->content, true) ?? [];
            $score = $data['compliance_score'] ?? 0;
            $sid   = $data['screen_id'] ?? '?';
            $title = $data['screen_title'] ?? '';

            $md .= "## [{$sid}] {$title}\n\n";
            $md .= "**점수**: {$score}/100\n\n";

            $additional = $data['additional_findings'] ?? [];
            $active = array_filter($additional, fn($f) => empty($f['ignored']) && empty($f['fixed']));

            if (!empty($active)) {
                $md .= "### T45 추가 발견\n\n";
                foreach ($active as $f) {
                    $icon = match($f['severity'] ?? 'info') { 'critical' => '🔴', 'warning' => '🟡', default => '🔵' };
                    $md .= "{$icon} **[{$f['category']}] {$f['title']}**\n";
                    $md .= "- 설명: {$f['description']}\n";
                    $md .= "- 제안: {$f['suggestion']}\n\n";
                }
            }

            $md .= "---\n\n";
        }

        return $md;
    }

    // ── Cost estimate ─────────────────────────────────────────────────────────

    public function estimatedCost(int $screenCount): float
    {
        return round($screenCount * self::COST_PER_SCREEN + self::COST_SYSTEM, 2);
    }

    // ── Data access ───────────────────────────────────────────────────────────

    public function loadScreenReview(int $projectId, int $screenId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::CODE_REVIEW->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screenId)
            ->latest('id')->first();
    }

    public function loadSystemReview(int $projectId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::CODE_REVIEW->value)
            ->where('scope_type', 'project')
            ->where('scope_id', $projectId)
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

    // ── Private builders ──────────────────────────────────────────────────────

    protected function loadT41Findings(int $projectId, int $screenId): array
    {
        $artifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::CODE_VALIDATION->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screenId)
            ->latest('id')->first();

        if (!$artifact) return [];

        $data = json_decode($artifact->content, true) ?? [];
        return array_filter(
            $data['violations'] ?? [],
            fn($v) => empty($v['ignored']) && empty($v['fixed'])
        );
    }

    protected function loadBackendRoutesForScreen(int $projectId, AiAgentScreen $screen): array
    {
        $beArtifacts = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::BACKEND_CODE->value)
            ->where('scope_type', 'resource')
            ->get();

        $routes = [];
        foreach ($beArtifacts as $a) {
            $data = json_decode($a->content, true) ?? [];
            foreach ($data['routes'] ?? [] as $r) {
                $routes[] = $r;
            }
        }

        return $routes;
    }

    protected function loadT44MatchForScreen(int $projectId, AiAgentScreen $screen): array
    {
        $artifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::API_INTEGRATION->value)
            ->where('scope_type', 'project')
            ->latest('id')->first();

        if (!$artifact) return [];

        $decoded  = json_decode($artifact->content, true) ?? [];
        $analysis = $decoded['analysis'] ?? [];

        $screenMatches    = [];
        $screenUnmatched  = [];

        foreach ($analysis['matches'] ?? [] as $m) {
            $fe = $m['frontend_call'] ?? [];
            if (($fe['screen_id'] ?? '') === $screen->screen_id) {
                $screenMatches[] = $m;
            }
        }

        foreach ($analysis['unmatched_frontend'] ?? [] as $u) {
            $fe = $u['frontend_call'] ?? [];
            if (($fe['screen_id'] ?? '') === $screen->screen_id) {
                $screenUnmatched[] = $u;
            }
        }

        return [
            'matches'   => $screenMatches,
            'unmatched' => $screenUnmatched,
        ];
    }

    protected function buildScreenContext(int $projectId, AiAgentScreen $screen): array
    {
        $context = [];

        $erd = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::ERD->value)
            ->where('scope_type', 'project')
            ->latest('id')->first();
        if ($erd) {
            $erdData = json_decode($erd->content, true) ?? [];
            $names = array_column($erdData['entities'] ?? [], 'name');
            $context['erd_entities'] = implode(', ', array_slice($names, 0, 15));
        }

        $api = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::API_SPEC->value)
            ->where('scope_type', 'project')
            ->latest('id')->first();
        if ($api) {
            $apiData = json_decode($api->content, true) ?? [];
            $paths = array_keys($apiData['paths'] ?? []);
            $context['api_endpoints'] = implode(', ', array_slice($paths, 0, 10));
        }

        $rbac = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::RBAC_MODEL->value)
            ->where('scope_type', 'project')
            ->latest('id')->first();
        if ($rbac) {
            $rbacData = json_decode($rbac->content, true) ?? [];
            $roles = array_column($rbacData['roles'] ?? [], 'name');
            $context['rbac_roles'] = implode(', ', $roles);
        }

        return $context;
    }

    protected function buildScreenUserMessage(
        AiAgentScreen $screen,
        array         $feContent,
        array         $t41Findings,
        array         $beRoutes,
        array         $t44Match,
        array         $context,
    ): string {
        $files = $feContent['files'] ?? [];
        $fileSections = '';
        foreach ($files as $file) {
            $lines   = $file['lines'] ?? (substr_count($file['content'] ?? '', "\n") + 1);
            $purpose = $file['purpose'] ?? '';
            $fileSections .= "\n### {$file['path']} ({$lines}줄) — {$purpose}\n```\n{$file['content']}\n```\n";
        }

        $t41Summary = '';
        if (!empty($t41Findings)) {
            $t41Summary = "\n## T41 이미 발견된 위반 (중복 보고 금지)\n";
            foreach ($t41Findings as $v) {
                $t41Summary .= "- [{$v['severity']}][{$v['category']}] {$v['title']} ({$v['file']})\n";
            }
        }

        $beRouteSummary = '';
        if (!empty($beRoutes)) {
            $beRouteSummary = "\n## Backend 엔드포인트 (관련)\n";
            foreach (array_slice($beRoutes, 0, 20) as $r) {
                $beRouteSummary .= "- {$r['method']} {$r['uri']} → {$r['controller']}\n";
            }
        }

        $t44Summary = '';
        if (!empty($t44Match['unmatched'])) {
            $t44Summary = "\n## T44: 매칭 안 된 API 호출 (통합 위반 후보)\n";
            foreach ($t44Match['unmatched'] as $u) {
                $fe = $u['frontend_call'] ?? [];
                $t44Summary .= "- {$fe['method']} {$fe['url']} — {$u['issue']}\n";
            }
        }

        $ctxSummary = '';
        if (!empty($context['erd_entities'])) $ctxSummary .= "\n## ERD 엔티티: {$context['erd_entities']}";
        if (!empty($context['api_endpoints'])) $ctxSummary .= "\n## API 주요 경로: {$context['api_endpoints']}";
        if (!empty($context['rbac_roles'])) $ctxSummary .= "\n## RBAC 역할: {$context['rbac_roles']}";

        return <<<MSG
## 리뷰 대상 화면
- 화면 ID: {$screen->screen_id}
- 화면명: {$screen->title}

{$ctxSummary}
{$t41Summary}
{$beRouteSummary}
{$t44Summary}

## Frontend 코드 파일
{$fileSections}

7개 카테고리로 검수하고 T41이 못 잡은 추가 발견만 record_screen_review 도구로 기록하세요.
MSG;
    }

    protected function buildSystemUserMessage(Project $project, array $screenReviews): string
    {
        $screenSummary = '';
        foreach ($screenReviews as $sr) {
            $sid    = $sr['screen_id'] ?? '?';
            $title  = $sr['screen_title'] ?? '';
            $score  = $sr['compliance_score'] ?? 0;
            $count  = count($sr['additional_findings'] ?? []);
            $screenSummary .= "- [{$sid}] {$title}: {$score}점, 추가 발견 {$count}건\n";

            foreach ($sr['additional_findings'] ?? [] as $f) {
                if (empty($f['ignored']) && empty($f['fixed'])) {
                    $screenSummary .= "  [{$f['severity']}][{$f['category']}] {$f['title']}\n";
                }
            }
        }

        return <<<MSG
## 프로젝트: {$project->name}

## 화면별 리뷰 요약
{$screenSummary}

위 화면별 리뷰 결과를 종합하여 시스템 전체 관점에서 평가하고 record_system_review 도구로 기록하세요.
MSG;
    }

    // ── Tools ─────────────────────────────────────────────────────────────────

    private function getScreenReviewTool(): array
    {
        return [
            'name'         => 'record_screen_review',
            'description'  => '화면 통합 코드 리뷰 결과를 기록합니다 (T41 미발견 항목 + 통합 관점).',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'compliance_score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                    'category_scores'  => [
                        'type'       => 'object',
                        'properties' => [
                            'spec_compliance' => ['type' => 'integer'],
                            'code_quality'    => ['type' => 'integer'],
                            'security'        => ['type' => 'integer'],
                            'best_practices'  => ['type' => 'integer'],
                            'performance'     => ['type' => 'integer'],
                            'data_flow'       => ['type' => 'integer'],
                            'integration'     => ['type' => 'integer'],
                        ],
                        'required' => ['spec_compliance', 'code_quality', 'security', 'best_practices', 'performance', 'data_flow', 'integration'],
                    ],
                    'additional_findings' => [
                        'type'        => 'array',
                        'description' => 'T41이 못 잡은 것만 — 중복 보고 금지',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'category'      => ['type' => 'string', 'enum' => ['spec_compliance', 'code_quality', 'security', 'best_practices', 'performance', 'data_flow', 'integration']],
                                'severity'      => ['type' => 'string', 'enum' => ['critical', 'warning', 'info']],
                                'title'         => ['type' => 'string'],
                                'description'   => ['type' => 'string'],
                                'frontend_file' => ['type' => 'string'],
                                'backend_file'  => ['type' => 'string'],
                                'suggestion'    => ['type' => 'string'],
                                'auto_fixable'  => ['type' => 'boolean'],
                            ],
                            'required' => ['category', 'severity', 'title', 'description', 'suggestion'],
                        ],
                    ],
                ],
                'required' => ['compliance_score', 'category_scores', 'additional_findings'],
            ],
        ];
    }

    private function getSystemReviewTool(): array
    {
        return [
            'name'         => 'record_system_review',
            'description'  => '시스템 전체 종합 코드 리뷰 결과를 기록합니다.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'overall_score'           => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                    'executive_summary'       => ['type' => 'string'],
                    'architecture_assessment' => ['type' => 'string'],
                    'data_flow_issues'        => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'title'              => ['type' => 'string'],
                                'description'        => ['type' => 'string'],
                                'severity'           => ['type' => 'string'],
                                'affected_screens'   => ['type' => 'array', 'items' => ['type' => 'string']],
                                'affected_resources' => ['type' => 'array', 'items' => ['type' => 'string']],
                                'suggestion'         => ['type' => 'string'],
                            ],
                            'required' => ['title', 'description', 'severity'],
                        ],
                    ],
                    'cross_cutting_concerns' => [
                        'type'  => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'strengths' => [
                        'type'  => 'array',
                        'items' => ['type' => 'string'],
                    ],
                ],
                'required' => ['overall_score', 'executive_summary'],
            ],
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function findFinding(array $findings, string $id): ?array
    {
        foreach ($findings as $f) {
            if (($f['id'] ?? '') === $id) return $f;
        }
        return null;
    }

    private function zeroScores(): array
    {
        return [
            'spec_compliance' => 0, 'code_quality' => 0, 'security' => 0,
            'best_practices'  => 0, 'performance'  => 0, 'data_flow' => 0, 'integration' => 0,
        ];
    }

    private function computeAverageScore(array $screenReviews): int
    {
        if (empty($screenReviews)) return 0;
        $scores = array_column($screenReviews, 'compliance_score');
        $scores = array_filter($scores, fn($s) => is_numeric($s));
        return empty($scores) ? 0 : (int) round(array_sum($scores) / count($scores));
    }

    private function resolveDevelopmentStage(int $projectId): AiAgentProjectStage
    {
        return AiAgentProjectStage::firstOrCreate(
            ['project_id' => $projectId, 'type' => StageType::DEVELOPMENT],
            ['order' => 4, 'status' => StageStatus::IN_PROGRESS],
        );
    }
}
