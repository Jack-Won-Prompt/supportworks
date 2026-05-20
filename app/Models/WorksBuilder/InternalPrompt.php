<?php

namespace App\Models\WorksBuilder;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 명세 v11 §2.1 — InternalPrompt.
 * AI에 전송된 prompt 원본 로그 (감사·재현용).
 */
class InternalPrompt extends Model
{
    protected $table = 'wb_internal_prompts';

    protected $fillable = [
        'task_id', 'purpose', 'review_round',
        'system_prompt', 'user_prompt', 'payload_metadata',
        'created_by',
    ];

    protected $casts = [
        'payload_metadata' => 'array',
        'review_round'     => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
