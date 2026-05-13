<?php

namespace App\Services\Agent;

use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\SystemErrorLog;

class FixOrchestrator
{
    public function __construct(
        private readonly IssueAggregator       $aggregator,
        private readonly CodeValidationService $codeValidation,
        private readonly CodeReviewService     $codeReview,
    ) {}

    // ── Single-group fix ──────────────────────────────────────────────────────

    /**
     * @return array{group_key:string, occurrences_fixed:int, occurrences_total:int, details:array}
     */
    public function fixGroup(
        Project  $project,
        string   $groupKey,
        int      $userId,
        ?callable $onProgress = null,
    ): array {
        $group = $this->aggregator->getGroup($project->id, $groupKey);
        if (!$group) {
            throw new \RuntimeException("그룹 {$groupKey}를 찾을 수 없습니다.");
        }
        if (!$group['auto_fixable']) {
            throw new \RuntimeException("이 그룹은 자동 수정이 불가능합니다.");
        }

        $results = [];
        $pending = array_filter(
            $group['occurrences'],
            fn($o) => !($o['fixed'] ?? false) && !($o['ignored'] ?? false) && ($o['auto_fixable'] ?? false)
        );

        foreach ($pending as $occurrence) {
            try {
                $result      = $this->fixOccurrence($project, $occurrence, $userId);
                $results[]   = array_merge($result, ['occurrence_id' => $occurrence['id']]);
                if ($onProgress) ($onProgress)($occurrence, $result);
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $results[] = ['success' => false, 'occurrence_id' => $occurrence['id'], 'message' => $e->getMessage()];
                if ($onProgress) ($onProgress)($occurrence, ['success' => false, 'message' => $e->getMessage()]);
            }
        }

        $fixedCount = count(array_filter($results, fn($r) => $r['success'] ?? false));

        return [
            'group_key'          => $groupKey,
            'title'              => $group['title'],
            'occurrences_fixed'  => $fixedCount,
            'occurrences_total'  => count($results),
            'details'            => $results,
        ];
    }

    // ── Batch fix (SSE) ───────────────────────────────────────────────────────

    /**
     * @param  callable(string $event, array $data): void  $onEvent
     */
    public function runBatch(
        Project  $project,
        int      $userId,
        callable $onEvent,
        string   $severityFilter = 'all',
    ): void {
        $groups = $this->aggregator->aggregateIssues($project->id);

        $toFix = array_values(array_filter($groups, function ($g) use ($severityFilter) {
            if ($g['status'] !== 'pending') return false;
            if (!$g['auto_fixable']) return false;
            if ($severityFilter === 'all') return true;
            return $g['severity'] === $severityFilter;
        }));

        $total          = count($toFix);
        $done           = 0;
        $totalFixed     = 0;
        $totalFailed    = 0;

        $onEvent('start', ['total' => $total, 'progress' => 0, 'message' => '자동 수정 시작...']);

        foreach ($toFix as $group) {
            $onEvent('group_start', [
                'group_key'        => $group['group_key'],
                'title'            => $group['title'],
                'severity'         => $group['severity'],
                'occurrences_count'=> count($group['occurrences']),
                'done'             => $done,
                'total'            => $total,
                'progress'         => $total > 0 ? (int) round(($done / $total) * 95) : 0,
            ]);

            try {
                $result      = $this->fixGroup($project, $group['group_key'], $userId);
                $totalFixed += $result['occurrences_fixed'];

                $onEvent('group_done', [
                    'group_key'         => $group['group_key'],
                    'title'             => $group['title'],
                    'occurrences_fixed' => $result['occurrences_fixed'],
                    'occurrences_total' => $result['occurrences_total'],
                    'done'              => $done + 1,
                    'total'             => $total,
                    'progress'          => $total > 0 ? (int) round((($done + 1) / $total) * 95) : 95,
                    'status'            => 'done',
                ]);
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $totalFailed++;
                $onEvent('group_error', [
                    'group_key' => $group['group_key'],
                    'title'     => $group['title'],
                    'error'     => $e->getMessage(),
                    'done'      => $done + 1,
                    'total'     => $total,
                    'progress'  => $total > 0 ? (int) round((($done + 1) / $total) * 95) : 95,
                    'status'    => 'failed',
                ]);
            }

            $done++;
        }

        $onEvent('complete', [
            'status'        => 'COMPLETED',
            'total'         => $total,
            'done'          => $done,
            'total_fixed'   => $totalFixed,
            'failed_groups' => $totalFailed,
            'progress'      => 100,
        ]);
    }

