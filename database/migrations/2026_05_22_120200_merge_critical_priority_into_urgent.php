<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SR 우선순위 '초긴급(critical)' → '긴급(urgent)' 으로 통합.
 * PRIORITIES = ['normal','urgent','critical','recheck'] → ['normal','urgent','recheck']
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('maint_requests')->where('priority', 'critical')->update(['priority' => 'urgent']);

        // ENUM 에서 'critical' 제거 (3종으로 축소)
        DB::statement("ALTER TABLE maint_requests MODIFY COLUMN priority ENUM('normal','urgent','recheck') NOT NULL DEFAULT 'normal'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE maint_requests MODIFY COLUMN priority ENUM('normal','urgent','critical','recheck') NOT NULL DEFAULT 'normal'");
        // 통합된 critical 은 복원 불가
    }
};
