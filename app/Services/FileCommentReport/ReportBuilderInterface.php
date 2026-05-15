<?php

namespace App\Services\FileCommentReport;

interface ReportBuilderInterface
{
    /**
     * 보고서 파일을 빌드하고 결과 파일의 절대 경로(임시) + 다운로드용 파일명을 반환한다.
     *
     * @return array{path: string, download_name: string, mime: string}
     */
    public function build(ReportContext $ctx): array;
}
