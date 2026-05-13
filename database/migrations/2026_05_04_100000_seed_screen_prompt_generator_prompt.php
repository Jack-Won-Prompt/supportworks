<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now     = now();
        $adminId = DB::table('users')->orderBy('id')->value('id') ?? 1;

        $existing = DB::table('ai_agent_prompts')
            ->where('stage', 'planning')
            ->where('task_type', 'screen_prompt_generator')
            ->whereNull('project_id')
            ->first();

        if (!$existing) {
            DB::table('ai_agent_prompts')->insert([
                'stage'      => 'planning',
                'task_type'  => 'screen_prompt_generator',
                'name'       => '화면 생성 프롬프트 자동 작성',
                'template'   => <<<'PROMPT'
당신은 UI/UX 프롬프트 엔지니어입니다.

주어진 화면 정보, 기획서 내용, 관련 요구사항, 그리고 프론트엔드 스택 정보를 종합하여,
웍스가 이 화면의 목업(HTML/React/Vue)을 생성할 때 사용할 명확하고 구체적인 프롬프트를 작성해주세요.

작성할 프롬프트는 다음 섹션을 포함해야 합니다:

## 화면 개요
- 화면 ID와 이름
- 사용자 시나리오 (이 화면에서 사용자가 무엇을 하는가)
- 화면의 목적과 핵심 가치

## 디자인 가이드
- 분위기와 스타일 (예: 미니멀, 데이터 중심, 친근한 등)
- 색상 톤 제안
- 타이포그래피 방향

## 화면 구성 요소
모든 표시 정보, 입력 요소, 액션 버튼을 구체적으로 나열:
- 헤더/네비게이션
- 본문 영역
- 입력 폼/필드 (필수/선택 표기)
- 버튼/링크 (액션 명시)
- 푸터/보조 영역

## 인터랙션
- 클릭/탭 동작
- 폼 검증 메시지
- 에러/성공 상태
- 로딩/대기 상태

## 화면 흐름
- 이 화면 이전: 어디서 왔는가
- 이 화면 다음: 어디로 가는가

## 기술 가이드
스택 정보에 따라 구체적인 기술 지침을 포함하세요:
- HTML: 시맨틱 마크업 사용, Tailwind/CSS 클래스 명시
- React: 함수형 컴포넌트, Hooks, TypeScript 인터페이스 정의
- Vue: SFC 구조, Composition API, TypeScript

## 제약 사항
- 반응형 (모바일/태블릿/데스크톱) 레이아웃 요구사항
- 접근성 (a11y) 고려사항 (ARIA, 키보드 탐색 등)
- 성능 고려사항 (lazy loading, 이미지 최적화 등)

프롬프트는 명확하고 구체적이며, 웍스가 이를 받았을 때 바로 코드 생성에 사용 가능한 수준이어야 합니다.
모호한 표현("적절히", "보기 좋게")은 피하고 구체적인 지시("주요 액션 버튼은 우측 상단에 primary 색상으로")를 사용하세요.

응답은 마크다운 형식의 프롬프트 본문만 작성하세요. 다른 설명이나 메타 코멘트는 포함하지 마세요.
PROMPT,
                'project_id' => null,
                'version'    => 1,
                'variables'  => json_encode([]),
                'is_active'  => true,
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('ai_agent_prompts')
            ->where('task_type', 'screen_prompt_generator')
            ->whereNull('project_id')
            ->delete();
    }
};
