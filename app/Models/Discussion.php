<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discussion extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'source_file_comment_id',
        'title', 'content', 'conclusion',
        'discussion_date', 'status',
        'comments_summary', 'comments_summary_at', 'comments_summary_count',
        'reflection_status', 'reflection_note',
        'reflected_planning_doc_id', 'reflection_decided_by', 'reflection_decided_at',
    ];

    protected $casts = [
        'discussion_date'       => 'date',
        'comments_summary_at'   => 'datetime',
        'reflection_decided_at' => 'datetime',
    ];

    public function project(): BelongsTo  { return $this->belongsTo(Project::class); }
    public function author(): BelongsTo   { return $this->belongsTo(User::class, 'user_id'); }
    public function sourceFileComment(): BelongsTo { return $this->belongsTo(FileComment::class, 'source_file_comment_id'); }
    public function reflectedPlanningDoc(): BelongsTo { return $this->belongsTo(PlanningDoc::class, 'reflected_planning_doc_id'); }
    public function reflectionDecidedBy(): BelongsTo  { return $this->belongsTo(User::class, 'reflection_decided_by'); }

    public function getReflectionStatusLabelAttribute(): string
    {
        return match($this->reflection_status) {
            'reflected' => '기획서 반영됨',
            'rejected'  => '반영하지 않음',
            default     => '미결정',
        };
    }
    public function comments(): HasMany   { return $this->hasMany(DiscussionComment::class)->orderBy('created_at'); }
    public function attachments(): HasMany{ return $this->hasMany(DiscussionAttachment::class); }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'discussion_participants')->withTimestamps();
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open'        => '진행 전',
            'in_progress' => '진행 중',
            'resolved'    => '완료',
            default       => $this->status,
        };
    }

    public function getStatusColorAttribute(): array
    {
        return match($this->status) {
            'open'        => ['bg' => '#dbeafe', 'fg' => '#1d4ed8'],
            'in_progress' => ['bg' => '#fef3c7', 'fg' => '#b45309'],
            'resolved'    => ['bg' => '#dcfce7', 'fg' => '#15803d'],
            default       => ['bg' => '#f3f4f6', 'fg' => '#6b7280'],
        };
    }
}
