<?php

namespace App\Services\AiWork;

use App\Models\AiWork\AiwErrorReport;
use App\Models\AiWork\AiwErrorSource;
use Illuminate\Support\Facades\DB;

/**
 * 운영 사이트가 보내온 에러를 받아 묶는다.
 *
 * 하는 일은 둘뿐이다 — 지문을 만들고, 같은 지문이면 세기만 한다.
 * 무엇을 고칠지 판단하는 일은 여기 있지 않다. 받는 일과 판단하는 일을 섞으면
 * 판단 규칙을 고칠 때마다 수집이 멈출 위험을 진다.
 */
class ErrorIntake
{
    public function __construct(private AiwNotifier $notifier) {}

    /**
     * @param  array{
     *     level?: ?string, exception?: ?string, message?: ?string,
     *     file?: ?string, line?: ?int, url?: ?string,
     *     trace?: ?string, context?: ?array<string, mixed>,
     * }  $payload
     * @return array{0: AiwErrorReport, 1: bool}  [기록, 처음 보는 에러인가]
     */
    public function record(AiwErrorSource $source, array $payload): array
    {
        $fingerprint = ErrorFingerprint::make(
            $payload['exception'] ?? null,
            $payload['file'] ?? null,
            isset($payload['line']) ? (int) $payload['line'] : null,
        );

        // 유일 키(project_id, fingerprint)가 묶기를 보장한다. 두 요청이 같은
        // 순간에 들어와도 행은 하나다 — 뒤엣것은 충돌하고 갱신으로 돌아온다.
        [$report, $isNew] = DB::transaction(function () use ($source, $payload, $fingerprint) {
            $existing = AiwErrorReport::query()
                ->where('project_id', $source->project_id)
                ->where('fingerprint', $fingerprint)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'count'        => $existing->count + 1,
                    'last_seen_at' => now(),
                    // 마지막에 본 모습으로 갱신한다. 같은 고장이라도 최근 것이
                    // 사람에게 더 쓸모 있다.
                    'message'      => $this->trim($payload['message'] ?? null, 60000),
                    'url'          => $this->trim($payload['url'] ?? null, 1000),
                    'source_id'    => $source->id,
                ])->save();

                return [$existing, false];
            }

            $report = AiwErrorReport::create([
                'project_id'    => $source->project_id,
                'source_id'     => $source->id,
                'fingerprint'   => $fingerprint,
                'level'         => $this->trim($payload['level'] ?? 'error', 16) ?: 'error',
                'exception'     => $this->trim($payload['exception'] ?? null, 255),
                'message'       => $this->trim($payload['message'] ?? null, 60000),
                'file'          => $this->trim($payload['file'] ?? null, 500),
                'line'          => isset($payload['line']) ? (int) $payload['line'] : null,
                'url'           => $this->trim($payload['url'] ?? null, 1000),
                'trace'         => $this->trim($payload['trace'] ?? null, 60000),
                'context'       => $payload['context'] ?? null,
                'count'         => 1,
                'first_seen_at' => now(),
                'last_seen_at'  => now(),
                'status'        => AiwErrorReport::STATUS_NEW,
            ]);

            return [$report, true];
        });

        $source->forceFill(['last_seen_at' => now()])->save();

        // 알림은 처음 볼 때만 보낸다. 같은 에러가 초당 열 번 올 때 열 번 울리면
        // 사람은 알림을 꺼 버리고, 그러면 정작 중요한 것도 놓친다.
        if ($isNew) {
            $this->notifier->safely(fn (AiwNotifier $n) => $n->errorReported($report));
        }

        return [$report, $isNew];
    }

    /** 길이 제한은 서버가 건다. 보내는 쪽을 믿고 저장하면 컬럼이 넘친다. */
    private function trim(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
