<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NAME = 'planning.to_be_analysis.system_v1';

    public function up(): void
    {
        if (DB::table('ai_agent_prompts')->where('name', self::NAME)->exists()) {
            return;
        }

        $adminId = DB::table('users')->orderBy('id')->value('id') ?? 1;

        DB::table('ai_agent_prompts')->insert([
            'project_id' => null,
            'stage'      => 'planning',
            'task_type'  => 'requirements_extraction',
            'name'       => self::NAME,
            'template'   => $this->template(),
            'variables'  => json_encode([
                ['key' => 'scope_label', 'description' => '분석 범위 (예: "프로젝트 전체")', 'required' => true],
                ['key' => 'file_list',   'description' => '첨부 파일 목록 (파일명 나열)',     'required' => true],
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
당신은 IT 시스템 요구사항 분석 전문가입니다.

사용자가 제공한 자료들(AS-IS 분석 결과, 업무 프로세스 문서, 인터뷰 내용 등)을 바탕으로
TO-BE 요구사항을 도출해주세요.

분석 대상: {scope_label}
첨부 파일: {file_list}

다음 기준으로 요구사항을 도출해주세요:
1. 각 요구사항은 명확하고 측정 가능한 형태로 작성
2. MoSCoW 우선순위 부여 (must/should/could/wont)
3. 기능 카테고리별 분류 (예: 사용자 관리, 보고서, 알림, 연동 등)
4. AS-IS 문제점과의 연관성 설명 (rationale)
5. 근거 파일 명시 (source_files)

요구사항 도출 원칙:
- AS-IS 분석에서 발견된 문제점은 반드시 해결 요구사항으로 포함
- 사용자/이해관계자 관점의 기능 요구사항 중심
- 기술적 구현 방법이 아닌 "무엇을 해야 하는가"에 집중
- MUST: 핵심 기능, 없으면 시스템 운영 불가
- SHOULD: 중요하지만 초기 버전 없이도 가능
- COULD: 있으면 좋지만 우선순위 낮음
- WONT: 이번 범위 외, 차후 검토

반드시 `record_to_be_requirements` 도구를 사용하여 구조화된 형태로 결과를 반환해주세요.
PROMPT;
    }
};
