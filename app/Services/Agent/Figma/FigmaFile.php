<?php

namespace App\Services\Agent\Figma;

class FigmaFile
{
    public function __construct(
        public readonly string $name,
        public readonly string $lastModified,
        public readonly string $thumbnailUrl,
        public readonly string $version,
        public readonly array  $document,
        public readonly array  $components,
        public readonly array  $styles,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:         $data['name']         ?? '',
            lastModified: $data['lastModified']  ?? '',
            thumbnailUrl: $data['thumbnailUrl']  ?? '',
            version:      $data['version']       ?? '',
            document:     $data['document']      ?? [],
            components:   $data['components']    ?? [],
            styles:       $data['styles']        ?? [],
        );
    }

    public function getFrames(): array
    {
        $frames = [];
        $this->collectByType($this->document, 'FRAME', $frames);
        return $frames;
    }

    public function getNodes(): array
    {
        $nodes = [];
        $this->flattenDocument($this->document, $nodes);
        return $nodes;
    }

    public function getComponentCount(): int
    {
        return count($this->components);
    }

    public function getStyleCount(): int
    {
        return count($this->styles);
    }

    private function collectByType(array $node, string $type, array &$result): void
    {
        if (($node['type'] ?? '') === $type) {
            $result[] = ['id' => $node['id'] ?? null, 'name' => $node['name'] ?? ''];
        }
        foreach ($node['children'] ?? [] as $child) {
            $this->collectByType($child, $type, $result);
        }
    }

    private function flattenDocument(array $node, array &$result): void
    {
        $result[] = [
            'id'   => $node['id']   ?? null,
            'name' => $node['name'] ?? '',
            'type' => $node['type'] ?? '',
        ];
        foreach ($node['children'] ?? [] as $child) {
            $this->flattenDocument($child, $result);
        }
    }
}
