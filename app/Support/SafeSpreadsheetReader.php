<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;

/**
 * 업로드된 스프레드시트를 안전하게 로드하기 위한 래퍼.
 *
 * phpspreadsheet 미패치 취약점(악성 업로드 파일) 완화용:
 *  - 확장자 화이트리스트로 리더를 명시 선택 → Gnumeric gzip 폭탄 / HTML(SSRF) 리더 등 미사용
 *  - 최대 파일 크기 제한 → 과대 파일 처리 차단
 *  - dataOnly=true 시 수식 미평가(WEBSERVICE() SSRF 차단) + 스타일/차트 파싱 생략(DoS 표면 축소)
 *
 * 텍스트만 추출하는 경로(AI 분석 등)는 dataOnly=true 를 사용하고,
 * 서식 렌더링이 필요한 경로(변환/리포트)는 dataOnly=false 로 호출한다.
 */
class SafeSpreadsheetReader
{
    /** 허용 확장자 → phpspreadsheet 리더 타입 */
    private const READERS = [
        'xlsx' => 'Xlsx',
        'xlsm' => 'Xlsx',
        'xls'  => 'Xls',
        'csv'  => 'Csv',
        'ods'  => 'Ods',
    ];

    /** 최대 허용 크기 (바이트). SR 임포트 검증(50MB)과 정렬 */
    private const MAX_BYTES = 52_428_800; // 50MB

    public static function load(string $path, bool $dataOnly = true): Spreadsheet
    {
        if (!is_file($path)) {
            throw new RuntimeException('스프레드시트 파일을 찾을 수 없습니다.');
        }

        $size = filesize($path);
        if ($size === false || $size > self::MAX_BYTES) {
            throw new RuntimeException('스프레드시트 파일이 너무 큽니다.');
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!isset(self::READERS[$ext])) {
            throw new RuntimeException("지원하지 않는 스프레드시트 형식입니다: {$ext}");
        }

        // 확장자로 리더를 명시 선택 (콘텐츠 자동판별로 위험한 리더가 선택되는 것 방지)
        $reader = IOFactory::createReader(self::READERS[$ext]);
        if ($dataOnly) {
            $reader->setReadDataOnly(true);
        }

        return $reader->load($path);
    }
}
