<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\GitCommit;
use App\Models\Maint\MaintRequest;
use App\Models\Project;
use App\Models\SystemErrorLog;
use App\Models\User;
use App\Models\WeeklyAiSummary;
use App\Models\WeeklyReport;
use App\Services\AiOrchestrator;
use App\Services\DocxWriter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WeeklyAiSummaryController extends Controller
{
    private function authorizeManager(Project $project): void
    {
        $user = auth()->user();
        // 웍스 서머리 접근 = 관리자 또는 SR 담당자
        if (!$user->isAdmin() && !(bool) ($user->is_sr_agent ?? false)) {
            abort(403);
        }
    }

    // ─── 저장된 서머리 조회 ──────────────────────────────────────────

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorizeManager($project);

        $type     = $request->input('type', 'full');                  // full | weekly
        $weekDate = $request->input('week');                          // Y-m-d (weekly)

        if ($type === 'weekly' && !$weekDate) return response()->json(['summary' => null]);

        // DB 캐시 우선 — 이미 생성된 서머리가 있으면 그대로 반환 (재생성 불필요)
        $q = WeeklyAiSummary::where('project_id', $project->id)->where('summary_type', $type);
        if ($type === 'weekly') $q->where('week_start_date', $weekDate);
        else                    $q->whereNull('week_start_date');

        $summary = $q->latest('updated_at')->first();
        if (!$summary) return response()->json(['summary' => null]);

        // 캐시 신선도 — 7일 이상 낡으면 클라이언트에서 자동 재생성하도록 플래그
        $isStale = $summary->updated_at && $summary->updated_at->lt(now()->subDays(7));

        // 'Git 커밋 내역' 접힌 영역용 — 캐시된 기간의 커밋을 prefix 로 분리해서 함께 반환
        [$rangeStart, $rangeEnd] = $this->resolveRange($type, $project, $weekDate, null, null);
        [$commitDetails, $commonCommitDetails] = $this->loadCommitDetails($project, $rangeStart, $rangeEnd);

        return response()->json([
            'summary' => [
                'content'              => $summary->content,
                'generated_at'         => $summary->updated_at->format('Y.m.d H:i'),
                'generated_by'         => $summary->generatedBy?->name ?? '',
                'metrics'              => $summary->metrics,
                'commit_details'       => $commitDetails,
                'common_commit_details'=> $commonCommitDetails,
                'is_stale'             => $isStale,
                'stale_days'           => $summary->updated_at ? (int) now()->diffInDays($summary->updated_at) : null,
            ],
        ]);
    }

    /** show() 가 호출 — 기간 내 커밋을 prefix 분리 후 UI 직렬화 */
    private function loadCommitDetails(Project $project, ?Carbon $rangeStart, ?Carbon $rangeEnd): array
    {
        $isLinked = \App\Models\ProjectGitLink::where('project_id', $project->id)
            ->where('source', 'withworks')->exists();
        if (!$isLinked) return [[], []];

        $memberUserIds = \App\Models\ProjectMember::where('project_id', $project->id)->pluck('user_id');
        $q = GitCommit::with('user:id,name,email')
            ->where('source', 'withworks')
            ->whereIn('user_id', $memberUserIds);
        if ($rangeStart && $rangeEnd) $q->whereBetween('committed_at', [$rangeStart, $rangeEnd]);
        $raw = $q->orderBy('committed_at')->get();

        // path_prefix 는 프로젝트의 소속 회사에서 조회 (회사 단위 매핑)
        $myPrefix    = $project->companyGroup?->path_prefix;
        $allPrefixes = \App\Models\CompanyGroup::whereNotNull('path_prefix')
            ->pluck('path_prefix')->all();
        [$proj, $common] = $this->partitionCommitsByPrefix($raw, $myPrefix, $allPrefixes);

        return [
            $this->serializeCommitsForUi($proj),
            $this->serializeCommitsForUi($common),
        ];
    }

    // ─── 웍스 서머리 생성 + 저장 ──────────────────────────────────────

    public function generate(Request $request, Project $project): JsonResponse
    {
        $this->authorizeManager($project);
        // AI 호출 + Git 동기화 합산이 PHP 기본 120초를 초과할 수 있어 충분히 확장
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $request->validate([
            'type'              => 'required|in:full,this_month,weekly',
            'week'              => 'nullable|date',
            'sr_company_ids'    => 'nullable|array',
            'sr_company_ids.*'  => 'integer',
        ]);

        $type           = $request->input('type');
        $srCompanyIds   = array_values(array_filter(array_map('intval', (array) $request->input('sr_company_ids', []))));

        // 기간 결정 — full=지난 30일, weekly=선택 주차
        [$rangeStart, $rangeEnd, $scopeLabel, $weekDate, $rsCol, $reCol] =
            $this->resolveRange($type, $project, $request->input('week'), null, null);

        if ($type === 'weekly' && !$weekDate) {
            return response()->json(['error' => '주차를 선택해주세요.'], 422);
        }

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) {
            return response()->json(['error' => '웍스 API 키가 설정되지 않았습니다. 관리자에게 문의해주세요.'], 422);
        }

        // 프로젝트가 withworks 와 연결된 경우 — 생성 전 Git 자동 증분 동기화 (DB 의 가장 최신 커밋 이후부터만 fetch)
        $isWithworksLinked = \App\Models\ProjectGitLink::where('project_id', $project->id)
            ->where('source', 'withworks')->exists();
        if ($isWithworksLinked) {
            try {
                app(\App\Services\WithWorks\WithWorksGitIngestService::class)
                    ->sync(null, null, auth()->id());
            } catch (\Throwable $e) {
                SystemErrorLog::record($e, 'warning');
            }
        }

        // 1) 위클리 보고서 수집 — 사용자 이메일까지 함께 로드 (이메일 기반 그룹화용)
        $reportsQ = WeeklyReport::where('project_id', $project->id)->with(['tasks', 'user:id,name,email']);
        if ($type === 'weekly' && $weekDate) {
            $reportsQ->where('week_start_date', $weekDate);
        } elseif ($type === 'custom' && $rangeStart && $rangeEnd) {
            $reportsQ->whereBetween('week_start_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()]);
        }
        $reports = $reportsQ->orderBy('week_start_date')->get();

        // 2) 유지보수 SR (해당 기간 내 완료/처리) — 사용자가 선택한 SR 회사 의 SR 만 포함.
        //    선택 0개 = SR 미포함.
        $maintRequests = collect();
        if (!empty($srCompanyIds)) {
            $srQ = MaintRequest::with(['assignee.user:id,name,email'])
                ->whereIn('company_group_id', $srCompanyIds);
            if ($rangeStart && $rangeEnd) {
                $srQ->where(function ($q) use ($rangeStart, $rangeEnd) {
                    $q->whereBetween('completed_at', [$rangeStart, $rangeEnd])
                      ->orWhereBetween('request_date', [$rangeStart, $rangeEnd]);
                });
            }
            $maintRequests = $srQ->orderBy('request_date')->get();
        }

        // 3) WITHWORKS Git 커밋 (해당 기간) — 프로젝트가 withworks 와 연결되어 있을 때만 포함
        //    포함 범위: 프로젝트 멤버의 user_id 가 매핑된 커밋 (이메일 기반 자동 매핑)
        $commits = collect();
        $commonCommits = collect();   // 어느 회사 키워드와도 매칭 안 되는 파일들의 커밋
        if ($isWithworksLinked) {
            $memberUserIds = \App\Models\ProjectMember::where('project_id', $project->id)->pluck('user_id');
            // 모든 브랜치 통합 — sha unique 라 중복 없음. committed_at(작성일) 기준 주차 판정.
            $commitsQ = GitCommit::with('user:id,name,email')
                ->where('source', 'withworks')
                ->whereIn('user_id', $memberUserIds);
            if ($rangeStart && $rangeEnd) {
                $commitsQ->whereBetween('committed_at', [$rangeStart, $rangeEnd]);
            }
            $rawCommits = $commitsQ->orderBy('committed_at')->get();

            // path_prefix 매칭 (회사 단위) — 프로젝트의 소속 회사의 키워드 사용
            $myPrefix = $project->companyGroup?->path_prefix;
            $allPrefixes = \App\Models\CompanyGroup::whereNotNull('path_prefix')
                ->pluck('path_prefix')->all();
            [$commits, $commonCommits] = $this->partitionCommitsByPrefix($rawCommits, $myPrefix, $allPrefixes);
        }

        if ($reports->isEmpty() && $maintRequests->isEmpty() && $commits->isEmpty() && $commonCommits->isEmpty()) {
            return response()->json(['error' => '분석할 데이터가 없습니다 (보고서·SR·커밋 모두 없음).'], 422);
        }

        // 4) 담당자별 정량 집계 — 프로젝트 / 공통 분리 계산
        $metrics       = $this->buildAssigneeMetrics($reports, $maintRequests, $commits);
        $commonMetrics = $this->buildAssigneeMetrics(collect(), collect(), $commonCommits);

        // 5) AI 프롬프트 구성
        $metricsTable       = $this->renderMetricsTable($metrics);
        $commonMetricsTable = $this->renderMetricsTable($commonMetrics);
        $reportSection      = $this->renderReportsForAi($reports);
        $srSection          = $this->renderMaintForAi($maintRequests);
        $commitSection      = $this->renderCommitsForAi($commits);
        $commonCommitSection = $this->renderCommitsForAi($commonCommits);

        $rangeLabel = $rangeStart && $rangeEnd
            ? $rangeStart->format('Y-m-d') . ' ~ ' . $rangeEnd->format('Y-m-d')
            : '전체 기간';

        $systemPrompt = <<<PROMPT
당신은 엄정한 엔지니어링 매니저입니다. 아래 데이터를 **논리적이고 냉정하게** 평가합니다.
모든 판단에는 **데이터 근거(어느 커밋·어느 파일·어떤 수치)** 가 함께 제시돼야 합니다.
"열심히 했다"는 인상이 아닌 **Git 커밋 제목·파일 경로·난이도·SR 처리·위클리 보고 사실** 만 근거로 사용합니다. 추측·온정주의·격려성 표현을 거부합니다.

다음 구성으로 작성하세요. 각 섹션은 마크다운 헤딩(##)으로 구분:

## 담당자별 업무 평가
각 담당자를 **반드시 `### 이름` 헤딩으로 구분**하세요. 그 아래에 다음 4개 불릿을 순서대로 작성:
- **라벨**: 우수 / 양호 / 개선 필요 / 위험 중 하나 (남용 금지)
- **주요 작업**: 어느 커밋·어느 파일·어떤 기능 (커밋 제목 인용 2~3개)
- **보고-실적**: 위클리 보고 내용과 실제 Git/SR 활동 일치 여부. 불일치는 명시적 지적
- **약점·우려**: 단순 변경 위주, 보고 누락, 책임 회피 등

라벨 기준 — **데이터로 입증된 경우에만**:
- **우수**: 난이도 평균 3.0+ + 커밋·SR 모두 활발 + 보고 일치
- **양호**: 일반적 수준 (난이도 평균 2.0~3.0)
- **개선 필요**: 활동 미미 또는 단순 변경(typo·docs·lint) 위주 또는 보고-실적 불일치
- **위험**: 활동 거의 없음 + 보고 누락 + 책임 회피

## 난이도 분석
- 단순 커밋 수가 아닌 **무게감**으로 판단. 쉬운 변경만 반복한 사람은 칭찬 대상이 아님.
- 평균 난이도가 낮고 쉬움 비율이 높은 담당자는 명시적으로 지적.
- 난이도 라벨: 쉬움(1.0-1.5) / 보통-쉬움(1.5-2.5) / 보통(2.5-3.5) / 어려움(3.5-4.5) / 매우 어려움(4.5-5.0)

## 주요 이슈
- 지연, 반복 실패, 보고-실적 불일치, 무책임한 영역. 담당자 이름과 함께 구체적으로.

## 해결 방안
각 이슈에 대해 관리자가 즉시 취할 조치 (담당자 면담·재배치·교육·기한 재설정 등).

## 종합 의견
이번 기간의 핵심 문제 + 즉시 조치 우선순위.

규칙:
- 한국어, 불릿. 짧고 단호하게.
- **추측·미사여구·격려성 표현 금지**. 데이터로 입증 안 되면 평가하지 마세요.
- 활동 0건은 "활동 없음 — 보고 내용 검증 필요" 로 명시.
- 담당자 이름은 그대로 표기.
PROMPT;

        $userPrompt = "기간: {$rangeLabel}\n"
            . "프로젝트: {$project->name}\n\n"
            . "### 담당자별 정량 지표 — 프로젝트 영역 (시스템 산출)\n{$metricsTable}\n\n"
            . "### 담당자별 정량 지표 — 공통 영역 (어느 프로젝트 키워드와도 매칭되지 않은 파일들의 활동)\n{$commonMetricsTable}\n\n"
            . "### 위클리 보고서 원본\n{$reportSection}\n\n"
            . "### 유지보수 SR 활동 ({$maintRequests->count()}건)\n{$srSection}\n\n"
            . "### WITHWORKS Git 커밋 — 프로젝트 영역 ({$commits->count()}건)\n{$commitSection}\n\n"
            . "### WITHWORKS Git 커밋 — 공통 영역 ({$commonCommits->count()}건)\n{$commonCommitSection}";

        try {
            $orchestrator = new AiOrchestrator(
                $settings->anthropicKey(),
                $settings->openaiKey(),
                $settings->manusKey(),
                $settings->manusEndpoint()
            );
            ['text' => $text] = $orchestrator->chatRawDirect(
                [['role' => 'user', 'content' => $userPrompt]],
                $systemPrompt
            );

            // 최종 content = 정량 표 (프로젝트 + 공통 분리) + AI 서술
            $commonBlock = $commonCommits->isNotEmpty()
                ? "\n\n## 📂 공통 영역 (어느 프로젝트 키워드와도 매칭되지 않은 파일)\n\n{$commonMetricsTable}\n"
                : '';
            $content = "## 📊 담당자별 정량 지표 — 프로젝트 영역\n\n기간: **{$rangeLabel}**\n\n{$metricsTable}"
                . $commonBlock
                . "\n\n---\n\n"
                . trim($text);

            // DB 저장 (upsert) — full 은 week_start_date=null, weekly 는 주차일자
            $keys = [
                'project_id'      => $project->id,
                'scope_key'       => WeeklyAiSummary::buildScopeKey($project->id, []),
                'summary_type'    => $type,
                'week_start_date' => $type === 'weekly' ? $weekDate : null,
            ];

            WeeklyAiSummary::updateOrCreate($keys, [
                'generated_by' => auth()->id(),
                'content'      => $content,
                'metrics'      => [
                    'project' => $metrics,
                    'common'  => $commonMetrics,
                ],
            ]);

            // weekly 타입이고 withworks 연결된 프로젝트면 — 담당자별 위클리 초안 자동 생성 (기존 보고서는 건너뜀)
            $autoCreated = 0;
            if ($type === 'weekly' && $weekDate && $isWithworksLinked) {
                $autoCreated = $this->autoGenerateAssigneeWeeklies(
                    $project, $weekDate, $rangeStart, $rangeEnd, $commits, $maintRequests
                );
            }

            return response()->json([
                'content'           => $content,
                'generated_at'      => now()->format('Y.m.d H:i'),
                'generated_by'      => auth()->user()->name,
                'metrics'           => $metrics,
                'common_metrics'    => $commonMetrics,
                'commit_details'    => $this->serializeCommitsForUi($commits),
                'common_commit_details' => $this->serializeCommitsForUi($commonCommits),
                'weekly_auto_created' => $autoCreated,
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            Log::warning('[WeeklyAiSummary] 생성 실패: ' . $e->getMessage());

            $raw = $e->getMessage();
            if (str_contains($raw, 'credit balance') || str_contains($raw, 'insufficient') || str_contains($raw, 'quota')) {
                $msg = '웍스 크레딧 또는 한도가 초과되었습니다.';
            } elseif (str_contains($raw, 'NO_KEY') || str_contains($raw, '사용 가능한 웍스')) {
                $msg = '웍스 API 키가 설정되어 있지 않습니다.';
            } else {
                $msg = '웍스 서머리 생성 중 오류가 발생했습니다.';
            }
            return response()->json(['error' => $msg], 500);
        }
    }

    /**
     * 기간 결정 helper
     *  - full   = 이전 달력 월 전체 (예: 5월 실행 → 4/1 00:00 ~ 4/30 23:59:59). 이번 달은 포함 안 됨.
     *  - weekly = 선택 주차 (월요일 ~ 일요일)
     */
    private function resolveRange(string $type, Project $project, ?string $week, ?string $rs, ?string $re): array
    {
        if ($type === 'weekly' && $week) {
            // weekly = 월~금 (5일)
            $start = Carbon::parse($week)->startOfDay();
            $end   = $start->copy()->addDays(5)->subSecond();
            return [$start, $end, '주차', $week, $week, null];
        }
        if ($type === 'this_month') {
            // 이번 달 1일 ~ 오늘
            $start = now()->startOfMonth();
            $end   = now()->endOfDay();
            return [$start, $end, '이번 달 (' . $start->format('Y-m-d') . ' ~ ' . $end->format('Y-m-d') . ')', null, null, null];
        }
        // full = 이전 달력 월
        $start = now()->subMonthNoOverflow()->startOfMonth();
        $end   = now()->subMonthNoOverflow()->endOfMonth();
        return [$start, $end, '지난 달 (' . $start->format('Y-m') . ')', null, null, null];
    }

    /**
     * 담당자별 메트릭 집계 — 이메일 기반 그룹화.
     * 같은 이메일이면 위클리·SR·커밋이 한 행으로 합쳐짐 (이름 표기가 달라도 동일 사용자로 인식).
     * 이메일이 없는 데이터는 "no-email:<name>" 별도 키로 처리 (혼합 방지).
     */
    private function buildAssigneeMetrics($reports, $maintRequests, $commits): array
    {
        $map = [];

        $ensure = function (?string $email, string $displayName) use (&$map): string {
            $key = $email ? mb_strtolower(trim($email)) : ('no-email:' . $displayName);
            if (!isset($map[$key])) {
                $map[$key] = [
                    'name'           => $displayName,
                    'email'          => $email,
                    'reports'        => 0,
                    'sr_completed'   => 0,
                    'sr_in_progress' => 0,
                    'sr_assigned'    => 0,
                    'commits'        => 0,
                    'insertions'     => 0,
                    'deletions'      => 0,
                    'difficulty_sum' => 0.0,
                    'difficulty_n'   => 0,    // 평균 계산용 분모
                    'diff_easy'      => 0,
                    'diff_medium'    => 0,
                    'diff_hard'      => 0,
                    'diff_critical'  => 0,
                ];
            } else {
                // 같은 이메일에 더 신뢰도 높은 표시명(User.name) 이 들어오면 갱신
                if (empty($map[$key]['name']) || $map[$key]['name'] === 'unknown') {
                    $map[$key]['name'] = $displayName;
                }
            }
            return $key;
        };

        foreach ($reports as $r) {
            $email = $r->user?->email;
            $name  = $r->user?->name ?: ($r->author_name ?: 'unknown');
            $key = $ensure($email, $name);
            $map[$key]['reports']++;
        }

        foreach ($maintRequests as $sr) {
            // 매핑 우선순위: maint_users.user.email → 그 외엔 이메일 없음(no-email 키)
            $email = $sr->assignee?->user?->email;
            $name  = $sr->assignee?->user?->name ?: ($sr->assignee?->name ?: '담당자 미지정');
            $key = $ensure($email, $name);
            $map[$key]['sr_assigned']++;
            if ($sr->status === 'completed' || !empty($sr->completed_at)) {
                $map[$key]['sr_completed']++;
            } elseif (in_array($sr->status, ['in_progress', 'pending_check', 'discussion_needed', 'review_requested', 'review_again'], true)) {
                $map[$key]['sr_in_progress']++;
            }
        }

        foreach ($commits as $c) {
            // 매핑 우선순위: git author_email → 그 다음 user 관계의 email
            $email = $c->author_email ?: $c->user?->email;
            $name  = $c->user?->name ?: ($c->author_name ?: 'unknown');
            $key = $ensure($email, $name);
            $map[$key]['commits']++;
            // partitionCommitsByPrefix 가 분리한 부분 LOC 가 있으면 그것을 사용
            $map[$key]['insertions'] += (int) ($c->attr_add ?? $c->insertions);
            $map[$key]['deletions']  += (int) ($c->attr_del ?? $c->deletions);
            // 난이도 집계 (값이 있을 때만)
            $diffVal = $c->attr_difficulty ?? $c->difficulty;
            if ($diffVal !== null) {
                $d = (float) $diffVal;
                $map[$key]['difficulty_sum'] += $d;
                $map[$key]['difficulty_n']++;
                if      ($d < 1.5) $map[$key]['diff_easy']++;
                elseif  ($d < 3.5) $map[$key]['diff_medium']++;
                elseif  ($d < 4.5) $map[$key]['diff_hard']++;
                else               $map[$key]['diff_critical']++;
            }
        }

        // 평균 난이도 계산 + 라벨
        foreach ($map as &$row) {
            $row['difficulty_avg']   = $row['difficulty_n'] > 0
                ? round($row['difficulty_sum'] / $row['difficulty_n'], 1)
                : null;
            $row['difficulty_label'] = \App\Models\GitCommit::difficultyLabel($row['difficulty_avg']);
        }
        unset($row);

        // 정렬: 활동 합산 내림차순
        uasort($map, fn($a, $b) =>
            ($b['commits'] + $b['sr_completed'] + $b['reports'])
            <=> ($a['commits'] + $a['sr_completed'] + $a['reports']));

        return array_values($map);
    }

    private function renderMetricsTable(array $metrics): string
    {
        if (empty($metrics)) return '활동 없음';
        $lines = [
            '| 담당자 | 위클리 | SR 완료 | SR 진행 | SR 배정 | 커밋 | +라인 | -라인 | 난이도 평균 | 난이도 분포 (E/M/H/C) |',
            '| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |',
        ];
        foreach ($metrics as $m) {
            $diffAvg = $m['difficulty_avg'] !== null
                ? sprintf('%.1f (%s)', $m['difficulty_avg'], $m['difficulty_label'])
                : '—';
            $diffDist = $m['commits'] > 0
                ? sprintf('%d/%d/%d/%d', $m['diff_easy'], $m['diff_medium'], $m['diff_hard'], $m['diff_critical'])
                : '—';
            $lines[] = sprintf(
                '| %s | %d | %d | %d | %d | %d | %d | %d | %s | %s |',
                $m['name'], $m['reports'], $m['sr_completed'], $m['sr_in_progress'], $m['sr_assigned'],
                $m['commits'], $m['insertions'], $m['deletions'], $diffAvg, $diffDist,
            );
        }
        return implode("\n", $lines);
    }

    private function renderReportsForAi($reports): string
    {
        if ($reports->isEmpty()) return '(보고서 없음)';
        $out = [];
        foreach ($reports as $r) {
            $completed  = $r->tasks->where('section', 'current_week')->where('status', 'completed')->pluck('task_name')->implode(', ');
            $inProgress = $r->tasks->where('section', 'current_week')->where('status', 'in_progress')->pluck('task_name')->implode(', ');
            $nextWeek   = $r->tasks->where('section', 'next_week')->pluck('task_name')->implode(', ');
            $out[] = "[{$r->week_label}] {$r->author_name}"
                . ($r->team_name ? " ({$r->team_name})" : '') . "\n"
                . "- 요약: " . mb_strimwidth(strip_tags($r->summary ?? '없음'), 0, 500, '…') . "\n"
                . "- 완료: " . ($completed ?: '없음') . "\n"
                . "- 진행: " . ($inProgress ?: '없음') . "\n"
                . "- 차주: " . ($nextWeek ?: '없음')
                . ($r->special_notes ? "\n- 특이사항: {$r->special_notes}" : '');
        }
        return implode("\n\n", $out);
    }

    private function renderMaintForAi($maints): string
    {
        if ($maints->isEmpty()) return '(SR 없음)';
        $out = [];
        foreach ($maints->take(50) as $sr) {  // 너무 많으면 50건만 컨텍스트
            $assignee = optional($sr->assignee)->name ?: '미지정';
            $title    = mb_strimwidth((string) $sr->summary, 0, 80, '…');
            $out[] = "- [{$sr->status}] {$title} (담당: {$assignee}, 요청일: " . optional($sr->request_date)->format('Y-m-d') . ", 완료일: " . optional($sr->completed_at)->format('Y-m-d') . ')';
        }
        if ($maints->count() > 50) $out[] = '… (총 ' . $maints->count() . '건 중 50건 표시)';
        return implode("\n", $out);
    }

    private function renderCommitsForAi($commits): string
    {
        if ($commits->isEmpty()) return '(커밋 없음)';
        $out = [];
        foreach ($commits->take(80) as $c) {
            $who  = $c->user?->name ?: $c->author_name;
            $subj = mb_strimwidth((string) $c->subject, 0, 100, '…');
            $add  = (int) ($c->attr_add ?? $c->insertions);
            $del  = (int) ($c->attr_del ?? $c->deletions);
            $diffVal = $c->attr_difficulty ?? $c->difficulty;
            $diff = $diffVal !== null ? sprintf(' [난이도 %.1f]', (float) $diffVal) : '';
            // 수정 파일 경로 일부 (최대 5개) — partition 후 attr_files 우선
            $files = $c->attr_files ?? (is_array($c->files_json) ? $c->files_json : []);
            $paths = '';
            if (!empty($files)) {
                $sample = array_slice($files, 0, 5);
                $names  = array_map(fn($f) => (string) ($f['path'] ?? ''), $sample);
                $more   = count($files) > 5 ? ' 외 ' . (count($files) - 5) . '건' : '';
                $paths  = '  ↳ 파일: ' . implode(', ', array_filter($names)) . $more;
            }
            $out[] = "- {$c->committed_at?->format('Y-m-d')} · {$who} · +{$add}/-{$del}{$diff} · {$subj}"
                  . ($paths ? "\n{$paths}" : '');
        }
        if ($commits->count() > 80) $out[] = '… (총 ' . $commits->count() . '건 중 80건 표시)';
        return implode("\n", $out);
    }

    /**
     * 커밋 컬렉션을 path_prefix 기준으로 (프로젝트 영역, 공통 영역) 으로 분리.
     *  - 매칭 방식: files_json[*].path 에 keyword 가 포함되는지 (str_contains)
     *  - 한 커밋이 양쪽 모두 해당하면 — 매칭된 파일들만 떼어내 양쪽에 모두 포함
     *  - LOC/난이도는 떼어낸 파일들 기준으로 재계산
     *  - 어느 prefix 와도 매칭 안 되는 파일들 → "공통" 그룹 (다른 프로젝트 영역은 제외)
     *  - files_json 비어있는 커밋은 공통으로만 (영역 판정 불가)
     */
    private function partitionCommitsByPrefix($commits, ?string $myPrefix, array $allPrefixes): array
    {
        // 정규화 — 빈 값 제거, 중복 제거
        $allPrefixes = array_values(array_filter(array_unique(array_map('trim', $allPrefixes))));

        $projectCommits = [];
        $commonCommits  = [];

        foreach ($commits as $c) {
            $files = is_array($c->files_json) ? $c->files_json : [];

            // 파일 정보가 없으면 영역 판정 불가 → 공통으로
            if (empty($files)) {
                $clone = clone $c;
                $clone->attr_files      = [];
                $clone->attr_add        = (int) $c->insertions;
                $clone->attr_del        = (int) $c->deletions;
                $clone->attr_difficulty = $c->difficulty;
                $commonCommits[] = $clone;
                continue;
            }

            $matched   = [];   // 내 prefix 매칭
            $unmatched = [];   // 어느 prefix 와도 매칭 안 됨 (공통)
            foreach ($files as $f) {
                $path = (string) ($f['path'] ?? '');
                if ($myPrefix !== null && $myPrefix !== '' && str_contains($path, $myPrefix)) {
                    $matched[] = $f;
                    continue;
                }
                // 다른 known prefix 와 매칭되면 그 프로젝트 영역 → 여기선 제외
                $matchedOther = false;
                foreach ($allPrefixes as $p) {
                    if ($p === $myPrefix) continue;
                    if (str_contains($path, $p)) { $matchedOther = true; break; }
                }
                if (!$matchedOther) $unmatched[] = $f;
            }

            if (!empty($matched)) {
                $clone = clone $c;
                $clone->attr_files      = $matched;
                $clone->attr_add        = (int) array_sum(array_column($matched, 'additions'));
                $clone->attr_del        = (int) array_sum(array_column($matched, 'deletions'));
                $clone->attr_difficulty = $this->recomputeDifficulty($clone->attr_add, $clone->attr_del, count($matched), (string) $c->subject);
                $projectCommits[] = $clone;
            }
            if (!empty($unmatched)) {
                $clone = clone $c;
                $clone->attr_files      = $unmatched;
                $clone->attr_add        = (int) array_sum(array_column($unmatched, 'additions'));
                $clone->attr_del        = (int) array_sum(array_column($unmatched, 'deletions'));
                $clone->attr_difficulty = $this->recomputeDifficulty($clone->attr_add, $clone->attr_del, count($unmatched), (string) $c->subject);
                $commonCommits[] = $clone;
            }
        }

        return [collect($projectCommits), collect($commonCommits)];
    }

    // ─── SR 전용 서머리 (프로젝트 무관) ───────────────────────────────

    /** 사용자가 선택한 SR 회사들 (관리자/SR담당자=전체, 일반=자기 회사만) 검증 후 반환 */
    private function authorizeSrCompanies(array $srCompanyIds): array
    {
        $user = auth()->user();
        $isPrivileged = $user->isAdmin() || (bool) ($user->is_sr_agent ?? false);

        $q = \App\Models\CompanyGroup::whereIn('id', $srCompanyIds)->where('shows_in_sr_menu', true);
        if (!$isPrivileged) {
            if (!$user->company_group_id) abort(403);
            $q->where('id', $user->company_group_id);
        }
        return $q->pluck('id')->all();
    }

    public function srShow(Request $request): JsonResponse
    {
        $request->validate([
            'type'             => 'required|in:full,this_month,weekly',
            'week'             => 'nullable|date',
            'sr_company_ids'   => 'required|array|min:1',
            'sr_company_ids.*' => 'integer',
        ]);
        $srCompanyIds = $this->authorizeSrCompanies((array) $request->input('sr_company_ids', []));
        if (empty($srCompanyIds)) return response()->json(['summary' => null]);

        $type     = $request->input('type');
        $weekDate = $request->input('week');
        if ($type === 'weekly' && !$weekDate) return response()->json(['summary' => null]);

        $scopeKey = WeeklyAiSummary::buildScopeKey(null, $srCompanyIds);
        $q = WeeklyAiSummary::where('scope_key', $scopeKey)->where('summary_type', $type);
        if ($type === 'weekly') $q->where('week_start_date', $weekDate);
        else                    $q->whereNull('week_start_date');

        $summary = $q->latest('updated_at')->first();
        if (!$summary) return response()->json(['summary' => null]);

        $isStale = $summary->updated_at && $summary->updated_at->lt(now()->subDays(7));

        // SR 전용 기간 결정 + commit_details
        [$rangeStart, $rangeEnd] = $this->resolveSrRange($type, $weekDate);
        [$commitDetails, $commonCommitDetails] = $this->loadSrCommitDetails($srCompanyIds, $rangeStart, $rangeEnd);

        return response()->json([
            'summary' => [
                'content'              => $summary->content,
                'generated_at'         => $summary->updated_at->format('Y.m.d H:i'),
                'generated_by'         => $summary->generatedBy?->name ?? '',
                'metrics'              => $summary->metrics,
                'commit_details'       => $commitDetails,
                'common_commit_details'=> $commonCommitDetails,
                'is_stale'             => $isStale,
                'stale_days'           => $summary->updated_at ? (int) now()->diffInDays($summary->updated_at) : null,
            ],
        ]);
    }

    public function srGenerate(Request $request): JsonResponse
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $request->validate([
            'type'             => 'required|in:full,this_month,weekly',
            'week'             => 'nullable|date',
            'sr_company_ids'   => 'required|array|min:1',
            'sr_company_ids.*' => 'integer',
        ]);
        $srCompanyIds = $this->authorizeSrCompanies((array) $request->input('sr_company_ids', []));
        if (empty($srCompanyIds)) return response()->json(['error' => 'SR 회사 권한이 없습니다.'], 403);

        $type     = $request->input('type');
        $weekDate = $request->input('week');
        if ($type === 'weekly' && !$weekDate) return response()->json(['error' => '주차를 선택해주세요.'], 422);

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) {
            return response()->json(['error' => '웍스 API 키가 설정되지 않았습니다. 관리자에게 문의해주세요.'], 422);
        }

        [$rangeStart, $rangeEnd] = $this->resolveSrRange($type, $weekDate);
        $rangeLabel = $rangeStart && $rangeEnd
            ? $rangeStart->format('Y-m-d') . ' ~ ' . $rangeEnd->format('Y-m-d')
            : '전체 기간';

        // 선택된 회사들 — uses_withworks 면 자동 Git sync
        $companies = \App\Models\CompanyGroup::whereIn('id', $srCompanyIds)->get(['id', 'name', 'path_prefix', 'uses_withworks']);
        if ($companies->where('uses_withworks', true)->isNotEmpty()) {
            try {
                // 증분 sync — DB 최신 커밋 이후만 fetch
                app(\App\Services\WithWorks\WithWorksGitIngestService::class)
                    ->sync(null, null, auth()->id());
            } catch (\Throwable $e) {
                SystemErrorLog::record($e, 'warning');
            }
        }

        // 1) 선택 회사들 소속 프로젝트의 위클리 보고서
        $projectIds = \App\Models\Project::whereIn('company_group_id', $srCompanyIds)->pluck('id');
        $reportsQ = WeeklyReport::whereIn('project_id', $projectIds)->with(['tasks', 'user:id,name,email']);
        if ($type === 'weekly' && $weekDate) {
            $reportsQ->where('week_start_date', $weekDate);
        }
        $reports = $reportsQ->orderBy('week_start_date')->get();

        // 2) SR
        $srQ = MaintRequest::with(['assignee.user:id,name,email'])
            ->whereIn('company_group_id', $srCompanyIds);
        if ($rangeStart && $rangeEnd) {
            $srQ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('completed_at', [$rangeStart, $rangeEnd])
                  ->orWhereBetween('request_date', [$rangeStart, $rangeEnd]);
            });
        }
        $maintRequests = $srQ->orderBy('request_date')->get();

        // 3) Git 커밋 — 선택 회사 소속 사용자들의 author 커밋
        $memberUserIds = User::whereIn('company_group_id', $srCompanyIds)->pluck('id');
        $commits = collect(); $commonCommits = collect();
        if ($memberUserIds->isNotEmpty()) {
            $commitsQ = GitCommit::with('user:id,name,email')
                ->where('source', 'withworks')
                ->whereIn('user_id', $memberUserIds);
            if ($rangeStart && $rangeEnd) $commitsQ->whereBetween('committed_at', [$rangeStart, $rangeEnd]);
            $rawCommits = $commitsQ->orderBy('committed_at')->get();

            // 선택 회사들의 prefix 합집합. 매칭된 파일들은 "SR 회사 영역", 그 외는 "공통".
            $companyPrefixes = $companies->pluck('path_prefix')->filter()->values()->all();
            $allPrefixes = \App\Models\CompanyGroup::whereNotNull('path_prefix')->pluck('path_prefix')->all();
            [$commits, $commonCommits] = $this->partitionCommitsByPrefixes($rawCommits, $companyPrefixes, $allPrefixes);
        }

        if ($reports->isEmpty() && $maintRequests->isEmpty() && $commits->isEmpty() && $commonCommits->isEmpty()) {
            return response()->json(['error' => '분석할 데이터가 없습니다 (보고서·SR·커밋 모두 없음).'], 422);
        }

        $metrics       = $this->buildAssigneeMetrics($reports, $maintRequests, $commits);
        $commonMetrics = $this->buildAssigneeMetrics(collect(), collect(), $commonCommits);

        $metricsTable       = $this->renderMetricsTable($metrics);
        $commonMetricsTable = $this->renderMetricsTable($commonMetrics);
        $reportSection      = $this->renderReportsForAi($reports);
        $srSection          = $this->renderMaintForAi($maintRequests);
        $commitSection      = $this->renderCommitsForAi($commits);
        $commonCommitSection = $this->renderCommitsForAi($commonCommits);

        $systemPrompt = <<<PROMPT
