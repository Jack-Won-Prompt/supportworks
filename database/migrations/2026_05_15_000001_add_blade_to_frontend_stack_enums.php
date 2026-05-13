<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        ['table' => 'ai_agent_project_configs', 'column' => 'frontend_stack', 'nullable' => false],
        ['table' => 'ai_agent_screens',         'column' => 'stack',          'nullable' => true],
        ['table' => 'ai_agent_stack_standards', 'column' => 'stack',          'nullable' => false],
    ];

    private const NEW_VALUES = "'html','react','vue','blade'";
    private const OLD_VALUES = "'html','react','vue'";

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            foreach (self::TABLES as $cfg) {
                $nullClause = $cfg['nullable'] ? 'NULL' : 'NOT NULL';
                DB::statement(sprintf(
                    "ALTER TABLE `%s` MODIFY COLUMN `%s` ENUM(%s) %s",
                    $cfg['table'],
                    $cfg['column'],
                    self::NEW_VALUES,
                    $nullClause
                ));
            }
        }
        // SQLite 등은 ENUM이 TEXT로 저장되므로 별도 조치 불필요.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            // blade 데이터가 남아 있으면 enum 축소 시 오류가 나므로 안전을 위해 먼저 NULL 처리
            DB::table('ai_agent_screens')->where('stack', 'blade')->update(['stack' => null]);

            foreach (self::TABLES as $cfg) {
                $nullClause = $cfg['nullable'] ? 'NULL' : 'NOT NULL';
                DB::statement(sprintf(
                    "ALTER TABLE `%s` MODIFY COLUMN `%s` ENUM(%s) %s",
                    $cfg['table'],
                    $cfg['column'],
                    self::OLD_VALUES,
                    $nullClause
                ));
            }
        }
    }
};
