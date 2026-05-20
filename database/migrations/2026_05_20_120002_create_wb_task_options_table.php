<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_task_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            // GNB 위치
            $table->enum('gnb_position', ['top', 'left', 'right', 'none'])->default('top');
            // 탭 구조
            $table->enum('tab_structure', ['single', 'top_tabs', 'left_tabs', 'sidebar_tabs', 'none'])->default('single');
            // 화면 전환 방식
            $table->enum('transition_type', ['page', 'modal', 'slide', 'tab_switch'])->default('page');
            // 메인 색상 (HEX or 프리셋 키)
            $table->string('main_color', 32)->default('#3b82f6');
            // 자유 메모 / 기타 옵션
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->unique('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_task_options');
    }
};
