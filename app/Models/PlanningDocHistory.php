<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanningDocHistory extends Model
{
    protected $fillable = [
        'planning_doc_id', 'version', 'change_type',
        'before_content', 'after_content', 'summary',
        'changed_by', 'approval_status', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function doc()
    {
        return $this->belongsTo(PlanningDoc::class, 'planning_doc_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getChangeTypeLabelAttribute(): string
    {
        return match($this->change_type) {
            'user_add'     => '사용자 추가',
            'user_edit'    => '사용자 수정',
            'ai_integrate' => '웍스 통합',
            'ai_suggest'   => '웍스 제안',
            'approved'     => '승인 반영',
            'rejected'     => '반려',
            default        => $this->change_type,
        };
    }

    public function getChangeTypeColorAttribute(): string
    {
        return match($this->change_type) {
            'user_add', 'user_edit' => 'blue',
            'ai_integrate', 'ai_suggest' => 'purple',
            'approved' => 'green',
            'rejected'  => 'red',
            default     => 'gray',
        };
    }
}
