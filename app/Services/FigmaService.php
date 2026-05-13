<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FigmaService
{
    private const BASE = 'https://api.figma.com/v1';

    public function __construct(private string $token) {}

    private function get(string $path): array
    {
        $res = Http::withOptions(['verify' => false])
            ->withHeaders(['X-Figma-Token' => $this->token])
            ->get(self::BASE . $path);

        if (!$res->successful()) {
            throw new \RuntimeException('Figma API 오류: ' . ($res->json('message') ?? $res->status()));
        }

        return $res->json();
    }

    public function getFile(string $fileKey): array
    {
        return $this->get("/files/{$fileKey}?depth=1");
    }

    public function getThumbnail(string $fileKey): ?string
    {
        try {
            $data = $this->get("/files/{$fileKey}?depth=0");
            return $data['thumbnailUrl'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** 모든 페이지와 프레임 이름 목록 */
    public function getStructure(string $fileKey): array
    {
        $data   = $this->getFile($fileKey);
        $pages  = [];
        foreach ($data['document']['children'] ?? [] as $page) {
            $frames = collect($page['children'] ?? [])
                ->where('type', 'FRAME')
                ->pluck('name')
                ->values()
                ->toArray();
            $pages[] = ['page' => $page['name'], 'frames' => $frames];
        }
        return ['name' => $data['name'] ?? 'Figma File', 'pages' => $pages];
    }

    /** 특정 노드의 이미지 URL 취득 */
    public function getImages(string $fileKey, array $nodeIds): array
    {
        $ids = implode(',', $nodeIds);
        $res = $this->get("/images/{$fileKey}?ids={$ids}&format=svg");
        return $res['images'] ?? [];
    }

    /** 특정 노드 상세 데이터 (depth 지정) */
    public function getNodes(string $fileKey, array $nodeIds, int $depth = 5): array
    {
        $ids = implode(',', $nodeIds);
        try {
            $res = $this->get("/files/{$fileKey}/nodes?ids={$ids}&depth={$depth}");
            return $res['nodes'] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    /** 게시된 스타일 목록 (색상·텍스트·이펙트) */
    public function getFileStyles(string $fileKey): array
    {
        try {
            $res = $this->get("/files/{$fileKey}/styles");
            return $res['meta']['styles'] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    /** 게시된 컴포넌트 목록 */
    public function getFileComponents(string $fileKey): array
    {
        try {
            $res = $this->get("/files/{$fileKey}/components");
            return $res['meta']['components'] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * 웍스 컨텍스트용 종합 Figma 데이터 빌드.
     * 파일 구조 + 디자인 토큰 + 컴포넌트 목록 + 대상 노드 상세 + SVG 내용을 하나의 문자열로 반환.
     */
    public function buildAiContext(string $fileKey, ?string $nodeId = null): string
    {
        $parts = [];

        // ── 1. 파일명 & 페이지 구조 ──────────────────────────────
        try {
            $struct  = $this->getStructure($fileKey);
            $parts[] = "## Figma 파일: {$struct['name']}";
            foreach ($struct['pages'] as $page) {
                $frames = implode(', ', array_column($page['frames'], 'name') ?: $page['frames']);
                if ($frames) $parts[] = "  페이지 [{$page['page']}]: {$frames}";
            }
        } catch (\Throwable $e) {
            $parts[] = "## Figma 파일 구조 로드 실패: " . $e->getMessage();
        }

        // ── 2. 디자인 토큰 (스타일) ──────────────────────────────
        $styles      = $this->getFileStyles($fileKey);
        $colorStyles = array_filter($styles, fn($s) => ($s['style_type'] ?? '') === 'FILL');
        $textStyles  = array_filter($styles, fn($s) => ($s['style_type'] ?? '') === 'TEXT');
        $effectStyles= array_filter($styles, fn($s) => ($s['style_type'] ?? '') === 'EFFECT');

        if ($colorStyles) {
            $parts[] = "\n## 색상 토큰 (" . count($colorStyles) . "개):";
            foreach (array_slice(array_values($colorStyles), 0, 30) as $s) {
                $parts[] = "  - {$s['name']}";
            }
        }
        if ($textStyles) {
            $parts[] = "\n## 텍스트 스타일 (" . count($textStyles) . "개):";
            foreach (array_slice(array_values($textStyles), 0, 20) as $s) {
                $parts[] = "  - {$s['name']}";
            }
        }
        if ($effectStyles) {
            $names = implode(', ', array_column(array_values($effectStyles), 'name'));
            $parts[] = "\n## 이펙트 스타일: {$names}";
        }

        // ── 3. 컴포넌트 목록 ─────────────────────────────────────
        $components = $this->getFileComponents($fileKey);
        if ($components) {
            $parts[] = "\n## 컴포넌트 목록 (" . count($components) . "개):";
            foreach (array_slice($components, 0, 40) as $c) {
                $desc = $c['description'] ?? '';
                $parts[] = "  - {$c['name']}" . ($desc ? ": {$desc}" : '');
            }
        }

        // ── 4. 대상 노드 상세 ────────────────────────────────────
        if ($nodeId) {
            $normalizedId = str_replace('-', ':', $nodeId);
            $nodes = $this->getNodes($fileKey, [$normalizedId], 5);
            if (!empty($nodes)) {
                $nodeData = reset($nodes);
                $doc      = $nodeData['document'] ?? null;
                if ($doc) {
                    $parts[] = "\n## 대상 노드: {$doc['name']} (type: {$doc['type']})";
                    $parts[] = $this->simplifyNodeToText($doc, 0);
                }
            }

            // SVG 추출
            try {
                $images = $this->getImages($fileKey, [$normalizedId]);
                $svgUrl = $images[$normalizedId] ?? null;
                if ($svgUrl) {
                    $svgContent = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])
                        ->timeout(10)->get($svgUrl)->body();
                    if (str_contains($svgContent, '<svg')) {
                        $parts[] = "\n## SVG 코드:";
                        $parts[] = "```svg\n" . mb_substr($svgContent, 0, 4000) . "\n```";
                    }
                }
            } catch (\Throwable) {}
        }

        return implode("\n", $parts);
    }

    /** 노드를 웍스가 읽기 쉬운 텍스트 구조로 변환 */
    private function simplifyNodeToText(array $node, int $depth): string
    {
        if ($depth > 5) return '';
        $indent = str_repeat('  ', $depth);
        $type   = $node['type'] ?? '';
        $name   = $node['name'] ?? '';
        $props  = [];

        // 크기
        if (!empty($node['absoluteBoundingBox'])) {
            $bb      = $node['absoluteBoundingBox'];
            $props[] = sprintf('%dx%d', round($bb['width']), round($bb['height']));
        }

        // Auto Layout
        if (!empty($node['layoutMode'])) {
            $dir  = $node['layoutMode'] === 'HORIZONTAL' ? 'row' : 'column';
            $gap  = $node['itemSpacing'] ?? 0;
            $pT   = $node['paddingTop']    ?? 0;
            $pR   = $node['paddingRight']  ?? 0;
            $pB   = $node['paddingBottom'] ?? 0;
            $pL   = $node['paddingLeft']   ?? 0;
            $jc   = strtolower($node['primaryAxisAlignItems']  ?? 'start');
            $ai   = strtolower($node['counterAxisAlignItems']  ?? 'start');
            $props[] = "flex-{$dir} gap:{$gap}px pad:{$pT}/{$pR}/{$pB}/{$pL}px justify:{$jc} align:{$ai}";
        }

        // 채우기 색상
        foreach ($node['fills'] ?? [] as $fill) {
            if (($fill['type'] ?? '') === 'SOLID' && !empty($fill['color'])) {
                $c  = $fill['color'];
                $hex = sprintf('#%02x%02x%02x',
                    round($c['r'] * 255), round($c['g'] * 255), round($c['b'] * 255));
                $op  = round(($fill['opacity'] ?? 1) * 100);
                $props[] = "fill:{$hex}" . ($op < 100 ? "@{$op}%" : '');
                break;
            }
        }

        // 타이포그래피
        if (!empty($node['style'])) {
            $s = $node['style'];
            $parts = [];
            if (!empty($s['fontFamily']))  $parts[] = $s['fontFamily'];
            if (!empty($s['fontSize']))    $parts[] = $s['fontSize'] . 'px';
            if (!empty($s['fontWeight']))  $parts[] = 'w' . $s['fontWeight'];
            if (!empty($s['lineHeightPx'])) $parts[] = 'lh' . round($s['lineHeightPx']) . 'px';
            if (!empty($s['letterSpacing'])) $parts[] = 'ls' . round($s['letterSpacing'], 1);
            if (!empty($s['textAlignHorizontal'])) $parts[] = strtolower($s['textAlignHorizontal']);
            if ($parts) $props[] = 'font:' . implode('/', $parts);
        }

        // Border radius
        if (!empty($node['cornerRadius'])) {
            $props[] = "radius:{$node['cornerRadius']}px";
        }

        // 테두리
        if (!empty($node['strokes'][0]['color'])) {
            $c   = $node['strokes'][0]['color'];
            $hex = sprintf('#%02x%02x%02x',
                round($c['r'] * 255), round($c['g'] * 255), round($c['b'] * 255));
            $w   = $node['strokeWeight'] ?? 1;
            $props[] = "border:{$w}px solid {$hex}";
        }

        // 텍스트 내용
        if ($type === 'TEXT' && !empty($node['characters'])) {
            $text    = mb_substr($node['characters'], 0, 60);
            $props[] = 'text:"' . str_replace('"', "'", $text) . '"';
        }

        $propStr   = $props ? ' [' . implode(' | ', $props) . ']' : '';
        $line      = "{$indent}- {$name} ({$type}){$propStr}";
        $childText = '';

        if (!empty($node['children'])) {
            $childLines = [];
            foreach (array_slice($node['children'], 0, 20) as $child) {
                $cl = $this->simplifyNodeToText($child, $depth + 1);
                if ($cl) $childLines[] = $cl;
            }
            if ($childLines) $childText = "\n" . implode("\n", $childLines);
        }

        return $line . $childText;
    }
}
