<?php

namespace App\Services\Agent\Parsers;

class FallbackFileParser implements FileParser
{
    public function supports(string $mimeType): bool
    {
        return true;
    }

    public function parse(string $storagePath, string $absolutePath): ParsedFileContent
    {
        return new ParsedFileContent(
            fileType:        'other',
            extractedText:   null,
            structure:       [],
            imageReferences: [],
            metadata: [
                'file_size' => @filesize($absolutePath) ?: 0,
            ],
        );
    }
}
