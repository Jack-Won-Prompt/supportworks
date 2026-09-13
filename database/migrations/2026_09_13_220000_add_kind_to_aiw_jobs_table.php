<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 사람이 낸 지시인가, 운영 오류가 스스로 만든 지시인가.
 *
 * 화면에서 구분이 되어야 한다. 밤새 저절로 생긴 지시가 사람이 낸 것과 섞여
 * 보이면, 아침에 목록을 열었을 때 무엇이 왜 돌았는지 알 수 없다.
 *
 * 자동 생성의 근거가 된 오류(error_report_id)도 함께 남긴다. 지시만 있고
 * 출처가 없으면 "이건 왜 생겼지" 를 역으로 찾아야 한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_jobs', function (Blueprint $table) {
            $table->string('kind', 16)->default('manual')->after('parent_job_id');

            $table->foreignId('error_report_id')->nullable()->after('kind')
                ->constrained('aiw_error_reports')->nullOnDelete();

            $table->index(['project_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('aiw_jobs', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'kind']);
            $table->dropConstrainedForeignId('error_report_id');
            $table->dropColumn('kind');
        });
    }
};
