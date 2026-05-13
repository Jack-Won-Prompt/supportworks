<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('file_annotations')->get();

        foreach ($rows as $row) {
            $data = json_decode($row->data, true);
            if (! is_array($data)) continue;

            $newData = $this->convert($row->type, $data);
            if ($newData === $data) continue;

            DB::table('file_annotations')
                ->where('id', $row->id)
                ->update(['data' => json_encode($newData)]);
        }
    }

    public function down(): void {}

    private function isFraction(float $v): bool
    {
        return $v >= 0 && $v <= 1.5;
    }

    private function convert(string $type, array $d): array
    {
        switch ($type) {
            case 'number':
                // Old public-share format: {cx, cy, n, color} with fractions
                // New unified format:       {x,  y,  n, color} with percentages
                if (isset($d['cx'])) {
                    if (! $this->isFraction((float) $d['cx'])) return $d;
                    $d['x'] = round($d['cx'] * 100, 4);
                    $d['y'] = round($d['cy'] * 100, 4);
                    unset($d['cx'], $d['cy']);
                } elseif (isset($d['x']) && $this->isFraction((float) $d['x'])) {
                    $d['x'] = round($d['x'] * 100, 4);
                    $d['y'] = round($d['y'] * 100, 4);
                }
                break;

            case 'rect':
            case 'line':
                if (isset($d['x1']) && $this->isFraction((float) $d['x1'])) {
                    $d['x1'] = round($d['x1'] * 100, 4);
                    $d['y1'] = round($d['y1'] * 100, 4);
                    $d['x2'] = round($d['x2'] * 100, 4);
                    $d['y2'] = round($d['y2'] * 100, 4);
                }
                break;

            case 'circle':
                if (isset($d['cx']) && $this->isFraction((float) $d['cx'])) {
                    $d['cx'] = round($d['cx'] * 100, 4);
                    $d['cy'] = round($d['cy'] * 100, 4);
                    $d['rx'] = round($d['rx'] * 100, 4);
                    $d['ry'] = round($d['ry'] * 100, 4);
                }
                break;

            case 'text':
                if (isset($d['x']) && $this->isFraction((float) $d['x'])) {
                    $d['x'] = round($d['x'] * 100, 4);
                    $d['y'] = round($d['y'] * 100, 4);
                }
                break;
        }
        return $d;
    }
};
