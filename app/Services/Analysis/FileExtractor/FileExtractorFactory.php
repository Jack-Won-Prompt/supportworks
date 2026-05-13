<?php

namespace App\Services\Analysis\FileExtractor;

class FileExtractorFactory
{
    /** @var FileExtractorInterface[] */
    private array $extractors;

    public function __construct()
    {
        $this->extractors = [
            new TxtExtractor(),
            new DocxExtractor(),
            new XlsxExtractor(),
            new PptxExtractor(),
            new PdfExtractor(),
        ];
    }

    public function make(string $mimeType, string $extension): FileExtractorInterface
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($mimeType, $extension)) {
                return $extractor;
            }
        }

        throw new \RuntimeException("지원하지 않는 파일 형식입니다: {$extension} ({$mimeType})");
    }

    public function supports(string $mimeType, string $extension): bool
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($mimeType, $extension)) {
                return true;
            }
        }
        return false;
    }
}
