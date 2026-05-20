<?php

namespace App\Models\WorksBuilder;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NgInput extends Model
{
    protected $table = 'wb_ng_inputs';

    protected $fillable = [
        'task_id', 'review_session_id', 'review_round',
        'highlights_snapshot', 'command_box',
        'miss_description', 'attachments',
        'reported_by', 'processed_for_learning', 'processed_at',
    ];

    protected $casts = [
        'highlights_snapshot'    => 'array',
        'attachments'            => 'array',
        'processed_for_learning' => 'boolean',
        'processed_at'           => 'datetime',
        'review_round'           => 'integer',
    ];

    public function task(): BelongsTo          { return $this->belongsTo(Task::class, 'task_id'); }
    public function reviewSession(): BelongsTo { return $this->belongsTo(ReviewSession::class, 'review_session_id'); }
    public function reporter(): BelongsTo      { return $this->belongsTo(User::class, 'reported_by'); }
}
