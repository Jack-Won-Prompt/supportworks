<?php

namespace App\Services\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\JobLogAppended;
use App\Events\AiWork\JobStatusChanged;
use App\Exceptions\AiWork\InvalidJobTransitionException;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobLog;
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

        // 사람의 답을 기다리기 시작한 시각. 재알림이 이걸 기준으로 돈다.
        // 대기를 벗어나면 지운다 — 다음 대기는 처음부터 다시 센다.
        if ($to->isWaitingForHuman()) {
            if ($from !== $to) {
                $attributes['waiting_since'] = now();
                $attributes['nudge_count'] = 0;
            }
        } elseif ($job->waiting_since !== null) {
            $attributes['waiting_since'] = null;
            $attributes['nudge_count'] = 0;
        }

        $job->forceFill($attributes)->save();

        if ($to->isTerminal()) {
            $this->cleanUpTerminal($job);
            $this->logTerminalReason($job, $context);
        }

        $this->emit($job);
    }

    /**
     * 데몬의 종료 보고. 이미 끝난 job 에 대한 보고는 확인으로 받고 아무 일도 하지 않는다.
     *
     * 두 가지 경우가 여기로 온다.
     *
     * 1. 같은 보고의 재시도 — 데몬은 응답이 유실되면 다시 보낸다(at-least-once).
     * 2. 서버가 먼저 끝낸 작업의 확인 — 사용자가 취소하면 서버가 cancelled 로
     *    바꾸고 데몬에 알리는데, 데몬은 세션을 멈춘 뒤 fail 로 보고한다.
     *    상태 이름은 다르지만 모순이 아니라 "그 지시대로 멈췄다"는 확인이다.
     *
     * 둘 다 409 로 거절할 이유가 없다. 종료는 최종 상태라 데몬이 달리 할 수 있는
     * 일이 없고, 거절하면 정상 흐름마다 경고만 쌓여 진짜 실패를 가린다.
     * 실제로 완료와 취소 양쪽에서 그렇게 관측됐다.
     *
     * 재보고의 payload 는 버린다 — 첫 보고가 이미 결과를 다 기록했고, 뒤늦게
     * 덮어쓰면 중간에 사람이 본 내용이 바뀔 수 있다.
     *
     * 다만 보고된 상태가 저장된 것과 다르면 서버 로그에 남긴다. 사용자가 볼
     * 것은 아니지만, 운영자가 불일치를 추적할 수는 있어야 한다.
     *
     * @param  array<string, mixed>  $context
     * @return bool 이번 호출이 실제로 상태를 바꿨는가
     *
     * @throws InvalidJobTransitionException 아직 끝나지 않은 job 에 허용되지 않는 전이일 때
     */
    public function reportTerminal(AiwJob $job, AiwJobStatus $to, array $context = []): bool
    {
        if (! $to->isTerminal()) {
            throw new \InvalidArgumentException("종료 상태가 아닙니다: {$to->value}");
        }

        if ($job->status->isTerminal()) {
            if ($job->status !== $to) {
                Log::warning('AI Works: 이미 종료된 작업에 다른 상태의 종료 보고가 왔습니다(무시).', [
                    'job_id'   => $job->id,
                    'stored'   => $job->status->value,
                    'reported' => $to->value,
                ]);
            }

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
     * 실패·취소 사유를 활동 로그에도 한 줄 남긴다.
     *
     * 화면의 활동 로그는 aiw_job_logs 만 그린다. 그런데 세션이 열리기 전에 실패하면
     * (경로 없음·매핑 없음·더러운 워킹트리 등) 로그 행이 0건이라 화면이 텅 빈다.
     * 사유는 error_message 에 있지만 그건 "결과" 카드에만 나오고 새로고침이 필요해서,
     * 사용자에게는 "아무 일도 일어나지 않은 것"처럼 보인다. 실제로 그렇게 관측됐다.
     *
     * 로그 행으로 남기면 기존 log.appended 실시간 경로를 그대로 타고 바로 보인다.
     *
     * @param  array<string, mixed>  $context
     */
    private function logTerminalReason(AiwJob $job, array $context): void
    {
        $reason = $context['error_message'] ?? null;

        if (! is_string($reason) || trim($reason) === '') {
            return;
        }

        try {
            $log = AiwJobLog::create([
                'job_id'  => $job->id,
                'seq'     => (int) AiwJobLog::where('job_id', $job->id)->max('seq') + 1,
                'type'    => 'error',
                'content' => $reason,
            ]);

            event(new JobLogAppended($log));
        } catch (\Throwable $e) {
            // 로그 한 줄 때문에 종료 처리가 실패하면 안 된다.
            Log::warning('AI Works: 종료 사유 로그 기록 실패', [
                'job_id' => $job->id,
                'error'  => $e->getMessage(),
            ]);
        }
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
