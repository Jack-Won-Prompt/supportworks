<?php

namespace App\Services\PromptBuilder;

use Illuminate\Support\Facades\Storage;

class FigmaAnalysisService
{
    public function analyze(?string $figmaUrl, ?string $figmaFilePath): array
    {
        $components = [];
        $screens    = [];

        if ($figmaFilePath && Storage::disk('private')->exists($figmaFilePath)) {
            $raw = Storage::disk('private')->get($figmaFilePath);
            $data = json_decode($raw, true);

            if ($data) {
                $components = $this->extractComponents($data);
                $screens    = $this->extractScreens($data);
            }
        }

        return [
            'url'        => $figmaUrl,
            'file_path'  => $figmaFilePath,
            'components' => $components,
            'screens'    => $screens,
            'analyzed'   => !empty($components),
        ];
    }

    private function extractComponents(array $data): array
    {
        $components = [];

        $traverse = function ($node) use (&$traverse, &$components) {
            if (isset($node['type']) && in_array($node['type'], ['COMPONENT', 'COMPONENT_SET', 'FRAME'])) {
                $components[] = [
                    'name'             => $node['name'] ?? 'Unknown',
                    'type'             => $node['type'],
                    'id'               => $node['id'] ?? null,
                    'matched_standard' => null,
                ];
            }

            foreach ($node['children'] ?? [] as $child) {
                $traverse($child);
            }
        };

        $traverse($data);

        return array_slice($components, 0, 50);
    }

    private function extractScreens(array $data): array
    {
        $screens = [];

        foreach ($data['document']['children'] ?? [] as $page) {
            foreach ($page['children'] ?? [] as $frame) {
                if (($frame['type'] ?? '') === 'FRAME') {
                    $screens[] = [
                        'name' => $frame['name'] ?? 'Unknown',
                        'id'   => $frame['id'] ?? null,
                    ];
                }
            }
        }

        return $screens;
    }
}
