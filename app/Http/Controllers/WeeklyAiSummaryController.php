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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
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
        $this->applyMainMergedFilter($q);
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
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

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

        // 정형 보고서는 AI 호출 없음 — 룰 기반. AI 키 체크 제거.

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
            // main/master 머지 완료된 커밋만 — 실제 산출물 기준.
            $commitsQ = GitCommit::with('user:id,name,email')
                ->where('source', 'withworks')
                ->whereIn('user_id', $memberUserIds);
            $this->applyMainMergedFilter($commitsQ);
            if ($rangeStart && $rangeEnd) {
                $commitsQ->whereBetween('committed_at', [$rangeStart, $rangeEnd]);
            }
            $rawCommits = $commitsQ->orderBy('committed_at')->get();

            // path_prefix 매칭 (회사 단위) — 프로젝트의 소속 회사의 키워드 사용
            $myPrefix = $project->companyGroup?->path_prefix;
            $allPrefixes = \App\Models\CompanyGroup::whereNotNull('path_prefix')
                ->pluck('path_prefix')->all();
            [$commits, $commonCommits] = $this->partitionCommitsByPrefix($rawCommits, $myPrefix, $allPrefixes);
            // patch_id 중복 (cherry-pick/revert) 제거 — 같은 patch_id 의 두번째부터는 분석에서 제외
            $commits       = $this->dedupeByPatchId($commits);
            $commonCommits = $this->dedupeByPatchId($commonCommits);
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


        try {
            // 정형 보고서 빌드 (명세 §5.3) — AI 호출 폐기, 룰 기반 자동 생성
            $structured = $this->buildStructuredReport(
                $type, $rangeLabel, $rangeStart, $rangeEnd,
                $metrics, $commonMetrics, $commits, $commonCommits
            );
            // 컨텍스트에 프로젝트 표시
            $structured['report']['project'] = ['id' => $project->id, 'name' => $project->name];
            // AI 요약 (보조) — 키 있을 때만 시도
            // AI 요약 임시 비활성 — fatal error 진단 중
            // $aiSummary = $this->generateAiSummaryFromReport($structured['report']);
            // if ($aiSummary) $structured['report']['ai_summary'] = $aiSummary;
            $reportJson = json_encode($structured, JSON_UNESCAPED_UNICODE);

            // DB 저장 (upsert)
            $keys = [
                'project_id'      => $project->id,
                'scope_key'       => WeeklyAiSummary::buildScopeKey($project->id, []),
                'summary_type'    => $type,
                'week_start_date' => $type === 'weekly' ? $weekDate : null,
            ];
            WeeklyAiSummary::updateOrCreate($keys, [
                'generated_by' => auth()->id(),
                'content'      => $reportJson,   // 정형 JSON 직렬화
                'metrics'      => [
                    'project' => $metrics,
                    'common'  => $commonMetrics,
                ],
            ]);

            // 담당자별 위클리 자동 생성
            $autoCreated = 0;
            if ($type === 'weekly' && $weekDate && $isWithworksLinked) {
                $autoCreated = $this->autoGenerateAssigneeWeeklies(
                    $project, $weekDate, $rangeStart, $rangeEnd, $commits, $maintRequests
                );
            }

            // 매니저·관리자 이메일 알림 — 본문은 정형 데이터의 간단 요약
            $notifyCgIds = array_values(array_unique(array_filter(array_merge(
                $project->company_group_id ? [(int) $project->company_group_id] : [],
                $srCompanyIds
            ))));
            $mailBody = $this->buildMailBodyFromReport($structured['report']);
            $mailsSent = $this->notifyManagersAfterGenerate(
                $notifyCgIds, $project, $type, $weekDate, $mailBody, $rangeLabel
            );

            return response()->json(array_merge($structured, [
                'generated_at'   => now()->format('Y.m.d H:i'),
                'generated_by'   => auth()->user()->name,
                'metrics'        => $metrics,
                'common_metrics' => $commonMetrics,
                'commit_details' => $this->serializeCommitsForUi($commits),
                'common_commit_details' => $this->serializeCommitsForUi($commonCommits),
                'weekly_auto_created' => $autoCreated,
                'mails_sent'     => $mailsSent,
            ]));
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            Log::warning('[WeeklyAiSummary] 생성 실패: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'error'  => '웍스 서머리 생성 중 오류가 발생했습니다.',
                'detail' => $e->getMessage(),
                'at'     => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }

    /**
     * 각 담당자 실적을 보고 AI 가 보완해야 할 점 1~3개 bullet 코멘트 생성.
     * 한 번의 batch 호출. 실패 시 룰 기반 코멘트(generateAutoComments) 그대로 유지.
     */
    private function generateAiCommentsForAssignees(array $assignees, string $periodLabel): array
    {
        if (empty($assignees)) return $assignees;
        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) return $assignees;

        // AI 입력 — 담당자별 실적 markdown
        $lines = ["기간: {$periodLabel}", ''];
        foreach ($assignees as $a) {
            $g = $a['git'] ?? []; $s = $a['sr'] ?? [];
            $top = $a['top_repeated_files'][0] ?? null;
            $lines[] = "### {$a['name']}";
            $lines[] = "- Git: 커밋 " . ($g['commits'] ?? 0) . ", +" . ($g['added'] ?? 0) . "/-" . ($g['deleted'] ?? 0) . " LOC, 파일 " . ($g['files'] ?? 0) . " (unique " . ($g['unique_files'] ?? 0) . ", 다양성 " . ($g['diversity'] ?? '—') . ")";
            $lines[] = "- SR: 배정 " . ($s['assigned'] ?? 0) . ", 완료 " . ($s['completed'] ?? 0) . ", 재오픈 " . ($s['reopened'] ?? 0) . ", 가중 " . ($s['weighted'] ?? 0) . ", 평균처리 " . ($s['avg_handling_days'] ?? '—') . "일";
            if ($top) $lines[] = "- 최다 반복 파일: {$top['path']} ({$top['count']}회)";
            $lines[] = '';
        }
        $userPrompt = implode("\n", $lines);

        $systemPrompt = <<<P
당신은 SR 담당자 KPI 보고서 코멘트 작성 분석가입니다.

각 담당자별로 다음 두 묶음을 bullet 로 작성:

1) **SR + Git 처리 실적** (1~2 bullet) — 정량 사실만:
   예) "SR 15건 배정 / 14건 완료 (완료율 93%, 평균 처리 2.3일)"
   예) "Git 100건 commit · +5,000/-1,200 LOC · 파일 360 (unique 80, 다양성 0.22)"

