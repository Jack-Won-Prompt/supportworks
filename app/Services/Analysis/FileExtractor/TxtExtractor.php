<?php

namespace App\Services\Analysis\FileExtractor;

class TxtExtractor implements FileExtractorInterface
{
    public function supports(string $mimeType, string $extension): bool
    {
        return in_array($extension, ['txt', 'md', 'csv', 'log'])
            || str_starts_with($mimeType, 'text/');
    }

    public function extract(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("파일을 읽을 수 없습니다: {$filePath}");
        }
        // BOM 제거
        return ltrim($content, "\xEF\xBB\xBF");
    }
}
