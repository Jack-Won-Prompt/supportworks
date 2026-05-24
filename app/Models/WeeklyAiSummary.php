<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyAiSummary extends Model
{
    protected $fillable = [
        'project_id',
        'sr_company_ids',
        'scope_key',
        'generated_by',
        'summary_type',
        'week_start_date',
        'range_start',
        'range_end',
        'content',
        'metrics',
    ];

    protected $casts = [
        'sr_company_ids'  => 'array',
        'week_start_date' => 'date',
        'range_start'     => 'date',
        'range_end'       => 'date',
        'metrics'         => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** scope 키 자동 계산: "p:{project_id}" 또는 "sr:{sorted_company_ids}" */
    public static function buildScopeKey(?int $projectId, array $srCompanyIds): string
    {
        if ($projectId) return 'p:' . $projectId;
        $ids = array_values(array_unique(array_map('intval', $srCompanyIds)));
        sort($ids);
        return 'sr:' . implode(',', $ids);
    }

    /**
     * 프로젝트 + 타입 + 주차 기준으로 저장된 서머리 조회 또는 null 반환
     */
    public static function findStored(int $projectId, string $type, ?string $weekDate): ?self
    {
        return self::where('project_id', $projectId)
            ->where('summary_type', $type)
            ->where('week_start_date', $weekDate)
            ->first();
    }
}
