<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * sub_tasks.status ENUM 에 'on_hold' 추가 (간트차트 "보류" 유형).
 */
return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE sub_tasks MODIFY COLUMN status ENUM('not_started','in_progress','completed','blocked','on_hold') NOT NULL DEFAULT 'not_started'");
    }

    public function down(): void
    {
        // on_hold 인 행을 not_started 로 되돌린 뒤 ENUM 축소
        DB::table('sub_tasks')->where('status', 'on_hold')->update(['status' => 'not_started']);
        DB::statement("ALTER TABLE sub_tasks MODIFY COLUMN status ENUM('not_started','in_progress','completed','blocked') NOT NULL DEFAULT 'not_started'");
    }
};
