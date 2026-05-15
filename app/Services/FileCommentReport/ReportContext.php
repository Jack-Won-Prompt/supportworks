<?php

namespace App\Services\FileCommentReport;

use App\Models\FileComment;
use App\Models\FileVersion;
use App\Models\ProjectFile;
use Illuminate\Support\Collection;

/**
 * Builder 들에 전달되는 입력 묶음.
 * 빌더는 이 컨텍스트만 보고 결과 파일을 생성한다.
 */
class ReportContext
{
    public function __construct(
        public readonly ProjectFile $file,
        public readonly int $version,
        public readonly bool $isCurrentVersion,
        public readonly ?FileVersion $versionRow,   // null = 현재 버전
        public readonly Collection $rootComments,   // parent_id IS NULL (replies eager-loaded)
        public readonly string $sourcePath,         // storage 상의 원본 파일 경로
        public readonly string $sourceName,         // 원본 파일명 (확장자 포함)
        public readonly string $generatedAt,
        public readonly ?string $generatedByName = null,
    ) {}

    /** "의견" 접미사가 붙은 다운로드 파일명 (확장자 유지) */
    public function downloadName(string $extOverride = null): string
    {
        $info = pathinfo($this->sourceName);
        $base = $info['filename'] ?? 'file';
        $ext  = $extOverride ?? ($info['extension'] ?? 'bin');
        return $base . '.의견포함.' . $ext;
    }

    /**
     * "페이지번호 정렬된" 의견 그룹 — page=null 은 0 으로 처리.
     * 페이지 없는(=0) 그룹은 항상 가장 뒤로 배치한다 — 모든 빌더 일관 규칙.
     */
    public function commentsByPage(): array
    {
        $byPage = [];
        foreach ($this->rootComments as $c) {
            $p = $c->page ?? 0;
            $byPage[$p] = $byPage[$p] ?? [];
            $byPage[$p][] = $c;
        }
        ksort($byPage);

        // 0 (페이지 미지정) 그룹을 맨 뒤로 이동
        if (array_key_exists(0, $byPage)) {
            $orphan = $byPage[0];
            unset($byPage[0]);
            $byPage[0] = $orphan;
        }
        return $byPage;
    }
}
