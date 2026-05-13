<?php

namespace App\Services\Agent\Figma\Contracts;

use App\Services\Agent\Figma\FigmaFile;

interface FigmaClient
{
    public function validateToken(): bool;

    public function getMe(): array;

    public function getFile(string $fileKey): FigmaFile;

    public function getFileNodes(string $fileKey, array $nodeIds): array;

    public function getFileStyles(string $fileKey): array;

    public function getFileComponents(string $fileKey): array;

    public function getImages(
        string $fileKey,
        array $nodeIds,
        string $format = 'png',
        float $scale = 1.0
    ): array;
}
