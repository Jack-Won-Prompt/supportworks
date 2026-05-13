<?php

namespace App\Services\Agent\Figma;

use App\Services\Agent\Figma\Contracts\FigmaClient;
use Illuminate\Support\Str;

class DesignTokenExtractor
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly FigmaClient $client,
    ) {}

    public function extract(string $fileKey): DesignTokenSet
    {
        $styles  = $this->client->getFileStyles($fileKey);
        $nodeIds = array_column($styles, 'node_id');

        // 배치로 노드 데이터 수집 (URL 길이 제한 대비)
        $nodesData = [];
        foreach (array_chunk($nodeIds, self::BATCH_SIZE) as $batch) {
            $raw = $this->client->getFileNodes($fileKey, $batch);
            foreach ($raw['nodes'] ?? [] as $id => $node) {
                $nodesData[$id] = $node['document'] ?? [];
            }
        }

        $tokenSet = new DesignTokenSet();

        foreach ($styles as $style) {
            $nodeId   = $style['node_id']    ?? '';
            $type     = $style['style_type'] ?? ($style['styleType'] ?? '');
            $name     = $style['name']       ?? '';
            $desc     = $style['description'] ?? null;
            $document = $nodesData[$nodeId]  ?? [];

            if (empty($document)) continue;

            $path = $this->parseName($name);
            if (empty($path)) continue;

            $category = match ($type) {
                'FILL'   => 'color',
                'TEXT'   => 'typography',
                'EFFECT' => 'shadow',
                'GRID'   => 'layout',
                default  => null,
            };

            if ($category) {
                $styleKey = $style['key'] ?? '';
                $tokenPath = implode('.', [$category, ...array_map(fn($p) => str_replace('-', '_', $p), $path)]);
                $tokenSet->addStyleKeyMapping($styleKey, $tokenPath);
            }

            match ($type) {
                'FILL'   => $this->extractColor($tokenSet, $path, $document, $desc),
                'TEXT'   => $this->extractTypography($tokenSet, $path, $document, $desc),
                'EFFECT' => $this->extractEffect($tokenSet, $path, $document, $desc),
                'GRID'   => $this->extractLayout($tokenSet, $path, $document, $desc),
                default  => null,
            };
        }

        return $tokenSet;
    }

    // ── Extractors ────────────────────────────────────────────────────────────

    private function extractColor(DesignTokenSet $tokenSet, array $path, array $document, ?string $desc): void
    {
        $fills = $document['fills'] ?? [];
        if (empty($fills)) return;

        $fill = $fills[0];
        if (($fill['type'] ?? '') !== 'SOLID') return;

        $c   = $fill['color'] ?? [];
        $hex = $this->rgbaToHex(
            $c['r'] ?? 0,
            $c['g'] ?? 0,
            $c['b'] ?? 0,
            $c['a'] ?? 1,
        );

        $tokenSet->addToken('color', $path, $hex, 'color', $desc);
    }

    private function extractTypography(DesignTokenSet $tokenSet, array $path, array $document, ?string $desc): void
    {
        $style = $document['style'] ?? [];
        if (empty($style)) return;

        $tokenSet->addToken('typography', $path, [
            'fontFamily'    => $style['fontFamily']  ?? 'inherit',
            'fontSize'      => ($style['fontSize']   ?? 16) . 'px',
            'fontWeight'    => $style['fontWeight']  ?? 400,
            'lineHeight'    => $this->parseLineHeight($style),
            'letterSpacing' => $this->parseLetterSpacing($style),
        ], 'typography', $desc);
    }

    private function extractEffect(DesignTokenSet $tokenSet, array $path, array $document, ?string $desc): void
    {
        $effects = array_filter(
            $document['effects'] ?? [],
            fn($e) => ($e['type'] ?? '') === 'DROP_SHADOW' && ($e['visible'] ?? true),
        );

        if (empty($effects)) return;

        $shadows = array_values(array_map(fn($e) => [
            'x'      => ($e['offset']['x']  ?? 0) . 'px',
            'y'      => ($e['offset']['y']  ?? 0) . 'px',
            'blur'   => ($e['radius']       ?? 0) . 'px',
            'spread' => ($e['spread']       ?? 0) . 'px',
            'color'  => $this->rgbaToHex(
                $e['color']['r'] ?? 0,
                $e['color']['g'] ?? 0,
                $e['color']['b'] ?? 0,
                $e['color']['a'] ?? 1,
            ),
        ], $effects));

        $value = count($shadows) === 1 ? $shadows[0] : $shadows;
        $tokenSet->addToken('shadow', $path, $value, 'shadow', $desc);
    }

    private function extractLayout(DesignTokenSet $tokenSet, array $path, array $document, ?string $desc): void
    {
        $grids = $document['layoutGrids'] ?? [];
        if (empty($grids)) return;

        $columnGrid = null;
        foreach ($grids as $grid) {
            if (($grid['pattern'] ?? '') === 'COLUMNS') {
                $columnGrid = $grid;
                break;
            }
        }

        if (!$columnGrid) return;

        $tokenSet->addToken('layout', $path, [
            'columns' => $columnGrid['count']      ?? 12,
            'gutter'  => ($columnGrid['gutterSize'] ?? 24) . 'px',
            'margin'  => ($columnGrid['offset']     ?? 16) . 'px',
        ], 'grid', $desc);
    }

    // ── Utilities ─────────────────────────────────────────────────────────────

    /**
     * "Primary/500" → ["primary", "500"]
     * "Heading/H1"  → ["heading", "h1"]
     */
    private function parseName(string $name): array
    {
        return collect(explode('/', $name))
            ->map(fn($p) => Str::slug(trim($p), '-'))
            ->filter()
            ->values()
            ->all();
    }

    private function rgbaToHex(float $r, float $g, float $b, float $a = 1.0): string
    {
        $ri  = (int) round($r * 255);
        $gi  = (int) round($g * 255);
        $bi  = (int) round($b * 255);
        $hex = sprintf('#%02X%02X%02X', $ri, $gi, $bi);

        if ($a < 0.999) {
            $hex .= sprintf('%02X', (int) round($a * 255));
        }

        return $hex;
    }

    private function parseLineHeight(array $style): string
    {
        if (isset($style['lineHeightPx'])) {
            return round($style['lineHeightPx'], 2) . 'px';
        }
        if (isset($style['lineHeightPercent']) && $style['lineHeightPercent'] !== 100) {
            return round($style['lineHeightPercent'] / 100, 3) . '';
        }
        return 'normal';
    }

    private function parseLetterSpacing(array $style): string
    {
        if (isset($style['letterSpacing']) && $style['letterSpacing'] !== 0) {
            return round($style['letterSpacing'], 3) . 'px';
        }
        return 'normal';
    }
}
