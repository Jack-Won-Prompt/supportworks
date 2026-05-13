<?php

namespace App\Models\PromptBuilder;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalFeedback extends Model
{
    protected $table = 'pb_external_feedbacks';

    protected $fillable = [
        'builder_id', 'builder_version', 'uploaded_by',
        'upload_method', 'archive_path', 'uploaded_files',
        'user_rating', 'user_memo', 'analysis_result',
        'applied_improvements', 'status',
    ];

    protected $casts = [
        'uploaded_files' => 'array',
        'analysis_result' => 'array',
        'applied_improvements' => 'array',
    ];

    public function builder(): BelongsTo
    {
        return $this->belongsTo(Builder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
