<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\FixOrchestrator;
use App\Services\Agent\IssueAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdditionalFixController extends Controller
{
    private const CACHE_PREFIX = 'ai-agent:additional-fix:batch:';
    private const CACHE_TTL    = 3600;

    public function __construct(
        private readonly IssueAggregator $aggregator,
        private readonly FixOrchestrator $orchestrator,
    ) {}

    // ── 메인 대시보드 ─────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $groups = $this->aggregator->aggregateIssues($project->id);
        $stats  = $this->computeStats($groups);

        return view('ai-agent.dev.additional-fix.index', [
            'project'         => $project,
            'groups'          => $groups,
            'stats'           => $stats,
            'batchStartUrl'   => route('ai-agent.projects.dev.additional-fix.batch.start', $project),
            'batchSseUrlTpl'  => route('ai-agent.projects.dev.additional-fix.batch.sse', [$project, 'SESSION_ID']),
            'exportUrl'       => route('ai-agent.projects.dev.additional-fix.export', $project),
            'cancelUrlTpl'    => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'pageTitle'       => '웍스 추가 수정 (T46)',
        ]);
    }

    // ── 그룹 목록 JSON ────────────────────────────────────────────────────────

    public function groups(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $groups = $this->aggregator->aggregateIssues($project->id);
        $stats  = $this->computeStats($groups);

        return response()->json(['groups' => $groups, 'stats' => $stats]);
    }

    // ── 단일 그룹 자동 수정 ───────────────────────────────────────────────────

    public function fixGroup(Request $request, Project $project, string $key): JsonResponse
    {
        $this->authorizeProject($project);

        try {
            $result = $this->orchestrator->fixGroup($project, $key, (int) auth()->id());

            return response()->json([
                'success'            => true,
                'occurrences_fixed'  => $result['occurrences_fixed'],
                'occurrences_total'  => $result['occurrences_total'],
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 그룹 무시 ─────────────────────────────────────────────────────────────

    public function ignoreGroup(Request $request, Project $project, string $key): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate(['reason' => 'nullable|string|max:500']);

        try {
            $this->orchestrator->ignoreGroup($project->id, $key, $validated['reason'] ?? null, (int) auth()->id());
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 수동 수정 완료 표시 ───────────────────────────────────────────────────

    public function manualFixed(Request $request, Project $project, string $key): JsonResponse
    {
        $this->authorizeProject($project);

        try {
            $this->orchestrator->markManualFixed($project->id, $key, (int) auth()->id());
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 일괄 자동 수정 시작 ───────────────────────────────────────────────────

    public function batchStart(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'severity_filter' => 'nullable|in:all,critical,warning',
            'confirmed'       => 'boolean',
        ]);

        $severityFilter = $validated['severity_filter'] ?? 'all';
        $confirmed      = (bool) ($validated['confirmed'] ?? false);

        if (!$confirmed) {
            $groups  = $this->aggregator->aggregateIssues($project->id);
            $toFix   = array_filter($groups, fn($g) => $g['status'] === 'pending' && $g['auto_fixable']
                && ($severityFilter === 'all' || $g['severity'] === $severityFilter));
            $count   = count($toFix);
            $occ     = array_sum(array_map(fn($g) => count(array_filter(
                $g['occurrences'], fn($o) => !($o['fixed'] ?? false) && !($o['ignored'] ?? false) && ($o['auto_fixable'] ?? false)
            )), $toFix));

            return response()->json([
                'requiresConfirmation' => true,
                'groupCount'           => $count,
                'occurrencesCount'     => $occ,
            ]);
        }

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'project_id'      => $project->id,
            'user_id'         => (int) auth()->id(),
            'severity_filter' => $severityFilter,
        ], self::CACHE_TTL);

        return response()->json(['success' => true, 'sessionId' => $sessionId]);
    }

    // ── 일괄 자동 수정 SSE ────────────────────────────────────────────────────

    public function batchSse(Project $project, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);
        $session = Cache::get(self::CACHE_PREFIX . $sessionId);

        return response()->stream(function () use ($session, $project, $sessionId) {
            $this->clearOutputBuffer();

            if (!$session || $session['project_id'] !== $project->id) {
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => '세션을 찾을 수 없습니다.']);
                return;
            }

            $this->sseEvent('status', ['status' => 'STARTING', 'message' => '자동 수정을 시작합니다...', 'progress' => 0]);
            Cache::forget(self::CACHE_PREFIX . $sessionId);

            try {
                $this->orchestrator->runBatch(
                    project:        $project,
                    userId:         $session['user_id'],
                    onEvent:        fn(string $ev, array $data) => $this->sseEvent($ev, $data),
                    severityFilter: $session['severity_filter'] ?? 'all',
                );
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => $e->getMessage()]);
            }
        }, 200, $this->sseHeaders());
    }

    // ── 재검증 안내 ───────────────────────────────────────────────────────────

    public function reverify(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        return response()->json([
            'success'     => true,
            'message'     => 'T41 Output 검증과 T45 웍스 코드 리뷰를 순서대로 재실행하세요.',
            'reverify_urls' => [
                'code_validation' => route('ai-agent.projects.dev.code-validation', $project),
                'code_review'     => route('ai-agent.projects.dev.code-review', $project),
            ],
        ]);
    }

    // ── 내보내기 ─────────────────────────────────────────────────────────────

    public function export(Project $project): Response
    {
        $this->authorizeProject($project);

        $groups = $this->aggregator->aggregateIssues($project->id);
        $stats  = $this->computeStats($groups);
        $md     = $this->buildMarkdownExport($project->name, $groups, $stats);

        $slug = Str::slug($project->name);
        $name = "{$slug}-additional-fix-" . now()->format('Ymd') . '.md';

        return response($md, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function computeStats(array $groups): array
    {
        $total     = count($groups);
        $pending   = count(array_filter($groups, fn($g) => $g['status'] === 'pending'));
        $fixed     = count(array_filter($groups, fn($g) => $g['status'] === 'fixed'));
        $ignored   = count(array_filter($groups, fn($g) => $g['status'] === 'ignored'));
        $critical  = count(array_filter($groups, fn($g) => $g['severity'] === 'critical' && $g['status'] === 'pending'));
        $warning   = count(array_filter($groups, fn($g) => $g['severity'] === 'warning' && $g['status'] === 'pending'));
        $info      = count(array_filter($groups, fn($g) => $g['severity'] === 'info' && $g['status'] === 'pending'));
        $autoFix   = count(array_filter($groups, fn($g) => $g['auto_fixable'] && $g['status'] === 'pending'));
        $critAuto  = count(array_filter($groups, fn($g) => $g['severity'] === 'critical' && $g['auto_fixable'] && $g['status'] === 'pending'));

        // Total occurrences
        $totalOcc = array_sum(array_map(fn($g) => count($g['occurrences']), $groups));
        $pendingOcc = array_sum(array_map(fn($g) => count(array_filter(
            $g['occurrences'], fn($o) => !($o['fixed'] ?? false) && !($o['ignored'] ?? false)
        )), $groups));
        $fixedOcc   = array_sum(array_map(fn($g) => count(array_filter($g['occurrences'], fn($o) => $o['fixed'] ?? false)), $groups));

        return compact(
            'total', 'pending', 'fixed', 'ignored',
            'critical', 'warning', 'info',
            'autoFix', 'critAuto',
            'totalOcc', 'pendingOcc', 'fixedOcc',
        );
    }

    private function buildMarkdownExport(string $projectName, array $groups, array $stats): string
    {
        $lines = [
            "# {$projectName} — 웍스 추가 수정 현황",
            '',
            "생성일: " . now()->format('Y-m-d H:i'),
            '',
            "## 통계",
            '',
            "| 항목 | 수치 |",
            "|---|---|",
            "| 전체 그룹 | {$stats['total']}건 |",
            "| 미해결 | {$stats['pending']}건 |",
            "| 해결 완료 | {$stats['fixed']}건 |",
            "| 무시 | {$stats['ignored']}건 |",
            "| Critical (미해결) | {$stats['critical']}건 |",
            '',
            '## 이슈 그룹',
            '',
        ];

        foreach ($groups as $g) {
            $icon = match($g['severity']) { 'critical' => '🔴', 'warning' => '🟡', default => '🔵' };
            $status = match($g['status']) { 'fixed' => '✅ 해결', 'ignored' => '🚫 무시', default => '⏳ 미해결' };
            $lines[] = "### {$icon} {$g['title']}";
            $lines[] = "- **카테고리**: {$g['category']} · **심각도**: {$g['severity']} · **상태**: {$status}";
            $lines[] = "- **자동 수정**: " . ($g['auto_fixable'] ? '가능' : '불가 (수동 필요)');
            $lines[] = "- **영향 파일**: " . count($g['affected_files']) . "개";
            $lines[] = "- **출처**: " . implode(', ', array_map('strtoupper', $g['sources']));
            if (!empty($g['description'])) $lines[] = "- **설명**: {$g['description']}";
            if (!empty($g['suggestion'])) $lines[] = "- **제안**: {$g['suggestion']}";
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function authorizeProject(Project $project): void
    {
        $userId = (int) auth()->id();
        if (!ProjectMember::where('project_id', $project->id)->where('user_id', $userId)->exists()
            && $project->created_by !== $userId) {
            abort(403);
        }
    }

    private function sseHeaders(): array
    {
        return [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ];
    }

    private function sseEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    private function clearOutputBuffer(): void
    {
        while (ob_get_level() > 0) { ob_end_clean(); }
        ob_implicit_flush(true);
    }
}
