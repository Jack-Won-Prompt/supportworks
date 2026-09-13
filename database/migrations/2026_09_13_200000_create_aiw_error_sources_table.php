<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 운영 사이트가 에러를 보내올 때 쓰는 자격.
 *
 * 프로젝트 구분을 요청 본문이 아니라 **토큰으로** 한다. 본문의 project_id 를
 * 믿으면 아무나 남의 프로젝트에 에러를, 나아가 작업 지시를 밀어 넣을 수 있다.
 *
 * 토큰은 담당자(aiw_agents)와 같은 방식이다 — 원문은 발급할 때 한 번만 보여
 * 주고 SHA-256 만 저장한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aiw_error_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // 어디서 오는 것인지 사람이 알아볼 이름. 예: 'korsafety.co.kr'
            $table->string('name', 100);

            $table->string('token_hash', 64)->unique();

            // 껐다 켜는 것이 지우는 것보다 낫다. 사고가 났을 때 되돌리기 쉽다.
            $table->boolean('enabled')->default(true);

            // 살아 있는 연결인지 화면에서 보기 위해서다. 한 달째 조용하면
            // 사이트가 보내지 않고 있는 것이고, 그것도 알아야 할 사실이다.
            $table->timestamp('last_seen_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aiw_error_sources');
    }
};
