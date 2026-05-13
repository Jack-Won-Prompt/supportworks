<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now     = now();
        $adminId = \Illuminate\Support\Facades\DB::table('users')->orderBy('id')->value('id') ?? 1;

        $prompts = [
            // ── 1.3 프로젝트 목표 ───────────────────────────────────────────
            [
                'stage'     => 'planning',
                'task_type' => 'planning_doc_section_1_3_objectives',
                'name'      => '기획서 §1.3 프로젝트 목표',
                'template'  => <<<'PROMPT'
당신은 IT 프로젝트 기획 전문가입니다.

제공된 AS-IS 현황 요약과 TO-BE 요구사항 개요를 분석하여,
이 프로젝트가 달성해야 할 핵심 목표를 3~5개 도출하세요.

출력 형식 (마크다운):
- 각 목표를 굵은 글씨 제목 + 1~2문장 설명으로 작성
- 목표는 SMART(구체적, 측정 가능, 달성 가능, 관련성, 기한) 원칙에 따라 작성
- 전체 2~3문단, 400~600자 이내

응답에는 마크다운 콘텐츠만 포함하세요. 별도의 설명을 추가하지 마세요.
PROMPT,
            ],

            // ── 5.1 우선순위 액션 ───────────────────────────────────────────
            [
                'stage'     => 'planning',
                'task_type' => 'planning_doc_section_5_1_priority_actions',
                'name'      => '기획서 §5.1 우선순위 액션',
                'template'  => <<<'PROMPT'
당신은 프로젝트 추진 전략 컨설턴트입니다.

Gap 분석의 권장 사항과 MUST 우선순위 요구사항을 종합하여,
프로젝트 초기 단계에서 가장 우선해야 할 액션 항목 5~7개를 도출하세요.

각 액션은:
- 명확한 동사로 시작 (예: "구축", "도입", "전환", "개선")
- 우선순위 근거 (왜 이것이 먼저인지)
- 예상 소요 기간 또는 마일스톤 (가능하면)

출력 형식 (마크다운):
- 번호 매김 리스트
- 각 항목: **[핵심 동작]** — 상세 설명 (2~3줄)

응답에는 마크다운 콘텐츠만 포함하세요.
PROMPT,
            ],

            // ── 5.2 단계적 접근 방안 ─────────────────────────────────────────
            [
                'stage'     => 'planning',
                'task_type' => 'planning_doc_section_5_2_phasing_strategy',
                'name'      => '기획서 §5.2 단계적 접근 방안',
                'template'  => <<<'PROMPT'
당신은 IT 프로젝트 로드맵 설계 전문가입니다.

Gap 분석의 단계적 접근 전략과 요구사항 우선순위를 바탕으로,
프로젝트를 3단계(Phase)로 나누어 단계적 접근 방안을 수립하세요.

각 단계:
- **Phase 1 (기반 구축)**: 즉시 필요한 핵심 기능
- **Phase 2 (기능 확장)**: 중기 개선 및 추가 기능
- **Phase 3 (최적화)**: 장기 성숙화 및 고도화

출력 형식 (마크다운):
- 각 Phase를 H4 제목으로
- 기간, 목표, 핵심 과제 포함
- 각 Phase당 3~5개 항목

응답에는 마크다운 콘텐츠만 포함하세요.
PROMPT,
            ],

            // ── 5.3 핵심 성공 요인 ──────────────────────────────────────────
            [
                'stage'     => 'planning',
                'task_type' => 'planning_doc_section_5_3_csf',
                'name'      => '기획서 §5.3 핵심 성공 요인',
                'template'  => <<<'PROMPT'
당신은 프로젝트 성공 요인 분석 전문가입니다.

Gap 목록, 리스크, MUST 요구사항을 종합하여,
이 프로젝트가 성공하기 위해 반드시 충족해야 할 핵심 성공 요인(CSF) 5~7개를 도출하세요.

각 CSF는:
- 구체적이고 측정 가능한 형태
- 담당 부서/역할 명시 (가능하면)
- 미충족 시 리스크 언급

출력 형식 (마크다운):
- 번호 매김 리스트
- **[CSF 제목]**: 설명 및 중요성 (2~3줄)

응답에는 마크다운 콘텐츠만 포함하세요.
PROMPT,
            ],

            // ── 5.4 리스크 대응 전략 ─────────────────────────────────────────
            [
                'stage'     => 'planning',
                'task_type' => 'planning_doc_section_5_4_risk_strategy',
                'name'      => '기획서 §5.4 리스크 대응 전략',
                'template'  => <<<'PROMPT'
당신은 IT 프로젝트 리스크 관리 전문가입니다.

Gap 분석에서 식별된 리스크 목록을 바탕으로,
각 리스크에 대한 구체적인 대응 전략을 수립하세요.

각 리스크 대응 전략:
- **회피(Avoid)**: 리스크 발생 원인 제거
- **완화(Mitigate)**: 발생 가능성 또는 영향도 축소
- **전이(Transfer)**: 외부 자원에 리스크 전가
- **수용(Accept)**: 대안 부재 시 계획적 수용

출력 형식 (마크다운):
- 각 리스크를 H4 제목으로
- 대응 전략, 담당자, 모니터링 방법 포함

응답에는 마크다운 콘텐츠만 포함하세요.
PROMPT,
            ],

            // ── 8.1 용어 정의 ────────────────────────────────────────────────
            [
                'stage'     => 'planning',
                'task_type' => 'planning_doc_section_8_1_glossary',
                'name'      => '기획서 §8.1 용어 정의',
                'template'  => <<<'PROMPT'
당신은 IT 문서 편집 전문가입니다.

요구사항 목록과 Gap 분석에서 사용된 기술 용어, 도메인 특화 용어, 약어 등을 수집하여
용어 정의 사전을 작성하세요.

출력 형식 (마크다운 표):
| 용어 | 설명 | 출처 |
|------|------|------|
| ... | ... | ... |

- 10~20개 핵심 용어
- 설명은 비전문가도 이해할 수 있는 1~2문장
- 출처는 "요구사항", "Gap 분석", "AS-IS" 중 하나

응답에는 마크다운 표만 포함하세요.
PROMPT,
            ],

            // ── 화면 상세 (화면당 공통) ──────────────────────────────────────
            [
                'stage'     => 'planning',
                'task_type' => 'planning_doc_screen_detail',
                'name'      => '기획서 화면별 상세 설명',
                'template'  => <<<'PROMPT'
당신은 UX/UI 기획 전문가입니다.

화면 정보와 관련 요구사항을 바탕으로,
이 화면의 기획 의도, 주요 기능, UX 고려사항을 작성하세요.

출력 형식 (마크다운):
- **기획 의도**: 이 화면이 왜 필요한지 (2~3문장)
- **주요 기능**: 불릿 리스트 (3~5개)
- **UX 고려사항**: 사용성, 접근성, 반응형 등 (2~3개)
- **관련 요구사항**: REQ-XXX 참조 (해당하는 경우)

응답에는 마크다운 콘텐츠만 포함하세요.
PROMPT,
            ],

            // ── 1.3 프로젝트 목표 (section_1_3 key alias) ────────────────────
            // AI_SECTIONS에서 key=section_1_3_objectives로 접근하지만
            // task_type에 그대로 매핑되므로 별도 등록 불필요.
            // 하지만 AI_SECTIONS의 prompt_key가 다를 경우 추가 시드.
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
        $taskTypes = [
            'planning_doc_section_1_3_objectives',
            'planning_doc_section_5_1_priority_actions',
            'planning_doc_section_5_2_phasing_strategy',
            'planning_doc_section_5_3_csf',
            'planning_doc_section_5_4_risk_strategy',
            'planning_doc_section_8_1_glossary',
            'planning_doc_screen_detail',
        ];

        DB::table('ai_agent_prompts')
            ->whereIn('task_type', $taskTypes)
            ->whereNull('project_id')
            ->delete();
    }
};
