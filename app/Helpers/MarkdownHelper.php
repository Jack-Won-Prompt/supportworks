<?php

namespace App\Helpers;

class MarkdownHelper
{
    public static function toHtml(string $markdown): string
    {
        $lines  = explode("\n", $markdown);
        $html   = '';
        $inList = false;

        foreach ($lines as $line) {
            $line = rtrim($line);

            // Headings
            if (preg_match('/^### (.+)$/', $line, $m)) {
                if ($inList) { $html .= '</ul>'; $inList = false; }
                $html .= '<h3>' . e($m[1]) . '</h3>';
                continue;
            }
            if (preg_match('/^## (.+)$/', $line, $m)) {
                if ($inList) { $html .= '</ul>'; $inList = false; }
                $html .= '<h2>' . e($m[1]) . '</h2>';
                continue;
            }
            if (preg_match('/^# (.+)$/', $line, $m)) {
                if ($inList) { $html .= '</ul>'; $inList = false; }
                $html .= '<h1>' . e($m[1]) . '</h1>';
                continue;
            }

            // HR
            if (preg_match('/^---+$/', $line)) {
                if ($inList) { $html .= '</ul>'; $inList = false; }
                $html .= '<hr>';
                continue;
            }

            // List items
            if (preg_match('/^[-*] (.+)$/', $line, $m)) {
                if (!$inList) { $html .= '<ul>'; $inList = true; }
                $html .= '<li>' . self::inline(e($m[1])) . '</li>';
                continue;
            }

            // Numbered list
            if (preg_match('/^\d+\. (.+)$/', $line, $m)) {
                if ($inList) { $html .= '</ul>'; $inList = false; }
                $html .= '<ol start="' . (substr($line, 0, strpos($line, '.')) ?: 1) . '"><li>' . self::inline(e($m[1])) . '</li></ol>';
                continue;
            }

            // Empty line
            if ($line === '') {
                if ($inList) { $html .= '</ul>'; $inList = false; }
                continue;
            }

            // Paragraph
            if ($inList) { $html .= '</ul>'; $inList = false; }
            $html .= '<p>' . self::inline(e($line)) . '</p>';
        }

        if ($inList) $html .= '</ul>';

        return $html;
    }

    private static function inline(string $text): string
    {
        // Bold
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        // Italic
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        // Inline code
        $text = preg_replace('/`(.+?)`/', '<code style="background:#f3f4f6;padding:1px 5px;border-radius:3px;font-size:12px;">$1</code>', $text);
        return $text;
    }
}
