<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanningDocInput extends Model
{
    protected $fillable = [
        'planning_doc_id', 'input_type', 'content',
        'file_path', 'file_name', 'status', 'created_by', 'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function doc()
    {
        return $this->belongsTo(PlanningDoc::class, 'planning_doc_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getInputTypeLabelAttribute(): string
    {
        return match($this->input_type) {
            'text'        => '텍스트',
            'memo'        => '메모',
            'requirement' => '요구사항',
            'file'        => '파일',
            default       => $this->input_type,
        };
    }
}
