<?php

namespace App\Services\Analysis\FileExtractor;

use App\Support\SafeSpreadsheetReader;

class XlsxExtractor implements FileExtractorInterface
{
    public function supports(string $mimeType, string $extension): bool
    {
        return in_array($extension, ['xlsx', 'xls'])
            || in_array($mimeType, [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
            ]);
    }

    public function extract(string $filePath): string
    {
        $spreadsheet = SafeSpreadsheetReader::load($filePath);
        $lines = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetName = $sheet->getTitle();
            $lines[] = "[{$sheetName}]";

            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $value = $cell->getFormattedValue();
                    $cells[] = trim((string) $value);
                }
                $rowText = implode(' | ', array_filter($cells, fn($c) => $c !== ''));
                if ($rowText !== '') {
                    $lines[] = $rowText;
                }
            }
        }

        return implode("\n", $lines);
    }
}
