<?php

namespace App\Services\Analysis\FileExtractor;

class PdfExtractor implements FileExtractorInterface
{
    public function supports(string $mimeType, string $extension): bool
    {
        return $extension === 'pdf' || $mimeType === 'application/pdf';
    }

    public function extract(string $filePath): string
    {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            return '[PDF 파싱 라이브러리(smalot/pdfparser)가 설치되지 않아 텍스트를 추출할 수 없습니다. 파일명: ' . basename($filePath) . ']';
        }

        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($filePath);
        return $pdf->getText();
    }
}
