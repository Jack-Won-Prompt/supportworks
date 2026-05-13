<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->string('doc_file_name')->nullable()->after('ai_provider');
            $table->string('doc_file_type', 20)->nullable()->after('doc_file_name');
            $table->text('doc_download_url')->nullable()->after('doc_file_type');
            $table->string('doc_status', 20)->nullable()->after('doc_download_url');
            $table->string('doc_task_id', 100)->nullable()->after('doc_status');
        });
    }

    public function down(): void
    {
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->dropColumn(['doc_file_name', 'doc_file_type', 'doc_download_url', 'doc_status', 'doc_task_id']);
        });
    }
};