2) **보완해야 할 점** (1~3 bullet) — 구체적 데이터 근거:
   예) "DataHeader.php 28회 수정 — 작업 단위 분할 필요"

규칙:
- 한국어. 사실 기반·논리적·냉정.
- **강점·칭찬·격려·"잘했다"·"좋다" 표현 절대 금지**. 실적은 수치 사실로만, 보완점은 구체 권고로.
- 추측 금지 — 입력 수치만 인용.
- 각 담당자를 "### 이름" 헤딩으로 구분.
- 각 bullet 은 "- {짧은 한 문장}" 형식.
- 실적 0 (커밋·SR·LOC 전부 0) 담당자는 "활동 없음 — 보고 내용 검증 필요" 한 줄만.
P;

        try {
            $orchestrator = new AiOrchestrator(
                $settings->anthropicKey(), $settings->openaiKey(),
                $settings->manusKey(), $settings->manusEndpoint()
            );
            ['text' => $text] = $orchestrator->chatRawDirect(
                [['role' => 'user', 'content' => $userPrompt]], $systemPrompt
            );
            $parsed = $this->parseAiCommentsByAssignee($text);
            foreach ($assignees as &$a) {
                $byName = $parsed[$a['name']] ?? null;
                if ($byName && !empty($byName)) {
                    $a['comments'] = $byName;
                }
            }
            unset($a);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e, 'warning');
        }
        return $assignees;
    }

    /** AI 응답 markdown 을 {name => [bullets]} 로 파싱 */
    private function parseAiCommentsByAssignee(string $text): array
    {
        $result = [];
        $currentName = null; $bullets = [];
        foreach (preg_split('/\R/', $text) as $line) {
            $trim = trim($line);
            if (preg_match('/^#{2,4}\s*(.+)$/u', $trim, $m)) {
                if ($currentName !== null) $result[$currentName] = $bullets;
                $currentName = trim($m[1]);
                $bullets = [];
            } elseif ($currentName !== null && preg_match('/^[ \t]*[-*•]\s+(.+)$/', $line, $m)) {
                $b = trim($m[1]);
                if ($b !== '') $bullets[] = $b;
            }
        }
        if ($currentName !== null) $result[$currentName] = $bullets;
        return $result;
    }

    /**
     * 정형 보고서 → AI 가 담당자별 1~2문장 + 팀 종합 1~2문장 요약 (보조).
     * AI 키 없으면 skip (정형 보고서는 그대로 유지).
     */
    private function generateAiSummaryFromReport(array $report): ?array
    {
        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) return null;

        // AI 입력 = 정형 보고서의 핵심만 markdown 으로
        $lines = [];
        $lines[] = '기간: ' . ($report['period']['label'] ?? '');
        $lines[] = '';
        foreach ($report['assignees'] ?? [] as $a) {
            $g = $a['git'] ?? []; $s = $a['sr'] ?? []; $p = $a['penalty'] ?? []; $sc = $a['score'] ?? [];
            $top = $a['top_repeated_files'][0] ?? null;
            $lines[] = "### {$a['name']}";
            $lines[] = "- Git: 커밋 " . ($g['commits'] ?? 0) . ", +" . ($g['added'] ?? 0) . "/-" . ($g['deleted'] ?? 0) . " LOC, " . ($g['files'] ?? 0) . " 파일 (unique " . ($g['unique_files'] ?? 0) . ", 다양성 " . ($g['diversity'] ?? '—') . ")";
            $lines[] = "- SR: 배정 " . ($s['assigned'] ?? 0) . ", 완료 " . ($s['completed'] ?? 0) . ", 재오픈 " . ($s['reopened'] ?? 0) . ", 가중 " . ($s['weighted'] ?? 0);
            $lines[] = "- WeeklyScore: " . ($sc['final'] ?? 0) . " (raw " . ($sc['raw'] ?? 0) . ", 페널티 -" . ($p['final'] ?? 0) . ")";
            if ($top) $lines[] = "- 최다 반복 파일: {$top['path']} ({$top['count']}회)";
            if (!empty($a['comments'])) {
                foreach (array_slice($a['comments'], 0, 3) as $c) $lines[] = "- 룰 코멘트: {$c}";
            }
            $lines[] = '';
        }
        $userPrompt = implode("\n", $lines);

        $systemPrompt = <<<P
당신은 SR 담당자 KPI 보고서를 짧고 객관적으로 요약하는 분석가입니다.

다음 정형 데이터를 보고 작성하세요:

## 담당자별 요약
각 담당자를 "### {이름}" 헤딩으로 구분, 그 아래 **1~2문장**으로 핵심 요약. 강점·약점·관찰 사항 위주.

## 팀 종합
팀 전체의 핵심 패턴 **1~2문장**.

