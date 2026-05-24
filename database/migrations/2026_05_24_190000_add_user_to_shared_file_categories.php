<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_file_categories', function (Blueprint $table) {
            // null = 회사 공유 폴더, non-null = 해당 사용자의 개인 폴더
            $table->foreignId('user_id')->nullable()->after('parent_id')
                  ->constrained('users')->cascadeOnDelete();
            $table->index(['company_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('shared_file_categories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['company_group_id', 'user_id']);
            $table->dropColumn('user_id');
        });
    }
};
