<?php

namespace App\Models\Agent;

use App\Enums\Agent\AgentFeedbackType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFeedback extends Model
{
    protected $table = 'ai_feedbacks';

    public const STATUS_OPEN      = 'open';
    public const STATUS_ANALYZED  = 'analyzed';
    public const STATUS_APPLIED   = 'applied';
    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = [
        'output_id',
        'user_id',
        'feedback_type',
        'message',
        'screenshot_path',
        'status',
        'analysis_meta',
    ];

    protected $casts = [
        'feedback_type' => AgentFeedbackType::class,
        'analysis_meta' => 'array',
    ];

    public function output(): BelongsTo
    {
        return $this->belongsTo(AiOutput::class, 'output_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
