<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §3 — Prompt Builder 완전 제거.
 *
 * pb_* 13개 테이블 DROP + projects 테이블의 pb_* 컬럼 제거.
 * 비가역 — down()은 명시적으로 차단한다.
 */
return new class extends Migration
{
    private const PB_TABLES = [
        'pb_wizard_sessions',
        'pb_user_preferences',
        'pb_change_audit_logs',
        'pb_learning_patterns',
        'pb_external_feedbacks',
        'pb_templates',
        'pb_dependencies',
        'pb_sequences',
        'pb_builder_versions',
        'pb_builders',
        'pb_standard_candidates',
        'pb_standard_assets',
        'pb_workspaces',
    ];

    private const PB_COLUMNS_ON_PROJECTS = [
        'pb_framework',
        'pb_framework_version',
        'pb_language',
        'pb_styling',
        'pb_state_management',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (self::PB_TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('projects', function (Blueprint $table) {
            foreach (self::PB_COLUMNS_ON_PROJECTS as $col) {
                if (Schema::hasColumn('projects', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Prompt Builder(pb_*) 테이블 삭제는 비가역입니다. 백업에서 복원하세요.'
        );
    }
};
