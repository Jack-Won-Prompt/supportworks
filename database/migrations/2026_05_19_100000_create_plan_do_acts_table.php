<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_do_acts', function (Blueprint $table) {
            $table->id();
            // 프로젝트는 선택사항 — 채팅 메시지 등 프로젝트 밖에서 등록될 수 있음
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_file_comment_id')->nullable()
                  ->constrained('file_comments')->nullOnDelete();
            $table->foreignId('source_message_id')->nullable()
                  ->constrained('messages')->nullOnDelete();
            $table->string('title', 255);
            $table->text('plan')->nullable();
            $table->text('do')->nullable();
            $table->text('act')->nullable();
            $table->string('status', 20)->default('plan');   // plan | do | act | done
            $table->text('source_excerpt')->nullable();       // 원본 의견/메시지 + 답변 스냅샷
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index('source_file_comment_id');
            $table->index('source_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_do_acts');
    }
};
