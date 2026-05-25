<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitCommit extends Model
{
    protected $fillable = [
        'source', 'branch', 'branches', 'sha', 'patch_id', 'author_name', 'author_email',
        'user_id', 'committed_at', 'subject', 'body', 'sr_ids', 'is_merge',
        'files_changed', 'files_json', 'insertions', 'deletions', 'difficulty',
    ];

    protected $casts = [
        'committed_at' => 'datetime',
        'difficulty'   => 'float',
        'files_json'   => 'array',
        'branches'     => 'array',
        'sr_ids'       => 'array',
        'is_merge'     => 'boolean',
    ];

    /** 커밋 메시지에서 [SR-xxxx] 패턴 파싱 → 정수 배열 */
    public static function parseSrIds(string $subject, string $body = ''): array
    {
        $text = $subject . "\n" . $body;
        preg_match_all('/\[SR-(\d+)\]/i', $text, $m);
        $ids = array_unique(array_map('intval', $m[1] ?? []));
        return array_values($ids);
    }

    /** 최초 발견 브랜치 = branches 배열의 첫 원소 (fallback: branch 컬럼) */
    public function firstBranch(): ?string
    {
        $arr = $this->branches;
        if (is_array($arr) && !empty($arr)) return (string) $arr[0];
        return $this->branch;
    }

    /** 최후 발견 브랜치 = branches 배열의 마지막 원소 (fallback: branch 컬럼) */
    public function lastBranch(): ?string
    {
        $arr = $this->branches;
        if (is_array($arr) && !empty($arr)) return (string) end($arr);
        return $this->branch;
    }

    /** 1.0~5.0 점수 → 한국어 라벨 */
    public static function difficultyLabel(?float $score): string
    {
        if ($score === null) return '미분석';
        return match(true) {
            $score < 1.5 => '쉬움',
            $score < 2.5 => '보통-쉬움',
            $score < 3.5 => '보통',
            $score < 4.5 => '어려움',
            default      => '매우 어려움',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
