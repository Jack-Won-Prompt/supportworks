<?php

namespace App\Services\FileCommentReport;

use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Comment;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Word: 원본 .docx 를 그대로 유지하면서 의견(+답글)을 Word 네이티브 검토 주석으로 추가한다.
 *
 * - PhpWord 는 렌더링 없이 "페이지 N의 위치"를 알 수 없어 페이지별 정확 매핑이 불가능.
 *   사용자 결정에 따라 "모든 의견을 첫 페이지의 첫 텍스트 요소에 모아 anchor" 한다.
 *   - 파일을 열자마자 우측 마진 풍선으로 전체 의견을 즉시 확인 가능
 *   - 각 의견 본문에 [페이지 N] 표기로 출처 페이지 식별 보장
 *
 * - Word/LibreOffice 검토 창(Review Pane)에서 모든 주석이 작성자·날짜와 함께 노출된다.
 * - 본문에 텍스트가 전혀 없는 문서(이미지·도형뿐)는 끝에 마커 섹션을 새로 만들고 거기에 anchor.
 */
class DocxReportBuilder implements ReportBuilderInterface
{
    public function build(ReportContext $ctx): array
    {
        $phpWord = $this->loadOrFresh($ctx);

        $byPage = $ctx->commentsByPage();   // [pageNo => [comments...]]

        if (count($byPage) > 0) {
            // 첫 anchor 후보(본문 첫 텍스트)를 찾아 모든 의견을 거기 모음
            $firstAnchor = $this->findFirstTextAnchor($phpWord);

            if ($firstAnchor !== null) {
                $this->attachAllCommentsAtFirst($phpWord, $byPage, $firstAnchor);
            } else {
                // 본문에 텍스트가 없으면 (이미지·도형만 있는 문서) 마커 섹션으로 폴백
                $this->appendMarkerSection($phpWord, $ctx, $byPage);
            }
        }

        $tmp = tempnam(sys_get_temp_dir(), 'fcr_') . '.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);

        // Word/LibreOffice 호환성 보정 (PhpWord 출력 후처리)
        // - 16진 comment id → 순차 정수
        // - <w:commentReference> 를 CommentReference rStyle 로 감싸서 본문 인라인 마커 표시
        // - styles.xml 에 CommentReference style 정의 보장
        $this->postProcessDocxForCommentDisplay($tmp);

        return [
            'path'          => $tmp,
            'download_name' => $ctx->downloadName('docx'),
            'mime'          => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
    }

    /**
     * 저장된 .docx ZIP 을 열어서 주석 표시를 위한 보정을 수행한다.
     */
    private function postProcessDocxForCommentDisplay(string $docxPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) return;

        $doc    = $zip->getFromName('word/document.xml');
        $cmts   = $zip->getFromName('word/comments.xml');
        $styles = $zip->getFromName('word/styles.xml');

        if ($doc === false || $cmts === false) {
            $zip->close();
            return;
        }

        // 1. 모든 comment id 수집 (document.xml + comments.xml 양쪽)
        $idsRaw = [];
        if (preg_match_all('/<w:(?:commentRangeStart|commentRangeEnd|commentReference)\b[^>]*\bw:id="([^"]+)"/', $doc, $m)) {
            foreach ($m[1] as $v) $idsRaw[] = $v;
        }
        if (preg_match_all('/<w:comment\b[^>]*\bw:id="([^"]+)"/', $cmts, $m)) {
            foreach ($m[1] as $v) $idsRaw[] = $v;
        }
        $uniqueIds = array_values(array_unique($idsRaw));

        // 2. 순차 정수로 매핑 (이미 0,1,2,.. 면 그대로)
        $idMap = [];
        $needsRemap = false;
        foreach ($uniqueIds as $i => $oldId) {
            $newId = (string) $i;
            $idMap[$oldId] = $newId;
            if ($oldId !== $newId) $needsRemap = true;
        }

        if ($needsRemap) {
            // 정확 치환을 위해 토큰별 placeholder 단계 거침
            $placeholderMap = [];
            foreach ($idMap as $old => $new) {
                $placeholderMap['__FCR_CID_' . md5($old) . '__'] = $new;
                $doc  = preg_replace('/(<w:(?:commentRangeStart|commentRangeEnd|commentReference)\b[^>]*\bw:id=")' . preg_quote($old, '/') . '(")/', '$1__FCR_CID_' . md5($old) . '__$2', $doc);
                $cmts = preg_replace('/(<w:comment\b[^>]*\bw:id=")' . preg_quote($old, '/') . '(")/', '$1__FCR_CID_' . md5($old) . '__$2', $cmts);
            }
            $doc  = strtr($doc,  $placeholderMap);
            $cmts = strtr($cmts, $placeholderMap);
        }

        // 3. <w:r><w:commentReference .../></w:r> → CommentReference rStyle 추가
        //    이미 rStyle 이 있는 경우는 건드리지 않음
        $doc = preg_replace_callback(
            '#<w:r>(?!<w:rPr>)(<w:commentReference\b[^/]*/>)</w:r>#',
            fn($m) => '<w:r><w:rPr><w:rStyle w:val="CommentReference"/></w:rPr>' . $m[1] . '</w:r>',
            $doc
        );

        // 4. styles.xml 에 CommentReference style 정의 보장
        if ($styles !== false && !preg_match('/w:styleId="CommentReference"/', $styles)) {
            $styleDef = '<w:style w:type="character" w:styleId="CommentReference"><w:name w:val="annotation reference"/><w:semiHidden/><w:unhideWhenUsed/><w:rPr><w:sz w:val="16"/><w:szCs w:val="16"/></w:rPr></w:style>';
            $styles = preg_replace('#</w:styles>#', $styleDef . '</w:styles>', $styles, 1);
            $zip->addFromString('word/styles.xml', $styles);
        }

        $zip->addFromString('word/document.xml', $doc);
        $zip->addFromString('word/comments.xml', $cmts);
        $zip->close();
    }

