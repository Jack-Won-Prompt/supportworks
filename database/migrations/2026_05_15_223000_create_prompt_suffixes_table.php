<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prompt_suffixes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100);
            $table->text('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'sort_order']);
        });

        // 기본 문구를 모든 기존 사용자에게 시드
        $defaultBody = "작업을 진행하시기 전에, 각 단계에서 사용자의 확인이 필요한 시점에는 임의로 진행하지 마시고 반드시 사용자에게 확인을 요청한 후에 다음 단계로 진행";
        $now         = now();

        $userIds = DB::table('users')->pluck('id');
        $rows = $userIds->map(fn ($uid) => [
            'user_id'    => $uid,
            'label'      => '확인 진행 추가',
            'body'       => $defaultBody,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (!empty($rows)) {
            DB::table('prompt_suffixes')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_suffixes');
    }
};
