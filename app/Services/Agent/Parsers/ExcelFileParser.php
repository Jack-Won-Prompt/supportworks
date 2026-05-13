<?php

namespace App\Services\Agent\Parsers;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelFileParser implements FileParser
{
    private const SUPPORTED = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'application/x-xls',
        'application/xls',
    ];

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, self::SUPPORTED, true);
    }

    public function parse(string $storagePath, string $absolutePath): ParsedFileContent
    {
        $spreadsheet = IOFactory::load($absolutePath);

        $sheets     = [];
        $allText    = [];
        $sheetCount = $spreadsheet->getSheetCount();

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetTitle = $sheet->getTitle();
            $rows       = [];
            $rowTexts   = [];

            foreach ($sheet->getRowIterator() as $row) {
                $cellValues = [];
                $cellIter   = $row->getCellIterator();
                $cellIter->setIterateOnlyExistingCells(false);

                foreach ($cellIter as $cell) {
                    $value = $cell->getFormattedValue();
                    if ($value !== '' && $value !== null) {
                        $cellValues[$cell->getColumn()] = $value;
                    }
                }

                if (!empty($cellValues)) {
                    $rows[]     = $cellValues;
                    $rowTexts[] = implode("\t", $cellValues);
                }
            }

            $sheetText = "[시트: {$sheetTitle}]\n" . implode("\n", $rowTexts);
            $allText[] = $sheetText;

            $sheets[] = [
                'title'    => $sheetTitle,
                'row_count'=> $sheet->getHighestRow(),
                'col_count'=> \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn()),
                'preview'  => array_slice($rows, 0, 5),
            ];
        }

        $extractedText = implode("\n\n", $allText);
        // 최대 50,000자 제한
        if (mb_strlen($extractedText) > 50000) {
            $extractedText = mb_substr($extractedText, 0, 50000) . "\n... (잘림)";
        }

        return new ParsedFileContent(
            fileType:       'excel',
            extractedText:  $extractedText,
            structure:      $sheets,
            imageReferences:[],
            metadata: [
                'sheet_count' => $sheetCount,
            ],
        );
    }
}
