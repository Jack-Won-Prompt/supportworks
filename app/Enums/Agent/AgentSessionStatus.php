<?php

namespace App\Enums\Agent;

enum AgentSessionStatus: string
{
    case DRAFT                  = 'draft';
    case SOURCE_REQUIRED        = 'source_required';
    case SOURCE_CONNECTED       = 'source_connected';
    case ANALYSIS_READY         = 'analysis_ready';
    case ANALYSIS_RUNNING       = 'analysis_running';
    case USER_DECISION_NEEDED   = 'user_decision_needed';
    case GENERATION_READY       = 'generation_ready';
    case GENERATING             = 'generating';
    case REVIEW_REQUIRED        = 'review_required';
    case FEEDBACK_RECEIVED      = 'feedback_received';
    case REVISION_READY         = 'revision_ready';
    case CONFLICT_DETECTED      = 'conflict_detected';
    case APPROVAL_REQUIRED      = 'approval_required';
    case CONFIRMED              = 'confirmed';
    case PAUSED                 = 'paused';
    case FAILED                 = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT                => '초안',
            self::SOURCE_REQUIRED      => '디자인 소스 필요',
            self::SOURCE_CONNECTED     => '소스 연결됨',
            self::ANALYSIS_READY       => '분석 준비',
            self::ANALYSIS_RUNNING     => '분석 중',
            self::USER_DECISION_NEEDED => '사용자 확인 필요',
            self::GENERATION_READY     => 'Output 생성 준비',
            self::GENERATING           => 'Output 생성 중',
            self::REVIEW_REQUIRED      => '검수 필요',
            self::FEEDBACK_RECEIVED    => '피드백 수신',
            self::REVISION_READY       => '수정 준비',
            self::CONFLICT_DETECTED    => '충돌 감지됨',
            self::APPROVAL_REQUIRED    => '최종 승인 필요',
            self::CONFIRMED            => '확정 완료',
            self::PAUSED               => '일시 중지',
            self::FAILED               => '실패',
        };
    }

    /** 사용자 개입(액션)이 필요한 상태인지. */
    public function needsUserAction(): bool
    {
        return in_array($this, [
            self::SOURCE_REQUIRED,
            self::USER_DECISION_NEEDED,
            self::REVIEW_REQUIRED,
            self::CONFLICT_DETECTED,
            self::APPROVAL_REQUIRED,
            self::FAILED,
        ], true);
    }

    /** 세션이 종료된 상태인지 (확정 또는 실패). */
    public function isTerminal(): bool
    {
        return in_array($this, [self::CONFIRMED, self::FAILED], true);
    }

    /** 백그라운드 작업이 진행 중인지. */
    public function isRunning(): bool
    {
        return in_array($this, [self::ANALYSIS_RUNNING, self::GENERATING], true);
    }
}
