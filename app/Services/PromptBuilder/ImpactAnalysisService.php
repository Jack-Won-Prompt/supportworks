<?php

namespace App\Services\PromptBuilder;

class ImpactAnalysisService
{
    public function analyze(array $mapping, array $relatedBuilders): array
    {
        $affectedCount = count($relatedBuilders);
        $standardCount = count($mapping['applied_standards'] ?? []);
        $candidateCount = count($mapping['candidates'] ?? []);

        $level = match (true) {
            $affectedCount > 10 => 'high',
            $affectedCount > 3  => 'medium',
            default             => 'low',
        };

        return [
            'level'            => $level,
            'affected_builders' => $affectedCount,
            'standards_applied' => $standardCount,
            'new_candidates'    => $candidateCount,
            'summary'          => $this->buildSummary($level, $affectedCount, $standardCount, $candidateCount),
        ];
    }

    private function buildSummary(string $level, int $affected, int $standards, int $candidates): string
    {
        $levelLabel = match ($level) {
            'high'   => '높음',
            'medium' => '중간',
            default  => '낮음',
        };

        return "영향도: {$levelLabel} | 관련 빌더: {$affected}개 | 적용 표준: {$standards}개 | 신규 후보: {$candidates}개";
    }
}
