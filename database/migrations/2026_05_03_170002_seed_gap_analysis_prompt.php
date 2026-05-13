<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NAME = 'planning.gap_analysis.system_v1';

    public function up(): void
    {
        if (DB::table('ai_agent_prompts')->where('name', self::NAME)->exists()) {
            return;
        }

        $adminId = DB::table('users')->orderBy('id')->value('id') ?? 1;

        DB::table('ai_agent_prompts')->insert([
            'project_id' => null,
            'stage'      => 'planning',
            'task_type'  => 'gap_analysis',
            'name'       => self::NAME,
            'template'   => $this->template(),
            'variables'  => json_encode([
                ['key' => 'as_is_summary',   'description' => 'AS-IS 분석 요약',        'required' => true],
                ['key' => 'issue_count',     'description' => 'AS-IS 이슈 수',           'required' => true],
                ['key' => 'req_count',       'description' => 'TO-BE 요구사항 수',       'required' => true],
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
당신은 시스템 개선 전략 분석 전문가입니다.

AS-IS 분석 결과와 TO-BE 요구사항을 비교하여 Gap(격차)을 분석해주세요.

## 역할
1. 현재 상태(AS-IS)와 목표 상태(TO-BE)의 차이(Gap) 도출
2. 개선 기회(Opportunities) 식별
3. 잠재 리스크(Risks) 평가
4. 우선순위 기반 권장사항(Recommendations) 제시

## Gap 항목 작성 기준
각 Gap은 반드시 포함:
- 명확하고 구체적인 제목
- 현재 상태 설명 (AS-IS에 근거)
- 목표 상태 설명 (TO-BE 요구사항에 근거)
- 카테고리: 보안/기능/UX/성능/데이터/인프라/기타
- 심각도: high(비즈니스 영향 크고 즉시 해결)/medium(중요하나 단계적 해결 가능)/low(권고 수준)
- 예상 노력 수준: high(3개월+)/medium(1~3개월)/low(1개월 이내)
- 관련 TO-BE 요구사항 ID (REQ-XXX 형식, 여러 개 가능)
- 권장 조치사항 (3개 이내 실행 가능한 액션)

## 리스크 평가 기준
- 발생 가능성 × 영향도로 평가
- 완화 방안 반드시 포함

## 출력
반드시 `record_gap_analysis` 도구를 사용하여 구조화된 형태로 반환해주세요.
PROMPT;
    }
};
