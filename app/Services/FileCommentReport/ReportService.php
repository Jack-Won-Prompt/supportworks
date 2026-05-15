<?php

namespace App\Services\FileCommentReport;

use App\Models\FileComment;
use App\Models\ProjectFile;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    /**
     * 파일 + 버전 + 의견을 받아 적절한 빌더로 보고서를 생성한다.
     *
     * @return array{path: string, download_name: string, mime: string}
     */
    public function build(ProjectFile $file, int $requestedVersion = 0): array
    {
        $currentVersion = $file->currentVersionNumber();
        if ($requestedVersion <= 0 || $requestedVersion > $currentVersion) {
            $requestedVersion = $currentVersion;
        }

        $isCurrent = ($requestedVersion === $currentVersion);
        $versionRow = $isCurrent
            ? null
            : $file->versions()->where('version', $requestedVersion)->first();

        $isUrl      = $file->isUrlType();
        $sourcePath = $isCurrent ? $file->path : $versionRow?->path;
        $sourceName = $isCurrent ? $file->original_name : ($versionRow?->original_name ?? $file->original_name);

        // URL 타입 파일은 디스크상의 원본이 없으므로 source 검증 스킵 → PDF 보고서로만 생성
        if (!$isUrl && !$sourcePath) {
            abort(404, '해당 버전의 원본 파일을 찾을 수 없습니다.');
        }

        // 의견 필터 — preview-data 와 동일한 규칙
        // · 현재 버전 → 미해결 + 동결되지 않은 코멘트
        // · 이전 버전 V → 그 버전에서 해결되었거나(resolved_at_version=V+1) 동결된(frozen_at_version=V+1) 코멘트
        $query = $file->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('page')
            ->orderBy('created_at');
        if ($isCurrent) {
            $query->where('resolved', false)
                  ->whereNull('frozen_at_version');
        } else {
            $query->where(function ($q) use ($requestedVersion) {
                $q->where('resolved_at_version', $requestedVersion + 1)
                  ->orWhere('frozen_at_version',  $requestedVersion + 1);
            });
        }
        $rootComments = $query->get();

        $absSource = '';
        if ($sourcePath) {
            $tryPath = Storage::disk('local')->path($sourcePath);
            if (is_file($tryPath)) {
                $absSource = $tryPath;
            } elseif (!$isUrl) {
                abort(404, '원본 파일을 디스크에서 찾을 수 없습니다.');
            }
        }

        // source 가 없으면 (URL 타입 등) PDF 보고서로 강제 — DocxReportBuilder 등은 source 없이는 동작 불가
        $forcePdf = ($absSource === '');
        $effectiveName = $sourceName ?: ($file->original_name ?: 'review.pdf');

        $ctx = new ReportContext(
            file:               $file,
            version:            $requestedVersion,
            isCurrentVersion:   $isCurrent,
            versionRow:         $versionRow,
            rootComments:       $rootComments,
            sourcePath:         $absSource,
            sourceName:         $effectiveName,
            generatedAt:        now()->format('Y-m-d H:i'),
            generatedByName:    auth()->user()?->name,
        );

        $ext = strtolower(pathinfo($effectiveName, PATHINFO_EXTENSION));
        $builder = $forcePdf
            ? new PdfReportBuilder()
            : match ($ext) {
                'docx', 'doc'   => new DocxReportBuilder(),
                'xlsx', 'xls'   => new XlsxReportBuilder(),
                'pptx', 'ppt'   => new PptxReportBuilder(),
                // PDF / 이미지 / 기타: 별도 PDF 보고서
                default         => new PdfReportBuilder(),
            };

        return $builder->build($ctx);
    }
}
