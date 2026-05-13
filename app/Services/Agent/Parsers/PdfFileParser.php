<?php

namespace App\Services\Agent\Parsers;

class PdfFileParser implements FileParser
{
    private const SUPPORTED = [
        'application/pdf',
    ];

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, self::SUPPORTED, true);
    }

    public function parse(string $storagePath, string $absolutePath): ParsedFileContent
    {
        $fileSize = @filesize($absolutePath) ?: 0;

        return new ParsedFileContent(
            fileType:        'pdf',
            extractedText:   null,
            structure:       [],
            imageReferences: [$storagePath],
            metadata: [
                'file_size' => $fileSize,
            ],
            needsAiVisual: true,
        );
    }
}
