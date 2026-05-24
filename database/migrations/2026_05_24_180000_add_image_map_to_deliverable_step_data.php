<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliverable_step_data', function (Blueprint $table) {
            // 본문 textarea 의 [[img:N]] 토큰 → URL 매핑 (필드별)
            $table->json('image_map')->nullable()->after('en_hash');
        });
    }

    public function down(): void
    {
        Schema::table('deliverable_step_data', function (Blueprint $table) {
            $table->dropColumn('image_map');
        });
    }
};
