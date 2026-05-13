<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

class DocxWriter
{
    private PhpWord $phpWord;
    private \PhpOffice\PhpWord\Element\Section $section;

    // 테마 색상
    private const C_TITLE  = '312E81';  // 인디고
    private const C_H1     = '4F46E5';  // 라이트 인디고
    private const C_H2     = '0891B2';  // 시안-600
    private const C_H3     = '374151';  // 다크 그레이
    private const C_BODY   = '1F2937';
    private const C_SUB    = '6B7280';
    private const C_TABLE_H = 'FFFFFFFF';
    private const C_TABLE_HBG = '4F46E5';
    private const C_TABLE_E  = 'F8F7FF';  // even row

    public function __construct()
    {
        $this->phpWord = new PhpWord();
        $this->phpWord->setDefaultFontName('맑은 고딕');
        $this->phpWord->setDefaultFontSize(11);

        // 스타일 정의
        $this->phpWord->addTitleStyle(0, [
            'name' => '맑은 고딕', 'size' => 22, 'bold' => true, 'color' => self::C_TITLE,
        ], ['alignment' => Jc::CENTER, 'spaceAfter' => 240, 'spaceBefore' => 120]);

        $this->phpWord->addTitleStyle(1, [
            'name' => '맑은 고딕', 'size' => 16, 'bold' => true, 'color' => self::C_H1,
        ], ['spaceBefore' => 280, 'spaceAfter' => 120,
            'borderBottomColor' => self::C_H1, 'borderBottomSize' => 6]);

        $this->phpWord->addTitleStyle(2, [
            'name' => '맑은 고딕', 'size' => 13, 'bold' => true, 'color' => self::C_H2,
        ], ['spaceBefore' => 200, 'spaceAfter' => 80]);

        $this->phpWord->addTitleStyle(3, [
            'name' => '맑은 고딕', 'size' => 11, 'bold' => true, 'color' => self::C_H3,
        ], ['spaceBefore' => 140, 'spaceAfter' => 60]);

        $this->section = $this->phpWord->addSection([
            'marginTop' => 1440, 'marginBottom' => 1440,
            'marginLeft' => 1080, 'marginRight' => 1080,
        ]);
    }

    // ── JSON 기반 로딩 ───────────────────────────────────────────

    public function loadFromJson(array $jsonData): self
    {
        // 표지 페이지 (색상 블록 + 페이지 나누기)
        $this->buildCoverBlock($jsonData);

        // 본문 푸터: 페이지 번호
        $footer = $this->section->addFooter();
        $footer->addPreserveText(
            '{PAGE} / {NUMPAGES}',
            ['name' => '맑은 고딕', 'size' => 9, 'color' => '9CA3AF'],
            ['alignment' => Jc::RIGHT]
        );

        foreach ($jsonData['sections'] ?? [] as $block) {
            $this->renderBlock($block);
        }

        return $this;
    }

