<?php

namespace App\Enums\Agent;

enum ArtifactType: string
{
    case AS_IS_ANALYSIS     = 'as_is_analysis';
    case TO_BE_REQUIREMENTS = 'to_be_requirements';
    case GAP_ANALYSIS       = 'gap_analysis';
    case PLANNING_DOC       = 'planning_doc';
    case IA_FLOW            = 'ia_flow';
    case SCREEN_PROMPTS     = 'screen_prompts';
    case MOCKUP             = 'mockup';
    case DESIGN_TOKENS      = 'design_tokens';
    case COMPONENT_SPEC     = 'component_spec';
    case LAYOUT_SPEC        = 'layout_spec';
    case DESIGN_REVIEW      = 'design_review';
    case DESIGN_SYSTEM_DOC  = 'design_system_doc';
    case DEV_HANDOFF        = 'dev_handoff';
    case ERD                = 'erd';
    case API_SPEC           = 'api_spec';
    case RBAC_MODEL         = 'rbac_model';
    case CODE_GEN_PROMPT    = 'code_gen_prompt';
    case FRONTEND_CODE      = 'frontend_code';
    case CODE_VALIDATION    = 'code_validation';
    case BACKEND_CODE       = 'backend_code';
    case API_INTEGRATION    = 'api_integration';
    case CODE_REVIEW        = 'code_review';
    case RELEASE_PACKAGE    = 'release_package';
    case DEPLOY_GUIDE       = 'deploy_guide';
    case USER_MANUAL        = 'user_manual';
    case MIGRATION_GUIDE    = 'migration_guide';

    public function label(): string
    {
        return match($this) {
            self::AS_IS_ANALYSIS     => 'AS-IS 분석',
            self::TO_BE_REQUIREMENTS => 'TO-BE 요구사항',
            self::GAP_ANALYSIS       => 'Gap 분석',
            self::PLANNING_DOC       => '기획서',
            self::IA_FLOW            => 'IA / 화면 흐름도',
            self::SCREEN_PROMPTS     => '화면 생성 프롬프트',
            self::MOCKUP             => '시안 목업',
            self::DESIGN_TOKENS      => '디자인 토큰',
            self::COMPONENT_SPEC     => '컴포넌트 명세서',
            self::LAYOUT_SPEC        => '표준 레이아웃',
            self::DESIGN_REVIEW      => '디자인 일관성 검수',
            self::DESIGN_SYSTEM_DOC  => '디자인 시스템 문서',
            self::DEV_HANDOFF        => '개발 핸드오프',
            self::ERD                => 'ERD',
            self::API_SPEC           => 'API 명세서',
            self::RBAC_MODEL         => '권한 모델 (RBAC)',
            self::CODE_GEN_PROMPT    => '코드 생성 프롬프트',
            self::FRONTEND_CODE      => '프론트엔드 코드',
            self::CODE_VALIDATION    => 'Output 검증',
            self::BACKEND_CODE       => '백엔드 코드',
            self::API_INTEGRATION    => 'API 연계',
            self::CODE_REVIEW        => 'AI 코드 리뷰',
            self::RELEASE_PACKAGE    => '릴리즈 패키지',
            self::DEPLOY_GUIDE       => '배포 가이드',
            self::USER_MANUAL        => '사용자 매뉴얼',
            self::MIGRATION_GUIDE    => '마이그레이션 가이드',
        };
    }

    public function stage(): StageType
    {
        return match($this) {
            self::AS_IS_ANALYSIS,
            self::TO_BE_REQUIREMENTS,
            self::GAP_ANALYSIS,
            self::PLANNING_DOC,
            self::IA_FLOW,
            self::SCREEN_PROMPTS,
            self::MOCKUP            => StageType::PLANNING,
            self::DESIGN_TOKENS,
            self::COMPONENT_SPEC,
            self::LAYOUT_SPEC,
            self::DESIGN_REVIEW,
            self::DESIGN_SYSTEM_DOC,
            self::DEV_HANDOFF       => StageType::DESIGN,
            self::ERD,
            self::API_SPEC,
            self::RBAC_MODEL,
            self::CODE_GEN_PROMPT   => StageType::DEV_PREP,
            self::FRONTEND_CODE,
            self::CODE_VALIDATION,
            self::BACKEND_CODE,
            self::API_INTEGRATION,
            self::CODE_REVIEW       => StageType::DEVELOPMENT,
            self::RELEASE_PACKAGE,
            self::DEPLOY_GUIDE,
            self::USER_MANUAL,
            self::MIGRATION_GUIDE   => StageType::RELEASE,
        };
    }
}
