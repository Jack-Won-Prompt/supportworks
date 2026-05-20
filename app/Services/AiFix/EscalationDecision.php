<?php

namespace App\Services\AiFix;

/**
 * EscalationEvaluator 의 결정 결과.
 */
final class EscalationDecision
{
    public const AUTO     = 'auto';      // 자동 수정·자동 머지 가능 (운영 정책에 따라)
    public const ESCALATE = 'escalate';  // 관리자 모바일/웹 승인 필요
    public const BLOCK    = 'block';     // 자동 수정 절대 불가, 사람 수동 처리

    public function __construct(
        public readonly string $verdict,    // self::AUTO / ESCALATE / BLOCK 중 하나

        /** 발동된 red 신호 키 목록 (예: ['many_files_changed', 'always_block_path']) */
        public readonly array $redSignals = [],

        /** 발동된 yellow 신호 키 목록 */
        public readonly array $yellowSignals = [],

        /** 사람이 읽을 수 있는 결정 사유 한 줄 */
        public readonly string $reason = '',

        /** 매칭된 차단 경로 (block 결정 시) */
        public readonly ?string $blockedPath = null,
    ) {}

    public function isAuto(): bool      { return $this->verdict === self::AUTO; }
    public function isEscalate(): bool  { return $this->verdict === self::ESCALATE; }
    public function isBlock(): bool     { return $this->verdict === self::BLOCK; }

    public function toArray(): array
    {
        return [
            'verdict'        => $this->verdict,
            'red_signals'    => $this->redSignals,
            'yellow_signals' => $this->yellowSignals,
            'reason'         => $this->reason,
            'blocked_path'   => $this->blockedPath,
        ];
    }
}