    /**
     * 본문에서 첫 번째 anchor 가능한 텍스트 요소를 찾아 반환.
     * 모든 의견은 이 첫 요소에 모아 anchor → 사용자가 첫 페이지에서 모든 의견을 즉시 확인.
     */
    private function findFirstTextAnchor(PhpWord $phpWord): ?AbstractElement
    {
        foreach ($phpWord->getSections() as $section) {
            $found = $this->firstTextElementIn($section->getElements());
            if ($found !== null) return $found;
        }
        return null;
    }

    /** @param array $elements */
    private function firstTextElementIn(array $elements): ?AbstractElement
    {
        foreach ($elements as $el) {
            if ($el instanceof TextRun) {
                $childEls = method_exists($el, 'getElements') ? $el->getElements() : [];
                foreach ($childEls as $child) {
                    if ($child instanceof Text && trim((string) $child->getText()) !== '') {
                        return $child;
                    }
                }
                // 내부에 Text 가 없는 TextRun 자체도 anchor 가능
                if (!empty($childEls)) return $el;
            } elseif ($el instanceof Text) {
                if (trim((string) $el->getText()) !== '') return $el;
            } elseif (method_exists($el, 'getElements')) {
                // Table/Cell/Header/Footer 같은 컨테이너는 재귀 탐색
                $found = $this->firstTextElementIn($el->getElements());
                if ($found !== null) return $found;
            }
        }
        return null;
    }

    /**
     * 모든 의견을 첫 anchor 요소에 등록 — 첫 페이지의 우측 마진 풍선으로 전체 노출.
     *
     * @param array<int, array> $byPage
     */
    private function attachAllCommentsAtFirst(PhpWord $phpWord, array $byPage, AbstractElement $firstAnchor): void
    {
        foreach ($byPage as $pageNo => $comments) {
            foreach ($comments as $c) {
                $this->buildAndAttachComment($phpWord, $firstAnchor, $pageNo, $c);
            }
        }
    }

