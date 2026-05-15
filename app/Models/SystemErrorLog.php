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

                $user = auth()->user();
                if ($user instanceof User) {
                    $context['user_name']    = $user->name;
                    $context['user_email']   = $user->email;
                    $context['user_role']    = $user->role;
                    $context['user_company'] = optional($user->companyGroup)->name;
                }

                $admin = auth('admin')->user();
                if ($admin) {
                    $context['admin_user_id']   = $admin->id;
                    $context['admin_user_name'] = $admin->name ?? null;
                }

                $route = $request->route();
                if ($route) {
                    $context['route_name']   = $route->getName();
                    $context['route_action'] = $route->getActionName();
                }

                $context['query'] = static::sanitizeParams($request->query());
                $context['input'] = static::sanitizeParams($request->except(array_keys($request->query())));
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

    /** 민감 키 마스킹 + 길이 제한 (요청 파라미터 저장용) */
    protected static function sanitizeParams(array $params, int $maxValueLen = 500, int $maxKeys = 30): array
    {
        $sensitive = ['password', 'password_confirmation', 'current_password', '_token', 'api_key', 'secret', 'token', 'authorization'];
        $out = [];
        $i = 0;
        foreach ($params as $key => $value) {
            if ($i++ >= $maxKeys) {
                $out['...'] = '(' . (count($params) - $maxKeys) . ' more)';
                break;
            }
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $out[$key] = '***';
                continue;
            }
            if (is_array($value)) {
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
                $out[$key] = mb_strlen($encoded) > $maxValueLen ? mb_substr($encoded, 0, $maxValueLen) . '…' : $encoded;
            } elseif (is_scalar($value) || $value === null) {
                $str = (string) $value;
                $out[$key] = mb_strlen($str) > $maxValueLen ? mb_substr($str, 0, $maxValueLen) . '…' : $str;
            } else {
                $out[$key] = '(' . gettype($value) . ')';
            }
        }
        return $out;
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
