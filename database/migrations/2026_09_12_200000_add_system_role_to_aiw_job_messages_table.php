<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 데몬이 사실을 알리는 메시지 역할(system)을 추가한다.
 *
 * 작업이 중단되면 데몬이 작업 폴더를 되돌리고 그 결과를 알려야 하는데, 지금
 * 있는 역할은 user(사람) / assistant(모델) / handover(세션 교체)뿐이라 담을
 * 곳이 없었다. assistant 로 보내면 모델이 한 말처럼 보여 사실과 어긋난다.
 *
 * enum 변경이라 Doctrine 없이 raw 로 바꾼다(Laravel 의 change() 는 enum 을
 * 다루지 못한다).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE aiw_job_messages MODIFY COLUMN role ENUM('user','assistant','handover','system') NOT NULL"
        );
    }

    public function down(): void
    {
        // 되돌리기 전에 새 값을 없애야 한다. 남아 있으면 ALTER 가 실패한다.
        DB::table('aiw_job_messages')->where('role', 'system')->delete();

        DB::statement(
            "ALTER TABLE aiw_job_messages MODIFY COLUMN role ENUM('user','assistant','handover') NOT NULL"
        );
    }
};
