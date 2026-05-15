<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            // 'scheduled' = 회의 예정, 'completed' = 회의록 작성 완료(=기존 데이터 의미)
            $table->string('status', 20)->default('completed')->after('title')->index();
        });
    }

    public function down(): void
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
