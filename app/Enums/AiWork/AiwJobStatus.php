<?php

namespace App\Enums\AiWork;

/**
 * AI Works job 상태와 전이 규칙.
 *
 * 전이 허용표를 여기 한 곳에만 둔다. 실제 전이 실행은 Phase 4 의
 * JobStateMachine 이 담당하고, 이 enum 은 "무엇이 허용되는가"만 안다.
 */
enum AiwJobStatus: string
{
    case Queued            = 'queued';
    case Dispatched        = 'dispatched';
    case Running           = 'running';
    case WaitingInput      = 'waiting_input';
    case WaitingPermission = 'waiting_permission';
    case Handover          = 'handover';
    case Completed         = 'completed';
    case Failed            = 'failed';
    case Cancelled         = 'cancelled';

    /** 더 이상 변경할 수 없는 종료 상태. */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled], true);
    }

    /** 데몬이 세션을 붙들고 있는 상태(취소·종료 요청을 보낼 수 있다). */
    public function isActive(): bool
    {
        return in_array($this, [
            self::Running, self::WaitingInput, self::WaitingPermission, self::Handover,
        ], true);
    }

    /** 데몬 응답을 기다리는 중이라 idle 타이머를 멈춰야 하는 상태. */
    public function isWaiting(): bool
    {
        return in_array($this, [self::WaitingInput, self::WaitingPermission], true);
    }

    /** @return list<self> 이 상태에서 넘어갈 수 있는 상태들. */
    public function allowedTransitions(): array
    {
        $terminal = [self::Completed, self::Failed, self::Cancelled];

        return match ($this) {
            self::Queued     => [self::Dispatched, self::Cancelled, self::Failed],
            self::Dispatched => [self::Running, self::Cancelled, self::Failed],
            self::Running    => array_merge(
                [self::WaitingInput, self::WaitingPermission, self::Handover],
                $terminal,
            ),
            // waiting_* 와 handover 는 running 으로 복귀하거나 종료된다.
            self::WaitingInput,
            self::WaitingPermission,
            self::Handover   => array_merge([self::Running], $terminal),
            // 종료 상태에서는 어디로도 갈 수 없다. 이어서 하려면 후속 job 을 만든다.
            self::Completed,
            self::Failed,
            self::Cancelled  => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Queued            => '대기',
            self::Dispatched        => '전달됨',
            self::Running           => '실행 중',
            self::WaitingInput      => '답변 대기',
            self::WaitingPermission => '승인 대기',
            self::Handover          => '컨텍스트 정리 중',
            self::Completed         => '완료',
            self::Failed            => '실패',
            self::Cancelled         => '취소됨',
        };
    }
}
