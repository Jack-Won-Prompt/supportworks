<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meeting_minute_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('file_path');               // storage path
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);

            // Workflow status: uploaded -> transcribing -> transcribed -> summarizing -> completed | failed
            $table->enum('status', ['uploaded', 'transcribing', 'transcribed', 'summarizing', 'completed', 'failed'])
                  ->default('uploaded');
            $table->text('transcription')->nullable();      // Whisper output (raw)
            $table->json('transcription_segments')->nullable(); // 시간별 세그먼트 (옵션)
            $table->longText('summary')->nullable();        // AI 생성 회의 요약/회의록
            $table->text('error_message')->nullable();

            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_recordings');
    }
};