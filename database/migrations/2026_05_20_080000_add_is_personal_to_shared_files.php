<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_files', function (Blueprint $table) {
            // 개인자료 여부 — true면 업로더 본인에게만 보임 (공유폴더 목록에서 숨김)
            $table->boolean('is_personal')->default(false)->after('description');
            $table->index(['company_group_id', 'is_personal'], 'shared_files_cg_personal_idx');
        });
    }

    public function down(): void
    {
        Schema::table('shared_files', function (Blueprint $table) {
            $table->dropIndex('shared_files_cg_personal_idx');
            $table->dropColumn('is_personal');
        });
    }
};
