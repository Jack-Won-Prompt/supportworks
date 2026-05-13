<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NAME = 'planning.as_is_analysis.system_v1';

    public function up(): void
    {
        if (DB::table('ai_agent_prompts')->where('name', self::NAME)->exists()) {
            return;
        }

        $adminId = DB::table('users')->orderBy('id')->value('id') ?? 1;

        DB::table('ai_agent_prompts')->insert([
            'project_id' => null,
            'stage'      => 'planning',
            'task_type'  => 'as_is_analysis',
            'name'       => self::NAME,
            'template'   => $this->template(),
            'variables'  => json_encode([
                ['key' => 'scope_label',  'description' => '분석 범위 (예: "프로젝트 전체" 또는 "SCR-001 로그인 화면")', 'required' => true],
                ['key' => 'file_list',    'description' => '첨부 파일 목록 (파일명 나열)',                              'required' => true],
                ['key' => 'focus_screen', 'description' => '화면 단위 분석 시 화면 제목',                              'required' => false],
            ]),
            'version'    => 1,
            'is_active'  => true,
            'created_by' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('ai_agent_prompts')->where('name', self::NAME)->delete();
    }

    private function template(): string
    {
        return <<<'PROMPT'
당신은 IT 시스템 현황 분석 전문가입니다.

사용자가 제공한 자료들(텍스트, Excel, PowerPoint, PDF, 이미지 등)을 면밀히 분석하여
AS-IS 현황 분석 보고서를 작성해주세요.

분석 대상: {scope_label}
첨부 파일: {file_list}

다음 항목을 도출해주세요:
1. 현황 요약 (2~3 문단 — 현재 시스템/프로세스의 개요, 규모, 주요 기능)
2. 주요 문제점 및 이슈 (각 이슈에 대해 카테고리, 심각도, 상세 설명, 출처 파일 명시)
3. 카테고리별 종합 분석 (성능/UX/기능/보안/기타 중 해당되는 것만)
4. 파일별 핵심 발견사항 (어떤 파일에서 무엇을 발견했는지)

분석 시 주의사항:
- 제공된 자료에 근거한 객관적 분석만 수행하세요.
- 추측이나 가정은 명확히 표시해주세요.
- 이슈의 심각도는 high(시스템 장애/보안 위협)/medium(기능 저하/UX 문제)/low(개선 권고) 기준으로 분류하세요.
- 출처는 "파일명#섹션" 형식으로 명시해주세요 (예: "report.pptx#슬라이드 3").

반드시 `record_as_is_analysis` 도구를 사용하여 구조화된 형태로 결과를 반환해주세요.
PROMPT;
    }
};
