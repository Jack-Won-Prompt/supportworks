<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            // 웍스 반영 시 — AI 가 분석/결정한 대상 STEP. 토글 해제 시 null.
            $table->unsignedSmallInteger('applied_step_order')->nullable()->after('reflected_by');
        });
    }

    public function down(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            $table->dropColumn('applied_step_order');
        });
    }
};
