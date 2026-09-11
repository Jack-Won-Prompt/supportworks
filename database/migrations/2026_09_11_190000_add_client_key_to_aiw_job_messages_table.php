<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 메시지 중복 판별을 seq 에서 client_key 로 옮긴다.
 *
 * 지금까지 데몬과 서버가 각자 seq 를 매겼다. 지시문은 서버가 seq 0 으로 넣고
 * 데몬의 첫 답변도 seq 0 이라, insertOrIgnore 가 답변을 조용히 버렸다.
 * 대화형에서는 사용자 메시지(max+1)와 데몬 메시지(자체 카운터)가 계속 어긋난다.
 *
 * 번호는 서버가 배정하고, 재전송 판별은 데몬이 보내는 client_key 로 한다.
 * 두 출처가 같은 키를 두고 다투지 않게 하는 것이 요점이다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_job_messages', function (Blueprint $table) {
            $table->string('client_key', 64)->nullable()->after('seq');
            $table->unique(['job_id', 'client_key']);
        });
    }

    public function down(): void
    {
        Schema::table('aiw_job_messages', function (Blueprint $table) {
            $table->dropUnique(['job_id', 'client_key']);
            $table->dropColumn('client_key');
        });
    }
};
