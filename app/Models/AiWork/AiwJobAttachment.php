<?php

namespace App\Models\AiWork;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** 지시·메시지에 붙은 이미지. 파일 자체는 비공개 디스크에 있다. */
class AiwJobAttachment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'job_id', 'message_id', 'path', 'mime', 'bytes',
        'width', 'height', 'original_name', 'created_by',
    ];

    protected $casts = [
        'bytes'      => 'integer',
        'width'      => 'integer',
        'height'     => 'integer',
        'created_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(AiwJob::class, 'job_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AiwJobMessage::class, 'message_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contents(): string
    {
        return Storage::disk('local')->get($this->path);
    }

    public function exists(): bool
    {
        return Storage::disk('local')->exists($this->path);
    }

    /** 파일이 지워지면 DB 행만 남아 담당자가 404 를 받는다. 함께 지운다. */
    protected static function booted(): void
    {
        static::deleting(function (self $attachment) {
            Storage::disk('local')->delete($attachment->path);
        });
    }

    public function humanSize(): string
    {
        $kb = $this->bytes / 1024;

        return $kb >= 1024
            ? number_format($kb / 1024, 1).'MB'
            : number_format($kb).'KB';
    }
}
