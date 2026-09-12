<?php

namespace App\Events\AiWork;

use Illuminate\Broadcasting\PrivateChannel;

/**
 * 커밋·푸시 / 배포 카드가 바뀌었다.
 *
 * 그 카드들은 서버가 그려 보낸다. 활동 로그는 실시간으로 흐르는데 카드만
 * 멈춰 있어, 로그에는 "원격에 올렸습니다" 가 찍혔는데 카드는 "진행 중…" 인
 * 상태가 자주 나왔다. 사용자는 성공을 실패로 오해한다.
 *
 * 내용을 싣지 않는다. 화면이 이 신호를 받고 그 부분만 다시 받아 간다 —
 * 카드 모양이 바뀌어도 이벤트를 고칠 필요가 없다.
 */
class JobArtifactsChanged extends AiwEvent
{
    public function __construct(public int $jobId) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.job.'.$this->jobId);
    }

    public function broadcastAs(): string
    {
        return 'job.artifacts';
    }

    public function broadcastWith(): array
    {
        return ['job_id' => $this->jobId];
    }
}
