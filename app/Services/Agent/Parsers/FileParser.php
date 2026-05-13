<?php

namespace App\Services\Agent\Parsers;

interface FileParser
{
    public function supports(string $mimeType): bool;

    /**
     * @param  string $storagePath  Storage 디스크 상의 경로 (disk('local')->path() 적용 전)
     * @param  string $absolutePath 실제 파일 절대 경로
     */
    public function parse(string $storagePath, string $absolutePath): ParsedFileContent;
}
