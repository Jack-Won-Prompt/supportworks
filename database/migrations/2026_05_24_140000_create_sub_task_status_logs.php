<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 간트 SubTask 상태 변경 이력.
 *   - 변경할 때마다 이유(reason)를 함께 기록
 *   - 추후 감사 추적·이력 표시에 사용
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sub_task_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_task_id')->constrained('sub_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['sub_task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_task_status_logs');
    }
};
