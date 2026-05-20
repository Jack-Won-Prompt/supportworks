<?php

namespace App\Models\WorksBuilder;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 명세 v11 §2.3 — TaskOption (스냅샷 보존).
 *
 * 옵션 변경 시 기존 row를 is_current=false, 새 row를 version+1로 추가.
 */
class TaskOption extends Model
{
    protected $table = 'wb_task_options';

    protected $fillable = [
        'task_id', 'options_data', 'version', 'is_current',
        'changed_by', 'changed_at',
    ];

    protected $casts = [
        'options_data' => 'array',
        'version'      => 'integer',
        'is_current'   => 'boolean',
        'changed_at'   => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->options_data ?? [];
        return $data[$key] ?? $default;
    }
}
