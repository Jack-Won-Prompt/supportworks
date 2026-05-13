<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SystemErrorLog extends Model
{
    protected $fillable = [
        'level', 'exception', 'message', 'file', 'line',
        'trace', 'context', 'is_resolved', 'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'context'     => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('is_resolved', false);
    }

    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    public static function record(\Throwable $e, string $level = 'error'): void
    {
        try {
            $context = [];
            if (app()->runningInConsole()) {
                $context['source'] = 'console';
            } else {
                $request = request();
                $context = [
                    'url'     => $request->fullUrl(),
                    'method'  => $request->method(),
                    'ip'      => $request->ip(),
                    'user_id' => optional(auth()->user())->id,
                ];
            }

            static::create([
                'level'     => $level,
                'exception' => get_class($e),
                'message'   => mb_substr($e->getMessage(), 0, 65535),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => mb_substr($e->getTraceAsString(), 0, 65535),
                'context'   => $context,
            ]);
        } catch (\Throwable) {
            // DB 오류 시 무한 루프 방지 — 조용히 무시
        }
    }

    /** 예외 없이 info/warning 메시지를 DB에 기록 */
    public static function log(string $level, string $message, array $context = []): void
    {
        try {
            static::create([
                'level'   => $level,
                'message' => mb_substr($message, 0, 65535),
                'context' => $context,
            ]);
        } catch (\Throwable) {}
    }
}
