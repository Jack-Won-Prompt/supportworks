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
    case DESIGN_SYSTEM_DOC  = 'design_system_doc';
    case ERD                = 'erd';
    case API_SPEC           = 'api_spec';
    case RBAC_MODEL         = 'rbac_model';
    case FRONTEND_CODE      = 'frontend_code';
    case BACKEND_CODE       = 'backend_code';
    case RELEASE_PACKAGE    = 'release_package';

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
            self::DESIGN_SYSTEM_DOC  => '디자인 시스템 문서',
            self::ERD                => 'ERD',
            self::API_SPEC           => 'API 명세서',
            self::RBAC_MODEL         => '권한 모델 (RBAC)',
            self::FRONTEND_CODE      => '프론트엔드 코드',
            self::BACKEND_CODE       => '백엔드 코드',
            self::RELEASE_PACKAGE    => '릴리즈 패키지',
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
            self::DESIGN_SYSTEM_DOC => StageType::DESIGN,
            self::ERD,
            self::API_SPEC,
            self::RBAC_MODEL        => StageType::DEV_PREP,
            self::FRONTEND_CODE,
            self::BACKEND_CODE      => StageType::DEVELOPMENT,
            self::RELEASE_PACKAGE   => StageType::RELEASE,
        };
    }
}
