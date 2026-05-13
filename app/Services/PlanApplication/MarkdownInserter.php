<?php

namespace App\Services\PlanApplication;

class MarkdownInserter
{
    public function insert(
        string $originalMarkdown,
        string $newSection,
        string $position,
        ?string $sectionAnchor = null
    ): string {
        // Normalize line endings
        $original = str_replace("\r\n", "\n", $originalMarkdown);
        $section  = rtrim(str_replace("\r\n", "\n", $newSection));

        if ($original === '') {
            return $section;
        }

        return match ($position) {
            'beginning'     => $this->insertAtBeginning($original, $section),
            'after_section' => $this->insertAfterSection($original, $section, $sectionAnchor ?? ''),
            default         => $this->insertAtEnd($original, $section),
        };
    }

    private function insertAtEnd(string $original, string $section): string
    {
        return rtrim($original) . "\n\n" . $section;
    }

    private function insertAtBeginning(string $original, string $section): string
    {
        return $section . "\n\n" . ltrim($original);
    }

    private function insertAfterSection(string $original, string $section, string $anchor): string
    {
        if ($anchor === '') {
            return $this->insertAtEnd($original, $section);
        }

        $lines       = explode("\n", $original);
        $totalLines  = count($lines);
        $anchorIndex = null;

        // Find the heading line matching the anchor (## or ### level)
        $anchorNormalized = mb_strtolower(trim($anchor));
        foreach ($lines as $i => $line) {
            if (preg_match('/^(#{2,3})\s+(.+)$/', $line, $m)) {
                // Skip headings inside code blocks
                if ($this->isInsideCodeBlock($lines, $i)) continue;

                if (mb_strtolower(trim($m[2])) === $anchorNormalized) {
                    $anchorIndex = $i;
                    break;
                }
            }
        }

        if ($anchorIndex === null) {
            return $this->insertAtEnd($original, $section);
        }

        // Find the next same-or-higher-level heading after the anchor
        $anchorLevel = strlen(preg_match('/^(#{2,3})/', $lines[$anchorIndex], $m) ? $m[1] : '##');
        $insertBefore = $totalLines; // default: insert at end

        for ($i = $anchorIndex + 1; $i < $totalLines; $i++) {
            if (preg_match('/^(#{1,' . $anchorLevel . '})\s+/', $lines[$i])) {
                if (!$this->isInsideCodeBlock($lines, $i)) {
                    $insertBefore = $i;
                    break;
                }
            }
        }

        // Build result: lines up to insertBefore, then blank line, section, then rest
        $before = array_slice($lines, 0, $insertBefore);
        $after  = array_slice($lines, $insertBefore);

        $result  = rtrim(implode("\n", $before));
        $result .= "\n\n" . $section;

        if (!empty($after)) {
            $result .= "\n\n" . ltrim(implode("\n", $after));
        }

        return $result;
    }

    private function isInsideCodeBlock(array $lines, int $targetIndex): bool
    {
        $inCode = false;
        for ($i = 0; $i < $targetIndex; $i++) {
            if (preg_match('/^```/', $lines[$i])) {
                $inCode = !$inCode;
            }
        }
        return $inCode;
    }
}
