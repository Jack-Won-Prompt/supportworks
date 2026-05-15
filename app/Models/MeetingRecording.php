<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeetingRecording extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'meeting_minute_id', 'project_id',
        'title', 'file_path', 'original_name', 'mime_type',
        'file_size', 'duration_seconds', 'status',
        'transcription', 'transcription_segments', 'summary',
        'error_message', 'recorded_at',
    ];

    protected $casts = [
        'transcription_segments' => 'array',
        'recorded_at'            => 'datetime',
        'file_size'              => 'integer',
        'duration_seconds'       => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meetingMinute(): BelongsTo
    {
        return $this->belongsTo(MeetingMinute::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)       return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    public function getFormattedDurationAttribute(): string
    {
        $s = $this->duration_seconds;
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $sec = $s % 60;
        if ($h > 0) return sprintf('%02d:%02d:%02d', $h, $m, $sec);
        return sprintf('%02d:%02d', $m, $sec);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'uploaded'     => '업로드 완료',
            'transcribing' => '녹취록 생성 중',
            'transcribed'  => '녹취록 완료',
            'summarizing'  => '회의록 생성 중',
            'completed'    => '완료',
            'failed'       => '실패',
            default        => $this->status,
        };
    }
}