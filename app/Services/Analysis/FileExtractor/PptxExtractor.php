<?php

namespace App\Services\Analysis\FileExtractor;

use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\RichText;

class PptxExtractor implements FileExtractorInterface
{
    public function supports(string $mimeType, string $extension): bool
    {
        return in_array($extension, ['pptx', 'ppt'])
            || in_array($mimeType, [
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.ms-powerpoint',
            ]);
    }

    public function extract(string $filePath): string
    {
        $presentation = IOFactory::load($filePath);
        $lines = [];

        foreach ($presentation->getAllSlides() as $index => $slide) {
            $slideNum = $index + 1;
            $lines[] = "[슬라이드 {$slideNum}]";

            foreach ($slide->getShapeCollection() as $shape) {
                if ($shape instanceof RichText) {
                    foreach ($shape->getParagraphs() as $paragraph) {
                        $text = '';
                        foreach ($paragraph->getRichTextElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $text .= $element->getText();
                            }
                        }
                        $text = trim($text);
                        if ($text !== '') {
                            $lines[] = $text;
                        }
                    }
                }
            }
        }

        return implode("\n", $lines);
    }
}
