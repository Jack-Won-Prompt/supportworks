<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 명세서 §8.3 — 주간/월간 KPI 집계 영구 저장.
 *
 * period_type:
 *   'weekly'    — 특정 ISO 주차 (월~금 또는 월~일, 운영 결정)
 *   'full'      — 직전 달력 월 전체
 *   'this_month'— 이번 달 1일 ~ 산출 시점
 *
 * 동일 (user_id, period_type, period_start) 조합은 unique → updateOrCreate 갱신.
 */
class WeeklyKpiSnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'period_type', 'iso_week', 'period_start', 'period_end',
        'sr_assigned', 'sr_completed', 'sr_reopened', 'sr_carried_over',
        'weighted_throughput', 'avg_difficulty', 'completion_rate', 'avg_handling_days',
        'git_commits', 'git_added_loc', 'git_deleted_loc', 'git_files', 'sr_linked_commits',
        'weekly_score_raw', 'weekly_score', 'penalty_raw', 'penalty_final',
    ];

    protected $casts = [
        'period_start'        => 'date',
        'period_end'          => 'date',
        'weighted_throughput' => 'float',
        'avg_difficulty'      => 'float',
        'completion_rate'     => 'float',
        'avg_handling_days'   => 'float',
        'weekly_score_raw'    => 'float',
        'weekly_score'        => 'float',
        'penalty_raw'         => 'float',
        'penalty_final'       => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
