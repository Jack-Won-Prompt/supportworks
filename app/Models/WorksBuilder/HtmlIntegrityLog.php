<?php

namespace App\Models\WorksBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HtmlIntegrityLog extends Model
{
    protected $table = 'wb_html_integrity_logs';

    protected $fillable = [
        'review_session_id', 'generated_html_id',
        'start_hash', 'end_hash', 'passed',
        'failure_reason', 'checked_at',
    ];

    protected $casts = [
        'passed'     => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function reviewSession(): BelongsTo { return $this->belongsTo(ReviewSession::class, 'review_session_id'); }
    public function generatedHtml(): BelongsTo { return $this->belongsTo(GeneratedHtml::class, 'generated_html_id'); }
}
