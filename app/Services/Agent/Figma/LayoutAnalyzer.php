<?php

namespace App\Services\Agent\Figma;

use App\Services\Agent\Figma\Contracts\FigmaClient;
use Illuminate\Support\Str;

class LayoutAnalyzer
{
    private const BATCH_SIZE  = 50;
    private const STD_THRESHOLD = 10.0; // 10% 이상 사용 시 표준으로 분류

    public function __construct(
        private readonly FigmaClient $client,
    ) {}

    public function analyze(string $fileKey): LayoutSpecSet
    {
        // 1. 파일에서 최상위 FRAME 목록 추출
        $file   = $this->client->getFile($fileKey);
        $frames = $file->getFrames();

        if (empty($frames)) {
            return new LayoutSpecSet([], [], [], 0);
        }

        // 2. 프레임 노드 데이터 배치 조회 (layoutGrids 포함)
        $frameIds  = array_column($frames, 'id');
        $nodesData = [];

        foreach (array_chunk($frameIds, self::BATCH_SIZE) as $batch) {
            $raw = $this->client->getFileNodes($fileKey, $batch);
            foreach ($raw['nodes'] ?? [] as $id => $node) {
                $nodesData[$id] = $node['document'] ?? [];
            }
        }

        // 3. 각 프레임의 레이아웃 정보 정규화
        $frameLayouts = [];
        foreach ($frames as $frame) {
            $id   = $frame['id'];
            $node = $nodesData[$id] ?? [];

            $frameLayouts[$id] = [
                'frame_name'  => $frame['name'],
                'grids'       => $node['layoutGrids'] ?? [],
                'width'       => $node['absoluteBoundingBox']['width'] ?? null,
                'height'      => $node['absoluteBoundingBox']['height'] ?? null,
                'padding'     => $this->extractPadding($node),
                'item_spacing' => $node['itemSpacing'] ?? null,
            ];
        }

        // 4. 그리드 패턴 그룹화
        $grouped = $this->groupSimilarLayouts($frameLayouts);

        // 5. 표준 식별
        $totalFrames     = count($frameLayouts);
        $standardLayouts = $this->identifyStandards($grouped, $totalFrames);

        // 6. 간격 스케일 분석
        $spacingScale = $this->analyzeSpacingScale($frameLayouts);

        // 7. 비표준 프레임 식별
        $nonStandard = $this->findNonStandardFrames($frameLayouts, array_keys($standardLayouts));

        $specSet = new LayoutSpecSet(
            standardLayouts:     $standardLayouts,
            spacingScale:        $spacingScale,
            nonStandardFrames:   $nonStandard,
            totalFramesAnalyzed: $totalFrames,
        );

        $specSet->setMetadata([
            'version' => '1.0',
            'source'  => [
                'type'         => 'figma',
                'file_key'     => $fileKey,
                'extracted_at' => now()->toIso8601String(),
            ],
        ]);

        return $specSet;
    }

    // ── Grouping ──────────────────────────────────────────────────────────────

    private function groupSimilarLayouts(array $frameLayouts): array
    {
        $groups = [];

        foreach ($frameLayouts as $nodeId => $layout) {
            $colGrid = $this->findColumnGrid($layout['grids']);
            $key     = $this->makeGroupKey($colGrid);

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'spec'   => $colGrid,
                    'frames' => [],
                ];
            }

