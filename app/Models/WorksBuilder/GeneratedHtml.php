<?php

namespace App\Models\WorksBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedHtml extends Model
{
    protected $table = 'wb_generated_html';

    protected $fillable = [
        'task_id', 'version', 'review_round',
        'generated_by', 'ai_call_log_id',
        'html_content', 'html_hash',
        'source_html_id',
    ];

    protected $casts = [
        'version'      => 'integer',
        'review_round' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function aiCallLog(): BelongsTo
    {
        return $this->belongsTo(AiCallLog::class, 'ai_call_log_id');
    }

    public function sourceHtml(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_html_id');
    }

    public static function hash(string $html): string
    {
        return hash('sha256', $html);
    }
}
