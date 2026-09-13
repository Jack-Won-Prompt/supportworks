<?php

namespace App\Services\AiWork;

use App\Enums\AiWork\AiwFailureCode;
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
            'error_message' => $this->explain($job),
            // 코드가 있어야 화면이 "어떻게 이어가나" 를 버튼으로 보여 줄 수 있다.
            'error_code'    => AiwFailureCode::CostLimit->value,
            'error_detail'  => [
                'cost'   => (float) $job->cost_usd,
                'limit'  => (float) $job->cost_limit_usd,
                'branch' => $job->branchName(),
            ],
        ]);

        return true;
    }

    /**
     * 사람에게 보여 줄 중단 사유.
     *
     * 구독 로그인으로 도는 PC 는 화면의 금액이 실제 청구가 아니라 "API 로 썼다면
     * 얼마였을지" 의 환산값이다. 그것을 말해 주지 않으면 돈이 빠져나간 것으로
     * 읽힌다 — 실제로 "비용이 진짜 이렇게 나온다는 의미인가요" 라는 질문을 받았다.
     */
    private function explain(AiwJob $job): string
    {
        $cost  = number_format((float) $job->cost_usd, 4);
        $limit = number_format((float) $job->cost_limit_usd, 4);
        $real  = $job->agent?->usesApiKey() ?? false;

        if ($real) {
            return sprintf('비용이 상한에 닿아 자동으로 멈췄습니다 ($%s / $%s).', $cost, $limit);
        }

        return sprintf(
            '예상 사용량이 상한에 닿아 자동으로 멈췄습니다 ($%s / $%s). '
            .'구독 로그인으로 실행되어 실제 청구는 없습니다 — 폭주를 막는 장치입니다.',
            $cost,
            $limit,
        );
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
