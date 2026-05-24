<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SubTask 다중 담당자 피벗 테이블.
 *   - 기존 sub_tasks.assignee_id 데이터는 피벗으로 백필
 *   - assignee_id 컬럼은 '대표(첫) 담당자'로 유지 (다른 코드 호환)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sub_task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_task_id')->constrained('sub_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sub_task_id', 'user_id']);
            $table->index('user_id');
        });

        // 기존 단일 담당자를 피벗으로 백필
        $now = now();
        DB::table('sub_tasks')
            ->whereNotNull('assignee_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($now) {
                $insert = [];
                foreach ($rows as $r) {
                    $insert[] = [
                        'sub_task_id' => $r->id,
                        'user_id'     => $r->assignee_id,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
                if (!empty($insert)) {
                    DB::table('sub_task_assignees')->insertOrIgnore($insert);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_task_assignees');
    }
};
