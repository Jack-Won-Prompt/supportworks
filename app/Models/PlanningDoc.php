<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class PlanningDoc extends Model
{
    use LogsActivity;
    protected $fillable = [
        'project_id', 'title', 'description', 'content', 'pending_content',
        'ai_summary', 'ai_conflicts', 'ai_suggestions',
        'version', 'status', 'created_by', 'approved_by', 'approved_at',
        'share_token',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function histories()
    {
        return $this->hasMany(PlanningDocHistory::class)->orderByDesc('created_at');
    }

    public function inputs()
    {
        return $this->hasMany(PlanningDocInput::class);
    }

    public function pendingInputs()
    {
        return $this->hasMany(PlanningDocInput::class)->where('status', 'pending');
    }

    public function planApplications()
    {
        return $this->hasMany(\App\Models\PlanApplication::class, 'plan_id')->whereNull('deleted_at')->orderByDesc('applied_at');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'          => '작성중',
            'ai_processed'   => '웍스 처리완료',
            'pending_review' => '검토 대기',
            'approved'       => '승인 완료',
            'rejected'       => '반려',
            default          => '알 수 없음',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft'          => 'gray',
            'ai_processed'   => 'blue',
            'pending_review' => 'yellow',
            'approved'       => 'green',
            'rejected'       => 'red',
            default          => 'gray',
        };
    }
}
