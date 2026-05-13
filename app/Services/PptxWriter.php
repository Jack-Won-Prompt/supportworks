<?php

namespace App\Services;

class PptxWriter
{
    private string $title;
    private string $subtitle;
    private string $company;
    private array  $slides = [];

    private static string $workerPath = 'scripts/pptxgen/worker.js';

    public function __construct(string $title = '프레젠테이션', string $company = 'SupportWorks')
    {
        $this->title   = $title;
        $this->company = $company;
        $this->subtitle = now()->format('Y년 m월 d일');
    }

    public function setSubtitle(string $subtitle): self
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    // ── Slide builders ───────────────────────────────────────────

    public function addTitleSlide(string $subtitle = ''): self
    {
        if ($subtitle) $this->subtitle = $subtitle;
        $this->slides[] = ['type' => 'title'];
        return $this;
    }

    public function addSlide(string $title, array $bullets = []): self
    {
        $normalized = [];
        foreach ($bullets as $b) {
            if (is_string($b)) {
                $normalized[] = ['text' => $b, 'level' => 0, 'bold' => false];
            } else {
                $normalized[] = $b;
            }
        }
        $this->slides[] = ['type' => 'content', 'title' => $title, 'bullets' => $normalized];
        return $this;
    }

    public function addTwoColumnSlide(string $title, string $leftTitle, array $leftItems, string $rightTitle, array $rightItems): self
    {
        $this->slides[] = [
            'type'        => 'two_column',
            'title'       => $title,
            'left_title'  => $leftTitle,
            'left_items'  => $leftItems,
            'right_title' => $rightTitle,
            'right_items' => $rightItems,
        ];
        return $this;
    }

    public function addSectionSlide(string $title, string $subtitle = ''): self
    {
        $this->slides[] = ['type' => 'section', 'title' => $title, 'subtitle' => $subtitle];
        return $this;
    }

    public function addClosingSlide(string $message = '감사합니다'): self
    {
        $this->slides[] = ['type' => 'closing', 'message' => $message];
        return $this;
    }

    /** 웍스가 생성한 JSON 구조를 그대로 슬라이드로 주입 */
    public function loadFromJson(array $jsonData): self
    {
        if (!empty($jsonData['title']))    $this->title    = $jsonData['title'];
        if (!empty($jsonData['subtitle'])) $this->subtitle = $jsonData['subtitle'];
        if (!empty($jsonData['company']))  $this->company  = $jsonData['company'];
        if (!empty($jsonData['slides']))   $this->slides   = $jsonData['slides'];
        return $this;
    }

    /** 마크다운 파싱 → 슬라이드 (폴백용) */
    public function addMarkdown(string $markdown): self
    {
        $lines      = explode("\n", $markdown);
        $curTitle   = '';
        $curBullets = [];

        $flush = function () use (&$curTitle, &$curBullets) {
            if ($curTitle !== '') {
                $this->addSlide($curTitle, $curBullets);
                $curTitle   = '';
                $curBullets = [];
            }
        };

        foreach ($lines as $line) {
            $line = rtrim($line);
            if ($line === '') continue;

            if (preg_match('/^#{1,2}\s+(.+)/', $line, $m)) {
                $flush();
                $curTitle = trim($m[1]);
            } elseif (preg_match('/^#{3,}\s+(.+)/', $line, $m)) {
                $curBullets[] = ['text' => trim($m[1]), 'level' => 0, 'bold' => true];
            } elseif (preg_match('/^\s{2,}[-*]\s+(.+)/', $line, $m)) {
                $curBullets[] = ['text' => $this->stripMd($m[1]), 'level' => 1, 'bold' => false];
            } elseif (preg_match('/^[-*•]\s+(.+)/', $line, $m)) {
                $curBullets[] = ['text' => $this->stripMd($m[1]), 'level' => 0, 'bold' => false];
            } elseif (preg_match('/^\d+\.\s+(.+)/', $line, $m)) {
                $curBullets[] = ['text' => $this->stripMd($m[1]), 'level' => 0, 'bold' => false];
            } else {
                $clean = $this->stripMd($line);
                if ($clean !== '') {
                    $curBullets[] = ['text' => $clean, 'level' => 0, 'bold' => false];
                }
            }
        }
        $flush();

        return $this;
    }

    // ── Save ─────────────────────────────────────────────────────

    public function save(string $outputPath): string
    {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // JSON 데이터 작성
        $jsonData = [
            'title'    => $this->title,
            'subtitle' => $this->subtitle,
            'company'  => $this->company,
            'slides'   => $this->slides,
        ];

        $tmpJson = sys_get_temp_dir() . '/pptx-' . uniqid() . '.json';
        file_put_contents($tmpJson, json_encode($jsonData, JSON_UNESCAPED_UNICODE));

        try {
            $workerAbs = base_path(self::$workerPath);
            $outAbs    = str_replace('/', DIRECTORY_SEPARATOR, $outputPath);
            $cmd       = 'node ' . escapeshellarg($workerAbs)
                       . ' '    . escapeshellarg($tmpJson)
                       . ' '    . escapeshellarg($outAbs);

            $output = [];
            $code   = 0;
            exec($cmd . ' 2>&1', $output, $code);

            if ($code !== 0 || !file_exists($outputPath)) {
                throw new \RuntimeException(
                    'PptxGen worker 오류: ' . implode("\n", $output)
                );
            }
        } finally {
            @unlink($tmpJson);
        }

        return $outputPath;
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function stripMd(string $text): string
    {
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        $text = preg_replace('/\*(.+?)\*/',     '$1', $text);
        $text = preg_replace('/`(.+?)`/',        '$1', $text);
        return trim($text);
    }
}
