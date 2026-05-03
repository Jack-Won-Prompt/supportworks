<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_stack_standards', function (Blueprint $table) {
            $table->id();
            $table->enum('stack', ['html', 'react', 'vue']);
            $table->enum('category', ['folder_structure', 'naming', 'component', 'state', 'api', 'styling', 'testing']);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->json('definition');              // 폴더 구조·명명 규칙 JSON 정의
            $table->json('validation_rules')->nullable(); // 자동 검증 규칙
            $table->json('examples')->nullable();     // 예시 코드/구조
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['stack', 'category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_stack_standards');
    }
};
