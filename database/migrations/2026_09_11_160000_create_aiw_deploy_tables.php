<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 배포 대상과 실행 기록.
 *
 * 웹 애플리케이션이 서버 셸 명령을 실행한다는 뜻이므로, 잘못 만들면 그 자체가
 * 취약점이다. 그래서 명령은 요청에서 오지 않고 **관리자가 미리 등록한 행에서만**
 * 온다. 화이트리스트 밖의 명령은 실행할 방법이 없다.
 *
 * deploy.sh 는 대개 migrate 를 포함해 DB 스키마까지 바꾼다. 되돌리기 비용이
 * 크므로 누가 언제 무엇을 했고 무엇이 출력됐는지 전부 남긴다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aiw_deploy_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name', 100);
            // 실행 위치와 명령. 둘 다 관리자만 등록·수정할 수 있다.
            $table->string('working_dir', 500);
            $table->string('command', 500);
            $table->unsignedSmallInteger('timeout_sec')->default(900);
            $table->boolean('enabled')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('project_id');
        });

        Schema::create('aiw_deploys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_id')->constrained('aiw_deploy_targets')->cascadeOnDelete();
            // 어느 작업 때문에 배포했는지. 화면에서 눌렀으면 채워지고, 없어도 된다.
            $table->foreignId('job_id')->nullable()->constrained('aiw_jobs')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();

            // queued → running → succeeded | failed
            $table->string('status', 20)->default('queued');
            $table->integer('exit_code')->nullable();
            $table->longText('output')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->index(['target_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aiw_deploys');
        Schema::dropIfExists('aiw_deploy_targets');
    }
};
