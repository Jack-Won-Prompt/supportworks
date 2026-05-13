<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUrs extends Model
{
    protected $table = 'project_urs';

    protected $fillable = [
        'project_id', 'created_by', 'status',
        'qa_questions', 'current_q_index', 'content', 'content_en',
    ];

    protected $casts = [
        'qa_questions' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentQuestion(): ?array
    {
        $qs = $this->qa_questions ?? [];
        return $qs[$this->current_q_index] ?? null;
    }

    public function totalQuestions(): int
    {
        return count($this->qa_questions ?? []);
    }

    public function isQaDone(): bool
    {
        return $this->current_q_index >= $this->totalQuestions() && $this->totalQuestions() > 0;
    }
}
