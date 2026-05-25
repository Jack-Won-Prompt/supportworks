<?php

namespace App\Models\Maint;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SR ↔ 난이도 표 단위 매핑 (명세서 §3.2 + §8.1).
 *
 * - 1 SR 당 여러 단위 매핑 가능 (1:N)
 * - 분석 시 동일 SR 의 매핑 중 MAX(score) 적용
 * - 매핑 추가/변경/삭제 시 부모 MaintRequest.difficulty_score 캐시 자동 갱신
 */
class SrDifficultyMapping extends Model
{
    protected $fillable = [
        'sr_id', 'difficulty_unit_no', 'score', 'mapped_by', 'mapped_at',
    ];

    protected $casts = [
        'mapped_at' => 'datetime',
        'score'     => 'integer',
    ];

    protected static function booted(): void
    {
        $sync = function (self $m) {
            $maxScore = static::where('sr_id', $m->sr_id)->max('score');
            \DB::table('maint_requests')->where('id', $m->sr_id)
                ->update(['difficulty_score' => $maxScore]);
        };
        static::saved($sync);
        static::deleted($sync);
    }

    public function sr(): BelongsTo
    {
        return $this->belongsTo(MaintRequest::class, 'sr_id');
    }

    public function mapper(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'mapped_by');
    }
}
