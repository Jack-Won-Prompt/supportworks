<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_groups', function (Blueprint $table) {
            // WITHWORKS 사용 회사 여부 — 관리자 공지사항 등에서 대상 필터로 사용
            $table->boolean('uses_withworks')->default(false)->after('is_active');
            $table->index('uses_withworks');
        });
    }

    public function down(): void
    {
        Schema::table('company_groups', function (Blueprint $table) {
            $table->dropIndex(['uses_withworks']);
            $table->dropColumn('uses_withworks');
        });
    }
};
