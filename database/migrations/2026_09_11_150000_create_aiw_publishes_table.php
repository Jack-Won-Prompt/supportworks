<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 커밋·푸시 요청 기록.
 *
 * 담당자는 git push 를 할 수 없다(샌드박스가 무조건 막는다). 사람이 결과를 보고
 * 버튼을 눌렀을 때만, 데몬이 서버 지시를 받아 직접 실행한다 — 담당자가 아니라
 * 데몬이 한다는 것이 요점이다.
 *
 * 원격 저장소를 바꾸는 동작이므로 누가 언제 무엇을 했는지가 반드시 남아야 한다.
 * 명령 출력도 통째로 보관한다. 실패했을 때 화면에서 이유를 볼 수 있어야 한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aiw_publishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('aiw_jobs')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();

            $table->string('source_branch', 191);
            $table->string('target_branch', 191);
            $table->string('commit_message', 500);

            // pending → running → succeeded | failed
            $table->string('status', 20)->default('pending');
            $table->text('output')->nullable();
            $table->string('commit_sha', 60)->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->index(['job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aiw_publishes');
    }
};
