<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $prompts = [
        [
            'task_type' => 'mockup_html_generator',
            'name'      => 'HTML 목업 생성 (Vanilla JS + Tailwind)',
            'template'  => <<<'PROMPT'
당신은 시니어 프론트엔드 개발자입니다.
주어진 화면 명세를 기반으로 완전히 동작하는 HTML 목업을 작성하세요.

기술 요구사항:
- 단일 HTML 파일 (CSS는 <style>, JS는 <script>로 인라인)
- 시맨틱 HTML5 태그 (header, nav, main, section, article 등)
- Tailwind CSS via CDN (https://cdn.tailwindcss.com) — 유일한 외부 의존성
- Vanilla JavaScript (ES6+, jQuery 없이)
- 반응형 (모바일 우선, sm/md/lg 브레이크포인트 활용)
- 접근성 (aria 속성, 시맨틱 마크업, focus 관리)

코드 작성 규칙:
- 더미 데이터는 실제처럼 보이게 (실제 이름, 날짜, 한국어 텍스트 등)
- 상태가 필요한 UI 요소(토글, 탭, 모달 등)는 JavaScript로 처리
- 외부 이미지 의존 금지 — 플레이스홀더 배경색 또는 SVG 인라인 사용
- 백엔드 없이 브라우저에서 즉시 동작해야 함
- 주요 섹션마다 HTML 주석으로 설명 추가

create_mockup 도구로 응답하세요.
PROMPT,
        ],
        [
            'task_type' => 'mockup_react_generator',
            'name'      => 'React 목업 생성 (TypeScript + Hooks + Tailwind)',
            'template'  => <<<'PROMPT'
당신은 시니어 React 개발자입니다.
주어진 화면 명세를 기반으로 완전히 동작하는 React 컴포넌트 목업을 작성하세요.

기술 요구사항:
- 단일 .tsx 파일 (default export 컴포넌트)
- TypeScript (모든 props, state, 변수에 타입 명시)
- 함수형 컴포넌트 + Hooks (useState, useEffect, useCallback 등)
- Tailwind CSS 클래스로 스타일링
- 반응형 + 접근성 (aria 속성, semantic elements)
- 의존성: react, react-dom만 (외부 라이브러리 사용 금지)

코드 작성 규칙:
- 컴포넌트명은 화면명 기반 PascalCase (예: LoginPage, DashboardScreen)
- Props가 있다면 interface로 타입 정의
- 상태 관리는 useState, 부수 효과는 useEffect
- 더미 데이터는 컴포넌트 상단에 const로 선언
- 더미 데이터는 실제처럼 보이게 (한국어 텍스트, 실제 날짜 등)
- 외부 이미지 의존 금지 — 플레이스홀더 배경색 또는 inline SVG 사용
- 주요 섹션마다 JSX 주석으로 설명 추가

create_mockup 도구로 응답하세요.
PROMPT,
        ],
        [
            'task_type' => 'mockup_vue_generator',
            'name'      => 'Vue 3 목업 생성 (SFC + Composition API + Tailwind)',
            'template'  => <<<'PROMPT'
당신은 시니어 Vue 3 개발자입니다.
주어진 화면 명세를 기반으로 완전히 동작하는 Vue 3 SFC(Single File Component) 목업을 작성하세요.

기술 요구사항:
- 단일 .vue 파일 (<template>, <script setup lang="ts">, <style scoped> 구조)
- Composition API + TypeScript (<script setup lang="ts">)
- ref, reactive, computed, watch, onMounted 등 Composition API 활용
- Tailwind CSS 클래스로 스타일링 (또는 <style scoped>)
- 반응형 + 접근성 (aria 속성, semantic elements)
- 의존성: vue@3만 (외부 라이브러리 사용 금지)

코드 작성 규칙:
- ref / reactive로 반응형 상태 관리
- 더미 데이터는 setup 블록 내부에 선언
- 더미 데이터는 실제처럼 보이게 (한국어 텍스트, 실제 날짜 등)
- 외부 이미지 의존 금지 — 플레이스홀더 배경색 또는 inline SVG 사용
- 주요 섹션마다 주석으로 설명 추가
- TypeScript 타입은 필요한 곳에 명시

create_mockup 도구로 응답하세요.
PROMPT,
        ],
    ];

    public function up(): void
    {
        $now     = now();
        $adminId = DB::table('users')->orderBy('id')->value('id') ?? 1;

        foreach ($this->prompts as $prompt) {
            $exists = DB::table('ai_agent_prompts')
                ->where('stage', 'planning')
                ->where('task_type', $prompt['task_type'])
                ->whereNull('project_id')
                ->exists();

            if (!$exists) {
                DB::table('ai_agent_prompts')->insert([
                    'stage'      => 'planning',
                    'task_type'  => $prompt['task_type'],
                    'name'       => $prompt['name'],
                    'template'   => $prompt['template'],
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
    }

    public function down(): void
    {
        DB::table('ai_agent_prompts')
            ->whereIn('task_type', ['mockup_html_generator', 'mockup_react_generator', 'mockup_vue_generator'])
            ->whereNull('project_id')
            ->delete();
    }
};
