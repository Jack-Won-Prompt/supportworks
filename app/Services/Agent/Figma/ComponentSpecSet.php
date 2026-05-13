<?php

namespace App\Services\Agent\Figma;

use Illuminate\Support\Str;

class ComponentSpecSet
{
    private array $components = [];
    private array $metadata   = [];

    public function setMetadata(array $meta): void
    {
        $this->metadata = $meta;
    }

    public function addComponent(string $key, array $spec): void
    {
        $this->components[$key] = $spec;
    }

    public function attachPreviews(array $previews): void
    {
        // $previews: { nodeId => imageUrl }
        foreach ($this->components as $key => &$component) {
            $nodeId = $component['figma_node_id'] ?? '';
            if ($nodeId && isset($previews[$nodeId])) {
                $component['preview_url'] = $previews[$nodeId];
            }
        }
        unset($component);
    }

    public function getComponents(): array
    {
        return $this->components;
    }

    public function getStats(): array
    {
        $components = collect($this->components);
        return [
            'total_components'  => $components->count(),
            'component_sets'    => $components->where('type', 'ComponentSet')->count(),
            'single_components' => $components->where('type', 'Component')->count(),
            'total_variants'    => (int) $components->sum('variants_count'),
        ];
    }

    public function toArray(): array
    {
        return [
            '$metadata'  => array_merge($this->metadata, ['stats' => $this->getStats()]),
            'components' => $this->components,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function toMarkdown(): string
    {
        $stats = $this->getStats();
        $lines = [
            '# Component Library',
            '',
            "총 {$stats['total_components']}개 컴포넌트 (Variants {$stats['total_variants']}개)",
            '',
        ];

        foreach ($this->components as $component) {
            $name    = $component['name']        ?? 'Untitled';
            $type    = $component['type']        ?? 'Component';
            $desc    = $component['description'] ?? '';
            $preview = $component['preview_url'] ?? null;
            $props   = $component['props']       ?? [];
            $tokens  = $component['tokens_used'] ?? [];

            $lines[] = "## {$name} ({$type})";
            $lines[] = '';

            if ($desc) {
                $lines[] = $desc;
                $lines[] = '';
            }

            if ($preview) {
                $lines[] = "![{$name}]({$preview})";
                $lines[] = '';
            }

            if (!empty($props)) {
                $lines[] = '### Props';
                $lines[] = '| 이름 | 값 | 기본값 |';
                $lines[] = '|------|-----|-------|';
                foreach ($props as $propName => $prop) {
                    $values  = implode(', ', $prop['values'] ?? []);
                    $default = $prop['default'] ?? '';
                    $lines[] = "| {$propName} | {$values} | {$default} |";
                }
                $lines[] = '';
            }

            if (!empty($tokens)) {
                $lines[] = '### 사용된 토큰';
                foreach ($tokens as $token) {
                    $lines[] = "- `{$token}`";
                }
                $lines[] = '';
            }

            $lines[] = '---';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    public function isEmpty(): bool
    {
        return empty($this->components);
    }
}
