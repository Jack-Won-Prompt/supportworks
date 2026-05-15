<?php

namespace App\Services\FileCommentReport;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * PDF/이미지/기타: 원본과 별도의 "의견 보고서" PDF 생성.
 * (원본 PDF 와 병합하지 않음 — 사용자 결정)
 */
class PdfReportBuilder implements ReportBuilderInterface
{
    public function build(ReportContext $ctx): array
    {
        $pdf = Pdf::loadView('files.comment_report_pdf', [
            'ctx'           => $ctx,
            'commentsByPage'=> $ctx->commentsByPage(),
        ])->setPaper('a4');

        $tmp = tempnam(sys_get_temp_dir(), 'fcr_') . '.pdf';
        file_put_contents($tmp, $pdf->output());

        $info = pathinfo($ctx->sourceName);
        $base = $info['filename'] ?? 'file';
        // PDF 보고서는 원본과 별도이므로 .의견.pdf 접미사
        $downloadName = $base . '.의견.pdf';

        return [
            'path'          => $tmp,
            'download_name' => $downloadName,
            'mime'          => 'application/pdf',
        ];
    }
}
