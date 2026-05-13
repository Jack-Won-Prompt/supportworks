<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_figma_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('figma_source_id')->constrained('ai_figma_sources')->cascadeOnDelete();

            $table->unsignedSmallInteger('snapshot_version')->default(1);

            // storage 디스크 경로 (config('ai-agent.storage.disk') 기준 상대경로)
            $table->string('raw_json_path', 500)->nullable();
            $table->string('normalized_json_path', 500)->nullable();
            $table->string('thumbnail_path', 500)->nullable();

            // 변경 감지용 sha256 (raw_json 기준)
            $table->char('checksum', 64)->nullable();

            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->index(['figma_source_id', 'snapshot_version']);
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_figma_snapshots');
    }
};
