<?php

namespace App\Services\Agent\Parsers;

use PhpOffice\PhpPresentation\IOFactory;

class PowerPointFileParser implements FileParser
{
    private const SUPPORTED = [
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-powerpoint',
        'application/mspowerpoint',
        'application/powerpoint',
    ];

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, self::SUPPORTED, true);
    }

    public function parse(string $storagePath, string $absolutePath): ParsedFileContent
    {
        $presentation = IOFactory::load($absolutePath);

        $slides    = [];
        $allText   = [];
        $slideNum  = 0;

        foreach ($presentation->getAllSlides() as $slide) {
            $slideNum++;
            $slideTexts = [];

            foreach ($slide->getShapeCollection() as $shape) {
                if (!method_exists($shape, 'getTextBody')) {
                    continue;
                }

                $textBody = $shape->getTextBody();
                if (!$textBody) {
                    continue;
                }

                foreach ($textBody->getParagraphIterator() as $paragraph) {
                    $text = '';
                    foreach ($paragraph->getRichTextElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText();
                        }
                    }
                    $trimmed = trim($text);
                    if ($trimmed !== '') {
                        $slideTexts[] = $trimmed;
                    }
                }
            }

            $slideLabel = "[슬라이드 {$slideNum}]";
            $allText[]  = $slideLabel . "\n" . implode("\n", $slideTexts);

            $slides[] = [
                'index'      => $slideNum,
                'text_count' => count($slideTexts),
                'preview'    => implode(' / ', array_slice($slideTexts, 0, 3)),
            ];
        }

        $extractedText = implode("\n\n", $allText);
        if (mb_strlen($extractedText) > 50000) {
            $extractedText = mb_substr($extractedText, 0, 50000) . "\n... (잘림)";
        }

        return new ParsedFileContent(
            fileType:       'pptx',
            extractedText:  $extractedText,
            structure:      $slides,
            imageReferences:[],
            metadata: [
                'slide_count' => $slideNum,
            ],
        );
    }
}
