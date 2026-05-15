<?php

namespace App\Services\FileCommentReport;

use PhpOffice\PhpSpreadsheet\Comment;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel: 원본 .xlsx 를 그대로 두고 의견을 시트별 셀 주석으로 추가한다.
 *
 * 매핑 규칙:
 *  - comment.page = 시트 인덱스 (1-based, 워크북 내 순서)
 *  - 시트 수를 초과하거나 page 가 없으면 → 마지막 시트로 모음
 *  - 같은 시트에 다수 의견이면 A1 한 셀의 주석에 통합
 */
class XlsxReportBuilder implements ReportBuilderInterface
{
    public function build(ReportContext $ctx): array
    {
        $spreadsheet = $this->loadOrFresh($ctx);

        $sheetCount = $spreadsheet->getSheetCount();
        if ($sheetCount === 0) {
            // 빈 워크북 방지
            $spreadsheet->createSheet();
            $sheetCount = 1;
        }

        // 시트 인덱스(0-based) → comments 그룹
        $bySheet = [];
        foreach ($ctx->commentsByPage() as $pageNo => $comments) {
            $idx = $pageNo > 0 ? min($pageNo, $sheetCount) - 1 : ($sheetCount - 1);
            $bySheet[$idx] = $bySheet[$idx] ?? [];
            foreach ($comments as $c) {
                // pageNo 정보 보존 (의견 본문에 표기)
                $bySheet[$idx][] = ['page' => $pageNo, 'comment' => $c];
            }
        }

        foreach ($bySheet as $sheetIdx => $items) {
            $sheet = $spreadsheet->getSheet($sheetIdx);
            $this->writeCellComment($sheet, 'A1', $items);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $tmp = tempnam(sys_get_temp_dir(), 'fcr_') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($tmp);

        return [
            'path'          => $tmp,
            'download_name' => $ctx->downloadName('xlsx'),
            'mime'          => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    private function loadOrFresh(ReportContext $ctx): Spreadsheet
    {
        try {
            return IOFactory::load($ctx->sourcePath);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[XlsxReportBuilder] load failed, falling back to fresh: ' . $e->getMessage());
            return new Spreadsheet();
        }
    }

    /**
     * 한 셀 주석에 여러 의견(+답글)을 RichText 로 통합 입력.
     */
    private function writeCellComment(Worksheet $sheet, string $cell, array $items): void
    {
        $existing = $sheet->getComment($cell);
        $rt = new RichText();

        // 기존 주석에 있던 텍스트가 있으면 보존 후 구분선
        $existingPlain = $existing->getText() ? $existing->getText()->getPlainText() : '';
        if ($existingPlain !== '') {
            $rt->createTextRun($existingPlain . "\n────────────────\n");
        }

        $hdr = $rt->createTextRun('📌 파일 의견');
        $hdr->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('4F46E5');
        $rt->createText("\n");

        foreach ($items as $i => $row) {
            $page    = $row['page'];
            $c       = $row['comment'];
            $author  = $c->user?->name ?? $c->guest_name ?? '외부';
            $time    = optional($c->created_at)->format('Y-m-d H:i');
            $resolved= $c->resolved ? '   [해결됨]' : '';
            $pageTag = $page > 0 ? "[페이지 {$page}] " : '[페이지 미지정] ';

            if ($i > 0) $rt->createText("\n");
            $rt->createText("\n");

            $tag = $rt->createTextRun($pageTag);
            $tag->getFont()->setBold(true)->setSize(10)->getColor()->setRGB($page > 0 ? '4F46E5' : '6B7280');

            $aRun = $rt->createTextRun($author);
            $aRun->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('1F2937');

            $mRun = $rt->createTextRun('  ' . $time . $resolved);
            $mRun->getFont()->setSize(9)->getColor()->setRGB('6B7280');
            $rt->createText("\n");

            $bRun = $rt->createTextRun((string) $c->content);
            $bRun->getFont()->setSize(10)->getColor()->setRGB('1F2937');

            if ($c->replies && count($c->replies) > 0) {
                foreach ($c->replies as $r) {
                    $rAuthor = $r->user?->name ?? $r->guest_name ?? '외부';
                    $rTime   = optional($r->created_at)->format('Y-m-d H:i');

                    $rt->createText("\n");
                    $rh = $rt->createTextRun('  ↳ ' . $rAuthor);
                    $rh->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('6D28D9');
                    $rm = $rt->createTextRun('  ' . $rTime);
                    $rm->getFont()->setSize(9)->getColor()->setRGB('6B7280');
                    $rt->createText("\n");
                    $rb = $rt->createTextRun('  ' . (string) $r->content);
                    $rb->getFont()->setSize(9)->getColor()->setRGB('374151');
                }
            }
        }

        $existing->setText($rt);
        $existing->setAuthor('SupportWorks');
        // 주석 박스 크기 — 내용에 맞게 넉넉히
        $existing->setWidth('360pt')->setHeight('220pt');
        // 시트 열자마자 항상 보이도록
        $existing->setVisible(true);
    }
}
