<?php

namespace App\Console\Commands\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Models\AiWork\AiwJob;
use App\Services\AiWork\JobStateMachine;
use Illuminate\Console\Command;

/**
 * 좀비 job 정리.
 *
 * 데몬이 죽거나 네트워크가 끊기면 job 이 running/waiting 인 채로 남는다. 세션은
 * 데몬 프로세스와 함께 사라졌으므로 복구할 수 없고, 화면에는 영원히 "실행 중"
 * 으로 보인다. 하트비트가 끊긴 지 충분히 지난 것만 정리한다.
 */
class ReapStaleJobsCommand extends Command
{
    protected $signature = 'aiw:reap-stale-jobs';

    protected $description = 'AI Works: 무응답 담당자의 활성 job 을 실패 처리한다';

    public function handle(JobStateMachine $states): int
    {
        // 하트비트 주기(offline_after_sec)의 4배를 기다린다. 일시적 네트워크
        // 끊김으로 멀쩡한 작업을 죽이지 않기 위한 여유다.
        $threshold = now()->subSeconds((int) config('aiw.offline_after_sec', 90) * 4);

        $jobs = AiwJob::query()
            ->whereIn('status', [
                AiwJobStatus::Running,
                AiwJobStatus::WaitingInput,
                AiwJobStatus::WaitingPermission,
                AiwJobStatus::Handover,
            ])
            ->whereHas('agent', fn ($q) => $q->where(function ($q) use ($threshold) {
                $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $threshold);
            }))
            ->get();

        foreach ($jobs as $job) {
            $states->transition($job, AiwJobStatus::Failed, [
                'error_message' => '담당자가 응답하지 않아 중단되었습니다. 후속 지시로 이어서 진행하세요.',
            ]);

            $this->warn("작업 #{$job->id} 를 실패 처리했습니다 (agent unreachable).");
        }

        return self::SUCCESS;
    }
}
