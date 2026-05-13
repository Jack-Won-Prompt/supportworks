<?php

namespace App\Services\Analysis\FileExtractor;

interface FileExtractorInterface
{
    public function extract(string $filePath): string;

    public function supports(string $mimeType, string $extension): bool;
}