규칙:
- 한국어, 간결하고 사실 기반
- 정형 데이터에 있는 수치만 인용, 추측 금지
- AI 활용 자체에 대한 비난 금지
- 마크다운 헤딩 ## ### 사용
P;

        try {
            $orchestrator = new AiOrchestrator(
                $settings->anthropicKey(), $settings->openaiKey(),
                $settings->manusKey(), $settings->manusEndpoint()
            );
            ['text' => $text, 'provider' => $provider] = $orchestrator->chatRawDirect(
                [['role' => 'user', 'content' => $userPrompt]], $systemPrompt
            );
            return ['markdown' => trim($text), 'provider' => $provider];
        } catch (\Throwable $e) {
            SystemErrorLog::record($e, 'warning');
            return null;
        }
    }

    /** 정형 report → 메일 본문용 간단 markdown */
    private function buildMailBodyFromReport(array $report): string
    {
        $lines = [];
        $lines[] = '기간: ' . ($report['period']['label'] ?? '');
        $lines[] = '대상 담당자: ' . count($report['assignees'] ?? []) . '명';
        $lines[] = '';
        foreach ($report['assignees'] ?? [] as $a) {
            $lines[] = "## {$a['name']}";
            $lines[] = "- Git: 커밋 {$a['git']['commits']}건, +{$a['git']['added']}/-{$a['git']['deleted']} LOC, {$a['git']['files']} 파일";
            $lines[] = "- SR: 배정 {$a['sr']['assigned']}, 완료 {$a['sr']['completed']}, 재오픈 {$a['sr']['reopened']}";
            $lines[] = "- WeeklyScore: {$a['score']['final']} (페널티 {$a['penalty']['final']})";
            if (!empty($a['comments'])) {
                foreach ($a['comments'] as $c) $lines[] = "  · {$c}";
            }
            $lines[] = '';
        }
        return implode("\n", $lines);
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
                    'sr_carried_over'=> 0,    // 명세 §3.3 이월 — 기간 끝까지 미완료
                    'sr_reopened'    => 0,    // 명세 §3.3 재오픈 — reopen_count >= 1
                    'sr_handling_days_sum' => 0.0,
                    'sr_handling_n'  => 0,
                    'sr_weighted'    => 0,    // 명세 §3.3 가중 처리량 = Σ difficulty_score
                    'sr_score_n'     => 0,    // 점수 매핑된 SR 수
                    'commits'        => 0,
                    'insertions'     => 0,
                    'deletions'      => 0,
                    'file_paths'     => [],   // {path => count} — 반복 횟수 집계용
                    'file_changes'   => 0,    // 중복 포함 총 변경 수
                    'difficulty_sum' => 0.0,
                    'difficulty_n'   => 0,
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

        // SR 담당자(is_sr_agent=true) 모두 사전 등록 — 실적 0 이라도 카드 노출 유지
        User::where('is_sr_agent', true)->whereNotNull('email')
            ->get(['id', 'name', 'email'])
            ->each(fn($u) => $ensure($u->email, $u->name));

        foreach ($reports as $r) {
            $email = $r->user?->email;
            $name  = $r->user?->name ?: ($r->author_name ?: 'unknown');
            $key = $ensure($email, $name);
            $map[$key]['reports']++;
        }

        // MaintUser.user_id 가 null 인 경우 — 이름으로 User 매핑 lookup (cache 1회)
        static $nameToEmail = null;
        if ($nameToEmail === null) {
            $nameToEmail = User::whereNotNull('email')->pluck('email', 'name')->all();
        }

        foreach ($maintRequests as $sr) {
            $email = $sr->assignee?->user?->email;
            $name  = $sr->assignee?->user?->name ?: ($sr->assignee?->name ?: '담당자 미지정');
            // user 매핑이 없으면 assignee.name 으로 User 조회 (동명이인 위험 있으나 현실적 차선책)
            if (!$email && $sr->assignee?->name && isset($nameToEmail[$sr->assignee->name])) {
                $email = $nameToEmail[$sr->assignee->name];
            }
            $key = $ensure($email, $name);
            $map[$key]['sr_assigned']++;
            $isCompleted = ($sr->status === 'completed' || !empty($sr->completed_at));
            if ($isCompleted) {
                $map[$key]['sr_completed']++;
                if ($sr->difficulty_score) {
                    $map[$key]['sr_weighted'] += (int) $sr->difficulty_score;
                    $map[$key]['sr_score_n']++;
                }
                // 평균 처리 시간 (명세 §3.3) — assigned_at → completed_at
                if ($sr->assigned_at && $sr->completed_at) {
                    $days = Carbon::parse($sr->assigned_at)->diffInDays(Carbon::parse($sr->completed_at), false);
                    if ($days >= 0) {
                        $map[$key]['sr_handling_days_sum'] += $days;
                        $map[$key]['sr_handling_n']++;
                    }
                }
            } else {
                // 진행 중 (= 이월) — completed 아닌 모든 active SR
                if (in_array($sr->status, ['in_progress', 'pending_check', 'discussion_needed', 'review_requested', 'review_again', 'assigned'], true)) {
                    $map[$key]['sr_in_progress']++;
                    $map[$key]['sr_carried_over']++;
                }
            }
            // 재오픈 SR
            if ((int) ($sr->reopen_count ?? 0) >= 1) {
                $map[$key]['sr_reopened']++;
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
            // 다양성 — 파일 path 별 반복 횟수 집계
            $files = $c->attr_files ?? (is_array($c->files_json) ? $c->files_json : []);
            foreach ($files as $f) {
                $p = (string) ($f['path'] ?? '');
                if ($p === '') continue;
                $map[$key]['file_paths'][$p] = ($map[$key]['file_paths'][$p] ?? 0) + 1;
                $map[$key]['file_changes']++;
            }
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

        // 평균 난이도 + SR 평균 점수 + Git LOC factor + WeeklyScore (명세 §5.1)
        foreach ($map as &$row) {
            $row['difficulty_avg']   = $row['difficulty_n'] > 0
                ? round($row['difficulty_sum'] / $row['difficulty_n'], 1)
                : null;
            $row['difficulty_label'] = \App\Models\GitCommit::difficultyLabel($row['difficulty_avg']);
            $row['sr_avg_score']     = $row['sr_score_n'] > 0
                ? round($row['sr_weighted'] / $row['sr_score_n'], 2)
                : null;
            // §3.3 추가 SR 지표
            $row['sr_completion_rate'] = $row['sr_assigned'] > 0
                ? round(($row['sr_completed'] / $row['sr_assigned']) * 100, 1)
                : null;
            $row['sr_avg_handling_days'] = $row['sr_handling_n'] > 0
                ? round($row['sr_handling_days_sum'] / $row['sr_handling_n'], 1)
                : null;
            // Git LOC factor = log10(net_loc + 1) — 명세 §5.2 diminishing returns
            $netLoc = max(0, (int) $row['insertions'] - (int) $row['deletions']);
            $row['git_loc_factor']   = round(log10($netLoc + 1), 2);

            // 다양성 가중치 = unique 파일 수 / 전체 변경 수 (0~1). 같은 파일 반복 수정 시 1보다 작아짐.
            $uniqueFiles = count($row['file_paths']);
            $totalChg    = max(1, (int) $row['file_changes']);
            $diversity   = min(1.0, $uniqueFiles / $totalChg);
            $row['git_unique_files']  = $uniqueFiles;
            $row['git_file_changes']  = $row['file_changes'];
            $row['git_diversity']     = round($diversity, 2);

            // Git raw 점수 — commits × LOC factor × 다양성
            $row['git_raw_score']    = round($row['commits'] * $row['git_loc_factor'] * $diversity, 2);

            // 반복 수정 Top 10 (2회 이상만) — 담당자별 어떤 파일을 몇 번 반복했는지
            $repeated = array_filter($row['file_paths'], fn($cnt) => $cnt >= 2);
            arsort($repeated);
            $row['top_repeated_files'] = array_slice(
                array_map(fn($p, $c) => ['path' => $p, 'count' => $c], array_keys($repeated), array_values($repeated)),
                0, 10
            );

            unset($row['file_paths']);
        }
        unset($row);

        // 팀 평균 (정규화 기준)
        $teamSrTotal  = array_sum(array_column($map, 'sr_weighted'));
        $teamGitTotal = array_sum(array_column($map, 'git_raw_score'));
        $n            = max(1, count($map));
        $teamSrAvg    = $teamSrTotal / $n;
        $teamGitAvg   = $teamGitTotal / $n;

        // WeeklyScore = α·NormSR + β·NormGit (명세 §5.1 — AI 활용 환경: 0.8 / 0.2)
        $alpha = 0.8; $beta = 0.2;
        foreach ($map as &$row) {
            $normSr  = $teamSrAvg  > 0 ? ($row['sr_weighted']    / $teamSrAvg)  : 0;
            $normGit = $teamGitAvg > 0 ? ($row['git_raw_score']  / $teamGitAvg) : 0;
            $row['norm_sr']           = round($normSr, 2);
            $row['norm_git']          = round($normGit, 2);
            $row['weekly_score_raw']  = round($alpha * $normSr + $beta * $normGit, 2);
        }
        unset($row);

        // 반복 커밋 페널티 §5.4 — 운영 결정으로 완전 제거. weekly_score = raw 그대로.
        foreach ($map as &$row) {
            $row['penalty_raw']   = 0;
            $row['penalty_final'] = 0;
            $row['penalty_detail']= ['file_repeat' => 0, 'trial_error' => 0, 'warned_files' => 0];
            $row['weekly_score']  = $row['weekly_score_raw'];
        }
        unset($row);

        // 정렬: SR 담당자 명시 우선순위 → 그 외 이름 가나다 → '담당자 미지정' 끝
        $priority = ['진세종', '정현우', '허정빈'];
        uasort($map, function ($a, $b) use ($priority) {
            $rank = function ($name) use ($priority) {
                if ($name === '담당자 미지정' || $name === 'unknown') return 9999;
                $i = array_search($name, $priority, true);
                return $i === false ? 1000 : $i;
            };
            $ra = $rank($a['name']);
            $rb = $rank($b['name']);
            if ($ra !== $rb) return $ra <=> $rb;
            return strcmp((string) $a['name'], (string) $b['name']);
        });

        return array_values($map);
    }

    private function renderMetricsTable(array $metrics): string
    {
        if (empty($metrics)) return '활동 없음';
        // 명세 §3.3 + §5.1 + §5.4 — SR 가중 처리량 + WeeklyScore + 반복 페널티
        $lines = [
            '| 담당자 | 위클리 | SR 배정 | SR 완료 | 완료율 | 처리일 | 이월 | 재오픈 | SR 가중 | 커밋 | +라인 | -라인 | Git 난이도 | 다양성 | Raw | 페널티 | WeeklyScore |',
            '| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |',
        ];
        foreach ($metrics as $m) {
            $diffAvg = $m['difficulty_avg'] !== null ? sprintf('%.1f', $m['difficulty_avg']) : '—';
            $srWeighted = ($m['sr_weighted'] ?? 0) > 0
                ? sprintf('%d (평균 %.1f)', $m['sr_weighted'], $m['sr_avg_score'] ?? 0)
                : '0';
            $diversity = isset($m['git_diversity'])
                ? sprintf('%.2f (%d/%d)', $m['git_diversity'], $m['git_unique_files'] ?? 0, $m['git_file_changes'] ?? 0)
                : '—';
            $rawScore = isset($m['weekly_score_raw']) ? sprintf('%.2f', $m['weekly_score_raw']) : '—';
            $penalty  = isset($m['penalty_final']) && $m['penalty_final'] > 0
                ? sprintf('−%.2f', $m['penalty_final'])
                : '0';
            $weeklyScore = isset($m['weekly_score'])
                ? sprintf('**%.2f**', $m['weekly_score'])
                : '—';
            $completionRate = isset($m['sr_completion_rate']) ? sprintf('%.1f%%', $m['sr_completion_rate']) : '—';
            $handlingDays   = isset($m['sr_avg_handling_days']) ? sprintf('%.1f일', $m['sr_avg_handling_days']) : '—';
            $lines[] = sprintf(
                '| %s | %d | %d | %d | %s | %s | %d | %d | %s | %d | %d | %d | %s | %s | %s | %s | %s |',
                $m['name'], $m['reports'],
                $m['sr_assigned'], $m['sr_completed'], $completionRate, $handlingDays,
                $m['sr_carried_over'] ?? $m['sr_in_progress'], $m['sr_reopened'] ?? 0,
                $srWeighted, $m['commits'], $m['insertions'], $m['deletions'], $diffAvg,
                $diversity, $rawScore, $penalty, $weeklyScore,
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
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

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

        // 정형 보고서는 AI 호출 없음

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

        // 3) Git 커밋 — SR 담당자(is_sr_agent=true) 의 author 커밋 전체
        //    SR 담당자는 처리 대상 회사 소속이 아닌 별도 회사(링크더랩 등)에서 일하므로 회사 매핑이 아닌 SR 담당자 플래그로 매칭.
        $memberUserIds = User::where('is_sr_agent', true)->whereNotNull('email')->pluck('id');
        $commits = collect(); $commonCommits = collect();
        if ($memberUserIds->isNotEmpty()) {
            $commitsQ = GitCommit::with('user:id,name,email')
                ->where('source', 'withworks')
                ->whereIn('user_id', $memberUserIds);
            $this->applyMainMergedFilter($commitsQ);
            if ($rangeStart && $rangeEnd) $commitsQ->whereBetween('committed_at', [$rangeStart, $rangeEnd]);
            $rawCommits = $commitsQ->orderBy('committed_at')->get();

            // 선택 회사들의 prefix 합집합. 매칭된 파일들은 "SR 회사 영역", 그 외는 "공통".
            $companyPrefixes = $companies->pluck('path_prefix')->filter()->values()->all();
            $allPrefixes = \App\Models\CompanyGroup::whereNotNull('path_prefix')->pluck('path_prefix')->all();
            [$commits, $commonCommits] = $this->partitionCommitsByPrefixes($rawCommits, $companyPrefixes, $allPrefixes);
            $commits       = $this->dedupeByPatchId($commits);
            $commonCommits = $this->dedupeByPatchId($commonCommits);
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

        $companyNames = $companies->pluck('name')->implode(', ');

        try {
            // 정형 보고서 (AI 호출 없음)
            $structured = $this->buildStructuredReport(
                $type, $rangeLabel, $rangeStart, $rangeEnd,
                $metrics, $commonMetrics, $commits, $commonCommits
            );
            $structured['report']['sr_companies'] = $companies->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->all();
            // AI 요약 임시 비활성 — fatal error 진단 중
            // $aiSummary = $this->generateAiSummaryFromReport($structured['report']);
            // if ($aiSummary) $structured['report']['ai_summary'] = $aiSummary;
            $reportJson = json_encode($structured, JSON_UNESCAPED_UNICODE);

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
                'content'        => $reportJson,
                'metrics'        => ['project' => $metrics, 'common' => $commonMetrics],
            ]);

            $autoCreated = 0;
            if ($type === 'weekly' && $weekDate) {
                $autoCreated = $this->autoGenerateSrAssigneeWeeklies(
                    $weekDate, $srCompanyIds, $commits, $maintRequests
                );
            }

            $mailBody = $this->buildMailBodyFromReport($structured['report']);
            $mailsSent = $this->notifyManagersAfterGenerate(
                $srCompanyIds, null, $type, $weekDate, $mailBody, $rangeLabel
            );

            return response()->json(array_merge($structured, [
                'generated_at'   => now()->format('Y.m.d H:i'),
                'generated_by'   => auth()->user()->name,
                'metrics'        => $metrics,
                'common_metrics' => $commonMetrics,
                'commit_details' => $this->serializeCommitsForUi($commits),
                'common_commit_details' => $this->serializeCommitsForUi($commonCommits),
                'weekly_auto_created' => $autoCreated,
                'mails_sent'     => $mailsSent,
            ]));
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            Log::warning('[WeeklyAiSummary:sr] 생성 실패: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'error'  => '웍스 서머리 생성 중 오류가 발생했습니다.',
                'detail' => $e->getMessage(),
                'at'     => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
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
        // SR 담당자 전체 — 처리 대상 회사 소속이 아니므로 is_sr_agent 플래그로 매칭
        $memberUsers = User::where('is_sr_agent', true)
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
        // SR 담당자 전체 — 처리 대상 회사 소속이 아니므로 is_sr_agent 플래그로 매칭
        $memberUserIds = User::where('is_sr_agent', true)->whereNotNull('email')->pluck('id');
        if ($memberUserIds->isEmpty()) return [[], []];

        $q = GitCommit::with('user:id,name,email')
            ->where('source', 'withworks')
            ->whereIn('user_id', $memberUserIds);
        $this->applyMainMergedFilter($q);
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

    /**
     * 정형 보고서 빌더 (명세 §5.3 JSON 스키마).
     * @return array  ['report' => [period, generated_at, data_source, limitations, assignees, comparison, conclusion]]
     */
    private function buildStructuredReport(
        string $periodType, string $periodLabel, ?Carbon $rangeStart, ?Carbon $rangeEnd,
        array $metrics, array $commonMetrics, $commits, $commonCommits
    ): array {
        // 모든 타입(full / this_month / weekly) — SR 담당자 카드는 실적 0 이라도 노출 유지.

        // 1) 산출 한계 자동 판정 (§4.2)
        $limitations = $this->detectLimitations($metrics);

        // 2) 담당자별 정형 데이터
        $assignees = [];
        foreach ($metrics as $m) {
            $name = $m['name'] ?? '—';
            $assignees[] = [
                'name'        => $name,
                'email'       => $m['email'] ?? null,
                'git'         => [
                    'commits'        => (int) $m['commits'],
                    'added'          => (int) $m['insertions'],
                    'deleted'        => (int) $m['deletions'],
                    'net'            => (int) $m['insertions'] - (int) $m['deletions'],
                    'files'          => (int) ($m['git_file_changes'] ?? 0),
                    'unique_files'   => (int) ($m['git_unique_files'] ?? 0),
                    'diversity'      => $m['git_diversity'] ?? null,
                ],
                'sr'          => [
                    'assigned'        => (int) $m['sr_assigned'],
                    'completed'       => (int) $m['sr_completed'],
                    'carried_over'    => (int) ($m['sr_carried_over'] ?? $m['sr_in_progress']),
                    'reopened'        => (int) ($m['sr_reopened'] ?? 0),
                    'weighted'        => (int) ($m['sr_weighted'] ?? 0),
                    'avg_score'       => $m['sr_avg_score'] ?? null,
                    'completion_rate' => $m['sr_completion_rate'] ?? null,
                    'avg_handling_days'=> $m['sr_avg_handling_days'] ?? null,
                ],
                'penalty'     => [
                    'items'      => $this->buildPenaltyItems($name, $commits),
                    'raw_total'  => $m['penalty_raw'] ?? 0,
                    'final'      => $m['penalty_final'] ?? 0,
                    'cap_applied'=> ($m['penalty_raw'] ?? 0) > ($m['penalty_final'] ?? 0),
                ],
                'score'       => [
                    'norm_sr'     => $m['norm_sr'] ?? 0,
                    'norm_git'    => $m['norm_git'] ?? 0,
                    'raw'         => $m['weekly_score_raw'] ?? 0,
                    'final'       => $m['weekly_score'] ?? 0,
                ],
                'comments'    => $this->generateAutoComments($name, $m, $commits),
                'top_repeated_files' => $m['top_repeated_files'] ?? [],
            ];
        }

        // 2-1) AI 코멘트 — 실적 기반 보완점 (실패 시 룰 기반 그대로)
        $assignees = $this->generateAiCommentsForAssignees($assignees, $periodLabel);

        // 3) 비교 요약 (§4.4) — 담당자 이름 순 고정
        $comparison = [
            'assignees' => array_map(fn($a) => $a['name'], $assignees),
            'metrics'   => $this->buildComparisonRows($assignees),
        ];

        // 4) 결론 (§4.5)
        $conclusion = [
            'limitations'  => array_values(array_map(
                fn($l) => $l['reason'],
                array_filter($limitations, fn($l) => $l['status'] !== 'available' && !empty($l['reason']))
            )),
            'prerequisites'=> array_values($this->buildPrerequisites($limitations)),
            'coaching_signals' => array_values($this->buildCoachingSignals($assignees)),
        ];

        return [
            'report' => [
                'period'      => [
                    'type'  => $periodType,
                    'label' => $periodLabel,
                    'from'  => $rangeStart?->toDateString(),
                    'to'    => $rangeEnd?->toDateString(),
                ],
                'generated_at' => now()->toIso8601String(),
                'data_source'  => 'withworks 저장소 git log + supportworks SR',
                'limitations'  => $limitations,
                'assignees'    => $assignees,
                'comparison'   => $comparison,
                'conclusion'   => $conclusion,
            ],
        ];
    }

    /** §4.2 산출 한계 자동 판정 */
    private function detectLimitations(array $metrics): array
    {
        $hasSrData = false; $hasDifficulty = false; $hasSrLinked = false;
        foreach ($metrics as $m) {
            if (($m['sr_assigned'] ?? 0) > 0) $hasSrData = true;
            if (($m['sr_weighted'] ?? 0) > 0) $hasDifficulty = true;
            if (($m['sr_linked_commits'] ?? 0) > 0) $hasSrLinked = true;
        }
        return [
            ['key' => 'sr_metrics',  'status' => $hasSrData ? 'available' : 'unavailable',
             'reason' => $hasSrData ? '' : 'SR 데이터 부재 또는 담당자 매핑 누락'],
            ['key' => 'sr_difficulty', 'status' => $hasDifficulty ? 'available' : 'unavailable',
             'reason' => $hasDifficulty ? '' : 'sr_difficulty_mappings 미구축 또는 SR 난이도 미매핑'],
            ['key' => 'sr_git_linked', 'status' => $hasSrLinked ? 'available' : 'unavailable',
             'reason' => $hasSrLinked ? '' : '커밋 메시지 [SR-xxxx] prefix 컨벤션 미적용'],
            ['key' => 'git_metrics',  'status' => 'available',  'reason' => 'git log 실측'],
            ['key' => 'repeat_penalty','status' => 'available', 'reason' => '§5.4.1 누진 페널티 산출 + 20% 상한 적용'],
        ];
    }

    /** 담당자별 페널티 항목 — §5.4 제거됨, 항상 빈 배열 */
    private function buildPenaltyItems(string $authorName, $commits): array
    {
        return [];
        // 이하 미사용 (§5.4 제거)
        $items = [];
        $fileCnt = [];
        $authorCommits = [];
        foreach ($commits as $c) {
            $cname = $c->user?->name ?: $c->author_name;
            if ($cname !== $authorName) continue;
            $authorCommits[] = $c;
            $files = is_array($c->files_json) ? $c->files_json : [];
            foreach ($files as $f) {
                $p = (string) ($f['path'] ?? '');
                if ($p === '') continue;
                $fileCnt[$p] = ($fileCnt[$p] ?? 0) + 1;
            }
        }
        // #1 동일 파일 반복 (5회+ 만)
        arsort($fileCnt);
        foreach ($fileCnt as $path => $n) {
            if ($n < 5) break;
            $score = $this->fileRepeatScore($n);
            $grade = $n >= 20 ? 'red' : ($n >= 10 ? 'orange' : 'yellow');
            $items[] = ['pattern' => 'p1_same_file', 'target' => $path, 'count' => $n, 'score' => -$score, 'grade' => $grade];
        }
        // #3 시행착오 키워드
        $kw = ['fix again','retry','다시','재시도','수정 v2','rollback','revert','되돌리'];
        foreach ($authorCommits as $c) {
            $s = mb_strtolower((string) $c->subject);
            foreach ($kw as $k) {
                if (str_contains($s, mb_strtolower($k))) {
                    $items[] = ['pattern' => 'p3_trial_keyword', 'target' => substr($c->sha, 0, 7), 'count' => 1, 'score' => -0.1, 'grade' => 'blue'];
                    break;
                }
            }
        }
        return $items;
    }

    private function fileRepeatScore(int $n): float
    {
        if ($n <= 4)  return 0;
        if ($n <= 9)  return round(0.10 * ($n - 4), 2);
        if ($n <= 19) return round(0.50 + 0.20 * ($n - 10), 2);
        return round(2.50 + 0.30 * ($n - 20), 2);
    }

    /**
     * 코멘트 자동 생성 — **보완해야 될 점만**. 강점·격려성 표현 금지.
     * 사실 기반·냉정·구체적. 추측이 아닌 데이터 임계 초과 시에만 출력.
     */
    private function generateAutoComments(string $authorName, array $m, $commits): array
    {
        $out = [];
        $repeated = $m['top_repeated_files'] ?? [];

        // 0a. SR 실적 요약 (배정·완료·완료율·평균 처리일)
        $srA = (int) ($m['sr_assigned'] ?? 0);
        $srC = (int) ($m['sr_completed'] ?? 0);
        if ($srA > 0) {
            $parts = ["SR {$srA}건 배정 · {$srC}건 완료"];
            if (isset($m['sr_completion_rate']))  $parts[] = "완료율 " . sprintf('%.1f', $m['sr_completion_rate']) . "%";
            if (isset($m['sr_avg_handling_days'])) $parts[] = "평균 처리 " . sprintf('%.1f', $m['sr_avg_handling_days']) . "일";
            $out[] = implode(' · ', $parts);
        }

        // 0b. Git 실적 요약 (커밋·LOC·파일·다양성)
        $cm = (int) ($m['commits'] ?? 0);
        if ($cm > 0) {
            $ins = (int) ($m['insertions'] ?? 0);
            $del = (int) ($m['deletions'] ?? 0);
            $uniq = (int) ($m['git_unique_files'] ?? 0);
            $chg  = (int) ($m['git_file_changes'] ?? 0);
            $div  = $m['git_diversity'] ?? null;
            $divStr = $div !== null ? sprintf(', 다양성 %.2f', $div) : '';
            $out[] = sprintf('Git %d건 commit · +%s/-%s LOC · 파일 %d (unique %d%s)',
                $cm, number_format($ins), number_format($del), $chg, $uniq, $divStr);
        }

        // 활동 0
        if ($srA === 0 && $cm === 0 && (int) ($m['reports'] ?? 0) === 0) {
            return ['활동 없음 — 보고 내용 검증 필요'];
        }

        // 1. 단일 파일 반복 수정 (10회+) — 영역 무관 일괄 지적
        foreach ($repeated as $r) {
            if ($r['count'] < 10) break;
            $out[] = "{$r['path']} {$r['count']}회 수정 — 작업 단위 분할·사전 영향 분석 필요";
        }

        // 2. lang JSON 다국어 다수 동시 변경
        $langCount = 0;
        foreach ($repeated as $r) {
            if (str_contains($r['path'], 'lang') && $r['count'] >= 5) $langCount++;
        }
        if ($langCount >= 3) {
            $out[] = "lang JSON 다국어 파일 {$langCount}개 동시 5회+ 수정 — 다국어 일괄 동기화 자동화 검토 필요";
        }

        // 3. "원복" 키워드 ≥3
        $revertCount = 0;
        foreach ($commits as $c) {
            $cname = $c->user?->name ?: $c->author_name;
            if ($cname !== $authorName) continue;
            if (str_contains((string) $c->subject, '원복')) $revertCount++;
        }
        if ($revertCount >= 3) {
            $out[] = "'원복' 키워드 {$revertCount}건 — 구체적 사유 메시지로 대체 필요 (페널티 + 추적성 저하)";
        }

        // 4. 시행착오 키워드 5건+ — commit 메시지 직접 카운트
        $kw = ['fix again', 'retry', '다시', '재시도', '수정 v2', 'rollback', 'revert', '되돌리'];
        $trialN = 0;
        foreach ($commits as $c) {
            $cname = $c->user?->name ?: $c->author_name;
            if ($cname !== $authorName) continue;
            $s = mb_strtolower((string) $c->subject);
            foreach ($kw as $k) {
                if (str_contains($s, mb_strtolower($k))) { $trialN++; break; }
            }
        }
        if ($trialN >= 5) {
            $out[] = "시행착오 키워드 {$trialN}건 — 작업 시작 전 컨텍스트(요구사항·기존 패턴) 점검 필요";
        }

        // 5. 다양성 0.3 이하 — 같은 파일 반복 비중 높음
        $diversity = $m['git_diversity'] ?? null;
        if ($diversity !== null && (int) $m['commits'] > 5 && $diversity < 0.3) {
            $out[] = "다양성 " . sprintf('%.2f', $diversity) . " — 같은 파일 반복 수정 비중 높음. 작업 단위·범위 점검 필요";
        }

        // 6. SR 연결 커밋 0%
        $srLinked = (int) ($m['sr_linked_commits'] ?? 0);
        if ((int) $m['commits'] > 0 && $srLinked === 0) {
            $out[] = '[SR-xxxx] prefix 미적용 — SR ↔ Git 추적성 확보 위해 컨벤션 도입 필요';
        }

        // 7. 재오픈 SR 1건+ — 완료 전 회귀 점검 부족
        $reopen = (int) ($m['sr_reopened'] ?? 0);
        if ($reopen >= 1) {
            $out[] = "재오픈 SR {$reopen}건 — 완료 전 동일 도메인 회귀 점검 필요";
        }

        // 8. 평균 처리 시간이 난이도별 표준 대비 2배 초과 (난이도 평균 기준 단순 근사)
        $avgDays = $m['sr_avg_handling_days'] ?? null;
        $avgScore = $m['sr_avg_score'] ?? null;
        if ($avgDays !== null && $avgScore !== null) {
            $standard = match (true) { $avgScore < 1.5 => 0.5, $avgScore < 2.5 => 1, $avgScore < 3.5 => 3, $avgScore < 4.5 => 7, default => 14 };
            if ($avgDays > $standard * 2) {
                $out[] = "평균 처리일 " . sprintf('%.1f', $avgDays) . "일 — 난이도 표준({$standard}일) 의 " . sprintf('%.1f', $avgDays / max(1, $standard)) . "배. 작업 분해·우선순위 점검 필요";
            }
        }

        return array_values(array_unique($out));
    }

    private function buildComparisonRows(array $assignees): array
    {
        return [
            // Git
            'commits'         => array_map(fn($a) => $a['git']['commits'] ?? 0, $assignees),
            'net_loc'         => array_map(fn($a) => $a['git']['net'] ?? 0, $assignees),
            'files'           => array_map(fn($a) => $a['git']['files'] ?? 0, $assignees),
            'diversity'       => array_map(fn($a) => $a['git']['diversity'] ?? null, $assignees),
            // SR
            'sr_assigned'     => array_map(fn($a) => $a['sr']['assigned'] ?? 0, $assignees),
            'sr_completed'    => array_map(fn($a) => $a['sr']['completed'] ?? 0, $assignees),
            'sr_completion_rate' => array_map(fn($a) => $a['sr']['completion_rate'] ?? null, $assignees),
            'sr_avg_handling_days' => array_map(fn($a) => $a['sr']['avg_handling_days'] ?? null, $assignees),
            'sr_weighted'     => array_map(fn($a) => $a['sr']['weighted'] ?? 0, $assignees),
            'sr_reopened'     => array_map(fn($a) => $a['sr']['reopened'] ?? 0, $assignees),
            // 종합
            'penalty_raw'     => array_map(fn($a) => $a['penalty']['raw_total'] ?? 0, $assignees),
            'penalty_final'   => array_map(fn($a) => $a['penalty']['final'] ?? 0, $assignees),
            'weekly_score'    => array_map(fn($a) => $a['score']['final'] ?? 0, $assignees),
        ];
    }

    private function buildPrerequisites(array $limitations): array
    {
        $out = [];
        foreach ($limitations as $l) {
            if ($l['status'] === 'available' || $l['status'] === '') continue;
            $out[] = match ($l['key']) {
                'sr_difficulty' => 'SR 등록 시 fulfillment 난이도 표 §13.3 단위 매핑 (sr_difficulty_mappings 활용)',
                'sr_git_linked' => '커밋 메시지에 `[SR-xxxx]` prefix 적용 운영팀 합의·공지',
                'sr_metrics'    => 'SR 담당자 매핑 정합성 점검',
                default         => $l['reason'],
            };
        }
        return $out;
    }

    private function buildCoachingSignals(array $assignees): array
    {
        $out = [];
        foreach ($assignees as $a) {
            $issues = [];
            if (!empty($a['top_repeated_files'])) {
                $top = $a['top_repeated_files'][0];
                if ($top['count'] >= 20)     $issues[] = "{$top['path']} {$top['count']}회 수정 — 강한 누진 페널티 대상";
                elseif ($top['count'] >= 10) $issues[] = "{$top['path']} {$top['count']}회 수정 — 사전 영향 분석 권장";
            }
            if (($a['sr']['reopened'] ?? 0) >= 2) $issues[] = "재오픈 SR {$a['sr']['reopened']}건 — 완료 전 회귀 점검 권장";
            if (!empty($issues)) {
                $out[] = ['name' => $a['name'], 'issues' => $issues];
            }
        }
        return $out;
    }

    /**
     * 서머리 생성 직후 알림 메일 — 회사 매니저(User, role=manager) + 회사 매핑 관리자(AdminUser)에게.
     * 본문: 짧은 요약 + Word 첨부.
     * 실패해도 본 흐름은 영향 없음 (try/catch + 로그).
     */
    private function notifyManagersAfterGenerate(
        array $companyGroupIds,
        ?Project $project,
        string $type,
        ?string $weekDate,
        string $content,
        string $rangeLabel
    ): int {
        try {
            $companyGroupIds = array_values(array_filter(array_map('intval', $companyGroupIds)));
            if (empty($companyGroupIds)) return 0;

            // 1) 수신자 수집
            $managerEmails = User::whereIn('company_group_id', $companyGroupIds)
                ->whereExists(fn($q) => $q->select(DB::raw(1))->from('project_members')
                    ->whereColumn('user_id', 'users.id')->where('role', 'manager'))
                ->whereNotNull('email')->pluck('email')->all();

            $adminEmails = DB::table('admin_company_group_access as a')
                ->join('admin_users as au', 'au.id', '=', 'a.admin_user_id')
                ->whereIn('a.company_group_id', $companyGroupIds)
                ->whereNotNull('au.email')->pluck('au.email')->all();

            $recipients = array_values(array_unique(array_filter(array_map(
                fn($e) => mb_strtolower(trim($e)),
                array_merge($managerEmails, $adminEmails)
            ))));
            if (empty($recipients)) return 0;

            // 2) Word 파일 생성
            $projectForDocx = $project ?: new Project(['name' => '회사 SR 통합 서머리']);
            $writer = new DocxWriter();
            $writer->buildAiSummary(
                $projectForDocx,
                $type === 'weekly' ? 'weekly' : 'full',
                $rangeLabel,
                $content,
                now()->format('Y.m.d H:i'),
                auth()->user()?->name ?? ''
            );
            @mkdir(storage_path('app/temp'), 0775, true);
            $filename = ($projectForDocx->name ?? 'AI서머리') . '_' . now()->format('Ymd_His') . '.docx';
            $tmpPath  = storage_path('app/temp/' . $filename);
            $writer->save($tmpPath);

            // 3) 본문 — 마크다운 제목/표 일부만 plain text 로
            $shortBody = mb_substr(strip_tags($content), 0, 1500);
            $subject = '[웍스 서머리] ' . ($projectForDocx->name ?? '') . ' — ' . $rangeLabel;
            $bodyHtml =
                '<p>안녕하세요, 새 웍스 서머리가 생성되었습니다.</p>' .
                '<p><b>대상:</b> ' . e($projectForDocx->name) . '<br>' .
                '<b>기간:</b> ' . e($rangeLabel) . '</p>' .
                '<p>요약은 첨부된 Word 파일을 확인해주세요. 아래는 일부 발췌입니다.</p>' .
                '<pre style="font-family:ui-monospace,monospace;font-size:12px;background:#f8fafc;padding:10px;border-radius:6px;white-space:pre-wrap;">'
                . e($shortBody) . '</pre>';

            $sent = 0;
            foreach ($recipients as $to) {
                try {
                    Mail::html($bodyHtml, function ($m) use ($to, $subject, $tmpPath, $filename) {
                        $m->to($to)->subject($subject)->attach($tmpPath, ['as' => $filename]);
                    });
                    $sent++;
                } catch (\Throwable $e) {
                    SystemErrorLog::record($e, 'warning');
                }
            }

            @unlink($tmpPath);
            return $sent;
        } catch (\Throwable $e) {
            SystemErrorLog::record($e, 'warning');
            return 0;
        }
    }

    /**
     * main/master 머지 완료된 커밋만 — branches JSON 에 'main' 또는 'master' 포함.
     * 명세서 의도: 실제 산출물만 평가 대상.
     */
    private function applyMainMergedFilter($query)
    {
        return $query->where(function ($q) {
            $q->whereRaw("JSON_CONTAINS(branches, '\"main\"')")
              ->orWhereRaw("JSON_CONTAINS(branches, '\"master\"')");
        });
    }

    /**
     * 반복 커밋 페널티 계산 (명세 §5.4.1)
     *  #1 동일 파일 반복 수정 — 누진 구조 (가장 강한 페널티)
     *     N≤2: 0 / N=3~4: 0 (경고만) / N=5~9: −0.10×(N−4)
     *     N=10~19: −0.50 + (−0.20×(N−10)) / N≥20: −2.50 + (−0.30×(N−20))
     *  #3 시행착오 메시지 — 매칭 1건당 −0.1
     *  (#2 SR 커밋 폭증 — SR-Git 연결 구현 후 추가 예정)
     */
    private function computeRepeatCommitPenalty(array $authorCommits): array
    {
        $detail = ['file_repeat' => 0, 'trial_error' => 0, 'warned_files' => 0];
        if (empty($authorCommits)) return ['total' => 0, 'detail' => $detail];

        // 1) 동일 파일 카운트
        $fileCount = [];
        foreach ($authorCommits as $c) {
            $files = is_array($c->files_json) ? $c->files_json : [];
            foreach ($files as $f) {
                $p = (string) ($f['path'] ?? '');
                if ($p === '') continue;
                $fileCount[$p] = ($fileCount[$p] ?? 0) + 1;
            }
        }
        $fileRepeatPenalty = 0.0;
        $warnedFiles = 0;
        foreach ($fileCount as $n) {
            if ($n <= 2)        { /* 정상 */ }
            elseif ($n <= 4)    { $warnedFiles++; }   // 경고만 — 점수 영향 없음
            elseif ($n <= 9)    { $fileRepeatPenalty += 0.10 * ($n - 4); }
            elseif ($n <= 19)   { $fileRepeatPenalty += 0.50 + 0.20 * ($n - 10); }
            else                { $fileRepeatPenalty += 2.50 + 0.30 * ($n - 20); }
        }
        $detail['file_repeat']  = round($fileRepeatPenalty, 2);
        $detail['warned_files'] = $warnedFiles;

        // 2) 시행착오 키워드
        $kw = ['fix again', 'retry', '다시', '재시도', '수정 v2', 'rollback', 'revert', '되돌리'];
        $trialHits = 0;
        foreach ($authorCommits as $c) {
            $subj = mb_strtolower((string) $c->subject);
            foreach ($kw as $k) {
                if (str_contains($subj, mb_strtolower($k))) { $trialHits++; break; }
            }
        }
        $detail['trial_error'] = round($trialHits * 0.1, 2);

        $total = $detail['file_repeat'] + $detail['trial_error'];
        return ['total' => $total, 'detail' => $detail];
    }

    /**
     * 같은 patch_id (= 동일 변경 내용 — cherry-pick/revert/rebase) 의 중복 제거.
     * 가장 빠른 (committed_at) 첫 등장만 유지, 나머지는 분석에서 제외.
     * patch_id 가 null 인 커밋(머지·빈커밋)은 그대로 통과.
     */
    private function dedupeByPatchId($commits)
    {
        $seen = [];
        $out  = [];
        foreach ($commits as $c) {
            $pid = $c->patch_id ?? null;
            if ($pid !== null && $pid !== '') {
                if (isset($seen[$pid])) continue;   // 중복 — 스킵
                $seen[$pid] = true;
            }
            $out[] = $c;
        }
        return collect($out);
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
