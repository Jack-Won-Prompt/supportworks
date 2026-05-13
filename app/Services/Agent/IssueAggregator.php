<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;

class IssueAggregator
{
    private const SEVERITY_ORDER = ['critical' => 0, 'warning' => 1, 'info' => 2];

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * T41 + T45의 모든 위반을 통합하여 우선순위 정렬된 그룹 목록 반환
     */
    public function aggregateIssues(int $projectId): array
    {
        $t41 = $this->loadT41Findings($projectId);
        $t45 = $this->loadT45Findings($projectId);

        $all     = $this->deduplicateFindings(array_merge($t41, $t45));
        $grouped = $this->groupSimilarIssues($all);

        return $this->sortByPriority($grouped);
    }

    /**
     * 특정 group_key의 그룹 반환 (현재 상태 반영)
     */
    public function getGroup(int $projectId, string $groupKey): ?array
    {
        $groups = $this->aggregateIssues($projectId);
        foreach ($groups as $g) {
            if ($g['group_key'] === $groupKey) return $g;
        }
        return null;
    }

    // ── Data loading ──────────────────────────────────────────────────────────

    /**
     * T41 (CODE_VALIDATION) 위반 로드
     */
    public function loadT41Findings(int $projectId): array
    {
        $artifacts = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::CODE_VALIDATION->value)
            ->where('scope_type', 'screen')
            ->get();

        $findings = [];
        foreach ($artifacts as $artifact) {
            $data = json_decode($artifact->content, true) ?? [];
            foreach ($data['violations'] ?? [] as $v) {
                if (!isset($v['id'])) continue;
                $findings[] = array_merge($v, [
                    'source'       => 't41',
                    'artifact_id'  => $artifact->id,
                    'scope_id'     => (int) $artifact->scope_id,
                    'frontend_file'=> $v['file'] ?? '',
                    'backend_file' => '',
                    'fixed'        => !empty($v['fixed']),
                    'ignored'      => !empty($v['ignored']),
                ]);
            }
        }

        return $findings;
    }

    /**
     * T45 (CODE_REVIEW) 추가 발견 로드
     */
    public function loadT45Findings(int $projectId): array
    {
        $artifacts = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::CODE_REVIEW->value)
            ->where('scope_type', 'screen')
            ->get();

        $findings = [];
        foreach ($artifacts as $artifact) {
            $data = json_decode($artifact->content, true) ?? [];
            foreach ($data['additional_findings'] ?? [] as $f) {
                if (!isset($f['id'])) continue;
                $findings[] = array_merge($f, [
                    'source'      => 't45',
                    'artifact_id' => $artifact->id,
                    'scope_id'    => (int) $artifact->scope_id,
                    'file'        => $f['frontend_file'] ?? '',
                    'line'        => null,
                    'fixed'       => !empty($f['fixed']),
                    'ignored'     => !empty($f['ignored']),
                ]);
            }
        }

        return $findings;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    protected function deduplicateFindings(array $findings): array
    {
        $seen   = [];
        $result = [];

        foreach ($findings as $f) {
            $key = ($f['artifact_id'] ?? '') . ':' . ($f['id'] ?? '');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[]   = $f;
            }
        }

        return $result;
    }

    protected function groupSimilarIssues(array $findings): array
    {
        $groups = [];

        foreach ($findings as $finding) {
            $groupKey = $this->generateGroupKey($finding);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'group_key'      => $groupKey,
                    'category'       => $finding['category'] ?? 'unknown',
                    'severity'       => $finding['severity'] ?? 'info',
                    'title'          => $finding['title'] ?? '',
                    'description'    => $finding['description'] ?? '',
                    'suggestion'     => $finding['suggestion'] ?? '',
                    'affected_files' => [],
                    'sources'        => [],
                    'occurrences'    => [],
                    'auto_fixable'   => true,
                    'status'         => 'pending',
                ];
            }

            // Track unique affected files
            $file = $finding['frontend_file'] ?? $finding['file'] ?? '';
            if ($file && !in_array($file, $groups[$groupKey]['affected_files'], true)) {
                $groups[$groupKey]['affected_files'][] = $file;
            }

            // Track sources
            $src = $finding['source'] ?? '';
            if ($src && !in_array($src, $groups[$groupKey]['sources'], true)) {
                $groups[$groupKey]['sources'][] = $src;
            }

            $groups[$groupKey]['occurrences'][]  = $finding;
            $groups[$groupKey]['auto_fixable']   = $groups[$groupKey]['auto_fixable']
                && ($finding['auto_fixable'] ?? false);
        }

        // Compute status for each group
        foreach ($groups as &$g) {
            $g['status'] = $this->computeGroupStatus($g['occurrences']);
        }
        unset($g);

        return array_values($groups);
    }

    protected function generateGroupKey(array $finding): string
    {
        return md5(($finding['category'] ?? '') . ':' . ($finding['title'] ?? ''));
    }

    protected function computeGroupStatus(array $occurrences): string
    {
        if (empty($occurrences)) return 'pending';

        $pending  = array_filter($occurrences, fn($o) => !($o['fixed'] ?? false) && !($o['ignored'] ?? false));
        $fixed    = array_filter($occurrences, fn($o) => $o['fixed'] ?? false);
        $ignored  = array_filter($occurrences, fn($o) => ($o['ignored'] ?? false) && !($o['fixed'] ?? false));

        if (count($fixed) === count($occurrences)) return 'fixed';
        if (empty($pending)) return 'ignored';
        return 'pending';
    }

    protected function sortByPriority(array $groups): array
    {
        usort($groups, function ($a, $b) {
            $sa = self::SEVERITY_ORDER[$a['severity']] ?? 99;
            $sb = self::SEVERITY_ORDER[$b['severity']] ?? 99;
            if ($sa !== $sb) return $sa - $sb;
            // secondary: auto_fixable first
            return ($b['auto_fixable'] ? 1 : 0) - ($a['auto_fixable'] ? 1 : 0);
        });

        return $groups;
    }
}
