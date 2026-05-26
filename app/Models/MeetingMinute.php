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

    /**
     * 사용자가 열람 가능한 회의록만 필터.
     *
     * 규칙:
     *   - 본인 작성(author) 회의록 — 무조건 보임
     *   - 참석자(attendees)로 등록 — 회사 달라도 보임
     *   - 같은 회사 + type=general — 회사 안에서 공유
     *   - 같은 회사 + type=project + 해당 프로젝트 멤버 — 프로젝트 단위로 제한
     *   - 회사 미소속 사용자는 본인 작성/참석 회의록만
     *
     * "회사가 같다고 회의록이 다 보이면 안 됨" — 프로젝트 회의록은 프로젝트 멤버만.
     */
    public function scopeCompanyOf(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            // 본인 작성
            $q->where('author_id', $user->id);

            // 참석자(계정 연결)
            $q->orWhereHas('attendees', function (Builder $aq) use ($user) {
                $aq->where('user_id', $user->id);
            });

            if ($user->company_group_id) {
                // 같은 회사 + 일반(type=general) — 회사 안에서 공유 (기존 동작 유지)
                $q->orWhere(function (Builder $g) use ($user) {
                    $g->where('company_group_id', $user->company_group_id)
                      ->where('type', 'general');
                });

                // 같은 회사 + 프로젝트(type=project) — 그 프로젝트 멤버만
                $q->orWhere(function (Builder $p) use ($user) {
                    $p->where('company_group_id', $user->company_group_id)
                      ->where('type', 'project')
                      ->whereIn('project_id', function ($sub) use ($user) {
                          $sub->select('project_id')
                              ->from('project_members')
                              ->where('user_id', $user->id);
                      });
                });
            }
        });
    }

    /**
     * 단건 회의록을 사용자가 열람할 수 있는지 — scopeCompanyOf 와 동일 규칙.
     * 컨트롤러 show/update 등에서 URL 직접 접근 차단용.
     */
    public function canBeViewedBy(User $user): bool
    {
        if ($user->isAdmin()) return true;

        // 본인 작성
        if ((int) $this->author_id === (int) $user->id) return true;

        // 참석자
        if ($this->attendees()->where('user_id', $user->id)->exists()) return true;

        // 같은 회사 검사
        $sameCompany = $user->company_group_id
            && $this->company_group_id
            && (int) $user->company_group_id === (int) $this->company_group_id;
        if (!$sameCompany) return false;

        // 같은 회사 + 일반 → 공유
        if ($this->type === 'general') return true;

        // 같은 회사 + 프로젝트 → 그 프로젝트 멤버만
        if ($this->type === 'project' && $this->project_id) {
            return \DB::table('project_members')
                ->where('project_id', $this->project_id)
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
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
