<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PromptSession extends Model
{
    protected $primaryKey = 'session_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'user_id',
        'mode',
        'project_id',
        'schedule_id',
        'original_input',
        'current_round',
        'rounds_data',
        'status',
        'created_at',
        'expires_at',
        'completed_at',
    ];

    protected $casts = [
        'rounds_data'  => 'array',
        'created_at'   => 'datetime',
        'expires_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function newSession(
        int $userId,
        string $mode,
        string $input,
        ?int $projectId = null,
        ?int $scheduleId = null
    ): self {
        return self::create([
            'session_id'     => 'sess_' . Str::random(16),
            'user_id'        => $userId,
            'mode'           => $mode,
            'project_id'     => $projectId,
            'schedule_id'    => $scheduleId,
            'original_input' => $input,
            'current_round'  => 1,
            'rounds_data'    => [],
            'status'         => 'in_progress',
            'created_at'     => now(),
            'expires_at'     => now()->addMinutes(30),
        ]);
    }

    public function addRound(array $questions, array $answers): void
    {
        $rounds = $this->rounds_data ?? [];
        $rounds[] = [
            'round'     => $this->current_round,
            'questions' => $questions,
            'answers'   => $answers,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->rounds_data   = $rounds;
        $this->current_round += 1;
        $this->save();
    }

    public function complete(): void
    {
        $this->status       = 'completed';
        $this->completed_at = now();
        $this->save();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
