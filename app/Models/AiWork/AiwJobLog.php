<?php

namespace App\Models\AiWork;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 실시간 진행 로그. seq 는 데몬이 부여하고 unique(job_id, seq) 가 중복을 흡수한다. */
class AiwJobLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;   // created_at 만 쓴다

    protected $fillable = ['job_id', 'seq', 'type', 'content', 'raw'];

    protected $casts = [
        'raw'        => 'array',
        'seq'        => 'integer',
        'created_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(AiwJob::class, 'job_id');
    }

    /**
     * raw 를 저장 한도(기본 4KB)에 맞춰 자른다. 전문은 데몬 로컬 jsonl 에만 남는다.
     * 잘린 경우 truncated:true 를 붙여 나중에 보는 사람이 오해하지 않게 한다.
     */
    public static function truncateRaw(?array $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        $max = (int) config('aiw.log_raw_max_bytes', 4096);
        $encoded = json_encode($raw, JSON_UNESCAPED_UNICODE);

        if ($encoded === false || strlen($encoded) <= $max) {
            return $raw;
        }

        return [
            'truncated' => true,
            'bytes'     => strlen($encoded),
            'preview'   => mb_strcut($encoded, 0, $max, 'UTF-8'),
        ];
    }
}
