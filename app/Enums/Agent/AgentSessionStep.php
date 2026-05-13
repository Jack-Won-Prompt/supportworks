<?php

namespace App\Enums\Agent;

/**
 * 세션의 current_step.
 *
 * 화면 좌측 사이드바의 단계 진행 표시 및 '이어서 진행' 액션 산정에 사용.
 * 순서는 ordinal()이 반환한다.
 */
enum AgentSessionStep: string
{
    case PROJECT_SELECTED            = 'project_selected';
    case OUTPUT_TYPE_SELECTED        = 'output_type_selected';
    case SOURCE_CONNECTED            = 'source_connected';
    case STRUCTURE_ANALYZED          = 'structure_analyzed';
    case IMPLEMENTATION_SCOPE_SELECTED = 'implementation_scope_selected';
    case OUTPUT_PLAN_READY           = 'output_plan_ready';
    case OUTPUT_GENERATED            = 'output_generated';
    case REVIEW_REQUIRED             = 'review_required';
    case FEEDBACK_RECEIVED           = 'feedback_received';
    case CONFLICT_REVIEW_REQUIRED    = 'conflict_review_required';
    case APPROVAL_REQUIRED           = 'approval_required';
    case CONFIRMED                   = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::PROJECT_SELECTED              => '프로젝트 선택',
            self::OUTPUT_TYPE_SELECTED          => 'Output 유형 선택',
            self::SOURCE_CONNECTED              => '디자인 소스 연결',
            self::STRUCTURE_ANALYZED            => '구조 분석 완료',
            self::IMPLEMENTATION_SCOPE_SELECTED => '구현 범위 선택',
            self::OUTPUT_PLAN_READY             => '구현 계획 준비',
            self::OUTPUT_GENERATED              => 'Output 생성',
            self::REVIEW_REQUIRED               => '검수 필요',
            self::FEEDBACK_RECEIVED             => '피드백 수신',
            self::CONFLICT_REVIEW_REQUIRED      => '충돌 검토',
            self::APPROVAL_REQUIRED             => '최종 승인',
            self::CONFIRMED                     => '확정 완료',
        };
    }

    public function ordinal(): int
    {
        return match ($this) {
            self::PROJECT_SELECTED              => 1,
            self::OUTPUT_TYPE_SELECTED          => 2,
            self::SOURCE_CONNECTED              => 3,
            self::STRUCTURE_ANALYZED            => 4,
            self::IMPLEMENTATION_SCOPE_SELECTED => 5,
            self::OUTPUT_PLAN_READY             => 6,
            self::OUTPUT_GENERATED              => 7,
            self::REVIEW_REQUIRED               => 8,
            self::FEEDBACK_RECEIVED             => 9,
            self::CONFLICT_REVIEW_REQUIRED      => 10,
            self::APPROVAL_REQUIRED             => 11,
            self::CONFIRMED                     => 12,
        };
    }
}
