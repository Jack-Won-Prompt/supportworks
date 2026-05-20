<?php

namespace App\Models\WorksBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LayoutPreview extends Model
{
    protected $table = 'wb_layout_previews';

    protected $fillable = [
        'task_id', 'task_options_id',
        'options_snapshot', 'preview_svg', 'preview_metadata',
    ];

    protected $casts = [
        'options_snapshot' => 'array',
        'preview_metadata' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function taskOption(): BelongsTo
    {
        return $this->belongsTo(TaskOption::class, 'task_options_id');
    }
}
