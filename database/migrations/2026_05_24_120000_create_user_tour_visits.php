<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 사용자별 투어(온보딩 가이드) 첫 방문 기록.
 *   - tour_key 별로 1회만 노출 (예: 'dashboard')
 *   - 기존 사용자는 모두 '이미 봤음'으로 백필 — 신규 가입자만 노출 대상
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_tour_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tour_key', 50);
            $table->timestamp('visited_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'tour_key']);
            $table->index('tour_key');
        });

        // 기존 사용자 전원에게 dashboard 투어 visited 백필 — 처음 본 사용자만 노출 대상
        $now = now();
        $userIds = DB::table('users')->pluck('id');
        $rows = $userIds->map(fn ($uid) => [
            'user_id'    => $uid,
            'tour_key'   => 'dashboard',
            'visited_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
        if (!empty($rows)) {
            // 청크 단위로 insert (대량 사용자 대응)
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('user_tour_visits')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tour_visits');
    }
};
