<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $structure = [
            'sections' => [
                [
                    'id'    => '1',
                    'title' => '프로젝트 개요',
                    'level' => 1,
                    'subsections' => [
                        ['id' => '1.1', 'title' => '프로젝트명',        'type' => 'data_injection', 'variable' => 'project.name'],
                        ['id' => '1.2', 'title' => '프로젝트 기간',     'type' => 'data_injection', 'variable' => 'project.duration'],
                        ['id' => '1.3', 'title' => '프로젝트 목표',     'type' => 'ai_generated',   'ai_prompt_key' => 'planning_doc.section_1_3_objectives',  'ai_input' => ['asis_summary', 'tobe_overview']],
                        ['id' => '1.4', 'title' => '핵심 이해관계자',   'type' => 'data_injection', 'variable' => 'project.stakeholders'],
                    ],
                ],
                [
                    'id'    => '2',
                    'title' => '현황 분석 (AS-IS)',
                    'level' => 1,
                    'data_source' => 'asis',
                    'subsections' => [
                        ['id' => '2.1', 'title' => '현황 요약',       'type' => 'data_injection', 'variable' => 'asis.summary'],
                        ['id' => '2.2', 'title' => '주요 문제점',     'type' => 'data_injection', 'variable' => 'asis.issues'],
                        ['id' => '2.3', 'title' => '카테고리별 분석', 'type' => 'data_injection', 'variable' => 'asis.categories'],
                    ],
                ],
                [
                    'id'    => '3',
                    'title' => '요구사항 분석 (TO-BE)',
                    'level' => 1,
                    'data_source' => 'tobe',
                    'subsections' => [
                        ['id' => '3.1', 'title' => '요구사항 개요',         'type' => 'data_injection', 'variable' => 'tobe.overview'],
                        ['id' => '3.2', 'title' => '우선순위별 요구사항',   'type' => 'data_injection', 'variable' => 'requirements.by_priority'],
                        ['id' => '3.3', 'title' => '카테고리별 요구사항',  'type' => 'data_injection', 'variable' => 'requirements.by_category'],
                    ],
                ],
                [
                    'id'    => '4',
                    'title' => 'Gap 분석',
                    'level' => 1,
                    'data_source' => 'gap',
                    'subsections' => [
                        ['id' => '4.1', 'title' => '분석 요약',   'type' => 'data_injection', 'variable' => 'gap.executive_summary'],
                        ['id' => '4.2', 'title' => '주요 Gap',    'type' => 'data_injection', 'variable' => 'gap.gaps'],
                        ['id' => '4.3', 'title' => '개선 기회',   'type' => 'data_injection', 'variable' => 'gap.opportunities'],
                        ['id' => '4.4', 'title' => '리스크 평가', 'type' => 'data_injection', 'variable' => 'gap.risks'],
                    ],
                ],
                [
                    'id'    => '5',
                    'title' => '추진 전략',
                    'level' => 1,
                    'data_source' => 'gap',
                    'subsections' => [
                        ['id' => '5.1', 'title' => '우선순위 액션',       'type' => 'ai_generated', 'ai_prompt_key' => 'planning_doc.section_5_1_priority_actions',  'ai_input' => ['gap.recommendations']],
                        ['id' => '5.2', 'title' => '단계적 접근 방안',   'type' => 'ai_generated', 'ai_prompt_key' => 'planning_doc.section_5_2_phasing_strategy',   'ai_input' => ['gap.recommendations', 'gap.risks']],
                        ['id' => '5.3', 'title' => '핵심 성공 요인',     'type' => 'ai_generated', 'ai_prompt_key' => 'planning_doc.section_5_3_csf',                'ai_input' => ['asis_summary', 'tobe_overview', 'gap.executive_summary']],
                        ['id' => '5.4', 'title' => '리스크 대응 전략',   'type' => 'ai_generated', 'ai_prompt_key' => 'planning_doc.section_5_4_risk_strategy',      'ai_input' => ['gap.risks']],
                    ],
                ],
                [
                    'id'    => '6',
                    'title' => '화면 설계',
                    'level' => 1,
                    'data_source' => 'screens',
                    'subsections' => [
                        ['id' => '6.1', 'title' => '화면 목록',       'type' => 'data_injection', 'variable' => 'screens'],
                        ['id' => '6.2', 'title' => '화면 흐름도',     'type' => 'placeholder',    'placeholder_key' => 'ia_flow_diagram', 'placeholder_note' => 'T23에서 자동 생성 예정'],
                        ['id' => '6.3', 'title' => '화면별 상세',     'type' => 'ai_generated',   'ai_prompt_key' => 'planning_doc.screen_detail', 'ai_input' => ['screens'], 'iterate_over' => 'screens'],
                    ],
                ],
                [
                    'id'    => '7',
                    'title' => '일정 및 리소스',
                    'level' => 1,
                    'data_source' => 'milestones',
                    'subsections' => [
                        ['id' => '7.1', 'title' => '전체 일정',          'type' => 'data_injection', 'variable' => 'project.duration'],
                        ['id' => '7.2', 'title' => '단계별 마일스톤',   'type' => 'data_injection', 'variable' => 'milestones'],
                        ['id' => '7.3', 'title' => '리소스 계획',       'type' => 'data_injection', 'variable' => 'project.members'],
                    ],
                ],
                [
                    'id'    => '8',
                    'title' => '부록',
                    'level' => 1,
                    'subsections' => [
                        ['id' => '8.1', 'title' => '용어 정의',   'type' => 'ai_generated',   'ai_prompt_key' => 'planning_doc.section_8_1_glossary', 'ai_input' => ['requirements', 'gaps']],
                        ['id' => '8.2', 'title' => '참고 자료',   'type' => 'data_injection', 'variable' => 'attached_files'],
                    ],
                ],
            ],
            'variables' => [
                'project.name'        => ['source' => 'supportworks.project', 'field' => 'name',       'type' => 'string'],
                'project.duration'    => ['source' => 'supportworks.project', 'field' => 'duration',   'type' => 'string'],
                'project.stakeholders'=> ['source' => 'supportworks.project', 'field' => 'members',    'type' => 'collection'],
                'project.members'     => ['source' => 'supportworks.project', 'field' => 'members',    'type' => 'collection'],
                'asis.summary'        => ['source' => 'ai_agent_artifacts', 'filter' => ['type' => 'as_is_analysis', 'scope_type' => 'project'], 'field' => 'content.summary',    'type' => 'text'],
                'asis.issues'         => ['source' => 'ai_agent_artifacts', 'filter' => ['type' => 'as_is_analysis', 'scope_type' => 'project'], 'field' => 'content.issues',     'type' => 'collection'],
                'asis.categories'     => ['source' => 'ai_agent_artifacts', 'filter' => ['type' => 'as_is_analysis', 'scope_type' => 'project'], 'field' => 'content.categories', 'type' => 'object'],
                'asis_summary'        => ['source' => 'ai_agent_artifacts', 'filter' => ['type' => 'as_is_analysis', 'scope_type' => 'project'], 'field' => 'content.summary',    'type' => 'text'],
                'tobe.overview'       => ['source' => 'ai_agent_artifacts', 'filter' => ['type' => 'to_be_requirements', 'scope_type' => 'project'], 'field' => 'content.overview', 'type' => 'text'],
                'tobe_overview'       => ['source' => 'ai_agent_artifacts', 'filter' => ['type' => 'to_be_requirements', 'scope_type' => 'project'], 'field' => 'content.overview', 'type' => 'text'],
                'requirements'        => ['source' => 'ai_agent_requirements', 'filter' => ['project_id' => '$current'], 'type' => 'collection'],
                'requirements.by_priority' => ['source' => 'ai_agent_requirements', 'filter' => ['project_id' => '$current'], 'group_by' => 'priority', 'type' => 'grouped_collection'],
                'requirements.by_category' => ['source' => 'ai_agent_requirements', 'filter' => ['project_id' => '$current'], 'group_by' => 'category', 'type' => 'grouped_collection'],
                'gap.executive_summary'  => ['source' => 'ai_agent_artifacts', 'filter' => ['type' => 'gap_analysis', 'scope_type' => 'project'], 'field' => 'content.executive_summary', 'type' => 'text'],
                'gap.gaps'               => ['source' => 'ai_agent_gaps',      'filter' => ['project_id' => '$current'], 'type' => 'collection'],
                'gap.opportunities'      => ['source' => 'ai_agent_artifacts', 'filter' => ['type' => 'gap_analysis', 'scope_type' => 'project'], 'field' => 'content.improvement_opportunities', 'type' => 'collection'],
                'gap.risks'              => ['source' => 'ai_agent_artifacts', 'filter' => ['type' => 'gap_analysis', 'scope_type' => 'project'], 'field' => 'content.risks',            'type' => 'collection'],
                'gap.recommendations'    => ['source' => 'ai_agent_artifacts', 'filter' => ['type' => 'gap_analysis', 'scope_type' => 'project'], 'field' => 'content.recommendations',  'type' => 'object'],
                'gaps'                   => ['source' => 'ai_agent_gaps',      'filter' => ['project_id' => '$current'], 'type' => 'collection'],
                'screens'                => ['source' => 'ai_agent_screens',   'filter' => ['project_id' => '$current'], 'type' => 'collection'],
                'milestones'             => ['source' => 'supportworks.schedules', 'filter' => ['project_id' => '$current'], 'type' => 'collection'],
                'attached_files'         => ['source' => 'ai_agent_artifact_files', 'filter' => ['project_id' => '$current'], 'type' => 'collection'],
            ],
            'required_data' => [
                ['key' => 'asis',         'label' => 'AS-IS 분석',    'task' => 'T17/T18', 'route_name' => 'ai-agent.projects.planning.as-is'],
                ['key' => 'tobe',         'label' => 'TO-BE 요구사항', 'task' => 'T19',     'route_name' => 'ai-agent.projects.planning.to-be'],
                ['key' => 'gap',          'label' => 'Gap 분석',       'task' => 'T20',     'route_name' => 'ai-agent.projects.planning.gap'],
                ['key' => 'screens',      'label' => '화면 목록',      'task' => 'T16',     'route_name' => 'ai-agent.projects.planning.screens', 'optional' => true],
            ],
            'metadata' => [
                'supports_diagrams'  => true,
                'supports_tables'    => true,
                'output_formats'     => ['markdown', 'pdf'],
                'estimated_sections' => 8,
                'ai_sections_count'  => 7,
            ],
        ];

        DB::table('ai_agent_planning_templates')->insert([
            'key'           => 'standard',
            'name'          => '표준 기획서 템플릿',
            'version'       => '1.0.0',
            'description'   => 'AS-IS 분석, TO-BE 요구사항, Gap 분석을 종합하여 프로젝트 기획서를 자동 작성하는 표준 템플릿',
            'structure'     => json_encode($structure, JSON_UNESCAPED_UNICODE),
            'template_path' => 'planning/standard_v1.md.blade.php',
            'is_active'     => true,
            'is_default'    => true,
            'created_by'    => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('ai_agent_planning_templates')->where('key', 'standard')->where('version', '1.0.0')->delete();
    }
};
