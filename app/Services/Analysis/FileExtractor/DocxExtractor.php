<?php

namespace App\Services\Analysis\FileExtractor;

use PhpOffice\PhpWord\IOFactory;

class DocxExtractor implements FileExtractorInterface
{
    public function supports(string $mimeType, string $extension): bool
    {
        return $extension === 'docx'
            || $mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }

    public function extract(string $filePath): string
    {
        $word = IOFactory::load($filePath);
        $lines = [];

        foreach ($word->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $lines[] = $this->extractFromElement($element);
            }
        }

        return implode("\n", array_filter($lines));
    }

    private function extractFromElement($element): string
    {
        if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                if ($child instanceof \PhpOffice\PhpWord\Element\Text) {
                    $parts[] = $child->getText();
                }
            }
            return implode('', $parts);
        }

        if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            return $element->getText();
        }

        if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            $rows = [];
            foreach ($element->getRows() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    $cellText = [];
                    foreach ($cell->getElements() as $el) {
                        $t = $this->extractFromElement($el);
                        if ($t) $cellText[] = $t;
                    }
                    $cells[] = implode(' ', $cellText);
                }
                $rows[] = implode(' | ', $cells);
            }
            return implode("\n", $rows);
        }

        if (method_exists($element, 'getElements')) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                $t = $this->extractFromElement($child);
                if ($t) $parts[] = $t;
            }
            return implode(' ', $parts);
        }

        return '';
    }
}
