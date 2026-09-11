<?php

namespace App\Services\AiWork;

use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobLog;
use App\Models\AiWork\AiwPublish;
use App\Events\AiWork\JobLogAppended;
use Illuminate\Support\Facades\Log;

/**
 * "배포까지 자동으로" 를 켠 작업의 뒷단계.
 *
 * 작업 완료 → 커밋·푸시 → 배포. 사람이 결과를 보고 누르는 단계를 건너뛴다.
 *
 * 지키는 것:
 *   1. 성공한 작업에서만 발동한다. 실패·취소된 결과를 운영에 올리지 않는다.
 *   2. 단계가 하나라도 실패하면 거기서 멈춘다. 푸시가 안 된 것을 배포하면
 *      서버는 옛 코드를 받아 가면서 성공한 것처럼 보인다.
 *   3. 각 단계를 활동 로그에 남긴다. 자동이라도 무슨 일이 있었는지 보여야 한다.
 *
 * 이 경로의 실패가 작업 자체의 기록을 망가뜨리지 않게, 모든 예외를 여기서 삼킨다.
 */
class AutoPipeline
{
    public function __construct(
        private PublishService $publishes,
        private DeployService $deploys,
    ) {}

    /** 작업이 완료됐을 때. 커밋·푸시를 시작한다. */
    public function afterComplete(AiwJob $job): void
    {
        if (! $job->auto_deploy) {
            return;
        }

        // 실패·취소는 여기 오지 않지만, 호출자가 바뀌어도 안전하도록 다시 확인한다.
        if ($job->status !== \App\Enums\AiWork\AiwJobStatus::Completed) {
            return;
        }

        try {
            $this->publishes->request($job, $job->creator, null, true);
            $this->note($job, '자동 진행: 커밋·푸시를 시작합니다.');
        } catch (\Throwable $e) {
            $this->note($job, '자동 진행 중단 — 커밋·푸시를 시작하지 못했습니다: '.$e->getMessage(), true);
        }
    }

    /** 커밋·푸시가 끝났을 때. 성공했으면 배포로 넘어간다. */
    public function afterPublish(AiwJob $job, AiwPublish $publish): void
    {
        if (! $job->auto_deploy || ! $publish->automatic) {
            return;
        }

        if ($publish->status !== 'succeeded') {
            // 푸시가 안 된 것을 배포하면 서버는 옛 코드를 받아 가면서 성공한 것처럼 보인다.
            $this->note($job, '자동 진행 중단 — 커밋·푸시가 실패해 배포하지 않습니다.', true);

            return;
        }

        $target = $job->autoDeployTarget;

        if (! $target || ! $target->enabled) {
            $this->note($job, '자동 진행 중단 — 배포 대상이 없거나 비활성 상태입니다.', true);

            return;
        }

        try {
            $this->deploys->request($target, $job->creator, '', $job, true);
            $this->note($job, sprintf('자동 진행: 배포를 시작합니다 (%s).', $target->name));
        } catch (\Throwable $e) {
            $this->note($job, '자동 진행 중단 — 배포를 시작하지 못했습니다: '.$e->getMessage(), true);
        }
    }

    /** 자동이라도 무슨 일이 있었는지 화면에 보여야 한다. */
    private function note(AiwJob $job, string $text, bool $isError = false): void
    {
        try {
            $log = AiwJobLog::create([
                'job_id'  => $job->id,
                'seq'     => (int) AiwJobLog::where('job_id', $job->id)->max('seq') + 1,
                'type'    => $isError ? 'error' : 'daemon',
                'content' => $text,
            ]);

            event(new JobLogAppended($log));
        } catch (\Throwable $e) {
            Log::warning('AI Works: 자동 진행 로그 기록 실패', [
                'job_id' => $job->id,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
