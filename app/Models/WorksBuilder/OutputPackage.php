<?php

namespace App\Models\WorksBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutputPackage extends Model
{
    protected $table = 'wb_output_packages';

    protected $fillable = [
        'task_id', 'file_path', 'file_size_bytes', 'package_hash',
        'included_html_id', 'build_metadata', 'built_at',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'build_metadata'  => 'array',
        'built_at'        => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function html(): BelongsTo
    {
        return $this->belongsTo(GeneratedHtml::class, 'included_html_id');
    }
}
