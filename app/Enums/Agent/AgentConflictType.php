<?php

namespace App\Enums\Agent;

enum AgentConflictType: string
{
    case FILE_PATH_CONFLICT       = 'file_path_conflict';
    case COMPONENT_NAME_CONFLICT  = 'component_name_conflict';
    case ROUTE_CONFLICT           = 'route_conflict';
    case CSS_CLASS_CONFLICT       = 'css_class_conflict';
    case ASSET_NAME_CONFLICT      = 'asset_name_conflict';
    case FIGMA_VERSION_MISMATCH   = 'figma_version_mismatch';
    case CONFIRMED_OUTPUT_CONFLICT = 'confirmed_output_conflict';
    case STACK_MISMATCH           = 'stack_mismatch';

    public function label(): string
    {
        return match ($this) {
            self::FILE_PATH_CONFLICT        => '파일 경로 충돌',
            self::COMPONENT_NAME_CONFLICT   => '컴포넌트 이름 충돌',
            self::ROUTE_CONFLICT            => '라우트 이름 충돌',
            self::CSS_CLASS_CONFLICT        => 'CSS 클래스 충돌',
            self::ASSET_NAME_CONFLICT       => 'asset 이름 충돌',
            self::FIGMA_VERSION_MISMATCH    => 'Figma 버전 불일치',
            self::CONFIRMED_OUTPUT_CONFLICT => '확정 산출물 충돌',
            self::STACK_MISMATCH            => 'Output 유형/프로젝트 stack 불일치',
        };
    }
}
