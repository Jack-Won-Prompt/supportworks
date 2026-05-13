<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_confirmed_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            // 동일 output_id에 대해 확정은 1회만 가능 (재확정은 새 Output 버전을 거쳐야 함)
            $table->foreignId('output_id')->unique()->constrained('ai_outputs')->cascadeOnDelete();

            $table->foreignId('confirmed_by')->constrained('users');
            $table->timestamp('confirmed_at');

            $table->text('summary')->nullable();
            // 다른 세션 AI context로 노출할 메타 (산출 파일/컴포넌트/route 요약)
            $table->json('context_meta')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'confirmed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_confirmed_outputs');
    }
};
