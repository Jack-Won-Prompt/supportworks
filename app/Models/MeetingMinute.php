<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MeetingMinute extends Model
{
    use LogsActivity;
    protected $fillable = [
        'title', 'status', 'type', 'project_id', 'project_code', 'weekly_department',
        'meeting_date', 'location', 'author_id', 'company_group_id',
        'agenda', 'discussion', 'decisions', 'ai_summary',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function companyGroup()
    {
        return $this->belongsTo(CompanyGroup::class);
    }

    public function attendees()
    {
        return $this->hasMany(MeetingAttendee::class, 'minute_id');
    }

    public function memos()
    {
        return $this->hasMany(MeetingMemo::class, 'minute_id')->latest();
    }

    public function actionItems()
    {
        return $this->hasMany(MeetingActionItem::class, 'minute_id')->orderBy('priority')->orderBy('due_date');
    }

    public function scopeCompanyOf(Builder $query, User $user): Builder
    {
        if ($user->company_group_id) {
            return $query->where('company_group_id', $user->company_group_id);
        }
        return $query->where('author_id', $user->id)->whereNull('company_group_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'project' ? '프로젝트' : '일반';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'scheduled' ? '예정' : '완료';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }
}
