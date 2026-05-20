<?php

namespace App\Models\WorksBuilder;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultConfirmation extends Model
{
    protected $table = 'wb_result_confirmations';

    protected $fillable = [
        'task_id', 'generated_html_id',
        'decision', 'note',
        'confirmed_by', 'confirmed_at',
    ];

    protected $casts = ['confirmed_at' => 'datetime'];

    public function task(): BelongsTo            { return $this->belongsTo(Task::class, 'task_id'); }
    public function html(): BelongsTo            { return $this->belongsTo(GeneratedHtml::class, 'generated_html_id'); }
    public function confirmer(): BelongsTo       { return $this->belongsTo(User::class, 'confirmed_by'); }
}
