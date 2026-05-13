<?php

namespace App\Services;

class ExcelWriter
{
    private string $title;
    private array  $sheets = [];

    private static string $workerPath = 'scripts/pptxgen/excel-worker.js';

    public function __construct(string $title = '문서')
    {
        $this->title = $title;
    }

    /** 웍스가 생성한 JSON 구조를 그대로 주입 */
    public function loadFromJson(array $jsonData): self
    {
        if (!empty($jsonData['title']))  $this->title  = $jsonData['title'];
        if (!empty($jsonData['sheets'])) $this->sheets = $jsonData['sheets'];
        return $this;
    }

    /** 단순 시트 추가 (직접 구성) */
    public function addSheet(string $name, string $title, array $headers, array $rows, array $options = []): self
    {
        $this->sheets[] = array_merge([
            'name'    => $name,
            'title'   => $title,
            'headers' => $headers,
            'rows'    => $rows,
        ], $options);
        return $this;
    }

    public function save(string $outputPath): string
    {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $jsonData = [
            'title'  => $this->title,
            'sheets' => $this->sheets,
        ];

        $tmpJson = sys_get_temp_dir() . '/xlsx-' . uniqid() . '.json';
        file_put_contents($tmpJson, json_encode($jsonData, JSON_UNESCAPED_UNICODE));

        try {
            $workerAbs = base_path(self::$workerPath);
            $outAbs    = str_replace('/', DIRECTORY_SEPARATOR, $outputPath);
            $cmd       = 'node ' . escapeshellarg($workerAbs)
                       . ' '    . escapeshellarg($tmpJson)
                       . ' '    . escapeshellarg($outAbs);

            $output = [];
            $code   = 0;
            exec($cmd . ' 2>&1', $output, $code);

            if ($code !== 0 || !file_exists($outputPath)) {
                throw new \RuntimeException(
                    'ExcelGen worker 오류: ' . implode("\n", $output)
                );
            }
        } finally {
            @unlink($tmpJson);
        }

        return $outputPath;
    }
}
