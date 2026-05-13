<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_screens', function (Blueprint $table) {
            $table->string('figma_file_key', 100)->nullable()->after('figma_dev_mode_url');
            $table->string('figma_frame_name', 255)->nullable()->after('figma_file_key');
            $table->timestamp('figma_mapped_at')->nullable()->after('figma_frame_name');
            $table->foreignId('figma_mapped_by')->nullable()->after('figma_mapped_at')
                ->constrained('users')->nullOnDelete();

            $table->index(['figma_file_key', 'figma_frame_id'], 'idx_figma_mapping');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_screens', function (Blueprint $table) {
            $table->dropIndex('idx_figma_mapping');
            $table->dropForeign(['figma_mapped_by']);
            $table->dropColumn(['figma_file_key', 'figma_frame_name', 'figma_mapped_at', 'figma_mapped_by']);
        });
    }
};
