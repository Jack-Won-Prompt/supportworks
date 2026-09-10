<?php

namespace App\Services\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\JobCancelRequested;
use App\Models\AiWork\AiwJob;
use Illuminate\Support\Facades\Log;

/**
 * 누적 비용 상한 강제.
 *
 * 무한 루프나 폭주가 금전 피해로 이어지지 않게 하는 마지막 방어선이다.
 * 데몬도 같은 판단을 하지만, 데몬을 신뢰할 수 없는 상황(버그·변조)을 대비해
 * 서버에서도 자른다.
 */
class CostGuard
{
    public function __construct(private JobStateMachine $states) {}

    /**
     * 데몬이 보고한 누적 비용을 반영하고, 상한을 넘었으면 job 을 중단시킨다.
     *
     * @return bool 상한 초과 여부. API 응답의 cost_over_limit 가 이 값이다.
     */
    public function apply(AiwJob $job, float $cumulativeCost): bool
    {
        if ($cumulativeCost > (float) $job->cost_usd) {
            $job->forceFill(['cost_usd' => $cumulativeCost])->save();
        }

        if (! $job->isOverCostLimit() || $job->status->isTerminal()) {
            return $job->isOverCostLimit();
        }

        Log::warning('AI Works: 비용 상한 초과로 작업을 중단합니다.', [
            'job_id'   => $job->id,
            'cost_usd' => (float) $job->cost_usd,
            'limit'    => (float) $job->cost_limit_usd,
        ]);

        // 데몬이 세션을 붙들고 있으므로 취소를 먼저 알리고 상태를 닫는다.
        $this->emitCancel($job);

        $this->states->transition($job, AiwJobStatus::Failed, [
            'error_message' => sprintf(
                '비용 상한을 초과했습니다 ($%s / $%s).',
                number_format((float) $job->cost_usd, 4),
                number_format((float) $job->cost_limit_usd, 4),
            ),
        ]);

        return true;
    }

    private function emitCancel(AiwJob $job): void
    {
        try {
            event(new JobCancelRequested($job, 'cost limit exceeded'));
        } catch (\Throwable $e) {
            Log::warning('AI Works: 취소 브로드캐스트 실패', ['job_id' => $job->id, 'error' => $e->getMessage()]);
        }
    }
}
