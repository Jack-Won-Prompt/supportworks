<?php

namespace App\Services\Agent\Parsers;

class ImageFileParser implements FileParser
{
    private const SUPPORTED = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
        'image/tiff',
    ];

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, self::SUPPORTED, true)
            || str_starts_with($mimeType, 'image/');
    }

    public function parse(string $storagePath, string $absolutePath): ParsedFileContent
    {
        $imageInfo = @getimagesize($absolutePath);

        $metadata = [
            'file_size' => @filesize($absolutePath) ?: 0,
        ];

        if ($imageInfo !== false) {
            $metadata['width']  = $imageInfo[0];
            $metadata['height'] = $imageInfo[1];
            $metadata['mime']   = $imageInfo['mime'] ?? null;
        }

        return new ParsedFileContent(
            fileType:        'image',
            extractedText:   null,
            structure:       [],
            imageReferences: [$storagePath],
            metadata:        $metadata,
            needsAiVisual:   true,
        );
    }
}
