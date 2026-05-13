<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyAiSummary extends Model
{
    protected $fillable = [
        'project_id',
        'generated_by',
        'summary_type',
        'week_start_date',
        'content',
    ];

    protected $casts = [
        'week_start_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
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
