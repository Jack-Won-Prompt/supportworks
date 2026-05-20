<?php

namespace App\Models\WorksBuilder;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewSession extends Model
{
    protected $table = 'wb_review_sessions';

    protected $fillable = [
        'task_id', 'review_round', 'generated_html_id',
        'started_at', 'ended_at', 'decision',
        'start_hash', 'end_hash', 'integrity_passed',
        'reviewer_id',
    ];

    protected $casts = [
        'started_at'       => 'datetime',
        'ended_at'         => 'datetime',
        'review_round'     => 'integer',
        'integrity_passed' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function html(): BelongsTo
    {
        return $this->belongsTo(GeneratedHtml::class, 'generated_html_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(ReviewHighlight::class, 'review_session_id');
    }

    public function ngInputs(): HasMany
    {
        return $this->hasMany(NgInput::class, 'review_session_id');
    }
}
