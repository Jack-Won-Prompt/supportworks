<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OfficeConverter
{
    public static function convertToPdf(string $storedFilePath): string
    {
        $sofficePath = self::findSoffice();

        if (!$sofficePath) {
            throw new \RuntimeException('LibreOffice를 찾을 수 없습니다. LIBREOFFICE_PATH 환경변수를 설정하세요.');
        }

        $inputAbsPath = Storage::disk('local')->path($storedFilePath);
        $relativeDir  = dirname($storedFilePath) . '/converted';
        $outputDir    = Storage::disk('local')->path($relativeDir);

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $baseName = pathinfo($storedFilePath, PATHINFO_FILENAME);
        $ext      = strtolower(pathinfo($storedFilePath, PATHINFO_EXTENSION));
        // Excel 변환 로직 변경 시 suffix를 바꿔 캐시 무효화 (현재: _p2)
        $suffix        = in_array($ext, ['xlsx', 'xls']) ? '_p2' : '';
        $relativePdf   = $relativeDir . '/' . $baseName . $suffix . '.pdf';
        $outputAbsPath = Storage::disk('local')->path($relativePdf);

        if (file_exists($outputAbsPath)) {
            return $relativePdf;
        }

        $convertInput = $inputAbsPath;
        $tempFile     = null;

        // XLSX/XLS: PhpSpreadsheet으로 각 시트를 1페이지로 설정 후 변환
        if (in_array($ext, ['xlsx', 'xls'])) {
            $prepared = self::prepareExcel($inputAbsPath, $outputDir, $baseName);
            // prepareExcel이 원본을 그대로 반환하면 tempFile로 취급하지 않음
            if ($prepared !== $inputAbsPath) {
                $tempFile = $prepared;
            }
            $convertInput = $prepared;
        }

        // Linux 웹서버 환경에서 홈 디렉터리 권한 문제 우회
        $userInstall = '';
        if (PHP_OS_FAMILY !== 'Windows') {
            $loProfile  = sys_get_temp_dir() . '/lo_profile_' . getmypid();
            $userInstall = ' "-env:UserInstallation=file://' . $loProfile . '"';
        }

        $cmd = '"' . $sofficePath . '"' . $userInstall
             . ' --headless --norestore --convert-to pdf --outdir "'
             . $outputDir . '" "' . $convertInput . '" 2>&1';

        exec($cmd, $output, $returnCode);

        // LibreOffice가 temp 파일 기준으로 PDF 이름을 만들었을 경우 rename
        if ($tempFile) {
            $tempPdf = $outputDir . DIRECTORY_SEPARATOR . pathinfo($tempFile, PATHINFO_FILENAME) . '.pdf';
            if (file_exists($tempPdf) && !file_exists($outputAbsPath)) {
                rename($tempPdf, $outputAbsPath);
            }
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }

        if ($returnCode !== 0 || !file_exists($outputAbsPath)) {
            throw new \RuntimeException('변환 실패: ' . implode(' | ', $output));
        }

        return $relativePdf;
    }

    private static function findSoffice(): ?string
    {
        // 1. 환경변수 우선
        $configured = config('services.libreoffice.path');
        if ($configured && file_exists($configured)) {
            return $configured;
        }

        // 2. OS별 기본 경로 탐색
        $candidates = PHP_OS_FAMILY === 'Windows'
            ? [
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
              ]
            : [
                '/usr/bin/libreoffice',
                '/usr/bin/soffice',
                '/usr/local/bin/libreoffice',
                '/opt/libreoffice/program/soffice',
              ];

        foreach ($candidates as $path) {
            if (file_exists($path)) return $path;
        }

        return null;
    }

    /**
     * PhpSpreadsheet으로 각 시트를 정확히 1페이지에 맞추도록 설정 후 임시 파일 반환.
     * - 차트 시트 등 PhpSpreadsheet이 처리할 수 없는 시트가 있으면 원본 경로를 반환(폴백).
     */
    private static function prepareExcel(string $inputAbsPath, string $outputDir, string $baseName): string
    {
        try {
            $spreadsheet    = IOFactory::load($inputAbsPath);
            $originalCount  = count($spreadsheet->getAllSheets());

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                try {
                    $setup   = $sheet->getPageSetup();
                    $margins = $sheet->getPageMargins();

                    // ── 방향 결정 (데이터 너비/높이 비율 기준) ──────
                    $highestRow    = max(1, $sheet->getHighestDataRow());
                    $highestCol    = $sheet->getHighestDataColumn();
                    $highestColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

                    $totalCharW = 0;
                    for ($c = 1; $c <= $highestColIdx; $c++) {
                        $w = $sheet->getColumnDimension(
                            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c)
                        )->getWidth();
                        $totalCharW += ($w < 0) ? 8.43 : $w;
                    }

                    $totalPtH = 0;
                    for ($r = 1; $r <= $highestRow; $r++) {
                        $h = $sheet->getRowDimension($r)->getRowHeight();
                        $totalPtH += ($h < 0) ? 15 : $h;
                    }

                    $contentW    = $totalCharW * 0.267; // cm
                    $contentH    = $totalPtH  * 0.0353; // cm
                    $orientation = ($contentW > $contentH)
                        ? \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                        : \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT;

                    // ── A4/A3 중 더 적합한 용지 선택 ────────────────
                    $isLandscape = ($orientation === \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                    $a3avW = $isLandscape ? 41.0 : 28.7;
                    $a3avH = $isLandscape ? 28.7 : 41.0;
                    $paperSize = (
                        ($contentW <= ($isLandscape ? 28.7 : 19.7)) &&
                        ($contentH <= ($isLandscape ? 20.0 : 28.7))
                    )
                        ? \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
                        : \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A3;

                    // ── 모든 시트를 정확히 1페이지에 맞춤 ───────────
                    $setup->setOrientation($orientation);
                    $setup->setPaperSize($paperSize);
                    $setup->setFitToPage(true);
                    $setup->setFitToWidth(1);
                    $setup->setFitToHeight(1);

                    // ── 여백 최소화 ──────────────────────────────────
                    $margins->setTop(0.5);
                    $margins->setBottom(0.5);
                    $margins->setLeft(0.5);
                    $margins->setRight(0.5);
                    $margins->setHeader(0);
                    $margins->setFooter(0);

                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning(
                        '[OfficeConverter] 시트 설정 실패, 기본값 유지: ' . $e->getMessage()
                    );
                }
            }

            $tempPath = $outputDir . DIRECTORY_SEPARATOR . $baseName . '_fitted.xlsx';
            $writer   = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tempPath);

            // 시트 수 검증: 재저장 후 시트가 누락됐으면 원본으로 폴백
            $check = IOFactory::load($tempPath);
            if (count($check->getAllSheets()) < $originalCount) {
                @unlink($tempPath);
                \Illuminate\Support\Facades\Log::warning(
                    "[OfficeConverter] 시트 누락 감지({$originalCount} → " . count($check->getAllSheets()) . "), 원본 파일로 변환"
                );
                return $inputAbsPath;
            }

            return $tempPath;

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                '[OfficeConverter] prepareExcel 실패, 원본 파일로 변환: ' . $e->getMessage()
            );
            return $inputAbsPath;
        }
    }
}
