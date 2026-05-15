<?php

namespace App\Services\FileCommentReport;

/**
 * PowerPoint: 원본 .pptx 의 ZIP 구조를 직접 조작하여 의견을 슬라이드 노트(notesSlide)에 주입한다.
 *
 * PhpPresentation 의 load + save 는 슬라이드 마스터/레이아웃을 재직렬화하면서 PowerPoint 가
 * "파일 복구" 메시지를 내는 호환성 문제를 자주 일으킨다. 직접 ZIP 수정으로 이를 회피.
 *
 * 매핑 규칙:
 *  - comment.page = 슬라이드 인덱스 (1-based, 발표 순서)
 *  - 슬라이드 수 초과 또는 page 없음 → 마지막 슬라이드 노트로
 *  - 같은 슬라이드 다수 의견 → 한 노트에 통합
 */
class PptxReportBuilder implements ReportBuilderInterface
{
    public function build(ReportContext $ctx): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'fcr_') . '.pptx';
        copy($ctx->sourcePath, $tmp);

        $zip = new \ZipArchive();
        if ($zip->open($tmp) !== true) {
            // 열 수 없으면 그대로 반환 (의견 미주입)
            return $this->result($tmp, $ctx);
        }

        $slideToNotes = $this->mapSlideToNotes($zip);
        $slideCount   = count($slideToNotes);
        if ($slideCount === 0) {
            $zip->close();
            return $this->result($tmp, $ctx);
        }

        // page → 슬라이드 인덱스(1-based) 매핑
        $bySlide = [];
        foreach ($ctx->commentsByPage() as $pageNo => $comments) {
            $idx = $pageNo > 0 ? min($pageNo, $slideCount) : $slideCount;
            $bySlide[$idx] = $bySlide[$idx] ?? [];
            foreach ($comments as $c) {
                $bySlide[$idx][] = ['page' => $pageNo, 'comment' => $c];
            }
        }

        foreach ($bySlide as $slideIdx => $items) {
            if (!isset($slideToNotes[$slideIdx])) continue;
            $notesPath = $slideToNotes[$slideIdx];
            $xml = $zip->getFromName($notesPath);
            if ($xml === false) continue;
            $newXml = $this->injectIntoNotesXml($xml, $items);
            if ($newXml !== $xml) {
                $zip->addFromString($notesPath, $newXml);
            }
        }

        // 노트가 있는 첫 슬라이드를 활성으로 두고, Normal View 분할바를 노트 영역이
        // 잘 보이도록 조정 (maximized → restored + restoredTop 76%)
        $this->ensureNotesPaneVisible($zip);

        $zip->close();

        return $this->result($tmp, $ctx);
    }

    private function result(string $path, ReportContext $ctx): array
    {
        return [
            'path'          => $path,
            'download_name' => $ctx->downloadName('pptx'),
            'mime'          => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];
    }

    /**
     * 슬라이드 인덱스 (1-based) → notesSlide xml 경로 매핑.
     * 각 slide{N}.xml.rels 에서 notesSlide 타겟을 읽어 정확히 매핑.
     */
    private function mapSlideToNotes(\ZipArchive $zip): array
    {
        $map = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('#^ppt/slides/_rels/slide(\d+)\.xml\.rels$#', $name, $m)) continue;
            $slideIdx = (int) $m[1];
            $relsXml  = $zip->getFromName($name);
            if ($relsXml === false) continue;
            // notesSlide rel 검출
            if (preg_match('#Target="([^"]*notesSlides/notesSlide\d+\.xml)"#', $relsXml, $tm)) {
                // 상대경로 정규화 ("../notesSlides/notesSlide3.xml" → "ppt/notesSlides/notesSlide3.xml")
                $rel = $tm[1];
                $abs = $this->resolveRelPath('ppt/slides/', $rel);
                $map[$slideIdx] = $abs;
            }
        }
        ksort($map);
        return $map;
    }

    private function resolveRelPath(string $baseDir, string $relPath): string
    {
        $parts = explode('/', rtrim($baseDir, '/') . '/' . $relPath);
        $stack = [];
        foreach ($parts as $p) {
            if ($p === '' || $p === '.') continue;
            if ($p === '..') array_pop($stack);
            else             $stack[] = $p;
        }
        return implode('/', $stack);
    }

    /**
     * notesSlide{N}.xml 본문 내 body placeholder 의 <p:txBody> 안쪽에 의견 paragraphs 를 추가.
     */
    private function injectIntoNotesXml(string $xml, array $items): string
    {
        $insertBlock = $this->buildAParagraphsBlock($items);
        if ($insertBlock === '') return $xml;

        // body 형식의 placeholder shape 의 <p:txBody> 안 마지막에 주입.
        // 패턴: <p:sp> ... <p:nvSpPr>...<p:ph type="body" .../> ... <p:txBody>...(여기 주입)</p:txBody>
        $patternBody = '#(<p:sp>(?:(?!</p:sp>).)*?<p:ph[^/>]*type="body"[^/>]*/>(?:(?!</p:sp>).)*?<p:txBody>(?:(?!</p:txBody>).)*)(</p:txBody>)#s';
        $count = 0;
        $new = preg_replace($patternBody, '$1' . $insertBlock . '$2', $xml, 1, $count);
        if ($count > 0 && $new !== null) return $new;

        // body placeholder 가 없으면 마지막 <p:txBody> 에 주입 (대부분 노트 영역)
        $lastClose = strrpos($xml, '</p:txBody>');
        if ($lastClose !== false) {
            return substr($xml, 0, $lastClose) . $insertBlock . substr($xml, $lastClose);
        }

        return $xml;
    }

    /**
     * 의견(+답글) 항목들을 <a:p> paragraph 시퀀스로 빌드.
     */
    private function buildAParagraphsBlock(array $items): string
    {
        $out = '';

        // 헤더
        $out .= $this->ap('파일 의견 (' . count($items) . '건)', size: 1300, color: '4F46E5', bold: true);

        foreach ($items as $i => $row) {
            $page    = $row['page'];
            $c       = $row['comment'];
            $author  = $c->user?->name ?? $c->guest_name ?? '외부';
            $time    = optional($c->created_at)->format('Y-m-d H:i');
            $resolved= $c->resolved ? '   [해결됨]' : '';

            // 빈 줄 구분
            $out .= $this->ap('', size: 1100);

            $tag = $page > 0 ? "[페이지 {$page}] " : '[페이지 미지정] ';
            $tagColor = $page > 0 ? '4F46E5' : '6B7280';

            // 머리: 라벨 + 작성자 + 시간 (같은 paragraph, 다른 run)
            $out .= '<a:p>'
                  . $this->run($tag,   1100, $tagColor, true)
                  . $this->run($author, 1100, '1F2937', true)
                  . $this->run('  ' . $time . $resolved, 900, '6B7280', false)
                  . '</a:p>';

            foreach (preg_split('/\r\n|\r|\n/', (string) $c->content) as $line) {
                $out .= $this->ap($line === '' ? ' ' : $line, size: 1100, color: '1F2937');
            }

            if ($c->replies && count($c->replies) > 0) {
                foreach ($c->replies as $r) {
                    $rAuthor = $r->user?->name ?? $r->guest_name ?? '외부';
                    $rTime   = optional($r->created_at)->format('Y-m-d H:i');

                    $out .= '<a:p><a:pPr marL="180000" lvl="0"/>'
                          . $this->run('↳ ' . $rAuthor, 1000, '6D28D9', true)
                          . $this->run('  ' . $rTime, 900, '6B7280', false)
                          . '</a:p>';

                    foreach (preg_split('/\r\n|\r|\n/', (string) $r->content) as $line) {
                        $out .= '<a:p><a:pPr marL="180000" lvl="0"/>'
                              . $this->run($line === '' ? ' ' : $line, 1000, '374151', false)
                              . '</a:p>';
                    }
                }
            }
        }

        return $out;
    }

    /** 단순 paragraph 한 줄 */
    private function ap(string $text, int $size = 1100, string $color = '1F2937', bool $bold = false): string
    {
        return '<a:p>' . $this->run($text, $size, $color, $bold) . '</a:p>';
    }

    /** Run XML — sz 단위는 1/100 pt (예: 1100 = 11pt) */
    private function run(string $text, int $size, string $color, bool $bold): string
    {
        $boldAttr = $bold ? ' b="1"' : '';
        $escaped  = $this->xmlEscape($text);
        return '<a:r><a:rPr lang="ko-KR" sz="' . $size . '"' . $boldAttr . ' dirty="0">'
             . '<a:solidFill><a:srgbClr val="' . $color . '"/></a:solidFill>'
             . '</a:rPr><a:t>' . $escaped . '</a:t></a:r>';
    }

    private function xmlEscape(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * PowerPoint Normal View 의 분할바를 노트 영역이 잘 보이도록 조정.
     * - horzBarState="maximized" → "restored" (분할바 사용)
     * - restoredTop sz=94610 (슬라이드 94.6%) → 76000 (슬라이드 76%, 노트 24%)
     * 파일에 viewProps.xml 이 없으면 새로 생성하고 Content_Types/관계도 등록.
     */
    private function ensureNotesPaneVisible(\ZipArchive $zip): void
    {
        $vpPath = 'ppt/viewProps.xml';
        $vpXml  = $zip->getFromName($vpPath);

        if ($vpXml === false) {
            $this->createViewPropsForNotes($zip);
            return;
        }

        $changed = false;

        // 1) horzBarState="maximized" → "restored" (분할바를 펼친 상태로)
        $newXml = preg_replace(
            '/horzBarState="maximized"/',
            'horzBarState="restored"',
            $vpXml,
            -1,
            $cnt
        );
        if ($cnt > 0) { $vpXml = $newXml; $changed = true; }

        // 2) restoredTop sz="..." 값을 76000 정도로 (슬라이드 76% / 노트 24%)
        $newXml = preg_replace_callback(
            '/<p:restoredTop\s+sz="(\d+)"([^\/]*)\/>/',
            function ($m) {
                $cur = (int) $m[1];
                $new = ($cur > 80000) ? 76000 : $cur;   // 너무 크게 잡힌 것만 줄임
                return '<p:restoredTop sz="' . $new . '"' . $m[2] . '/>';
            },
            $vpXml,
            -1,
            $cnt
        );
        if ($cnt > 0) { $vpXml = $newXml; $changed = true; }

        // 3) <p:normalViewPr> 자체가 없으면 추가
        if (!str_contains($vpXml, '<p:normalViewPr')) {
            $insert = '<p:normalViewPr horzBarState="restored"><p:restoredLeft sz="15611"/><p:restoredTop sz="76000"/></p:normalViewPr>';
            $vpXml = preg_replace('/<p:viewPr([^>]*)>/', '<p:viewPr$1>' . $insert, $vpXml, 1, $cnt);
            if ($cnt > 0) $changed = true;
        }

        if ($changed) {
            $zip->addFromString($vpPath, $vpXml);
        }
    }

    private function createViewPropsForNotes(\ZipArchive $zip): void
    {
        // viewProps.xml 본체
        $vp = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<p:viewPr xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
            . ' xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
            . '<p:normalViewPr horzBarState="restored">'
            . '<p:restoredLeft sz="15611"/><p:restoredTop sz="76000"/>'
            . '</p:normalViewPr>'
            . '<p:gridSpacing cx="76200" cy="76200"/>'
            . '</p:viewPr>';
        $zip->addFromString('ppt/viewProps.xml', $vp);

        // Content_Types 등록
        $ct = $zip->getFromName('[Content_Types].xml');
        if ($ct !== false && !str_contains($ct, 'ppt/viewProps.xml')) {
            $override = '<Override PartName="/ppt/viewProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.viewProps+xml"/>';
            $ct = preg_replace('/<\/Types>/', $override . '</Types>', $ct, 1);
            $zip->addFromString('[Content_Types].xml', $ct);
        }

        // presentation.xml.rels 에 관계 추가
        $relsPath = 'ppt/_rels/presentation.xml.rels';
        $rels = $zip->getFromName($relsPath);
        if ($rels !== false && !str_contains($rels, 'viewProps.xml')) {
            // 새 rId 결정 (가장 큰 번호 + 1)
            preg_match_all('/Id="rId(\d+)"/', $rels, $rm);
            $maxId = !empty($rm[1]) ? max(array_map('intval', $rm[1])) : 0;
            $newId = 'rId' . ($maxId + 1);
            $relEl = '<Relationship Id="' . $newId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/viewProps" Target="viewProps.xml"/>';
            $rels = preg_replace('/<\/Relationships>/', $relEl . '</Relationships>', $rels, 1);
            $zip->addFromString($relsPath, $rels);
        }
    }
}
