<?php

namespace App\Services\AiWork;

use App\Models\AiWork\AiwErrorReport;
use App\Models\AiWork\AiwErrorSource;
use Illuminate\Support\Facades\DB;

/**
 * 운영 사이트가 보내온 에러를 받아 묶고, 어떻게 다룰지 갈라 둔다.
 *
 * 지문을 만들어 같은 것은 세기만 하고, 처음 보는 것에는 판정(ErrorTriage)을
 * 붙인다. 규칙 자체는 ErrorTriage 가 갖는다 — 여기는 그 결과를 첫 상태로
 * 옮겨 적을 뿐이다. 규칙을 고칠 때 수집 경로를 건드리지 않기 위해서다.
 *
 * 판정까지가 이 단계의 끝이다. 작업 지시를 만드는 일은 아직 하지 않는다.
 */
class ErrorIntake
{
    public function __construct(
        private AiwNotifier $notifier,
        private ErrorTriage $triage,
    ) {}

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

            $report = new AiwErrorReport([
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

            /*
             * 받는 즉시 판정한다. 나중에 훑어 판정하면, 그 사이 화면에는
             * 봇 스캔과 진짜 고장이 섞여 보이고 알림도 섞여 나간다.
             *
             * 판정이 곧 첫 상태다 — 무시할 것은 처음부터 덮어 두고, 사람이
             * 봐야 하는 것은 그렇게 표시한다. 둘 다 사람이 되돌릴 수 있다.
             */
            [$verdict, $reason] = $this->triage->decide($report);

            $report->verdict        = $verdict;
            $report->verdict_reason = $reason;
            $report->status         = match ($verdict) {
                ErrorTriage::IGNORE => AiwErrorReport::STATUS_IGNORED,
                ErrorTriage::HUMAN  => AiwErrorReport::STATUS_BLOCKED,
                default             => AiwErrorReport::STATUS_NEW,
            };

            $report->save();

            return [$report, true];
        });

        $source->forceFill(['last_seen_at' => now()])->save();

        /*
         * 알림은 처음 볼 때만, 그리고 고칠 값어치가 있을 때만 보낸다.
         *
         * 같은 에러가 초당 열 번 올 때 열 번 울리면 사람은 알림을 꺼 버리고,
         * 그러면 정작 중요한 것도 놓친다. 404 와 봇 스캔으로 울리는 것도 같다 —
         * 몇 번 겪으면 알림 자체를 믿지 않게 된다.
         */
        if ($isNew && $report->verdict !== ErrorTriage::IGNORE) {
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
