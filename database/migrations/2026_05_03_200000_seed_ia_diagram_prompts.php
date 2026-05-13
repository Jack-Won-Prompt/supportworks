<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now     = now();
        $adminId = DB::table('users')->orderBy('id')->value('id') ?? 1;

        $prompts = [
            [
                'stage'     => 'planning',
                'task_type' => 'ia_flow_ia_diagram',
                'name'      => 'IA / 화면 흐름도 §IA 구조도',
                'template'  => <<<'PROMPT'
당신은 정보 아키텍처(IA) 설계 전문가입니다.

제공된 기획서 내용과 화면 목록을 바탕으로 시스템의 IA(Information Architecture) 구조도를 Mermaid 다이어그램으로 작성하세요.

규칙:
- `graph LR` 형식 사용
- 최상위 노드: 시스템명
- 2단계: 주요 기능 그룹 (메뉴/모듈) - 기획서 내용을 참조하여 의미 있는 그룹으로 분류
- 3단계: 개별 화면 (제공된 화면 목록 활용, SCR-XXX 형식)
- 노드 레이블에 한국어 사용
- 화면이 없으면 기획서에서 주요 기능을 추론하여 표현

출력: Mermaid 코드만 출력하세요. 코드 블록 마커(```)를 포함하지 마세요.
PROMPT,
            ],

            [
                'stage'     => 'planning',
                'task_type' => 'ia_flow_screen_flow',
                'name'      => 'IA / 화면 흐름도 §화면 흐름도',
                'template'  => <<<'PROMPT'
당신은 UX 설계 전문가입니다.

제공된 화면 목록과 요구사항을 바탕으로 사용자가 이동하는 화면 흐름도를 Mermaid 다이어그램으로 작성하세요.

규칙:
- `flowchart TD` 형식 사용
- 시작/종료는 원형 노드((시작)), ((종료)) 사용
- 화면은 사각형 노드[SCR-XXX: 화면명] 사용
- 조건 분기는 마름모 노드{조건} 사용
- 주요 사용자 시나리오(로그인 → 메인 → 핵심 기능 → 완료) 흐름을 표현
- 노드 레이블에 한국어 사용
- 화면이 없으면 기획서에서 주요 사용자 흐름을 추론

출력: Mermaid 코드만 출력하세요. 코드 블록 마커(```)를 포함하지 마세요.
PROMPT,
            ],
        ];

        foreach ($prompts as $prompt) {
            $existing = DB::table('ai_agent_prompts')
                ->where('stage', $prompt['stage'])
                ->where('task_type', $prompt['task_type'])
                ->whereNull('project_id')
                ->first();

            if (!$existing) {
                DB::table('ai_agent_prompts')->insert(array_merge($prompt, [
                    'project_id' => null,
                    'version'    => 1,
                    'variables'  => json_encode([]),
                    'is_active'  => true,
                    'created_by' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('ai_agent_prompts')
            ->whereIn('task_type', ['ia_flow_ia_diagram', 'ia_flow_screen_flow'])
            ->whereNull('project_id')
            ->delete();
    }
};
