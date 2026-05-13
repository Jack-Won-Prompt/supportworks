<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    protected $fillable = [
        'session_id', 'role', 'content',
        'html_output', 'css_output', 'js_output', 'code_lang', 'ai_provider',
        'doc_file_name', 'doc_file_type', 'doc_download_url', 'doc_status', 'doc_task_id',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'session_id');
    }

    public function hasCode(): bool
    {
        return filled($this->html_output) || filled($this->css_output) || filled($this->js_output);
    }
}
