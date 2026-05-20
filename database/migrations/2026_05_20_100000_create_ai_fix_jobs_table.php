<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * ai_fix_jobs — AI 자동 수정 작업의 상태 추적 테이블.
 *
 * 상태 머신:
 *   pending           초기 상태 (SystemErrorLog 로부터 생성)
 *   analyzing         AI 가 에러 분석 중
 *   blocked           EscalationEvaluator 가 BLOCK 판정 (수동 처리 필요)
 *   awaiting_approval ESCALATE 판정, 관리자 모바일/웹 승인 대기
 *   auto_approved     AUTO 판정 — 사람 승인 없이 진행
 *   applying          worktree 생성됨, AI 가 코드 변경 중
 *   testing           테스트 실행 중
 *   tests_failed      터미널 실패 — 사람 검토 필요
 *   ready_to_deploy   테스트 통과, deploy 명령 대기
 *   deploying         deploy.sh 실행 중
 *   deployed          성공
 *   deploy_failed     deploy.sh 실패
 *   rolled_back       deploy 실패 후 자동 롤백됨
 *   rejected          관리자가 거부
 *   cancelled         관리자가 취소
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_fix_jobs', function (Blueprint $table) {
            $table->id();

            // 원본 에러 (system_error_logs.id)
            $table->foreignId('system_error_log_id')
                  ->constrained('system_error_logs')
                  ->cascadeOnDelete();

            // 상태 머신
            $table->string('status', 32)->default('pending');

            // EscalationEvaluator 결과
            $table->string('decision', 16)->nullable();           // auto | escalate | block
            $table->json('red_signals')->nullable();
            $table->json('yellow_signals')->nullable();
            $table->text('decision_reason')->nullable();
            $table->string('blocked_path')->nullable();           // BLOCK 시 매칭된 경로

            // 작업 산출물
            $table->string('branch_name')->nullable();            // ai-fix/<job-id>
            $table->string('worktree_path')->nullable();
            $table->text('proposed_fix_summary')->nullable();     // AI 가 작성한 변경 요약
            $table->json('changed_files')->nullable();            // 실제로 변경된 상대 경로 목록
            $table->json('test_result')->nullable();              // {passed, output, coverage_delta}
            $table->string('pr_url')->nullable();                 // GitHub PR (선택)

            // 배포 결과
            $table->string('deployed_commit', 40)->nullable();    // 배포된 git SHA
            $table->text('deploy_log')->nullable();               // deploy.sh 출력 일부

            // 사람 개입
            $table->foreignId('approved_by_admin_id')->nullable()
                  ->constrained('admin_users')->nullOnDelete();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('deployed_at')->nullable();
            $table->timestamp('finished_at')->nullable();         // 모든 터미널 상태 진입 시각

            // 메타
            $table->text('error_message')->nullable();            // 마지막 실패 사유
            $table->unsignedInteger('retry_count')->default(0);

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('system_error_log_id');
            $table->index('decision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_fix_jobs');
    }
};