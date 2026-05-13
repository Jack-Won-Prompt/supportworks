<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 기존 레코드는 모두 기획서에 이미 삽입된 상태이므로 완료로 표시
        DB::table('plan_applications')
            ->whereNull('deleted_at')
            ->where('is_completed', false)
            ->update([
                'is_completed' => true,
                'completed_at' => DB::raw('applied_at'),
            ]);
    }

    public function down(): void
    {
        DB::table('plan_applications')
            ->whereNull('deleted_at')
            ->whereNotNull('completed_at')
            ->update([
                'is_completed' => false,
                'completed_at' => null,
            ]);
    }
};
