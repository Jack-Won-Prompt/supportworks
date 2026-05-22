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

    /**
     * 위치 표시값 — Outlook/Teams 가 자동 입력하는
     * "Name <email@domain>" 형태이면 이름(앞부분)만 추출. 그 외는 원본 그대로.
     */
    public function getDisplayLocationAttribute(): ?string
    {
        $raw = $this->location;
        if (!$raw) return $raw;
        if (preg_match('/^(.+?)\s*<\s*[^<>\s]+@[^<>\s]+\s*>\s*$/', $raw, $m)) {
            return trim($m[1]);
        }
        return $raw;
    }

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

    public function recordings()
    {
        return $this->hasMany(MeetingRecording::class, 'meeting_minute_id')->latest('recorded_at');
    }

    public function scopeCompanyOf(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            if ($user->company_group_id) {
                $q->where('company_group_id', $user->company_group_id);
            } else {
                $q->where('author_id', $user->id)->whereNull('company_group_id');
            }
            // 참석자(계정 연결)로 등록된 회의록도 포함 — 회사가 달라도 열람 가능
            $q->orWhereHas('attendees', function (Builder $aq) use ($user) {
                $aq->where('user_id', $user->id);
            });
        });
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
