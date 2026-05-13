<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiSession extends Model
{
    protected $fillable = [
        'user_id', 'figma_file_id', 'project_id', 'prompt_category', 'title', 'share_token', 'is_shared',
        'agent_type', 'dev_settings', 'doc_type', 'output_filename', 'output_extension',
    ];

    protected $casts = [
        'is_shared'    => 'boolean',
        'dev_settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function figmaFile(): BelongsTo
    {
        return $this->belongsTo(FigmaFile::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Project::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'session_id')->orderBy('created_at');
    }

    /**
     * 슬라이딩 윈도우 방식으로 메시지 배열 반환.
     * - 첫 번째 사용자 메시지(프로젝트 맥락)는 항상 포함
     * - 이후 최근 메시지를 CHARACTER_BUDGET 이내로 제한
     * - 생략된 구간에는 요약 메시지 삽입
     */
    private const CHARACTER_BUDGET = 40_000; // ≈ 10,000 토큰

    public function toClaudeMessages(): array
    {
        $all = $this->messages->map(fn($m) => [
            'role'    => $m->role,
            'content' => $m->content,
        ])->values()->toArray();

        if (count($all) <= 2) {
            return $all;
        }

        // 첫 번째 메시지는 항상 포함 (초기 맥락 앵커)
        $first  = $all[0];
        $rest   = array_slice($all, 1);
        $budget = self::CHARACTER_BUDGET - mb_strlen($first['content']);

        // 최근 메시지부터 역순으로 예산 내에서 선택
        $selected = [];
        $used     = 0;
        foreach (array_reverse($rest) as $msg) {
            $len = mb_strlen($msg['content']);
            if ($used + $len > $budget) break;
            array_unshift($selected, $msg);
            $used += $len;
        }

        $skipped = count($rest) - count($selected);
        if ($skipped > 0) {
            array_unshift($selected, [
                'role'    => 'user',
                'content' => "[이전 {$skipped}개의 메시지가 토큰 절약을 위해 생략되었습니다. 위 첫 메시지와 아래 최근 대화를 기반으로 답변해주세요.]",
            ]);
            array_splice($selected, 1, 0, [[
                'role'    => 'assistant',
                'content' => '네, 이전 대화 맥락을 유지하며 최근 내용을 기반으로 답변하겠습니다.',
            ]]);
        }

        return array_merge([$first], $selected);
    }
}