    /**
     * 단일 의견(+답글)을 Word 네이티브 Comment 로 등록 (지정한 anchor 요소에)
     *
     * NOTE: 모든 run 에 한글 가능 폰트(맑은 고딕)를 명시적으로 지정한다.
     * 미지정 시 Word/LibreOffice 가 한글 미지원 기본 폰트(Calibri 등)로 fallback 하여
     * 메모 영역은 보이지만 한글이 빈칸으로 렌더링되는 문제가 발생함.
     */
    private function buildAndAttachComment(PhpWord $phpWord, AbstractElement $anchor, int $pageNo, $comment): void
    {
        $author   = $comment->user?->name ?? $comment->guest_name ?? '외부';
        $time     = optional($comment->created_at)->format('Y-m-d H:i');
        $initials = $this->makeInitials($author);
        $font     = '맑은 고딕';

        $cmt = new Comment($author, new \DateTime(), $initials);

        $head = $cmt->addTextRun();
        if ($pageNo > 0) {
            $head->addText("[페이지 {$pageNo}] ", ['name' => $font, 'bold' => true, 'color' => '4F46E5', 'size' => 10]);
        } else {
            $head->addText("[페이지 미지정] ", ['name' => $font, 'bold' => true, 'color' => '6B7280', 'size' => 10]);
        }
        $head->addText($author, ['name' => $font, 'bold' => true, 'size' => 10]);
        $head->addText('  ' . $time, ['name' => $font, 'color' => '6B7280', 'size' => 9]);
        if ($comment->resolved) {
            $head->addText('  [해결됨]', ['name' => $font, 'bold' => true, 'color' => '166534', 'size' => 9]);
        }

        foreach (preg_split('/\r\n|\r|\n/', (string) $comment->content) as $line) {
            $cmt->addText($line === '' ? ' ' : $line, ['name' => $font, 'size' => 11, 'color' => '1F2937']);
        }

        if ($comment->replies && count($comment->replies) > 0) {
            foreach ($comment->replies as $r) {
                $rAuthor = $r->user?->name ?? $r->guest_name ?? '외부';
                $rTime   = optional($r->created_at)->format('Y-m-d H:i');

                $rHead = $cmt->addTextRun();
                $rHead->addText('  ↳ ' . $rAuthor, ['name' => $font, 'bold' => true, 'color' => '6D28D9', 'size' => 10]);
                $rHead->addText('  ' . $rTime, ['name' => $font, 'color' => '6B7280', 'size' => 9]);

                foreach (preg_split('/\r\n|\r|\n/', (string) $r->content) as $line) {
                    $cmt->addText('  ' . ($line === '' ? ' ' : $line), ['name' => $font, 'size' => 10, 'color' => '374151']);
                }
            }
        }

        $phpWord->addComment($cmt);
        $cmt->setStartElement($anchor);
        $cmt->setEndElement($anchor);
    }

    private function loadOrFresh(ReportContext $ctx): PhpWord
    {
        try {
            return IOFactory::load($ctx->sourcePath);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[DocxReportBuilder] load failed, falling back to fresh: ' . $e->getMessage());
            $fresh = new PhpWord();
            $fresh->setDefaultFontName('맑은 고딕');
            $fresh->setDefaultFontSize(11);
            return $fresh;
        }
    }

    /**
     * 문서 끝에 작은 마커 섹션을 추가하고, 각 의견을 Word 네이티브 주석으로 anchor 한다.
     */
    private function appendMarkerSection(PhpWord $phpWord, ReportContext $ctx, array $byPage): void
    {
        $section = $phpWord->addSection();

        // 안내 헤더 — 검토 창 사용을 유도
        $section->addText(
            '■ 의견 메모 — Word 검토 창(Review Pane)에서 확인하세요',
            ['name' => '맑은 고딕', 'size' => 11, 'bold' => true, 'color' => '4F46E5'],
            ['alignment' => Jc::START, 'spaceAfter' => 120]
        );
        $section->addText(
            "검토 → 검토 창 표시 (단축키 Alt+R, T) 를 누르면 모든 의견이 작성자·날짜와 함께 우측 패널에 나열됩니다.\n각 의견 본문에 [페이지 N] 표기가 있어 출처 페이지를 식별할 수 있습니다.",
            ['name' => '맑은 고딕', 'size' => 9, 'color' => '6B7280'],
            ['spaceAfter' => 200]
        );

        foreach ($byPage as $pageNo => $comments) {
            $label = $pageNo > 0
                ? "📌 페이지 {$pageNo}  (의견 " . count($comments) . "건)"
                : '📌 (페이지 미지정)  (의견 ' . count($comments) . '건)';

            // 페이지 그룹 마커 — 단순 단락
            $markerRun = $section->addTextRun(['spaceBefore' => 120, 'spaceAfter' => 60]);
            $markerText = $markerRun->addText($label, [
                'name' => '맑은 고딕', 'size' => 11, 'bold' => true, 'color' => '0891B2',
            ]);

            foreach ($comments as $c) {
                $this->buildAndAttachComment($phpWord, $markerText, $pageNo, $c);
            }
        }
    }

    private function makeInitials(string $name): string
    {
        $name = trim($name);
        if ($name === '') return '?';
        // 한글이면 첫 글자 1자, 영문이면 단어 첫글자 2자 결합
        if (preg_match('/^[\x{AC00}-\x{D7A3}]/u', $name)) {
            return mb_substr($name, 0, 1, 'UTF-8');
        }
        $parts = preg_split('/\s+/', $name);
        $ini   = '';
        foreach ($parts as $p) {
            $ini .= mb_substr($p, 0, 1, 'UTF-8');
            if (mb_strlen($ini) >= 2) break;
        }
        return $ini ?: mb_substr($name, 0, 2);
    }
}
