<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->unsignedBigInteger('source_file_comment_id')->nullable()->after('user_id');
            $table->foreign('source_file_comment_id')
                ->references('id')->on('file_comments')
                ->nullOnDelete();
            $table->index('source_file_comment_id');
        });
    }

    public function down(): void
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->dropForeign(['source_file_comment_id']);
            $table->dropIndex(['source_file_comment_id']);
            $table->dropColumn('source_file_comment_id');
        });
    }
};
