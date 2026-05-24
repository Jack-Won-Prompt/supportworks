<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // branch 컬럼: 'origin/main' → 'main'
        DB::statement("UPDATE git_commits SET branch = SUBSTRING(branch, 8) WHERE branch LIKE 'origin/%'");

        // branches JSON: ["origin/main"] → ["main"]. MariaDB/MySQL 호환을 위해 PHP 백필.
        // 큰 테이블이면 청크로 처리.
        \App\Models\GitCommit::whereNotNull('branches')->chunkById(500, function ($commits) {
            foreach ($commits as $c) {
                $arr = is_array($c->branches) ? $c->branches : [];
                $changed = false;
                foreach ($arr as &$b) {
                    if (is_string($b) && str_starts_with($b, 'origin/')) {
                        $b = substr($b, 7);
                        $changed = true;
                    }
                }
                unset($b);
                if ($changed) {
                    $c->branches = array_values(array_unique($arr));
                    $c->save();
                }
            }
        });
    }

    public function down(): void
    {
        // 일방향 정리 — 되돌리지 않음
    }
};
