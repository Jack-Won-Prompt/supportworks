<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisSessionFile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'analysis_session_id', 'original_filename', 'stored_path',
        'mime_type', 'size', 'extracted_text',
        'extraction_status', 'extraction_error', 'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'size'        => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AnalysisSession::class, 'analysis_session_id');
    }

    public function getSizeHumanAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
