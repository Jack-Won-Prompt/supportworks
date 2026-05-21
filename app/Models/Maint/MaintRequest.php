<?php

namespace App\Models\Maint;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintRequest extends Model
{
    protected $fillable = [
        'excel_no', 'source_sheet', 'menu_id', 'company_group_id',
        'request_date', 'priority', 'category',
        'summary', 'content', 'status',
        'progress_raw', 'colo_check_raw',
        'colo_user_id', 'assignee_id', 'assignee_raw',
        'eta', 'grid_refresh', 'completed_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'eta'          => 'date',
        'completed_at' => 'datetime',
        'excel_no'     => 'integer',
    ];

    public const PRIORITIES = ['normal', 'urgent', 'critical', 'recheck'];

    public const STATUSES = [
        'draft', 'requested', 'planned', 'in_progress', 'pending_check',
        'discussion_needed', 'on_hold', 'awaiting_file', 'replied',
        'review_requested', 'review_again', 'completed',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(MaintMenu::class, 'menu_id');
    }

    public function coloUser(): BelongsTo
    {
        return $this->belongsTo(MaintUser::class, 'colo_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(MaintUser::class, 'assignee_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(MaintRequestNote::class, 'request_id');
    }

    public function coloNotes(): HasMany
    {
        return $this->notes()->where('note_type', 'colo');
    }

    public function linkNotes(): HasMany
    {
        return $this->notes()->where('note_type', 'link');
    }
}
