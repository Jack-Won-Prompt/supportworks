<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI Works — 작업 PC(데몬) 등록.
 *
 * status 컬럼을 두지 않는다. 온라인 여부는 last_seen_at 으로 계산한다
 * (AiwAgent::isOnline). 별도 판정 스케줄러가 필요 없고, 저장된 상태가
 * 실제와 어긋날 여지도 없다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aiw_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // 원문 토큰은 생성 시 1회만 표시하고 저장하지 않는다. hash('sha256', $raw) 만 보관.
            $table->string('token_hash')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable();          // 토큰 만료. 기본 90일
            $table->json('allowed_ips')->nullable();              // 허용 IP/CIDR. null = 제한 없음
            $table->string('last_used_ip', 45)->nullable();       // IPv6 고려해 45
            $table->timestamp('last_seen_at')->nullable();        // 하트비트
            $table->json('capabilities')->nullable();             // claude/os/node 버전, 셸 등
            $table->timestamps();

            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aiw_agents');
    }
};
