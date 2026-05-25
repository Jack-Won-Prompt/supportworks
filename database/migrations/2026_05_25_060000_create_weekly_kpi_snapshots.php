<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세서 §8.3 weekly_kpi_snapshots — 주간/월간 집계 결과 영구 저장.
 * 명세 원본은 iso_week 만 가정하나 운영 결정으로 weekly/full/this_month 모두 저장.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            // 기간 식별
            $table->string('period_type', 16);            // 'weekly' / 'full' / 'this_month'
            $table->string('iso_week', 16)->nullable();   // 'YYYY-WNN' (weekly 만 사용)
            $table->date('period_start');
            $table->date('period_end');

            // SR 지표 (§3.3)
            $table->unsignedInteger('sr_assigned')->default(0);
            $table->unsignedInteger('sr_completed')->default(0);
            $table->unsignedInteger('sr_reopened')->default(0);
            $table->unsignedInteger('sr_carried_over')->default(0);
            $table->decimal('weighted_throughput', 8, 2)->default(0);
            $table->decimal('avg_difficulty', 3, 2)->nullable();
            $table->decimal('completion_rate', 5, 4)->nullable();
            $table->decimal('avg_handling_days', 5, 2)->nullable();

            // Git 지표 (§4.2)
            $table->unsignedInteger('git_commits')->default(0);
            $table->unsignedBigInteger('git_added_loc')->default(0);
            $table->unsignedBigInteger('git_deleted_loc')->default(0);
            $table->unsignedInteger('git_files')->default(0);
            $table->unsignedInteger('sr_linked_commits')->default(0);

            // 종합 점수 (§5.1)
            $table->decimal('weekly_score_raw', 6, 2)->nullable();
            $table->decimal('weekly_score', 6, 2)->nullable();
            $table->decimal('penalty_raw', 6, 2)->default(0);
            $table->decimal('penalty_final', 6, 2)->default(0);

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['period_type', 'period_start'], 'idx_kpi_period');
            $table->unique(['user_id', 'period_type', 'period_start'], 'uniq_kpi_user_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_kpi_snapshots');
    }
};
