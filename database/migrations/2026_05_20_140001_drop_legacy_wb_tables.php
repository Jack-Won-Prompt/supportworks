<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §2 — 기존 wb_* 13개 테이블 전부 DROP 후 재생성.
 *
 * 사용자 결정으로 기존 테스트 Task 데이터는 폐기한다.
 * 비가역 — down()은 명시적으로 차단한다.
 */
return new class extends Migration
{
    private const LEGACY_WB_TABLES = [
        'wb_notification_settings',
        'wb_notifications',
        'wb_html_integrity_logs',
        'wb_ng_inputs',
        'wb_review_highlights',
        'wb_review_sessions',
        'wb_result_confirmations',
        'wb_generated_html',
        'wb_generated_prompts',
        'wb_layout_previews',
        'wb_task_options',
        'wb_checklist_items',
        'wb_tasks',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (self::LEGACY_WB_TABLES as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        throw new \RuntimeException(
            '기존 wb_* 테이블 삭제는 비가역입니다. 백업에서 복원하세요.'
        );
    }
};
