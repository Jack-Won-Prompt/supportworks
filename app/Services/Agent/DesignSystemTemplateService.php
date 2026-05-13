<?php

namespace App\Services\Agent;

class DesignSystemTemplateService
{
    private const TEMPLATE_MD   = 'design/system_v1_md.blade.php';
    private const TEMPLATE_HTML = 'design/system_v1_html.blade.php';

    /**
     * Renders the Markdown template with the given data context.
     */
    public function renderMarkdown(array $data): string
    {
        $path = resource_path('templates/' . self::TEMPLATE_MD);
        return view()->file($path, $data)->render();
    }

    /**
     * Renders the standalone HTML template (no external dependencies).
     */
    public function renderHtml(array $data): string
    {
        $path = resource_path('templates/' . self::TEMPLATE_HTML);
        return view()->file($path, $data)->render();
    }

    /**
     * Collects all color leaf values from the token tree.
     * Returns [['group' => ..., 'name' => ..., 'value' => ...], ...]
     */
    public static function flattenColors(array $tokens): array
    {
        $colors = $tokens['color'] ?? [];
        $result = [];
        self::walkTokenLeaves($colors, '', $result, 'color');
        return $result;
    }

    /**
     * Collects typography tokens as flat list.
     */
    public static function flattenTypography(array $tokens): array
    {
        $typo   = $tokens['typography'] ?? [];
        $result = [];
        self::walkTokenLeaves($typo, '', $result, 'typography');
        return $result;
    }

    /**
     * Collects shadow tokens.
     */
    public static function flattenShadows(array $tokens): array
    {
        $shadows = $tokens['shadow'] ?? $tokens['shadows'] ?? [];
        $result  = [];
        self::walkTokenLeaves($shadows, '', $result, 'shadow');
        return $result;
    }

    private static function walkTokenLeaves(array $node, string $prefix, array &$result, string $category): void
    {
        foreach ($node as $key => $value) {
            if (!is_array($value)) continue;

            if (isset($value['$value'])) {
                $result[] = [
                    'path'  => $prefix ? "{$prefix}.{$key}" : $key,
                    'value' => $value['$value'],
                    'type'  => $value['$type'] ?? $category,
                ];
            } else {
                self::walkTokenLeaves($value, $prefix ? "{$prefix}.{$key}" : $key, $result, $category);
            }
        }
    }
}
