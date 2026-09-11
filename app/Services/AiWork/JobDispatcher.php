<?php

namespace App\Services\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\JobDispatched;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwJob;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * 지시를 작업 PC 로 내보낸다.
 *
 * 에이전트가 오프라인이면 예외를 던지지 않고 queued 로 남겨 둔다. 데몬이 다시
 * 올라오면 /jobs/pending 으로 스스로 집어가므로, 사용자를 실패 화면으로 보낼
 * 이유가 없다.
 */
class JobDispatcher
{
    public function __construct(private JobStateMachine $states) {}

    /**
     * @return bool 실제로 발행했는지(false = 에이전트 오프라인이라 대기)
     */
    public function dispatch(AiwJob $job, ?string $resumeSessionId = null): bool
    {
        $mapping = $this->mapping($job);

        if (! $job->agent->is_online) {
            Log::info('AI Works: 작업 PC 오프라인 — 지시를 대기 상태로 둡니다.', ['job_id' => $job->id]);

            return false;
        }

        $this->emit($job, $mapping, $resumeSessionId);

        // 이미 dispatched 면 재발행일 뿐이므로 상태는 그대로 둔다(멱등).
        if ($job->status === AiwJobStatus::Queued) {
            $this->states->transition($job, AiwJobStatus::Dispatched);
        }

        return true;
    }

    /**
     * 무응답 재전송. dispatched 에서 데몬이 start 를 보내지 않을 때 쓴다.
     * 같은 페이로드를 다시 쏘는 것뿐이라 여러 번 호출해도 안전하다.
     */
    public function redispatch(AiwJob $job): bool
    {
        if (! in_array($job->status, [AiwJobStatus::Queued, AiwJobStatus::Dispatched], true)) {
            throw new RuntimeException('재전송은 대기·전달 상태에서만 가능합니다.');
        }

        return $this->dispatch($job, $job->currentSessionId());
    }

    /** 후속 지시: 원 job 의 마지막 세션을 이어받아 실행한다. */
    public function dispatchFollowUp(AiwJob $job): bool
    {
        return $this->dispatch($job, $job->parent?->currentSessionId());
    }

    private function mapping(AiwJob $job): AiwAgentProject
    {
        $mapping = AiwAgentProject::query()
            ->where('agent_id', $job->agent_id)
            ->where('project_id', $job->project_id)
            ->first();

        if (! $mapping) {
            // 매핑이 없으면 데몬은 어느 폴더에서 실행할지 알 수 없다.
            throw new RuntimeException('이 담당자에 해당 프로젝트의 로컬 경로가 매핑되어 있지 않습니다.');
        }

        return $mapping;
    }

    private function emit(AiwJob $job, AiwAgentProject $mapping, ?string $resumeSessionId): void
    {
        try {
            event(new JobDispatched($job, $mapping->local_path, $mapping->default_branch, $resumeSessionId));
        } catch (\Throwable $e) {
            // 발행에 실패해도 job 은 queued/dispatched 로 남아 데몬이 폴링으로 집어간다.
            Log::warning('AI Works: 지시 브로드캐스트 실패 — 데몬 폴링으로 복구됩니다.', [
                'job_id' => $job->id,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