당신은 엄정한 엔지니어링 매니저입니다. 아래 데이터를 **논리적이고 냉정하게** 평가합니다.
모든 판단에는 **데이터 근거(어느 커밋·어느 파일·어떤 수치)** 가 함께 제시돼야 합니다.
추측·온정주의·격려성 표현 거부.

마크다운 헤딩(##)으로 섹션 구분, 한국어, 불릿, 짧고 단호하게.

## 담당자별 업무 평가
각 담당자를 **반드시 `### 이름` 헤딩으로 구분**하세요. 아래 4개 불릿 순서대로 — **라벨**(우수/양호/개선 필요/위험, 남용 금지) / **주요 작업**(커밋 제목 2~3개 인용) / **보고-실적**(일치 여부 명시) / **약점·우려**.

## 난이도 분석
쉬운 변경만 반복한 사람 명시적 지적. 난이도 라벨: 쉬움(1.0-1.5)/보통-쉬움(1.5-2.5)/보통(2.5-3.5)/어려움(3.5-4.5)/매우 어려움(4.5-5.0).

## 주요 이슈
지연·반복 실패·보고-실적 불일치를 담당자 이름과 함께 구체적으로.

## 해결 방안
관리자가 즉시 취할 조치 (면담·재배치·교육·기한 재설정).

## 종합 의견
핵심 문제 + 즉시 조치 우선순위.
PROMPT;
        $companyNames = $companies->pluck('name')->implode(', ');
        $userPrompt = "기간: {$rangeLabel}\nSR 회사: {$companyNames}\n\n"
            . "### 담당자별 정량 지표 — 회사 영역\n{$metricsTable}\n\n"
            . "### 담당자별 정량 지표 — 공통 영역\n{$commonMetricsTable}\n\n"
            . "### 위클리 보고서\n{$reportSection}\n\n"
            . "### 유지보수 SR ({$maintRequests->count()}건)\n{$srSection}\n\n"
            . "### Git 커밋 — 회사 영역 ({$commits->count()}건)\n{$commitSection}\n\n"
            . "### Git 커밋 — 공통 영역 ({$commonCommits->count()}건)\n{$commonCommitSection}";

        try {
            $orchestrator = new AiOrchestrator(
                $settings->anthropicKey(), $settings->openaiKey(),
                $settings->manusKey(), $settings->manusEndpoint()
            );
            ['text' => $text] = $orchestrator->chatRawDirect(
                [['role' => 'user', 'content' => $userPrompt]], $systemPrompt
            );

            $commonBlock = $commonCommits->isNotEmpty()
                ? "\n\n## 📂 공통 영역\n\n{$commonMetricsTable}\n"
                : '';
            $content = "## 📊 담당자별 정량 지표 — SR 회사 영역\n\n기간: **{$rangeLabel}**\nSR 회사: **{$companyNames}**\n\n{$metricsTable}"
                . $commonBlock . "\n\n---\n\n" . trim($text);

            $scopeKey = WeeklyAiSummary::buildScopeKey(null, $srCompanyIds);
            $keys = [
                'project_id'      => null,
                'scope_key'       => $scopeKey,
                'summary_type'    => $type,
                'week_start_date' => $type === 'weekly' ? $weekDate : null,
            ];
            WeeklyAiSummary::updateOrCreate($keys, [
                'sr_company_ids' => $srCompanyIds,
                'generated_by'   => auth()->id(),
                'content'        => $content,
                'metrics'        => ['project' => $metrics, 'common' => $commonMetrics],
            ]);

            // weekly + SR 회사 선택 시 — 담당자별 위클리 초안 자동 생성
            $autoCreated = 0;
            if ($type === 'weekly' && $weekDate) {
                $autoCreated = $this->autoGenerateSrAssigneeWeeklies(
                    $weekDate, $srCompanyIds, $commits, $maintRequests
                );
            }

            return response()->json([
                'content'           => $content,
                'generated_at'      => now()->format('Y.m.d H:i'),
                'generated_by'      => auth()->user()->name,
                'metrics'           => $metrics,
                'common_metrics'    => $commonMetrics,
                'commit_details'    => $this->serializeCommitsForUi($commits),
                'common_commit_details' => $this->serializeCommitsForUi($commonCommits),
                'weekly_auto_created' => $autoCreated,
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['error' => '웍스 서머리 생성 중 오류가 발생했습니다.'], 500);
        }
    }

    /**
     * SR 전용 모드 위클리 자동 생성.
     * 멤버 사용자의 (회사) 첫 프로젝트를 default project_id 로. 그것도 없으면 스킵.
     */
    private function autoGenerateSrAssigneeWeeklies(
        string $weekDate, array $srCompanyIds, $commits, $maintRequests
    ): int
    {
        $memberUsers = User::whereIn('company_group_id', $srCompanyIds)
            ->whereNotNull('email')
            ->get(['id', 'name', 'email', 'company_group_id']);

        // 회사별 default 프로젝트 (회사 소속 프로젝트 중 첫번째)
        $companyDefaultProject = \App\Models\Project::whereIn('company_group_id', $srCompanyIds)
            ->orderBy('id')
            ->get(['id', 'company_group_id'])
            ->groupBy('company_group_id')
            ->map(fn($g) => $g->first()->id);

        $byUser = [];
        foreach ($memberUsers as $u) $byUser[$u->id] = ['commits' => [], 'srs' => []];

        foreach ($commits as $c) {
            if ($c->user_id && isset($byUser[$c->user_id])) {
                $byUser[$c->user_id]['commits'][] = $c;
            }
        }
        foreach ($maintRequests as $sr) {
            $u = $sr->assignee?->user;
            if ($u && isset($byUser[$u->id])) {
                $byUser[$u->id]['srs'][] = $sr;
            }
        }

        $created = 0;
        $weekStart  = Carbon::parse($weekDate);
        $year       = (int) $weekStart->isoFormat('GGGG');
        $weekNumber = (int) $weekStart->isoFormat('W');

        foreach ($memberUsers as $u) {
            $data = $byUser[$u->id];
            if (empty($data['commits']) && empty($data['srs'])) continue;

            $projectId = $companyDefaultProject[$u->company_group_id] ?? null;
            if (!$projectId) continue;   // 회사 default 프로젝트 없으면 스킵

            $exists = WeeklyReport::where('project_id', $projectId)
                ->where('user_id', $u->id)
                ->where('week_start_date', $weekStart->toDateString())
                ->exists();
            if ($exists) continue;

            $summary = $this->buildAutoReportSummary($data['commits'], $data['srs']);

            $report = WeeklyReport::create([
                'project_id'       => $projectId,
                'user_id'          => $u->id,
                'company_group_id' => $u->company_group_id,
                'team_name'        => null,
                'author_name'      => $u->name,
                'manager_name'     => null,
                'report_date'      => $weekStart->copy()->addDays(6),
                'year'             => $year,
                'week_number'      => $weekNumber,
                'week_start_date'  => $weekStart->toDateString(),
                'status'           => 'draft',
                'summary'          => $summary,
                'special_notes'    => null,
            ]);

            $sortOrder = 0;
            foreach ($data['commits'] as $c) {
                \App\Models\WeeklyReportTask::create([
                    'weekly_report_id' => $report->id,
                    'section'          => 'current_week',
                    'task_name'        => '[Git] ' . mb_strimwidth((string) $c->subject, 0, 200, '…'),
                    'status'           => 'completed',
                    'sort_order'       => ++$sortOrder,
                ]);
            }
            foreach ($data['srs'] as $sr) {
                $status = ($sr->status === 'completed' || $sr->completed_at) ? 'completed' : 'in_progress';
                \App\Models\WeeklyReportTask::create([
                    'weekly_report_id' => $report->id,
                    'section'          => 'current_week',
                    'task_name'        => '[SR] ' . mb_strimwidth((string) $sr->summary, 0, 200, '…'),
                    'status'           => $status,
                    'sort_order'       => ++$sortOrder,
                ]);
            }
            $created++;
        }

        return $created;
    }

    /** SR 전용 기간 helper — full=이전 달력 월, weekly=선택 주차. 이번 달은 포함 안 됨. */
    private function resolveSrRange(string $type, ?string $week): array
    {
        if ($type === 'weekly' && $week) {
            $start = Carbon::parse($week)->startOfDay();
            $end   = $start->copy()->addDays(5)->subSecond();
            return [$start, $end];
        }
        if ($type === 'this_month') {
            return [now()->startOfMonth(), now()->endOfDay()];
        }
        return [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()];
    }

    /** SR 전용 commit_details 로드 */
    private function loadSrCommitDetails(array $srCompanyIds, ?Carbon $rangeStart, ?Carbon $rangeEnd): array
    {
        $memberUserIds = User::whereIn('company_group_id', $srCompanyIds)->pluck('id');
        if ($memberUserIds->isEmpty()) return [[], []];

        $q = GitCommit::with('user:id,name,email')
            ->where('source', 'withworks')
            ->whereIn('user_id', $memberUserIds);
        if ($rangeStart && $rangeEnd) $q->whereBetween('committed_at', [$rangeStart, $rangeEnd]);
        $raw = $q->orderBy('committed_at')->get();

        $companyPrefixes = \App\Models\CompanyGroup::whereIn('id', $srCompanyIds)
            ->whereNotNull('path_prefix')->pluck('path_prefix')->all();
        $allPrefixes = \App\Models\CompanyGroup::whereNotNull('path_prefix')->pluck('path_prefix')->all();
        [$proj, $common] = $this->partitionCommitsByPrefixes($raw, $companyPrefixes, $allPrefixes);

        return [
            $this->serializeCommitsForUi($proj),
            $this->serializeCommitsForUi($common),
        ];
    }

    /** 복수 prefix 매칭 변형 — 선택 회사들의 prefix 합집합 매칭 */
    private function partitionCommitsByPrefixes($commits, array $myPrefixes, array $allPrefixes): array
    {
        $myPrefixes  = array_values(array_filter(array_unique(array_map('trim', $myPrefixes))));
        $allPrefixes = array_values(array_filter(array_unique(array_map('trim', $allPrefixes))));

        $projectCommits = []; $commonCommits = [];
        foreach ($commits as $c) {
            $files = is_array($c->files_json) ? $c->files_json : [];
            if (empty($files)) {
                $clone = clone $c;
                $clone->attr_files = []; $clone->attr_add = (int)$c->insertions;
                $clone->attr_del = (int)$c->deletions; $clone->attr_difficulty = $c->difficulty;
                $commonCommits[] = $clone;
                continue;
            }
            $matched = []; $unmatched = [];
            foreach ($files as $f) {
                $path = (string) ($f['path'] ?? '');
                $hitMine = false;
                foreach ($myPrefixes as $p) { if ($p !== '' && str_contains($path, $p)) { $hitMine = true; break; } }
                if ($hitMine) { $matched[] = $f; continue; }
                $hitOther = false;
                foreach ($allPrefixes as $p) {
                    if (in_array($p, $myPrefixes, true)) continue;
                    if ($p !== '' && str_contains($path, $p)) { $hitOther = true; break; }
                }
                if (!$hitOther) $unmatched[] = $f;
            }
            if (!empty($matched)) {
                $clone = clone $c;
                $clone->attr_files = $matched;
                $clone->attr_add = (int) array_sum(array_column($matched, 'additions'));
                $clone->attr_del = (int) array_sum(array_column($matched, 'deletions'));
                $clone->attr_difficulty = $this->recomputeDifficulty($clone->attr_add, $clone->attr_del, count($matched), (string) $c->subject);
                $projectCommits[] = $clone;
            }
            if (!empty($unmatched)) {
                $clone = clone $c;
                $clone->attr_files = $unmatched;
                $clone->attr_add = (int) array_sum(array_column($unmatched, 'additions'));
                $clone->attr_del = (int) array_sum(array_column($unmatched, 'deletions'));
                $clone->attr_difficulty = $this->recomputeDifficulty($clone->attr_add, $clone->attr_del, count($unmatched), (string) $c->subject);
                $commonCommits[] = $clone;
            }
        }
        return [collect($projectCommits), collect($commonCommits)];
    }

    public function srDownload(Request $request)
    {
        // Word 다운로드는 일단 미구현 — JSON 으로 안내
        return response()->json(['error' => 'SR 전용 서머리 Word 다운로드는 추후 지원합니다.'], 501);
    }

    /** UI 의 "Git 커밋 내역" 접힌 영역용 — 관리자/매니저만 노출 */
    private function serializeCommitsForUi($commits): array
    {
        $out = [];
        foreach ($commits as $c) {
            $files    = $c->attr_files ?? (is_array($c->files_json) ? $c->files_json : []);
            $branches = is_array($c->branches) ? $c->branches : ($c->branch ? [(string) $c->branch] : []);
            $out[] = [
                'sha'           => substr((string) $c->sha, 0, 7),
                'date'          => $c->committed_at?->format('Y-m-d'),
                'author'        => $c->user?->name ?: $c->author_name,
                'subject'       => (string) $c->subject,
                'add'           => (int) ($c->attr_add ?? $c->insertions),
                'del'           => (int) ($c->attr_del ?? $c->deletions),
                'difficulty'    => $c->attr_difficulty ?? $c->difficulty,
                'first_branch'  => $branches[0] ?? null,
                'last_branch'   => !empty($branches) ? end($branches) : null,
                'branches'      => $branches,
                'files_count'   => count($files),
                'files'         => array_map(
                    fn($f) => [
                        'path'      => (string) ($f['path'] ?? ''),
                        'status'    => (string) ($f['status'] ?? ''),
                        'additions' => (int) ($f['additions'] ?? 0),
                        'deletions' => (int) ($f['deletions'] ?? 0),
                    ],
                    array_slice($files, 0, 50)
                ),
            ];
        }
        return $out;
    }

    /** WithWorksGitIngestService::computeDifficulty 와 동일한 휴리스틱 (떼어낸 파일들의 부분 LOC 로 재계산) */
    private function recomputeDifficulty(int $add, int $del, int $files, string $subject): float
    {
        $loc = $add + $del;
        $score = match(true) {
            $loc < 30   => 1.0,
            $loc < 100  => 2.0,
            $loc < 300  => 3.0,
            $loc < 800  => 4.0,
            default     => 5.0,
        };
        if      ($files >= 15) $score += 1.0;
        elseif  ($files >= 6)  $score += 0.5;
        elseif  ($files >= 2)  $score += 0.3;
        $s = mb_strtolower($subject);
        $down = ['typo', 'wip', 'minor', 'docs', 'comment', 'readme', 'lint', 'format'];
        $up   = ['security', 'vuln', 'migration', 'schema', 'breaking', 'critical', 'hotfix', 'perf', 'optimi', 'refactor'];
        foreach ($down as $kw) if (str_contains($s, $kw)) { $score -= 0.5; break; }
        foreach ($up   as $kw) if (str_contains($s, $kw)) { $score += 0.5; break; }
        return round(max(1.0, min(5.0, $score)), 1);
    }

    /**
     * weekly 타입 AI 서머리 생성 직후 자동 호출 — 해당 주 활동 있는 담당자별 위클리 초안 자동 생성.
     *  - 기존 WeeklyReport 있는 사용자는 건너뜀 (수기 보고서 보호)
     *  - 데이터 소스: Git 커밋 + SR 처리 내역
     *  - 비용 절약을 위해 템플릿 기반 (AI 호출 없음). 담당자는 [편집] 화면에서 직접 보강.
     */
    private function autoGenerateAssigneeWeeklies(
        Project $project, string $weekDate, ?Carbon $rangeStart, ?Carbon $rangeEnd,
        $commits, $maintRequests
    ): int
    {
        // 프로젝트 멤버 사용자 로드 (이메일 기반)
        $memberUsers = User::whereIn('id',
            \App\Models\ProjectMember::where('project_id', $project->id)->pluck('user_id')
        )->get(['id', 'name', 'email', 'company_group_id']);

        // 사용자별 커밋·SR 집계
        $byUser = []; // user_id => ['commits' => [], 'srs' => []]
        foreach ($memberUsers as $u) $byUser[$u->id] = ['commits' => [], 'srs' => []];

        foreach ($commits as $c) {
            if ($c->user_id && isset($byUser[$c->user_id])) {
                $byUser[$c->user_id]['commits'][] = $c;
            }
        }
        foreach ($maintRequests as $sr) {
            $u = $sr->assignee?->user;
            if ($u && isset($byUser[$u->id])) {
                $byUser[$u->id]['srs'][] = $sr;
            }
        }

        $created = 0;
        $weekStart = Carbon::parse($weekDate);
        $year       = (int) $weekStart->isoFormat('GGGG');
        $weekNumber = (int) $weekStart->isoFormat('W');

        foreach ($memberUsers as $u) {
            $data = $byUser[$u->id];
            if (empty($data['commits']) && empty($data['srs'])) continue;  // 활동 없는 사용자 스킵

            // 이미 있으면 스킵 (수기 보고서 보호)
            $exists = WeeklyReport::where('project_id', $project->id)
                ->where('user_id', $u->id)
                ->where('week_start_date', $weekStart->toDateString())
                ->exists();
            if ($exists) continue;

            $summary = $this->buildAutoReportSummary($data['commits'], $data['srs']);

            $report = WeeklyReport::create([
                'project_id'       => $project->id,
                'user_id'          => $u->id,
                'company_group_id' => $u->company_group_id,
                'team_name'        => null,
                'author_name'      => $u->name,
                'manager_name'     => null,
                'report_date'      => $weekStart->copy()->addDays(6),
                'year'             => $year,
                'week_number'      => $weekNumber,
                'week_start_date'  => $weekStart->toDateString(),
                'status'           => 'draft',
                'summary'          => $summary,
                'special_notes'    => null,
            ]);

            // 커밋·SR 을 current_week 작업 태스크로 추가
            $sortOrder = 0;
            foreach ($data['commits'] as $c) {
                \App\Models\WeeklyReportTask::create([
                    'weekly_report_id' => $report->id,
                    'section'          => 'current_week',
                    'task_name'        => '[Git] ' . mb_strimwidth((string) $c->subject, 0, 200, '…'),
                    'status'           => 'completed',
                    'sort_order'       => ++$sortOrder,
                ]);
            }
            foreach ($data['srs'] as $sr) {
                $status = ($sr->status === 'completed' || $sr->completed_at) ? 'completed' : 'in_progress';
                \App\Models\WeeklyReportTask::create([
                    'weekly_report_id' => $report->id,
                    'section'          => 'current_week',
                    'task_name'        => '[SR] ' . mb_strimwidth((string) $sr->summary, 0, 200, '…'),
                    'status'           => $status,
                    'sort_order'       => ++$sortOrder,
                ]);
            }
            $created++;
        }
        return $created;
    }

    /** 자동 생성 위클리의 summary HTML 빌드 (템플릿) */
    private function buildAutoReportSummary($commits, $srs): string
    {
        $lines = ['<p><em>※ 시스템이 Git 커밋과 SR 처리 내역을 기반으로 자동 생성한 초안입니다. 보완 입력 가능.</em></p>'];

        if (!empty($commits)) {
            $lines[] = '<p><strong>Git 활동 (' . count($commits) . '건)</strong></p><ul>';
            foreach (array_slice($commits, 0, 20) as $c) {
                $subj = htmlspecialchars(mb_strimwidth((string) $c->subject, 0, 120, '…'), ENT_QUOTES, 'UTF-8');
                $date = optional($c->committed_at)->format('m-d');
                $lines[] = "<li>{$date} — {$subj}</li>";
            }
            if (count($commits) > 20) $lines[] = '<li>… 외 ' . (count($commits) - 20) . '건</li>';
            $lines[] = '</ul>';
        }

        if (!empty($srs)) {
            $lines[] = '<p><strong>SR 처리 (' . count($srs) . '건)</strong></p><ul>';
            foreach (array_slice($srs, 0, 20) as $sr) {
                $subj = htmlspecialchars(mb_strimwidth((string) $sr->summary, 0, 120, '…'), ENT_QUOTES, 'UTF-8');
                $status = htmlspecialchars((string) $sr->status, ENT_QUOTES, 'UTF-8');
                $lines[] = "<li>[{$status}] {$subj}</li>";
            }
            if (count($srs) > 20) $lines[] = '<li>… 외 ' . (count($srs) - 20) . '건</li>';
            $lines[] = '</ul>';
        }

        return implode("\n", $lines);
    }

    // ─── Word 다운로드 ───────────────────────────────────────────────

    public function download(Request $request, Project $project): BinaryFileResponse
    {
        $this->authorizeManager($project);

        $type     = $request->input('type', 'full');
        $weekDate = $type === 'weekly' ? $request->input('week') : null;

        $q = WeeklyAiSummary::where('project_id', $project->id)->where('summary_type', $type);
        if ($type === 'weekly') $q->where('week_start_date', $weekDate);
        else                    $q->whereNull('week_start_date');
        $summary = $q->latest('updated_at')->first();

        abort_if(!$summary, 404, '저장된 웍스 서머리가 없습니다. 먼저 생성해주세요.');

        $weekLabel = null;
        if ($type === 'weekly' && $weekDate) {
            $weekLabel = Carbon::parse($weekDate)->locale('ko')->isoFormat('YYYY년 M월 W주차');
        } elseif ($type === 'full') {
            $weekLabel = '지난 달 (' . $summary->updated_at->format('Y-m-d') . ' 기준)';
        }

        $generatedAt = $summary->updated_at->format('Y.m.d H:i');
        $generatedBy = $summary->generatedBy?->name ?? '';

        $writer   = new DocxWriter();
        $writer->buildAiSummary($project, $type, $weekLabel, $summary->content, $generatedAt, $generatedBy);

        $typeSlug = $type === 'full' ? 'full' : ($weekDate ?? 'weekly');
        $filename = $project->name . '_AI서머리_' . $typeSlug . '_' . now()->format('Ymd') . '.docx';
        $path     = storage_path('app/temp/' . $filename);

        $writer->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
