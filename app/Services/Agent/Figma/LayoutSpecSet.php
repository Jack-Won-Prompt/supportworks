<?php

namespace App\Services\Agent\Figma;

class LayoutSpecSet
{
    private array $metadata = [];

    public function __construct(
        private array $standardLayouts,
        private array $spacingScale,
        private array $nonStandardFrames,
        private int   $totalFramesAnalyzed,
    ) {}

    public function setMetadata(array $meta): void
    {
        $this->metadata = $meta;
    }

    public function getStandardLayouts(): array
    {
        return $this->standardLayouts;
    }

    public function getSpacingScale(): array
    {
        return $this->spacingScale;
    }

    public function getNonStandardFrames(): array
    {
        return $this->nonStandardFrames;
    }

    public function getStats(): array
    {
        return [
            'total_frames_analyzed'        => $this->totalFramesAnalyzed,
            'standard_layouts_identified'  => count($this->standardLayouts),
            'non_standard_frames'          => count($this->nonStandardFrames),
        ];
    }

    public function isEmpty(): bool
    {
        return $this->totalFramesAnalyzed === 0;
    }

    public function toArray(): array
    {
        return [
            '$metadata'       => array_merge($this->metadata, ['stats' => $this->getStats()]),
            'standard_layouts' => $this->standardLayouts,
            'spacing_scale'   => $this->spacingScale,
            'non_standard_frames' => $this->nonStandardFrames,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
