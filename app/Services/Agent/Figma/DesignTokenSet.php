<?php

namespace App\Services\Agent\Figma;

class DesignTokenSet
{
    private array $tokens      = [];
    private array $metadata    = [];
    private array $styleKeyMap = []; // Figma style key → dot-joined token path

    public function addMetadata(array $meta): void
    {
        $this->metadata = array_merge($this->metadata, $meta);
    }

    public function addToken(
        string  $category,
        array   $path,
        mixed   $value,
        string  $type,
        ?string $description = null,
    ): void {
        if (empty($path)) return;

        if (!isset($this->tokens[$category])) {
            $this->tokens[$category] = [];
        }

        $node = &$this->tokens[$category];

        foreach ($path as $i => $segment) {
            if ($i === count($path) - 1) {
                $entry = ['$value' => $value, '$type' => $type];
                if ($description) $entry['$description'] = $description;
                $node[$segment] = $entry;
            } else {
                if (!isset($node[$segment]) || !is_array($node[$segment])) {
                    $node[$segment] = [];
                }
                $node = &$node[$segment];
            }
        }
    }

    public function toArray(): array
    {
        return ['$metadata' => $this->metadata, ...$this->tokens];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function getCategoryTokens(string $category): array
    {
        return $this->tokens[$category] ?? [];
    }

    public function getCategories(): array
    {
        return array_keys($this->tokens);
    }

    public function getTokenCount(): int
    {
        return $this->countLeaves($this->tokens);
    }

    public function getCategoryCount(string $category): int
    {
        return $this->countLeaves($this->tokens[$category] ?? []);
    }

    public function isEmpty(): bool
    {
        return empty($this->tokens);
    }

    /**
     * All color hex values defined in the token set.
     */
    public function getColorValues(): array
    {
        $values = [];
        $this->collectLeafValues($this->tokens['color'] ?? [], 'color', $values);
        return array_values(array_unique($values));
    }

    /**
     * Find the dot-path token name for a given raw value (e.g. "#3B82F6" → "color.primary.500").
     */
    public function findTokenByValue(mixed $searchValue): ?string
    {
        return $this->searchLeafByValue($this->tokens, '', $searchValue);
    }

    /**
     * Flat list of token paths + values for the given category (for 웍스 context).
     * Returns: [["path" => "color.primary.500", "value" => "#3B82F6"], ...]
     */
    public function flattenCategory(string $category): array
    {
        $result = [];
        $this->flattenLeaves($this->tokens[$category] ?? [], $category, $result);
        return $result;
    }

    private function collectLeafValues(array $node, string $type, array &$values): void
    {
        if (isset($node['$value'])) {
            $v = $node['$value'];
            if (is_string($v) && $v !== '') {
                $values[] = $v;
            }
            return;
        }
        foreach ($node as $key => $child) {
            if (str_starts_with((string) $key, '$')) continue;
            if (is_array($child)) {
                $this->collectLeafValues($child, $type, $values);
            }
        }
    }

    private function searchLeafByValue(array $node, string $path, mixed $search): ?string
    {
        if (isset($node['$value'])) {
            return $node['$value'] === $search ? ltrim($path, '.') : null;
        }
        foreach ($node as $key => $child) {
            if (str_starts_with((string) $key, '$')) continue;
            if (is_array($child)) {
                $found = $this->searchLeafByValue($child, "{$path}.{$key}", $search);
                if ($found !== null) return $found;
            }
        }
        return null;
    }

    private function flattenLeaves(array $node, string $path, array &$result): void
    {
        if (isset($node['$value'])) {
            $result[] = ['path' => ltrim($path, '.'), 'value' => $node['$value'], 'type' => $node['$type'] ?? null];
            return;
        }
        foreach ($node as $key => $child) {
            if (str_starts_with((string) $key, '$')) continue;
            if (is_array($child)) {
                $this->flattenLeaves($child, "{$path}.{$key}", $result);
            }
        }
    }

    public function addStyleKeyMapping(string $styleKey, string $tokenPath): void
    {
        if ($styleKey !== '') {
            $this->styleKeyMap[$styleKey] = $tokenPath;
        }
    }

    public function findByStyleKey(string $styleKey): ?string
    {
        return $this->styleKeyMap[$styleKey] ?? null;
    }

    public function toCss(): string
    {
        $vars = [];
        $this->flattenToCssVars($this->tokens, '', $vars);

        $lines = [':root {'];
        foreach ($vars as $k => $v) {
            $lines[] = "    {$k}: {$v};";
        }
        $lines[] = '}';
        return implode("\n", $lines);
    }

    public function toTailwindConfig(): string
    {
        $colors    = $this->buildTailwindColors($this->tokens['color'] ?? []);
        $fonts     = $this->buildTailwindFonts($this->tokens['typography'] ?? []);
        $fontSizes = $this->buildTailwindFontSizes($this->tokens['typography'] ?? []);

        $json = fn($v) => json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<JS
/** @type {import('tailwindcss').Config} */
module.exports = {
    theme: {
        extend: {
            colors: {$json($colors)},
            fontFamily: {$json($fonts)},
            fontSize: {$json($fontSizes)},
        },
    },
};
JS;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function countLeaves(array $node): int
    {
        if (isset($node['$value'])) return 1;
        $count = 0;
        foreach ($node as $key => $child) {
            if (str_starts_with((string) $key, '$')) continue;
            if (is_array($child)) $count += $this->countLeaves($child);
        }
        return $count;
    }

    private function flattenToCssVars(array $node, string $prefix, array &$vars): void
    {
        if (isset($node['$value'])) {
            $key   = '--' . ltrim(preg_replace('/[^a-z0-9-]/', '-', strtolower($prefix)), '-');
            $value = $node['$value'];

            if (is_string($value)) {
                $vars[$key] = $value;
            } elseif (is_array($value)) {
                $type = $node['$type'] ?? '';
                if ($type === 'shadow') {
                    $vars[$key] = "{$value['x']} {$value['y']} {$value['blur']} {$value['spread']} {$value['color']}";
                } elseif ($type === 'typography') {
                    foreach (['fontFamily', 'fontSize', 'fontWeight', 'lineHeight', 'letterSpacing'] as $prop) {
                        if (isset($value[$prop])) {
                            $propKey          = $key . '-' . strtolower(preg_replace('/([A-Z])/', '-$1', $prop));
                            $vars[$propKey]   = (string) $value[$prop];
                        }
                    }
                }
            }
            return;
        }

        foreach ($node as $key => $child) {
            if (str_starts_with((string) $key, '$')) continue;
            if (is_array($child)) {
                $next = $prefix ? "{$prefix}-{$key}" : $key;
                $this->flattenToCssVars($child, $next, $vars);
            }
        }
    }

    private function buildTailwindColors(array $node, string $path = ''): array
    {
        if (isset($node['$value'])) {
            return [$path => $node['$value']];
        }

        $result = [];
        foreach ($node as $key => $child) {
            if (str_starts_with((string) $key, '$')) continue;
            if (is_array($child)) {
                $sub = $this->buildTailwindColors($child, $key);
                if (count($sub) === 1 && isset($sub[$key])) {
                    $result[$key] = $sub[$key];
                } else {
                    $result[$key] = $sub;
                }
            }
        }
        return $result;
    }

    private function buildTailwindFonts(array $typography): array
    {
        $fonts = [];
        $this->walkTypography($typography, function (string $path, array $value) use (&$fonts) {
            if (isset($value['fontFamily'])) {
                $group = explode('.', $path)[0] ?? 'sans';
                if (!isset($fonts[$group])) {
                    $fonts[$group] = [$value['fontFamily']];
                }
            }
        });
        return $fonts;
    }

    private function buildTailwindFontSizes(array $typography): array
    {
        $sizes = [];
        $this->walkTypography($typography, function (string $path, array $value) use (&$sizes) {
            if (isset($value['fontSize'])) {
                $sizes[$path] = [$value['fontSize'], ['lineHeight' => $value['lineHeight'] ?? 'normal']];
            }
        });
        return $sizes;
    }

    private function walkTypography(array $node, callable $callback, string $path = ''): void
    {
        if (isset($node['$value'])) {
            $callback($path, $node['$value']);
            return;
        }
        foreach ($node as $key => $child) {
            if (str_starts_with((string) $key, '$')) continue;
            if (is_array($child)) {
                $this->walkTypography($child, $callback, $path ? "{$path}.{$key}" : $key);
            }
        }
    }
}
