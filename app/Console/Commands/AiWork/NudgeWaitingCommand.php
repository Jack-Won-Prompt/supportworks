<?php

namespace App\Console\Commands\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Models\AiWork\AiwJob;
use App\Services\AiWork\AiwNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 사람의 답을 오래 기다리는 작업을 다시 알린다.
 *
 * 대기에 들어갈 때 푸시를 한 번 보내는 것으로 끝이었다. 그 알림을 놓치면 작업은
 * 세션 최대 수명(기본 2시간)까지 서 있다가 조용히 중단되고, 사람에게는 "왜 안
 * 끝났지" 로만 보인다. 원격에 사람이 없다는 전제에서 이 침묵이 가장 비싸다.
 *
 * 무한정 울리지는 않는다. 정해진 횟수만 보내고 그친다 — 답하지 않기로 한 것도
 * 사람의 선택이고, 계속 울리면 다음부터는 알림 자체를 끄게 된다.
 */
class NudgeWaitingCommand extends Command
{
    protected $signature = 'aiw:nudge-waiting';

    protected $description = 'AI Works: 회신·승인을 오래 기다리는 작업을 다시 알린다';

    public function handle(AiwNotifier $notifier): int
    {
        $afterMin = max(1, (int) config('aiw.nudge_after_min', 10));
        $max      = max(0, (int) config('aiw.nudge_max', 3));

        if ($max === 0) {
            return self::SUCCESS;
        }

        $jobs = AiwJob::query()
            ->whereIn('status', [AiwJobStatus::WaitingInput, AiwJobStatus::WaitingPermission])
            ->whereNotNull('waiting_since')
            ->where('nudge_count', '<', $max)
            ->with('creator')
            ->get();

        $sent = 0;

        foreach ($jobs as $job) {
            $waited = (int) $job->waiting_since->diffInMinutes(now());

            // 같은 간격으로 나눠 보낸다. 10분 설정이면 10·20·30분에 한 번씩.
            if ($waited < $afterMin * ((int) $job->nudge_count + 1)) {
                continue;
            }

            $attempt = (int) $job->nudge_count + 1;

            try {
                $notifier->waitingTooLong($job, $waited, $attempt);
            } catch (\Throwable $e) {
                // 알림 실패로 카운트를 올리지 않으면 매 분 같은 실패를 반복한다.
                Log::warning('AI Works: 대기 재알림 실패', ['job_id' => $job->id, 'error' => $e->getMessage()]);
            }

            // saveQuietly: 이 저장이 status 를 바꾸지 않으므로 알림 훅을 깨우지 않는다.
            $job->forceFill(['nudge_count' => $attempt])->saveQuietly();
            $sent++;
        }

        if ($sent > 0) {
            $this->info("대기 중인 작업 {$sent}건을 다시 알렸습니다.");
        }

        return self::SUCCESS;
    }
}
