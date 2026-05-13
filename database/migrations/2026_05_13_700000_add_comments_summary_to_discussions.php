<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->longText('comments_summary')->nullable()->after('conclusion');
            $table->timestamp('comments_summary_at')->nullable()->after('comments_summary');
            $table->unsignedInteger('comments_summary_count')->nullable()->after('comments_summary_at');
        });
    }

    public function down(): void
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->dropColumn(['comments_summary', 'comments_summary_at', 'comments_summary_count']);
        });
    }
};
