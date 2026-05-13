<?php

namespace App\Services\Agent\Figma;

use App\Services\Agent\Figma\Contracts\FigmaClient;
use Illuminate\Support\Str;

class ComponentExtractor
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly FigmaClient $client,
    ) {}

    public function extract(string $fileKey, ?DesignTokenSet $tokens = null): ComponentSpecSet
    {
        $components = $this->client->getFileComponents($fileKey);

        if (empty($components)) {
            return new ComponentSpecSet();
        }

        // ComponentSet vs standalone 분류
        $grouped = $this->groupByComponentSet($components);

        // 대표 노드 ID 목록 (ComponentSet node ID 또는 단일 component node ID)
        $representativeNodeIds = array_keys($grouped);

        // 노드 데이터 배치 조회
        $nodesData = [];
        foreach (array_chunk($representativeNodeIds, self::BATCH_SIZE) as $batch) {
            $raw = $this->client->getFileNodes($fileKey, $batch);
            foreach ($raw['nodes'] ?? [] as $id => $node) {
                $nodesData[$id] = $node['document'] ?? [];
            }
        }

        $spec = new ComponentSpecSet();
        $spec->setMetadata([
            'version' => '1.0',
            'source'  => [
                'type'         => 'figma',
                'file_key'     => $fileKey,
                'extracted_at' => now()->toIso8601String(),
            ],
        ]);

        foreach ($grouped as $nodeId => $componentData) {
            $node = $nodesData[$nodeId] ?? null;

            // 노드 데이터가 없으면 component metadata에서 최소 정보 사용
            $componentSpec = $this->buildComponentSpec($nodeId, $node, $componentData);

            if ($tokens) {
                $componentSpec['tokens_used'] = $node
                    ? $this->mapTokens($node, $tokens)
                    : [];
            }

            $key = Str::slug($componentSpec['name']);
            if ($key === '') {
                $key = 'component-' . $nodeId;
            }
            $spec->addComponent($key, $componentSpec);
        }

        // 미리보기 이미지 일괄 요청
        if (!empty($representativeNodeIds)) {
            try {
                $previews = $this->client->getImages($fileKey, $representativeNodeIds, 'png', 1.0);
                $spec->attachPreviews($previews);
            } catch (\Exception) {
                // 이미지 실패는 무시 (선택 기능)
            }
        }

        return $spec;
    }

    // ── Grouping ──────────────────────────────────────────────────────────────

    /**
     * component_set_id 기준으로 그룹화.
     * - component_set_id 있음 → variants 배열로 묶기 (key = component_set_id)
     * - component_set_id 없음 → 단일 컴포넌트 (key = node_id)
     */
    private function groupByComponentSet(array $components): array
    {
        $grouped = [];

        foreach ($components as $component) {
            $nodeId  = $component['node_id']          ?? ($component['nodeId'] ?? '');
            $setId   = $component['component_set_id'] ?? ($component['componentSetId'] ?? null);

            if ($setId) {
                if (!isset($grouped[$setId])) {
                    $grouped[$setId] = ['type' => 'ComponentSet', 'variants' => [], 'data' => null];
                }
                $grouped[$setId]['variants'][$nodeId] = $component;
            } else {
                $grouped[$nodeId] = [
                    'type'     => 'Component',
                    'variants' => [],
                    'data'     => $component,
                ];
            }
        }

        return $grouped;
    }

    // ── Spec builder ──────────────────────────────────────────────────────────

    private function buildComponentSpec(string $nodeId, ?array $node, array $componentData): array
    {
        $type = $componentData['type'];

        // name: 노드 데이터 우선, 없으면 첫 variant 이름에서 컴포넌트명 추출
        $name = $node['name'] ?? $this->inferName($componentData);
        $desc = $node['description'] ?? '';

        $spec = [
            'name'          => $name,
            'type'          => $type,
            'description'   => $desc,
            'figma_node_id' => $nodeId,
            'preview_url'   => null,
            'documentation' => '',
        ];

        if ($type === 'ComponentSet') {
            $variants                = $componentData['variants'];
            $spec['props']           = $this->extractPropsFromVariants($variants);
            $spec['variants_count']  = count($variants);
        } else {
            $spec['props']          = [];
            $spec['variants_count'] = 1;
        }

        return $spec;
    }

    private function inferName(array $componentData): string
    {
        if ($componentData['type'] === 'Component' && $componentData['data']) {
            // "Button/Primary" → "Button"
            $raw = $componentData['data']['name'] ?? '';
            $parts = explode('/', $raw);
            return trim($parts[0]);
        }

        // ComponentSet: 첫 variant 이름에서 추출 ("Button" from "Type=Primary,...")
        $firstVariant = reset($componentData['variants']);
        if ($firstVariant) {
            $variantName = $firstVariant['name'] ?? '';
            // "Button" 부분만 (앞에 슬래시로 구분된 경우)
            $parts = explode('/', $variantName);
            return trim($parts[0]);
        }

        return 'Untitled';
    }

    // ── Props extraction ──────────────────────────────────────────────────────

    /**
     * variant 이름 "Type=Primary, Size=Medium, State=Default" 파싱
     */
    private function parseVariantProps(string $variantName): array
    {
        $props = [];
        // "Button/Type=Primary, Size=Medium" → "Type=Primary, Size=Medium"
        if (str_contains($variantName, '/')) {
            $variantName = substr($variantName, strrpos($variantName, '/') + 1);
        }

        foreach (explode(',', $variantName) as $pair) {
            $parts = explode('=', trim($pair), 2);
            if (count($parts) === 2) {
                $key          = Str::slug(trim($parts[0]), '_');
                $props[$key]  = trim($parts[1]);
            }
        }

        return $props;
    }

    /**
     * 모든 variants에서 props 값 집합 도출
     */
    private function extractPropsFromVariants(array $variants): array
    {
        $allProps = [];
        $first    = true;

        foreach ($variants as $variant) {
            $name  = $variant['name'] ?? '';
            $props = $this->parseVariantProps($name);

            foreach ($props as $key => $value) {
                if (!isset($allProps[$key])) {
                    $allProps[$key] = ['values' => [], 'default' => $value];
                }
                if (!in_array($value, $allProps[$key]['values'], true)) {
                    $allProps[$key]['values'][] = $value;
                }
            }

            $first = false;
        }

        return $allProps;
    }

    // ── Token mapping ─────────────────────────────────────────────────────────

    private function mapTokens(array $node, DesignTokenSet $tokens): array
    {
        $used = [];
        $this->scanNodeForTokens($node, $tokens, $used);
        return array_values(array_unique($used));
    }

    private function scanNodeForTokens(array $node, DesignTokenSet $tokens, array &$result): void
    {
        // node['styles'] = {"fills": "styleKey123", "text": "styleKey456", ...}
        foreach ($node['styles'] ?? [] as $styleKey) {
            $path = $tokens->findByStyleKey((string) $styleKey);
            if ($path) {
                $result[] = $path;
            }
        }

        foreach ($node['children'] ?? [] as $child) {
            $this->scanNodeForTokens($child, $tokens, $result);
        }
    }
}