    // ── Ignore group ──────────────────────────────────────────────────────────

    public function ignoreGroup(int $projectId, string $groupKey, ?string $reason, int $userId): void
    {
        $group = $this->aggregator->getGroup($projectId, $groupKey);
        if (!$group) {
            throw new \RuntimeException("그룹 {$groupKey}를 찾을 수 없습니다.");
        }

        $pending = array_filter(
            $group['occurrences'],
            fn($o) => !($o['fixed'] ?? false) && !($o['ignored'] ?? false)
        );

        foreach ($pending as $occurrence) {
            $this->markIgnoredInSource($occurrence, $reason, $userId);
        }
    }

    // ── Mark manual fixed ─────────────────────────────────────────────────────

    public function markManualFixed(int $projectId, string $groupKey, int $userId): void
    {
        $group = $this->aggregator->getGroup($projectId, $groupKey);
        if (!$group) {
            throw new \RuntimeException("그룹 {$groupKey}를 찾을 수 없습니다.");
        }

        foreach ($group['occurrences'] as $occurrence) {
            if (!($occurrence['fixed'] ?? false)) {
                $this->markFixedInSource($occurrence, $userId, 'manual');
            }
        }
    }

    // ── Private: fix single occurrence ────────────────────────────────────────

    private function fixOccurrence(Project $project, array $occurrence, int $userId): array
    {
        $screen = AiAgentScreen::find($occurrence['scope_id']);
        if (!$screen) {
            throw new \RuntimeException("화면 ID {$occurrence['scope_id']}를 찾을 수 없습니다.");
        }

        if ($occurrence['source'] === 't41') {
            return $this->codeValidation->applyAutoFix($project, $screen, $occurrence['id'], $userId);
        }

        // source === 't45'
        return $this->codeReview->applyAutoFix($project, $screen, $occurrence['id'], $userId);
    }

    // ── Private: source artifact update ──────────────────────────────────────

    private function markFixedInSource(array $occurrence, int $userId, string $method = 'auto'): void
    {
        $artifact = AiAgentArtifact::find($occurrence['artifact_id']);
        if (!$artifact) return;

        $data = json_decode($artifact->content, true) ?? [];

        if ($occurrence['source'] === 't41') {
            foreach ($data['violations'] as &$v) {
                if (($v['id'] ?? '') === $occurrence['id']) {
                    $v['fixed']    = true;
                    $v['fixed_at'] = now()->toIso8601String();
                    $v['fix_method'] = $method;
                    break;
                }
            }
            unset($v);
        } else {
            foreach ($data['additional_findings'] as &$f) {
                if (($f['id'] ?? '') === $occurrence['id']) {
                    $f['fixed']    = true;
                    $f['fixed_at'] = now()->toIso8601String();
                    $f['fix_method'] = $method;
                    break;
                }
            }
            unset($f);

            // Sync all_violations
            $data['all_violations'] = array_merge(
                array_map(fn($v) => array_merge($v, ['source' => 't41']), $data['from_t41'] ?? []),
                $data['additional_findings'],
            );
        }

        $artifact->updateWithVersion(
            content: json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  $userId,
            meta:    ['change_type' => "fix_{$method}_t46", 'occurrence_id' => $occurrence['id']],
        );
    }

    private function markIgnoredInSource(array $occurrence, ?string $reason, int $userId): void
    {
        $artifact = AiAgentArtifact::find($occurrence['artifact_id']);
        if (!$artifact) return;

        $data = json_decode($artifact->content, true) ?? [];

        if ($occurrence['source'] === 't41') {
            foreach ($data['violations'] as &$v) {
                if (($v['id'] ?? '') === $occurrence['id']) {
                    $v['ignored']    = true;
                    $v['ignored_at'] = now()->toIso8601String();
                    if ($reason) $v['ignore_reason'] = $reason;
                    break;
                }
            }
            unset($v);
        } else {
            foreach ($data['additional_findings'] as &$f) {
                if (($f['id'] ?? '') === $occurrence['id']) {
                    $f['ignored']    = true;
                    $f['ignored_at'] = now()->toIso8601String();
                    if ($reason) $f['ignore_reason'] = $reason;
                    break;
                }
            }
            unset($f);

            $data['all_violations'] = array_merge(
                array_map(fn($v) => array_merge($v, ['source' => 't41']), $data['from_t41'] ?? []),
                $data['additional_findings'],
            );
        }

        $artifact->updateWithVersion(
            content: json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  $userId,
            meta:    ['change_type' => 'ignore_t46', 'occurrence_id' => $occurrence['id']],
        );
    }
}
