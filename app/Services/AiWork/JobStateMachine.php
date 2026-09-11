<?php

namespace App\Services\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\JobStatusChanged;
use App\Exceptions\AiWork\InvalidJobTransitionException;
use App\Models\AiWork\AiwJob;
use Illuminate\Support\Facades\Log;

/**
 * job 상태 변경의 유일한 통로.
 *
 * 컨트롤러·서비스가 status 를 직접 쓰지 않는다. 전이 검증, 타임스탬프 기록,
 * 종료 시 뒷정리, 이벤트 발행이 한 곳에 모여 있어야 "전이는 됐는데 이벤트가
 * 안 나갔다" 같은 어긋남이 생기지 않는다.
 */
class JobStateMachine
{
    /**
     * @param  array<string, mixed>  $context  전이와 함께 기록할 컬럼(result_summary, error_message 등)
     *
     * @throws InvalidJobTransitionException
     */
    public function transition(AiwJob $job, AiwJobStatus $to, array $context = []): void
    {
        $from = $job->status;

        if (! $from->canTransitionTo($to)) {
            throw new InvalidJobTransitionException($from, $to, $job->id);
        }

        $attributes = array_merge($context, ['status' => $to]);

        // 타임스탬프는 호출자가 잊어버리기 쉬우므로 여기서 채운다.
        if ($to === AiwJobStatus::Dispatched && $job->dispatched_at === null) {
            $attributes['dispatched_at'] = now();
        }

        if ($to === AiwJobStatus::Running && $job->started_at === null) {
            $attributes['started_at'] = now();
        }

        if ($to->isTerminal() && $job->finished_at === null) {
            $attributes['finished_at'] = now();
        }

        $job->forceFill($attributes)->save();

        if ($to->isTerminal()) {
            $this->cleanUpTerminal($job);
        }

        $this->emit($job);
    }

    /**
     * 데몬의 종료 보고. 같은 종료 상태로의 재보고는 아무 일도 하지 않고 성공으로 친다.
     *
     * 데몬은 응답이 유실되면 같은 보고를 재시도한다(at-least-once). 서버가 이미
     * 처리한 보고를 409 로 거절하면 정상적으로 끝난 작업마다 데몬 로그에 경고가
     * 쌓여, 진짜 실패를 가린다. 실제로 그렇게 관측됐다.
     *
     * 재보고의 payload 는 버린다 — 첫 보고가 이미 결과를 다 기록했고, 뒤늦게
     * 덮어쓰면 중간에 사람이 본 내용이 바뀔 수 있다.
     *
     * 다른 종료 상태와 충돌하면(예: 취소된 job 에 완료 보고) 그대로 예외를 던진다.
     * 그건 재시도가 아니라 진짜 모순이다.
     *
     * @param  array<string, mixed>  $context
     * @return bool 이번 호출이 실제로 상태를 바꿨는가
     *
     * @throws InvalidJobTransitionException
     */
    public function reportTerminal(AiwJob $job, AiwJobStatus $to, array $context = []): bool
    {
        if (! $to->isTerminal()) {
            throw new \InvalidArgumentException("종료 상태가 아닙니다: {$to->value}");
        }

        if ($job->status === $to) {
            return false;
        }

        $this->transition($job, $to, $context);

        return true;
    }

    /**
     * 종료된 job 뒷정리.
     *
     * 승인 대기가 남아 있으면 사용자가 이미 끝난 작업의 카드를 누르게 되고,
     * 데몬은 그 결정을 받을 세션이 없다.
     */
    private function cleanUpTerminal(AiwJob $job): void
    {
        $job->permissionRequests()->pending()->update([
            'status'     => 'expired',
            'decided_at' => now(),
        ]);
    }

    /**
     * 브로드캐스트 실패가 상태 전이를 되돌리게 두지 않는다.
     * 전이는 이미 커밋됐고, 화면은 새로고침·폴백으로 복구된다.
     */
    private function emit(AiwJob $job): void
    {
        try {
            event(new JobStatusChanged($job));
        } catch (\Throwable $e) {
            Log::warning('AI Works: 상태 변경 브로드캐스트 실패(전이는 완료됨)', [
                'job_id' => $job->id,
                'status' => $job->status->value,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
