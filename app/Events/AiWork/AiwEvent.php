<?php

namespace App\Events\AiWork;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * AI Works 브로드캐스트 이벤트 공통 베이스.
 *
 * - 커넥션을 reverb 로 고정한다. 기본 커넥션(pusher)은 기존 채팅·협업 전용이며
 *   AI Works 가 그쪽 가용성에 영향을 주지 않게 분리한다.
 * - 전부 ShouldBroadcastNow 다. 큐 드라이버가 database 이고 Horizon 이 없어
 *   큐를 경유하면 워커 지연이 그대로 실시간성 저하로 나타난다.
 */
abstract class AiwEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @return list<string> */
    public function broadcastConnections(): array
    {
        return ['reverb'];
    }
}
