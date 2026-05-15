<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            // 산출물 등 외부 작업으로 새 버전이 등록될 때, 그 시점에 활성이던 코멘트를
            // 이전 버전에 "동결" 시키는 마커. resolved 상태와 별개로 동작.
            $table->unsignedSmallInteger('frozen_at_version')->nullable()->after('resolved_at_version');
            $table->index(['project_file_id', 'frozen_at_version'], 'fc_pf_frozen_idx');
        });
    }

    public function down(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            $table->dropIndex('fc_pf_frozen_idx');
            $table->dropColumn('frozen_at_version');
        });
    }
};
