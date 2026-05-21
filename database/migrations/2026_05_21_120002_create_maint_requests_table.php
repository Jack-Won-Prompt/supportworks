<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maint_requests', function (Blueprint $t) {
            $t->id();
            $t->integer('excel_no')->nullable()->comment('엑셀 원본 No 컬럼 (추적용)');
            $t->string('source_sheet', 30)->default('sheet1')->comment('엑셀 시트명');
            $t->foreignId('menu_id')->constrained('maint_menus')->restrictOnDelete();
            $t->date('request_date')->nullable()->comment('엑셀 [콜로 요청 일자]');
            $t->enum('priority', ['normal', 'urgent', 'critical', 'recheck'])
                ->default('normal')
                ->comment('엑셀 [긴급/재확인]');
            $t->string('category', 100)->nullable()->comment('엑셀 [구분]');
            $t->string('summary', 500)->comment('엑셀 [내용] 첫 줄 (요약)');
            $t->text('content')->nullable()->comment('엑셀 [내용] 원문');
            $t->enum('status', [
                'draft', 'requested', 'planned', 'in_progress', 'pending_check',
                'discussion_needed', 'on_hold', 'awaiting_file', 'replied',
                'review_requested', 'review_again', 'completed',
            ])->default('draft')->comment('엑셀 [콜로 완료 확인]+[진행사항] 매핑');
            $t->string('progress_raw', 100)->nullable()->comment('엑셀 [진행사항] 원본 텍스트');
            $t->string('colo_check_raw', 50)->nullable()->comment('엑셀 [콜로 완료 확인] 원본 텍스트');
            $t->foreignId('colo_user_id')->nullable()->constrained('maint_users')->nullOnDelete()
                ->comment('엑셀 [콜로 담당자]');
            $t->foreignId('assignee_id')->nullable()->constrained('maint_users')->nullOnDelete()
                ->comment('엑셀 [담당자] (위드웍스)');
            $t->string('assignee_raw', 100)->nullable()->comment('복수 담당자/특이표기 원본 보존');
            $t->date('eta')->nullable()->comment('엑셀 [완료예상일자]');
            $t->string('grid_refresh', 100)->nullable()->comment('엑셀 [그리드 새로고침]');
            $t->timestamp('completed_at')->nullable()->comment('완료처리 시각');
            $t->timestamps();

            $t->index('menu_id', 'idx_requests_menu');
            $t->index('status', 'idx_requests_status');
            $t->index('priority', 'idx_requests_priority');
            $t->index('request_date', 'idx_requests_date');
            $t->index('colo_user_id', 'idx_requests_colo_user');
            $t->index('assignee_id', 'idx_requests_assignee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maint_requests');
    }
};
