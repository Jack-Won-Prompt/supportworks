<?php

namespace App\Models\WorksBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewHighlight extends Model
{
    protected $table = 'wb_review_highlights';

    protected $fillable = [
        'review_session_id',
        'selector_path', 'tag_name', 'classes', 'text_snippet',
        'bbox_x', 'bbox_y', 'bbox_w', 'bbox_h',
    ];

    public function reviewSession(): BelongsTo
    {
        return $this->belongsTo(ReviewSession::class, 'review_session_id');
    }
}