            $groups[$key]['frames'][] = [
                'node_id'    => $nodeId,
                'frame_name' => $layout['frame_name'],
                'width'      => $layout['width'],
            ];
        }

        return $groups;
    }

    private function makeGroupKey(?array $colGrid): string
    {
        if (!$colGrid) return 'freeform';
        return sprintf(
            'cols-%d-gutter-%d-margin-%d',
            (int) ($colGrid['count']      ?? 0),
            (int) ($colGrid['gutterSize'] ?? 0),
            (int) ($colGrid['offset']     ?? 0),
        );
    }

    // ── Standard identification ───────────────────────────────────────────────

    private function identifyStandards(array $groups, int $totalFrames): array
    {
        if ($totalFrames === 0) return [];

        $standards = [];

        foreach ($groups as $key => $group) {
            $count   = count($group['frames']);
            $percent = ($count / $totalFrames) * 100;

            if ($percent < self::STD_THRESHOLD) continue;

            $spec  = $group['spec'];
            $nSpec = $this->normalizeGridSpec($spec);

            // 이름 자동 생성
            $name = $this->generateStandardName($nSpec, $group['frames']);

            $standards[$key] = [
                'name'           => $name,
                'description'    => '',
                'usage_count'    => $count,
                'usage_percent'  => round($percent, 1),
                'used_in_frames' => array_column($group['frames'], 'node_id'),
                'frame_names'    => array_column($group['frames'], 'frame_name'),
                'spec'           => $nSpec,
            ];
        }

        // 사용 빈도 내림차순 정렬
        uasort($standards, fn($a, $b) => $b['usage_count'] <=> $a['usage_count']);

        return $standards;
    }

    private function generateStandardName(array $spec, array $frames): string
    {
        if ($spec['type'] === 'freeform') {
            return 'Freeform (No Grid)';
        }

        $cols   = $spec['columns'] ?? '?';
        $gutter = $spec['gutter']  ?? '?';

        // 프레임 너비로 breakpoint 유추
        $widths = array_filter(array_column($frames, 'width'));
        $avgW   = empty($widths) ? 0 : (int) (array_sum($widths) / count($widths));

        $bpLabel = match(true) {
            $avgW >= 1440 => 'Desktop Wide',
            $avgW >= 1024 => 'Desktop',
            $avgW >= 768  => 'Tablet',
            $avgW > 0     => 'Mobile',
            default       => '',
        };

        return trim("{$bpLabel} {$cols} Column Grid ({$gutter} gutter)");
    }

    private function normalizeGridSpec(?array $spec): array
    {
        if (!$spec) {
            return ['type' => 'freeform'];
        }

        return [
            'type'      => 'grid',
            'pattern'   => $spec['pattern']    ?? 'COLUMNS',
            'columns'   => (int) ($spec['count']       ?? 0),
            'gutter'    => (int) ($spec['gutterSize']  ?? 0) . 'px',
            'margin'    => (int) ($spec['offset']      ?? 0) . 'px',
            'alignment' => $spec['alignment'] ?? 'STRETCH',
        ];
    }

    // ── Spacing scale ─────────────────────────────────────────────────────────

    private function analyzeSpacingScale(array $frameLayouts): array
    {
        $counts = [];

        foreach ($frameLayouts as $layout) {
            $this->tallySpacing($layout['item_spacing'], $counts);
            foreach ($layout['padding'] as $v) {
                $this->tallySpacing($v, $counts);
            }
        }

        if (empty($counts)) {
            return ['name' => 'Standard Spacing Scale', 'values' => [], 'usage_count' => []];
        }

        arsort($counts);

        // 상위 8개 값
        $topValues = array_slice(array_keys($counts), 0, 8);
        sort($topValues);

        // usage_count는 px 단위 레이블로
        $usageCount = [];
        foreach ($counts as $val => $c) {
            $usageCount[$val . 'px'] = $c;
        }

        return [
            'name'        => 'Standard Spacing Scale',
            'values'      => array_map(fn($v) => $v . 'px', $topValues),
            'usage_count' => $usageCount,
        ];
    }

    private function tallySpacing(mixed $value, array &$counts): void
    {
        if (!is_numeric($value) || $value <= 0) return;
        $v = (int) round((float) $value);
        if ($v > 0 && $v <= 200) { // 200px 이하만 (너무 큰 값 제외)
            $counts[$v] = ($counts[$v] ?? 0) + 1;
        }
    }

    // ── Non-standard frames ───────────────────────────────────────────────────

    private function findNonStandardFrames(array $frameLayouts, array $standardKeys): array
    {
        $nonStandard = [];

        foreach ($frameLayouts as $nodeId => $layout) {
            $colGrid = $this->findColumnGrid($layout['grids']);
            $key     = $this->makeGroupKey($colGrid);

            if (!in_array($key, $standardKeys, true)) {
                $nonStandard[] = [
                    'node_id'    => $nodeId,
                    'frame_name' => $layout['frame_name'],
                    'deviation'  => $this->describeDeviation($colGrid, $standardKeys),
                    'severity'   => 'warning',
                ];
            }
        }

        return $nonStandard;
    }

    private function describeDeviation(?array $grid, array $standardKeys): string
    {
        if (!$grid) {
            $hasGridStds = collect($standardKeys)->contains(fn($k) => $k !== 'freeform');
            return $hasGridStds ? 'No grid system (standards use grid)' : 'No grid system';
        }

        $cols        = (int) ($grid['count'] ?? 0);
        $stdCols     = collect($standardKeys)
            ->filter(fn($k) => $k !== 'freeform')
            ->map(fn($k) => (int) (explode('-', $k)[1] ?? 0))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (!in_array($cols, $stdCols, true)) {
            $stdList = implode(', ', $stdCols);
            return "Uses {$cols} columns (standards: {$stdList})";
        }

        return 'Custom gutter/margin (differs from standards)';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function findColumnGrid(array $grids): ?array
    {
        foreach ($grids as $grid) {
            if (($grid['pattern'] ?? '') === 'COLUMNS') {
                return $grid;
            }
        }
        return null;
    }

    private function extractPadding(array $node): array
    {
        return [
            'top'    => $node['paddingTop']    ?? 0,
            'right'  => $node['paddingRight']  ?? 0,
            'bottom' => $node['paddingBottom'] ?? 0,
            'left'   => $node['paddingLeft']   ?? 0,
        ];
    }
}
