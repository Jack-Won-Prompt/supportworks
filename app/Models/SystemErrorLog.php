<?php

namespace App\Models;

use App\Services\FcmService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

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

            $log = static::create([
                'level'     => $level,
                'exception' => get_class($e),
                'message'   => mb_substr($e->getMessage(), 0, 65535),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => mb_substr($e->getTraceAsString(), 0, 65535),
                'context'   => $context,
            ]);

            static::notifyAdmins($log);
            static::maybeTriggerAiFix($log);
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
            $log = static::create([
                'level'   => $level,
                'message' => mb_substr($message, 0, 65535),
                'context' => $context,
            ]);

            static::notifyAdmins($log);
            static::maybeTriggerAiFix($log);
        } catch (\Throwable) {}
    }

    /**
     * config('ai-fix.auto_trigger') 가 true 이고 critical 레벨일 때
     * AnalyzeSystemErrorJob 을 dispatch 해서 AI 파이프라인 시작.
     * 어떤 실패도 record()/log() 의 정상 흐름을 막지 않도록 자체 try/catch.
     */
    protected static function maybeTriggerAiFix(self $log): void
    {
        try {
            if (!config('ai-fix.auto_trigger', false)) return;

            $critical = ['error', 'critical', 'alert', 'emergency'];
            if (!in_array($log->level, $critical, true)) return;

            \App\Jobs\AnalyzeSystemErrorJob::dispatch($log->id);
        } catch (\Throwable) {
            // 트리거 실패는 무시 — 에러 기록 자체는 이미 성공했고
            // 운영자가 ai-fix:analyze 로 수동 트리거 가능
        }
    }

    /**
     * 에러 수준(error/critical/alert/emergency) 발생 시 관리자 사용자(role=admin)에게 FCM 발송.
     * 같은 (exception+file+line) 조합은 5분 쿨다운으로 스팸 방지.
     */
    protected static function notifyAdmins(self $log): void
    {
        try {
            $critical = ['error', 'critical', 'alert', 'emergency'];
            if (!in_array($log->level, $critical, true)) return;

            $cacheKey = 'sys_err_notify:' . md5(($log->exception ?? '') . '|' . ($log->file ?? '') . '|' . ($log->line ?? ''));
            if (Cache::has($cacheKey)) return;
            Cache::put($cacheKey, 1, 300);

            $adminIds = User::where('role', 'admin')->pluck('id')->all();
            if (empty($adminIds)) return;

            $title = '시스템 에러 (' . $log->level . ')';
            $bodyShort = $log->exception
                ? $log->exception . ': ' . mb_substr($log->message ?? '', 0, 100)
                : mb_substr($log->message ?? '', 0, 140);

            FcmService::notifyUsers($adminIds, $title, $bodyShort, [
                'type'     => 'system_error',
                'error_id' => (string) $log->id,
                'level'    => (string) $log->level,
            ]);
        } catch (\Throwable) {
            // 알림 실패는 무시 — 에러 로그 자체 기록은 성공해야 함
        }
    }
}
