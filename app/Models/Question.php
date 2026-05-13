<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use LogsActivity;
    protected $fillable = [
        'project_id', 'user_id', 'title', 'content', 'status', 'is_private',
        'converted_to_issue_id',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function acceptedAnswer()
    {
        return $this->hasOne(Answer::class)->where('is_accepted', true);
    }

    public function convertedIssue()
    {
        return $this->belongsTo(Issue::class, 'converted_to_issue_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'blue',
            'answered' => 'green',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open' => '답변 대기',
            'answered' => '답변 완료',
            'closed' => '종료',
            default => '알 수 없음',
        };
    }
}
