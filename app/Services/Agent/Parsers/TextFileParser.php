<?php

namespace App\Services\Agent\Parsers;

class TextFileParser implements FileParser
{
    private const SUPPORTED = [
        'text/plain',
        'text/csv',
        'text/markdown',
        'text/x-markdown',
        'application/json',
    ];

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, self::SUPPORTED, true)
            || str_starts_with($mimeType, 'text/');
    }

    public function parse(string $storagePath, string $absolutePath): ParsedFileContent
    {
        $content = @file_get_contents($absolutePath) ?: '';

        // UTF-8 보장 (BOM 제거)
        $content = str_starts_with($content, "\xEF\xBB\xBF")
            ? substr($content, 3)
            : $content;

        // 너무 긴 파일은 잘라냄 (웍스 컨텍스트 보호)
        $maxChars = 50000;
        $truncated = false;
        if (mb_strlen($content) > $maxChars) {
            $content = mb_substr($content, 0, $maxChars);
            $truncated = true;
        }

        return new ParsedFileContent(
            fileType:       'text',
            extractedText:  $content,
            structure:      [],
            imageReferences:[],
            metadata: [
                'char_count' => mb_strlen($content),
                'line_count' => substr_count($content, "\n") + 1,
                'truncated'  => $truncated,
            ],
        );
    }
}
