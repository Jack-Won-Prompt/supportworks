<?php

namespace App\Events\AiWork;

use Illuminate\Broadcasting\PrivateChannel;

/**
 * 서버 → 담당자 PC: 매핑 폴더를 다시 점검하거나 정리하라는 요청.
 *
 * 폴더가 어떤 상태인지는 그 PC 만 안다. 화면은 담당자가 보고해 준 마지막 결과를
 * 보여 줄 뿐이라, 사람이 폴더를 정리해도 화면은 몇 시간 전 상태로 남는다.
 * 이 요청이 그 간극을 메운다.
 *
 * 정리(cleanup)는 버리는 것이 아니다 — 데몬이 미커밋 변경을 보관 브랜치로 옮기고
 * 작업 폴더만 깨끗하게 만든다. 화면의 파일 목록만 보고 그것이 누구의 작업인지
 * 알 수 없기 때문이다.
 */
class MappingSetupRequested extends AiwEvent
{
    /** @param 'recheck'|'cleanup' $action */
    public function __construct(
        public int $agentId,
        public int $projectId,
        public string $action,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.agent.'.$this->agentId);
    }

    public function broadcastAs(): string
    {
        return $this->action === 'cleanup'
            ? 'mapping.cleanup-requested'
            : 'mapping.recheck-requested';
    }

    public function broadcastWith(): array
    {
        return [
            'project_id' => $this->projectId,
            'action'     => $this->action,
        ];
    }
}
