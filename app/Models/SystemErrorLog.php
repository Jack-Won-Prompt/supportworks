<?php

namespace App\Models;

use App\Services\FcmService;
use App\Services\SmsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SystemErrorLog extends Model
{
    protected $fillable = [
        'level', 'source', 'origin', 'exception', 'message', 'file', 'line',
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

    public function scopeSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeOrigin(Builder $query, string $origin): Builder
    {
        return $query->where('origin', $origin);
    }

    /**
     * 외부 시스템(withworks 등)에서 HMAC 인증 후 들어온 페이로드를 기록.
     * source/origin 컬럼과 context 페이로드를 보존하고, SR 담당자에게 알림 발송.
     */
    public static function recordExternal(array $payload): self
    {
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];

        $record = self::create([
            'level'     => (string) ($payload['level']     ?? 'error'),
            'source'    => (string) ($payload['source']    ?? 'external'),
            'origin'    => (string) ($payload['origin']    ?? 'server'),
            'exception' => (string) ($payload['exception'] ?? 'External'),
            'message'   => mb_substr((string) ($payload['message'] ?? ''), 0, 65000),
            'file'      => (string) ($payload['file']      ?? ''),
            'line'      => (int)    ($payload['line']      ?? 0),
            'trace'     => mb_substr((string) ($payload['trace'] ?? ''), 0, 65000),
            'context'   => $context,
        ]);

        // 외부 에러는 SR 담당자에게만 알림 발송 (FCM + Email + SMS, 5분 쿨다운).
        static::notifySrAgents($record);

        return $record;
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

    /**
     * 브라우저(또는 모바일) 클라이언트에서 발생한 JS/Dart 에러를 기록.
     * Throwable 인스턴스가 없으니 record() 대신 이 메서드 사용.
     *
     * notifyAdmins / maybeTriggerAiFix 동일하게 호출 — level=error 이면 FCM 발송.
     * (자동 AI Fix 트리거는 config('ai-fix.auto_trigger')=false default 라서 침묵)
     *
     * @param  array{exception?:string, message?:string, file?:string, line?:int, trace?:string, context?:array}  $data
     */
    public static function recordClient(array $data, string $level = 'error'): void
    {
        try {
            $log = static::create([
                'level'     => $level,
                'exception' => mb_substr((string) ($data['exception'] ?? 'ClientError'), 0, 255),
                'message'   => mb_substr((string) ($data['message']   ?? ''), 0, 65535),
                'file'      => mb_substr((string) ($data['file']      ?? ''), 0, 255),
                'line'      => isset($data['line']) ? (int) $data['line'] : null,
                'trace'     => mb_substr((string) ($data['trace']     ?? ''), 0, 65535),
                'context'   => is_array($data['context'] ?? null) ? $data['context'] : [],
            ]);

            static::notifyAdmins($log);
            static::maybeTriggerAiFix($log);
        } catch (\Throwable) {
            // 에러 기록 중 에러는 조용히 무시 (무한 루프 방지)
        }
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

            // 메타 루프 가드: AI Fix 시스템 자체에서 발생한 에러는 분석 대상에서 제외.
            // (AiFix 가 자기 결함을 자기가 또 fix 시도하는 무한 루프 방지 — E2E 검증 중
            // 발견된 결함, 2026-05-21)
            if (static::isAiFixInternalError($log)) return;

            \App\Jobs\AnalyzeSystemErrorJob::dispatch($log->id);
        } catch (\Throwable) {
            // 트리거 실패는 무시 — 에러 기록 자체는 이미 성공했고
            // 운영자가 ai-fix:analyze 로 수동 트리거 가능
        }
    }

    /**
     * 에러가 AI Fix 시스템 내부에서 발생했는지 판정. 메타 루프 방지용.
     */
    protected static function isAiFixInternalError(self $log): bool
    {
        $file = (string) ($log->file ?? '');
        $msg  = (string) ($log->message ?? '');
        return str_contains($file, '/app/Services/AiFix/')
            || str_contains($file, '/app/Jobs/AnalyzeSystemErrorJob')
            || str_contains($file, '/app/Jobs/ApplyAiFixJob')
            || str_contains($file, '/app/Jobs/DeployAiFixJob')
            || str_contains($file, '/app/Http/Controllers/Admin/AdminAiFixJobController')
            || str_contains($file, '/app/Http/Controllers/Api/Mobile/AiFixJobController')
            || str_contains($file, '/app/Models/AiFixJob.php')
            // ai_fix_jobs 테이블에 대한 DB 결함 (FK violation 등) 도 메타 루프 대상
            || str_contains($msg, '`ai_fix_jobs`')
            || str_contains($msg, 'ai_fix_jobs.');
    }

    /**
     * 에러 수준(error/critical/alert/emergency) 발생 시 관리자(role=admin)에게
     * FCM + 이메일 동시 발송. 같은 (exception+file+line) 조합은 5분 쿨다운.
     * 각 채널은 독립 try-catch — 한 채널 실패가 다른 채널 막지 않음.
     */
    protected static function notifyAdmins(self $log): void
    {
        try {
            $critical = ['error', 'critical', 'alert', 'emergency'];
            if (!in_array($log->level, $critical, true)) return;

            $cacheKey = 'sys_err_notify:' . md5(($log->exception ?? '') . '|' . ($log->file ?? '') . '|' . ($log->line ?? ''));
            if (Cache::has($cacheKey)) return;
            Cache::put($cacheKey, 1, 300);

            $admins = User::where('role', 'admin')->get(['id', 'name', 'email']);
            if ($admins->isEmpty()) return;

            $title = '시스템 에러 (' . $log->level . ')';
            $bodyShort = $log->exception
                ? $log->exception . ': ' . mb_substr($log->message ?? '', 0, 100)
                : mb_substr($log->message ?? '', 0, 140);

            // 1) FCM (batch)
            try {
                FcmService::notifyUsers($admins->pluck('id')->all(), $title, $bodyShort, [
                    'type'     => 'system_error',
                    'error_id' => (string) $log->id,
                    'level'    => (string) $log->level,
                ]);
            } catch (\Throwable $e) {
                Log::warning('[SystemErrorLog] FCM 발송 실패: ' . $e->getMessage());
            }

            // 2) Email (admin 별 개별 발송, 한 명 실패해도 나머지 계속)
            $emailBody = static::buildAdminEmailBody($log);
            foreach ($admins as $admin) {
                if (empty($admin->email)) continue;
                try {
                    Mail::raw($emailBody, function ($m) use ($admin, $title) {
                        $m->to($admin->email, $admin->name ?? null)
                          ->subject('[SupportWorks] ' . $title);
                    });
                } catch (\Throwable $e) {
                    Log::warning("[SystemErrorLog] email to {$admin->email} 실패: " . $e->getMessage());
                }
            }
        } catch (\Throwable) {
            // 채널 외부 실패는 무시 — 에러 로그 자체 기록은 성공해야 함
        }
    }

    /** 관리자 이메일 본문 (plain text). APP_URL 있으면 admin 상세 링크 포함. */
    protected static function buildAdminEmailBody(self $log): string
    {
        $appUrl    = rtrim((string) config('app.url'), '/');
        $detailUrl = $appUrl !== '' ? "$appUrl/admin/system-errors/{$log->id}" : '';

        return implode("\n", array_filter([
            '시스템 에러가 발생했습니다.',
            '',
            'Level     : ' . $log->level,
            'Exception : ' . ($log->exception ?? '-'),
            'Message   : ' . mb_substr($log->message ?? '', 0, 500),
            'File      : ' . ($log->file ?? '-') . ($log->line ? ':' . $log->line : ''),
            'Occurred  : ' . optional($log->created_at)->toIso8601String(),
            $detailUrl !== '' ? '' : null,
            $detailUrl !== '' ? "Detail    : $detailUrl" : null,
        ], fn ($x) => $x !== null));
    }

    /**
     * 외부 시스템(withworks 등) 에서 들어온 에러를 SR 담당자(is_sr_agent=1)에게 통지.
     * 채널: FCM + Email + SMS. error/critical/alert/emergency 만 발송, 5분 쿨다운.
     *
     * 정책 결정 근거 (2026-05-28):
     *  - admin 은 기존 notifyAdmins() 흐름에서 별도 처리 — 본 메서드는 SR 전용.
     *  - 쿨다운 키는 source+exception+file+line — 같은 시스템·동일 위치 도배 차단.
     *  - SMS 는 phone 있는 SR 만 — SmsService 가 normalize 후 미존재 처리.
     */
    protected static function notifySrAgents(self $log): void
    {
        try {
            // SR 사용자 영역 = withworks 만 관리하므로 알림도 source=withworks 한정.
            // 향후 다른 source 도 SR 통지가 필요해지면 이 가드를 풀거나 화이트리스트화.
            if ($log->source !== 'withworks') return;

            $critical = ['error', 'critical', 'alert', 'emergency'];
            if (!in_array($log->level, $critical, true)) return;

            // 쿨다운 키 — admin 알림과 별도 네임스페이스
            $cacheKey = 'sys_err_notify_sr:' . md5(
                ($log->source    ?? '') . '|' .
                ($log->exception ?? '') . '|' .
                ($log->file      ?? '') . '|' .
                ($log->line      ?? '')
            );
            if (Cache::has($cacheKey)) return;
            Cache::put($cacheKey, 1, 300);

            $srAgents = User::where('is_sr_agent', 1)->get(['id', 'name', 'email', 'phone']);
            if ($srAgents->isEmpty()) return;

            $sourceTag = $log->source ?: 'external';
            $title     = '[' . strtoupper($log->level) . '/' . $sourceTag . '] 외부 시스템 에러';
            $bodyShort = $log->exception
                ? class_basename($log->exception) . ': ' . mb_substr($log->message ?? '', 0, 100)
                : mb_substr($log->message ?? '', 0, 140);

            // 1) FCM (batch)
            try {
                FcmService::notifyUsers($srAgents->pluck('id')->all(), $title, $bodyShort, [
                    'type'     => 'system_error_external',
                    'error_id' => (string) $log->id,
                    'source'   => (string) $sourceTag,
                    'level'    => (string) $log->level,
                ]);
            } catch (\Throwable $e) {
                Log::warning('[SystemErrorLog] FCM(SR) 발송 실패: ' . $e->getMessage());
            }

            // 2) Email — SR 별 개별 발송
            $emailBody = static::buildSrEmailBody($log);
            foreach ($srAgents as $sr) {
                if (empty($sr->email)) continue;
                try {
                    Mail::raw($emailBody, function ($m) use ($sr, $title) {
                        $m->to($sr->email, $sr->name ?? null)
                          ->subject('[SupportWorks] ' . $title);
                    });
                } catch (\Throwable $e) {
                    Log::warning("[SystemErrorLog] email(SR) to {$sr->email} 실패: " . $e->getMessage());
                }
            }

            // 3) SMS — phone 있는 SR 만 (SmsService 가 정규화/미존재 처리)
            try {
                $smsBody = static::buildSrSmsBody($log);
                // alsoFcm=false : 위에서 이미 FCM 발송했으므로 SmsService 의 fallback FCM 끔.
                SmsService::sendMany($srAgents, $smsBody, false);
            } catch (\Throwable $e) {
                Log::warning('[SystemErrorLog] SMS(SR) 발송 실패: ' . $e->getMessage());
            }
        } catch (\Throwable) {
            // 외부 채널 실패는 무시 — 에러 로그 자체 기록은 성공해야 함
        }
    }

    /** SR 이메일 본문 (plain text). user 영역 상세 링크 포함. */
    protected static function buildSrEmailBody(self $log): string
    {
        $appUrl    = rtrim((string) config('app.url'), '/');
        $detailUrl = $appUrl !== '' ? "$appUrl/user/system-errors/{$log->id}" : '';

        return implode("\n", array_filter([
            '외부 시스템에서 발생한 에러입니다.',
            '',
            'Source    : ' . ($log->source ?? '-'),
            'Origin    : ' . ($log->origin ?? '-'),
            'Level     : ' . $log->level,
            'Exception : ' . ($log->exception ?? '-'),
            'Message   : ' . mb_substr($log->message ?? '', 0, 500),
            'File      : ' . ($log->file ?? '-') . ($log->line ? ':' . $log->line : ''),
            'Occurred  : ' . optional($log->created_at)->toIso8601String(),
            $detailUrl !== '' ? '' : null,
            $detailUrl !== '' ? "Detail    : $detailUrl" : null,
        ], fn ($x) => $x !== null));
    }

    /** SR SMS 본문 — 90byte 단문 권장. 길면 자동 LMS 전환되긴 함. */
    protected static function buildSrSmsBody(self $log): string
    {
        $src   = $log->source ?: 'ext';
        $level = strtoupper($log->level);
        $msg   = mb_substr($log->message ?? '', 0, 80);
        return "[SW에러:{$level}/{$src}] {$msg}";
    }
}