    private function buildCoverBlock(array $jsonData): void
    {
        $title    = $jsonData['title']    ?? '';
        $subtitle = $jsonData['subtitle'] ?? '';
        $author   = $jsonData['author']   ?? '';
        $date     = $jsonData['date']     ?? '';

        $this->phpWord->addTableStyle('_CoverTable', [
            'borderSize' => 0, 'borderColor' => 'FFFFFF',
            'cellMarginTop' => 0, 'cellMarginBottom' => 0,
            'cellMarginLeft' => 0, 'cellMarginRight' => 0,
        ]);
        $table = $this->section->addTable('_CoverTable');

        // 메인 블록 (배경 없음)
        $table->addRow(5400);
        $cell = $table->addCell(10080, ['valign' => 'center']);
        $cell->addText('', [], ['spaceBefore' => 1440]);
        if ($title) {
            $cell->addText($title, [
                'name' => '맑은 고딕', 'size' => 28, 'bold' => true, 'color' => '1F2937',
            ], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);
        }
        if ($subtitle) {
            $cell->addText($subtitle, [
                'name' => '맑은 고딕', 'size' => 13, 'color' => '4F46E5',
            ], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        // 하단 정보 바 (배경 없음)
        $table->addRow(480);
        $accent = $table->addCell(10080, ['valign' => 'center']);
        $info   = trim($author . (!empty($author) && !empty($date) ? '  |  ' : '') . $date);
        $accent->addText($info ?: ' ', [
            'name' => '맑은 고딕', 'size' => 11, 'color' => '6B7280',
        ], ['alignment' => Jc::CENTER]);

        $this->section->addPageBreak();
    }

    private function renderBlock(array $block): void
    {
        $type = $block['type'] ?? 'paragraph';

        switch ($type) {
            case 'heading':
                $level = (int)($block['level'] ?? 1);
                $this->addHeading($block['text'] ?? '', $level);
                break;

            case 'paragraph':
                $this->addText($block['text'] ?? '', $block['bold'] ?? false);
                break;

            case 'bullets':
                foreach ($block['items'] ?? [] as $item) {
                    $indent = ($item['level'] ?? 0);
                    $text   = is_string($item) ? $item : ($item['text'] ?? '');
                    $this->addBullet($text, $indent);
                }
                break;

            case 'numbered':
                foreach ($block['items'] ?? [] as $i => $item) {
                    $text = is_string($item) ? $item : ($item['text'] ?? '');
                    $this->section->addListItem($text, 0, [
                        'name' => '맑은 고딕', 'size' => 11, 'color' => self::C_BODY,
                    ], 'numberedList');
                }
                break;

            case 'table':
                $this->addTable($block['headers'] ?? [], $block['rows'] ?? [], $block['col_widths'] ?? []);
                break;

            case 'divider':
                $this->addDivider();
                break;

            case 'empty':
            default:
                $this->addEmpty();
                break;
        }
    }

    // ── 개별 요소 추가 ───────────────────────────────────────────

    public function addTitle(string $text): self
    {
        $this->section->addTitle($text, 0);
        return $this;
    }

    public function addSubtitle(string $text): self
    {
        $this->section->addText($text, [
            'name' => '맑은 고딕', 'size' => 13, 'color' => self::C_H1, 'italic' => true,
        ], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);
        return $this;
    }

    public function addMeta(string $text): self
    {
        $this->section->addText($text, [
            'name' => '맑은 고딕', 'size' => 9, 'color' => self::C_SUB,
        ], ['alignment' => Jc::CENTER, 'spaceAfter' => 60]);
        return $this;
    }

    public function addHeading(string $text, int $level = 1): self
    {
        $this->section->addTitle($text, min($level, 3));
        return $this;
    }

    public function addText(string $text, bool $bold = false): self
    {
        $font = ['name' => '맑은 고딕', 'size' => 11, 'color' => self::C_BODY];
        if ($bold) $font['bold'] = true;
        $this->section->addText($text, $font, [
            'spaceAfter' => 80, 'lineHeight' => 1.5,
        ]);
        return $this;
    }

    public function addBullet(string $text, int $level = 0): self
    {
        $this->section->addListItem($text, $level, [
            'name' => '맑은 고딕', 'size' => 11, 'color' => self::C_BODY,
        ]);
        return $this;
    }

    public function addDivider(): self
    {
        $this->section->addText('', [], ['borderBottomColor' => 'CCCCCC', 'borderBottomSize' => 4, 'spaceAfter' => 120]);
        return $this;
    }

    public function addTable(array $headers, array $rows, array $colWidths = []): self
    {
        if (empty($headers) && empty($rows)) return $this;

        $colCount   = max(count($headers), count($rows[0] ?? []));
        $totalWidth = 8640; // twips (6인치 @ 1440/inch)
        $defWidth   = intval($totalWidth / max($colCount, 1));

        $tableStyle = [
            'borderColor' => 'DDD6FE',
            'borderSize'  => 4,
            'cellMargin'  => 80,
        ];
        $this->phpWord->addTableStyle('DataTable', $tableStyle);
        $table = $this->section->addTable('DataTable');

        // 헤더 행
        if (!empty($headers)) {
            $table->addRow(400);
            foreach ($headers as $i => $h) {
                $w = isset($colWidths[$i]) ? intval($colWidths[$i] * 720) : $defWidth;
                $cell = $table->addCell($w, [
                    'bgColor' => self::C_TABLE_HBG,
                    'borderColor' => '312E81',
                ]);
                $cell->addText((string)$h, [
                    'name' => '맑은 고딕', 'size' => 10, 'bold' => true,
                    'color' => self::C_TABLE_H,
                ], ['alignment' => Jc::CENTER]);
            }
        }

        // 데이터 행
        foreach ($rows as $ri => $row) {
            $table->addRow(340);
            $isEven = $ri % 2 === 0;
            foreach ($row as $ci => $val) {
                $w = isset($colWidths[$ci]) ? intval($colWidths[$ci] * 720) : $defWidth;
                $cell = $table->addCell($w, [
                    'bgColor' => $isEven ? 'F8F7FF' : 'FFFFFF',
                ]);
                $cell->addText((string)$val, [
                    'name' => '맑은 고딕', 'size' => 10, 'color' => self::C_BODY,
                ]);
            }
            // 열 수가 부족하면 빈 셀 채움
            for ($ci = count($row); $ci < $colCount; $ci++) {
                $table->addCell($defWidth);
            }
        }

        $this->section->addTextBreak(1);
        return $this;
    }

    public function addEmpty(): self
    {
        $this->section->addTextBreak(1);
        return $this;
    }

    /** 마크다운 파싱 → 단락 추가 (폴백용) */
    public function addMarkdown(string $markdown): self
    {
        foreach (explode("\n", $markdown) as $line) {
            $line = rtrim($line);
            if ($line === '') continue;

            if (preg_match('/^# (.+)/', $line, $m))       $this->addHeading($m[1], 1);
            elseif (preg_match('/^## (.+)/', $line, $m))  $this->addHeading($m[1], 2);
            elseif (preg_match('/^### (.+)/', $line, $m)) $this->addHeading($m[1], 3);
            elseif (preg_match('/^[-*•]\s+(.+)/', $line, $m)) $this->addBullet($m[1]);
            elseif (preg_match('/^\d+\.\s+(.+)/', $line, $m)) $this->addBullet($m[1]);
            else {
                $clean = preg_replace(['/\*\*(.+?)\*\*/', '/`(.+?)`/'], '$1', $line);
                $this->addText($clean);
            }
        }
        return $this;
    }

    public function save(string $path): string
    {
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        IOFactory::createWriter($this->phpWord, 'Word2007')->save($path);
        return $path;
    }

    // ── 주간 업무 보고서 빌더 ──────────────────────────────────────

    /**
     * 주간 업무 보고서를 고정 포맷으로 생성합니다.
     * 웍스 제공자(Claude/Manus/ChatGPT)에 관계없이 동일한 포맷을 유지합니다.
     * - 섹션 1: 웍스 텍스트를 정규화 후 동일 스타일로 렌더링
     * - 섹션 2·3·4: DB 데이터로 고정 포맷 테이블
     * - 섹션 5: 사용자 원문 정규화 렌더링
     */
    public function buildWeeklyReport(\App\Models\WeeklyReport $report, ?string $aiSummary = null): self
    {
        $tasks      = $report->tasks;
        $completed  = $tasks->where('section', 'current_week')->where('status', 'completed')->values();
        $inProgress = $tasks->where('section', 'current_week')->where('status', 'in_progress')->values();
        $nextWeek   = $tasks->where('section', 'next_week')->values();

        // ── 표지 (항상 동일 구조) ──
        $this->buildCoverBlock([
            'title'    => $report->project->name . ' - 주간 업무 보고',
            'subtitle' => $report->week_label,
            'author'   => ($report->team_name ? $report->team_name . '  |  ' : '') . $report->author_name,
            'date'     => $report->report_date->format('Y년 m월 d일'),
            'sections' => [],
        ]);

        // 본문 푸터: 페이지 번호
        $footer = $this->section->addFooter();
        $footer->addPreserveText(
            '{PAGE} / {NUMPAGES}',
            ['name' => '맑은 고딕', 'size' => 9, 'color' => '9CA3AF'],
            ['alignment' => Jc::RIGHT]
        );

        // ── 1. 주요 성과 요약 (웍스 텍스트 → 정규화 → 고정 스타일) ──
        $this->addHeading('1. 주요 성과 요약', 1);
        $rawSummary  = $aiSummary ?: strip_tags(html_entity_decode($report->summary ?? ''));
        $this->renderNormalizedText($rawSummary);

        // ── 2. 금주 업무 실시 사항 (고정 테이블) ──
        $this->addHeading('2. 금주 업무 실시 사항', 1);
        if ($completed->isNotEmpty()) {
            $this->addWeeklyTable(
                ['No.', '업무명', '시작일', '종료일', '상태'],
                $completed->values()->map(fn ($t, $i) => [
                    $i + 1,
                    $t->task_name,
                    $t->start_date?->format('Y.m.d') ?? '-',
                    $t->end_date?->format('Y.m.d') ?? '-',
                    '완료',
                ])->toArray(),
                [0.5, 4.5, 1.5, 1.5, 1.0]
            );
        } else {
            $this->addText('해당 사항 없음');
        }

        // ── 3. 진행 중인 업무 (고정 테이블) ──
        $this->addHeading('3. 진행 중인 업무', 1);
        if ($inProgress->isNotEmpty()) {
            $this->addWeeklyTable(
                ['No.', '업무명', '시작일', '종료일', '상태'],
                $inProgress->values()->map(fn ($t, $i) => [
                    $i + 1,
                    $t->task_name,
                    $t->start_date?->format('Y.m.d') ?? '-',
                    $t->end_date?->format('Y.m.d') ?? '-',
                    '진행중',
                ])->toArray(),
                [0.5, 4.5, 1.5, 1.5, 1.0]
            );
        } else {
            $this->addText('해당 사항 없음');
        }

        // ── 4. 차주 업무 계획 (고정 테이블) ──
        $this->addHeading('4. 차주 업무 계획', 1);
        if ($nextWeek->isNotEmpty()) {
            $this->addWeeklyTable(
                ['No.', '업무명', '시작일', '종료일'],
                $nextWeek->values()->map(fn ($t, $i) => [
                    $i + 1,
                    $t->task_name,
                    $t->start_date?->format('Y.m.d') ?? '-',
                    $t->end_date?->format('Y.m.d') ?? '-',
                ])->toArray(),
                [0.5, 5.5, 1.5, 1.5]
            );
        } else {
            $this->addText('해당 사항 없음');
        }

        // ── 5. 특이 사항 및 건의 사항 / 업무 지연 ──
        $this->addHeading('5. 특이 사항 및 건의 사항 / 업무 지연', 1);
        $this->renderNormalizedText($report->special_notes ?? '');

        return $this;
    }

    // ── 매니저 웍스 서머리 종합 보고서 빌더 ────────────────────────────

    /**
     * 프로젝트 전체(또는 특정 주차)의 팀원별 웍스 서머리를 종합한 매니저 보고서를 생성합니다.
     *
     * @param \App\Models\Project                            $project
     * @param \Illuminate\Support\Collection<\App\Models\WeeklyReport> $reports
     * @param string|null                                    $weekFilter  날짜 문자열 or 'all' or null
     */
    /**
     * 웍스 서머리 전용 Word 파일 생성.
     * WeeklyAiSummary DB 레코드의 markdown content를 표지 + 본문으로 빌드합니다.
     */
    public function buildAiSummary(
        \App\Models\Project $project,
        string $summaryType,
        ?string $weekLabel,
        string $content,
        string $generatedAt,
        string $generatedBy
    ): self {
        $typeLabel = $summaryType === 'full' ? '프로젝트 전체 웍스 서머리' : '주차 웍스 서머리';
        $subtitle  = $summaryType === 'full' ? '전체 기간 종합 분석' : ($weekLabel ?? '');

        // ── 표지 ──
        $this->buildCoverBlock([
            'title'    => $project->name,
            'subtitle' => $typeLabel,
            'author'   => $subtitle,
            'date'     => $generatedAt . '  ·  ' . $generatedBy,
            'sections' => [],
        ]);

        // ── 푸터 ──
        $footer = $this->section->addFooter();
        $footer->addPreserveText(
            'SupportWorks  |  ' . $project->name . '  ' . $typeLabel . '  |  {PAGE} / {NUMPAGES}',
            ['name' => '맑은 고딕', 'size' => 9, 'color' => '9CA3AF'],
            ['alignment' => Jc::RIGHT]
        );

        // ── 본문: markdown → 구조화 렌더링 ──
        $this->addMarkdown($content);

        return $this;
    }

    public function buildManagerSummary(\App\Models\Project $project, $reports, ?string $weekFilter): self
    {
        $grouped = $reports
            ->groupBy(fn($r) => $r->week_start_date->format('Y-m-d'))
            ->sortKeysDesc();

        $weekCount   = $grouped->count();
        $memberCount = $reports->unique('user_id')->count();

        $subtitle = ($weekFilter && $weekFilter !== 'all')
            ? ($reports->first()?->week_label ?? '')
            : '전체 주차 종합  (' . $weekCount . '주차  ·  ' . $memberCount . '명)';

        // ── 표지 ──
        $this->buildCoverBlock([
            'title'    => $project->name,
            'subtitle' => '주간 업무 웍스 서머리 종합',
            'author'   => $subtitle,
            'date'     => now()->format('Y년 m월 d일'),
            'sections' => [],
        ]);

        // ── 푸터 ──
        $footer = $this->section->addFooter();
        $footer->addPreserveText(
            'SupportWorks  |  ' . $project->name . '  주간 웍스 서머리  |  {PAGE} / {NUMPAGES}',
            ['name' => '맑은 고딕', 'size' => 9, 'color' => '9CA3AF'],
            ['alignment' => Jc::RIGHT]
        );

        $weekKeys  = $grouped->keys()->all();
        $lastKey   = end($weekKeys);

        foreach ($grouped as $weekDate => $weekReports) {
            $first     = $weekReports->first();
            $weekStart = \Carbon\Carbon::parse($weekDate);
            $weekEnd   = $weekStart->copy()->addDays(6);

            // 주차 헤더
            $this->addHeading(
                $first->week_label
                    . '   ' . $weekStart->format('Y.m.d')
                    . ' ~ ' . $weekEnd->format('m.d'),
                1
            );
            $this->addText($weekReports->count() . '명 보고  ·  제출 완료 '
                . $weekReports->where('status', 'submitted')->count() . '명', false);
            $this->addDivider();

            // 팀원별 섹션
            foreach ($weekReports as $report) {
                $nameLabel = $report->author_name
                    . ($report->team_name ? '  |  ' . $report->team_name : '');
                $this->addHeading($nameLabel, 2);

                // 상태 배지 (제출 완료 / 임시 저장)
                $statusText = $report->status === 'submitted' ? '제출 완료' : '임시 저장';
                $this->addText('[ ' . $statusText . '  ·  ' . $report->report_date->format('Y.m.d') . ' ]', false);

                // 웍스 서머리
                $this->addHeading('주요 성과 요약', 3);
                $rawSummary = strip_tags(html_entity_decode($report->summary ?? ''));
                $this->renderNormalizedText($rawSummary ?: '(작성 내용 없음)');

                // 금주 업무 현황 테이블
                $currentTasks = $report->tasks->where('section', 'current_week')->values();
                if ($currentTasks->isNotEmpty()) {
                    $this->addHeading('금주 업무 현황', 3);
                    $this->addWeeklyTable(
                        ['업무명', '상태', '시작일', '종료일'],
                        $currentTasks->map(fn($t) => [
                            $t->task_name,
                            $t->status_label,
                            $t->start_date?->format('Y.m.d') ?? '-',
                            $t->end_date?->format('Y.m.d') ?? '-',
                        ])->toArray(),
                        [4.5, 1.5, 1.5, 1.5]
                    );
                }

                // 차주 계획 테이블
                $nextTasks = $report->tasks->where('section', 'next_week')->values();
                if ($nextTasks->isNotEmpty()) {
                    $this->addHeading('차주 업무 계획', 3);
                    $this->addWeeklyTable(
                        ['업무명', '예정 시작일', '예정 종료일'],
                        $nextTasks->map(fn($t) => [
                            $t->task_name,
                            $t->start_date?->format('Y.m.d') ?? '-',
                            $t->end_date?->format('Y.m.d') ?? '-',
                        ])->toArray(),
                        [5.5, 1.75, 1.75]
                    );
                }

                // 특이 사항
                if (!empty(trim($report->special_notes ?? ''))) {
                    $this->addHeading('특이 사항', 3);
                    $this->renderNormalizedText($report->special_notes);
                }

                $this->addEmpty();
            }

            // 주차 구분 페이지 나누기 (마지막 주차 제외)
            if ($weekDate !== $lastKey) {
                $this->section->addPageBreak();
            }
        }

        return $this;
    }

    /**
     * 웍스 제공자와 무관하게 동일한 스타일로 텍스트를 렌더링합니다.
     * 마크다운 헤더/볼드/이탤릭/리스트를 모두 정규화하여 고정 Word 스타일로 출력합니다.
     */
    private function renderNormalizedText(string $raw): void
    {
        $text = $this->normalizeAiText($raw);

        if (trim($text) === '') {
            $this->addText('(내용 없음)');
            return;
        }

        foreach (explode("\n", $text) as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // 불릿 항목 (·로 정규화된 것)
            if (str_starts_with($line, '· ')) {
                $this->addBullet(mb_substr($line, 2));
            } else {
                $this->addText($line);
            }
        }
    }

    /**
     * 어떤 웍스가 생성한 텍스트도 순수 텍스트로 정규화합니다.
     * Claude / Manus / ChatGPT 모두 동일한 출력 형식이 보장됩니다.
     */
    private function normalizeAiText(string $text): string
    {
        // HTML 태그 및 엔티티 제거
        $text = strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        // 마크다운 헤더(## 등) → 제거
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);

        // 볼드/이탤릭 (**text**, *text*, __text__, _text_) → 텍스트만
        $text = preg_replace('/\*{1,3}([^*\n]+)\*{1,3}/', '$1', $text);
        $text = preg_replace('/_{1,2}([^_\n]+)_{1,2}/', '$1', $text);

        // 인라인 코드/코드블록 → 텍스트만
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        $text = preg_replace('/`([^`]+)`/', '$1', $text);

        // 마크다운 링크 [text](url) → text
        $text = preg_replace('/\[([^\]]+)\]\([^\)]*\)/', '$1', $text);

        // 번호 목록 (1. / 2. 등) → 불릿 기호로 통일
        $text = preg_replace('/^\s*\d+\.\s+/m', '· ', $text);

        // 불릿 목록 (- / * / • / ▪ 등) → 불릿 기호로 통일
        $text = preg_replace('/^\s*[-*•▪▸]\s+/m', '· ', $text);

        // 수평선 (---/===) → 제거
        $text = preg_replace('/^\s*[-=]{3,}\s*$/m', '', $text);

        // 과도한 빈줄 정리 (3줄 이상 → 1줄)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * 주간 보고 전용 고정 테이블.
     * - 테이블 폭: 인쇄 영역 전체 (A4 기준 여백 각 1080 twips 제외 후 9746 twips)
     * - 배경색 없음: 헤더/데이터 모두 흰 배경, 테두리로만 구분
     * - 웍스 제공자와 무관하게 항상 동일한 포맷
     */
    private function addWeeklyTable(array $headers, array $rows, array $colWidths = []): void
    {
        if (empty($rows)) return;

        $colCount   = count($headers);
        // A4(11906 twips) - 좌여백(1080) - 우여백(1080) = 인쇄 전체 폭
        $totalWidth = 9746;
        $defWidth   = intval($totalWidth / max($colCount, 1));

        $styleName = '_WeeklyReportTable';
        $this->phpWord->addTableStyle($styleName, [
            'borderColor' => '9CA3AF',
            'borderSize'  => 4,
            'cellMarginTop'    => 80,
            'cellMarginBottom' => 80,
            'cellMarginLeft'   => 100,
            'cellMarginRight'  => 100,
        ]);
        $table = $this->section->addTable($styleName);

        // 헤더 행: 배경색 없음, 굵은 글씨 + 하단 이중선으로 구분
        $table->addRow(420);
        foreach ($headers as $i => $h) {
            $w    = isset($colWidths[$i]) ? intval($colWidths[$i] * ($totalWidth / 9.0)) : $defWidth;
            $cell = $table->addCell($w, [
                'borderBottomColor' => '374151',
                'borderBottomSize'  => 8,
            ]);
            $cell->addText((string) $h, [
                'name' => '맑은 고딕', 'size' => 10, 'bold' => true, 'color' => self::C_H3,
            ], ['alignment' => Jc::CENTER]);
        }

        // 데이터 행: 배경색 없음, 얇은 테두리만
        foreach ($rows as $ri => $row) {
            $table->addRow(360);
            foreach ($row as $ci => $val) {
                $w    = isset($colWidths[$ci]) ? intval($colWidths[$ci] * ($totalWidth / 9.0)) : $defWidth;
                $cell = $table->addCell($w);
                $align = ($ci === 0 || $ci >= $colCount - 2) ? Jc::CENTER : Jc::LEFT;
                $cell->addText((string) $val, [
                    'name' => '맑은 고딕', 'size' => 10, 'color' => self::C_BODY,
                ], ['alignment' => $align]);
            }
        }

        $this->section->addTextBreak(1);
    }
}
