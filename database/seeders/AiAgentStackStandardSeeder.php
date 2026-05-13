<?php

namespace Database\Seeders;

use App\Enums\Agent\FrontendStack;
use App\Models\Agent\AiAgentStackStandard;
use Illuminate\Database\Seeder;

class AiAgentStackStandardSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $row) {
            AiAgentStackStandard::updateOrCreate(
                [
                    'stack'    => $row['stack'],
                    'category' => $row['category'],
                    'name'     => $row['name'],
                ],
                [
                    'description'      => $row['description'],
                    'definition'       => $row['definition'],
                    'validation_rules' => $row['validation_rules'] ?? null,
                    'examples'         => $row['examples'] ?? null,
                    'is_active'        => true,
                ]
            );
        }

        $this->command->info('StackStandard 시드 완료: ' . count($this->data()) . '개 레코드 (HTML/React/Vue/Blade × 4카테고리)');
    }

    // -------------------------------------------------------------------------
    // 시드 데이터 정의
    // -------------------------------------------------------------------------

    private function data(): array
    {
        return [
            // ─────────────────────────────────────────────────────────────────
            // HTML
            // ─────────────────────────────────────────────────────────────────
            [
                'stack'       => FrontendStack::HTML,
                'category'    => 'folder_structure',
                'name'        => 'HTML 표준 폴더 구조',
                'description' => '바닐라 HTML 프로젝트의 표준 폴더 레이아웃',
                'definition'  => [
                    'root'      => 'frontend',
                    'structure' => [
                        'pages/'          => '라우트별 HTML 파일 (index.html, about.html …)',
                        'assets/css/'     => '글로벌 스타일시트 (main.css, tailwind 포함)',
                        'assets/js/'      => '페이지별 JS 파일 + 공용 유틸',
                        'assets/images/'  => '이미지/아이콘 (최적화 후 배치)',
                        'components/'     => '재사용 HTML 단편 (헤더, 푸터, 모달 …)',
                        'templates/'      => '웍스 생성 기반 Jinja2/Mustache 템플릿',
                    ],
                    'naming' => [
                        'page'      => 'kebab-case.html  (예: user-profile.html)',
                        'css'       => 'kebab-case.css   (예: nav-bar.css)',
                        'js'        => 'camelCase.js     (예: formHandler.js)',
                        'component' => 'kebab-case.html  (예: modal-confirm.html)',
                        'image'     => 'kebab-case 확장자 (예: hero-banner.webp)',
                    ],
                ],
                'examples' => [
                    'tree' => "frontend/\n├── pages/\n│   ├── index.html\n│   └── user-profile.html\n├── assets/\n│   ├── css/main.css\n│   ├── js/formHandler.js\n│   └── images/hero-banner.webp\n└── components/\n    ├── nav-bar.html\n    └── modal-confirm.html",
                ],
            ],

            [
                'stack'       => FrontendStack::HTML,
                'category'    => 'naming',
                'name'        => 'HTML 명명 규칙',
                'description' => '파일·CSS 클래스·JS 변수·id 명명 표준',
                'definition'  => [
                    'file'      => 'kebab-case (소문자, 하이픈 구분)',
                    'id'        => 'kebab-case (예: user-profile-form)',
                    'class'     => 'BEM + Tailwind 유틸리티 혼용 (예: card__title, text-gray-700)',
                    'js_var'    => 'camelCase (예: userProfileData)',
                    'js_const'  => 'UPPER_SNAKE_CASE (예: API_BASE_URL)',
                    'js_func'   => 'camelCase + 동사 접두어 (예: fetchUserData, handleSubmit)',
                    'data_attr' => 'data-kebab-case (예: data-user-id)',
                ],
                'validation_rules' => [
                    'forbidden_patterns' => ['onclick=', 'style="', 'id="[A-Z]'],
                    'required_patterns'  => ['lang="ko"', '<meta charset', '<meta name="viewport"'],
                    'accessibility'      => ['alt 필수 (img)', 'label-input 연결', 'aria-label (아이콘 버튼)'],
                ],
            ],

            [
                'stack'       => FrontendStack::HTML,
                'category'    => 'component',
                'name'        => 'HTML 컴포넌트 패턴',
                'description' => '재사용 HTML 단편·JavaScript 모듈 구성 표준',
                'definition'  => [
                    'style_approach'   => 'Tailwind CSS 유틸리티 우선, 커스텀 CSS는 assets/css/custom.css',
                    'js_module'        => 'ES Module (type="module") — import/export 사용',
                    'state_management' => '전역 상태: 간단한 window.__APP_STATE__ 객체 또는 LocalStorage',
                    'event_pattern'    => 'addEventListener (인라인 onXxx 금지)',
                    'fetch_pattern'    => 'fetch API + async/await, Axios 선택적 사용',
                    'component_skeleton' => "<!-- components/modal-confirm.html -->\n<template id=\"modal-confirm\">\n  <div class=\"fixed inset-0 flex items-center justify-center bg-black/50\" role=\"dialog\" aria-modal=\"true\">\n    <div class=\"bg-white rounded-lg p-6 max-w-sm w-full\">\n      <h2 class=\"text-lg font-semibold\" id=\"modal-title\"><!-- title --></h2>\n      <p class=\"mt-2 text-sm text-gray-600\" id=\"modal-body\"><!-- body --></p>\n      <div class=\"mt-4 flex justify-end gap-2\">\n        <button id=\"modal-cancel\" class=\"btn-secondary\">취소</button>\n        <button id=\"modal-confirm\" class=\"btn-primary\">확인</button>\n      </div>\n    </div>\n  </div>\n</template>",
                ],
                'validation_rules' => [
                    'required_patterns'  => ['role=', 'aria-'],
                    'forbidden_patterns' => ['document.write', 'eval(', 'innerHTML ='],
                    'accessibility'      => ['role="dialog" + aria-modal', 'focus trap (모달)', 'skip-link'],
                ],
            ],

            [
                'stack'       => FrontendStack::HTML,
                'category'    => 'styling',
                'name'        => 'HTML 스타일링 표준',
                'description' => 'Tailwind CSS 기반 스타일링 규칙',
                'definition'  => [
                    'framework'          => 'Tailwind CSS 3.x (CDN 또는 빌드 통합)',
                    'custom_css'         => 'assets/css/custom.css (Tailwind @layer 확장)',
                    'color_convention'   => 'Tailwind 팔레트 우선, 커스텀 색상은 tailwind.config.js colors에 등록',
                    'responsive'         => 'Mobile-first: sm(640) → md(768) → lg(1024) → xl(1280)',
                    'dark_mode'          => 'class 전략 (html.dark), tailwind.config.js darkMode: "class"',
                    'spacing_scale'      => 'Tailwind 기본 스케일 (4px 기준), 임의 값 [px] 최소화',
                    'typography'         => '@tailwindcss/typography 플러그인 권장 (prose 클래스)',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // REACT
            // ─────────────────────────────────────────────────────────────────
            [
                'stack'       => FrontendStack::REACT,
                'category'    => 'folder_structure',
                'name'        => 'React 표준 폴더 구조',
                'description' => 'React 18 + TypeScript + Vite 기반 프로젝트 표준 레이아웃',
                'definition'  => [
                    'root'      => 'frontend',
                    'structure' => [
                        'src/components/'  => '재사용 컴포넌트 (PascalCase/, 인덱스 배럴)',
                        'src/pages/'       => '라우트 진입점 컴포넌트 (XxxPage.tsx)',
                        'src/hooks/'       => '커스텀 훅 (useXxx.ts)',
                        'src/services/'    => 'API 호출 함수 (xxxApi.ts)',
                        'src/stores/'      => 'Zustand 또는 Redux 슬라이스',
                        'src/utils/'       => '순수 유틸 함수 (테스트 필수)',
                        'src/types/'       => 'TypeScript 인터페이스·타입 (xxxTypes.ts)',
                        'src/constants/'   => '상수·Enum 매핑 (xxxConstants.ts)',
                        'src/assets/'      => '이미지·폰트 (빌드 시 해시)',
                    ],
                    'naming' => [
                        'component'  => 'PascalCase.tsx   (예: UserProfileCard.tsx)',
                        'page'       => 'XxxPage.tsx      (예: UserProfilePage.tsx)',
                        'hook'       => 'useXxx.ts        (예: useUserProfile.ts)',
                        'service'    => 'xxxApi.ts        (예: userApi.ts)',
                        'store'      => 'useXxxStore.ts   (예: useAuthStore.ts)',
                        'type'       => 'XxxTypes.ts      (예: UserTypes.ts)',
                        'util'       => 'camelCase.ts     (예: formatDate.ts)',
                    ],
                ],
                'examples' => [
                    'tree' => "frontend/\n└── src/\n    ├── components/\n    │   └── UserProfileCard/\n    │       ├── UserProfileCard.tsx\n    │       ├── UserProfileCard.test.tsx\n    │       └── index.ts\n    ├── pages/UserProfilePage.tsx\n    ├── hooks/useUserProfile.ts\n    ├── services/userApi.ts\n    ├── stores/useAuthStore.ts\n    └── types/UserTypes.ts",
                ],
            ],

            [
                'stack'       => FrontendStack::REACT,
                'category'    => 'naming',
                'name'        => 'React 명명 규칙',
                'description' => '컴포넌트·훅·타입·상수 명명 표준',
                'definition'  => [
                    'component'      => 'PascalCase (예: UserProfileCard)',
                    'hook'           => 'use 접두어 + PascalCase (예: useUserProfile)',
                    'prop_type'      => '컴포넌트명 + Props (예: UserProfileCardProps)',
                    'event_handler'  => 'handle + PascalCase (예: handleSubmit, handleUserSelect)',
                    'boolean_prop'   => 'is/has/can 접두어 (예: isLoading, hasError)',
                    'async_function' => '동사 접두어 (예: fetchUser, loadDashboard)',
                    'constant'       => 'UPPER_SNAKE_CASE (예: MAX_FILE_SIZE)',
                    'enum_value'     => 'PascalCase (예: UserRole.Admin)',
                    'css_module'     => 'camelCase (예: styles.userCard)',
                ],
                'validation_rules' => [
                    'required_patterns'  => ['React.FC', ': FC<', 'interface.*Props'],
                    'forbidden_patterns' => ['var ', 'class.*extends React.Component', '.defaultProps'],
                    'accessibility'      => ['aria-label', 'role 명시 (비시맨틱 클릭 요소)', 'alt (img)'],
                ],
            ],

            [
                'stack'       => FrontendStack::REACT,
                'category'    => 'component',
                'name'        => 'React 컴포넌트 패턴',
                'description' => '함수형 컴포넌트 + Hooks 기반 표준 패턴',
                'definition'  => [
                    'style_approach'   => 'Tailwind CSS 유틸리티 (cn() 헬퍼로 조건부 클래스 관리)',
                    'state_management' => '로컬: useState/useReducer, 전역: Zustand (useXxxStore)',
                    'data_fetching'    => 'TanStack Query (useQuery/useMutation) 권장',
                    'form_handling'    => 'React Hook Form + Zod 스키마 검증',
                    'error_boundary'   => 'react-error-boundary, 페이지 단위 설정',
                    'props_pattern'    => 'interface Props 명시적 정의, children: React.ReactNode',
                    'component_skeleton' => "import { type FC } from 'react';\nimport { cn } from '@/utils/cn';\n\ninterface UserProfileCardProps {\n  userId: number;\n  className?: string;\n}\n\nexport const UserProfileCard: FC<UserProfileCardProps> = ({ userId, className }) => {\n  return (\n    <div className={cn('rounded-lg border p-4', className)}>\n      {/* content */}\n    </div>\n  );\n};\n\nexport default UserProfileCard;",
                ],
                'validation_rules' => [
                    'required_patterns'  => ['export const', ': FC<', 'interface.*Props'],
                    'forbidden_patterns' => ['any', 'as any', '@ts-ignore', 'useEffect.*\\[\\].*fetch'],
                    'accessibility'      => ['button type 명시', 'form label', 'img alt'],
                ],
            ],

            [
                'stack'       => FrontendStack::REACT,
                'category'    => 'styling',
                'name'        => 'React 스타일링 표준',
                'description' => 'Tailwind CSS + cn() 헬퍼 기반 스타일링 규칙',
                'definition'  => [
                    'framework'        => 'Tailwind CSS 3.x + clsx + tailwind-merge (cn 헬퍼)',
                    'cn_helper'        => "import { clsx } from 'clsx'; import { twMerge } from 'tailwind-merge'; export const cn = (...inputs) => twMerge(clsx(inputs));",
                    'variant_pattern'  => 'cva (class-variance-authority) 로 variant 관리',
                    'responsive'       => 'Mobile-first: sm → md → lg → xl → 2xl',
                    'dark_mode'        => 'Tailwind class 전략, useTheme 훅으로 토글',
                    'animation'        => 'Tailwind transition + Framer Motion (복잡한 애니메이션)',
                    'css_modules'      => '컴포넌트별 복잡한 스타일만 *.module.css 사용 (최소화)',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // VUE
            // ─────────────────────────────────────────────────────────────────
            [
                'stack'       => FrontendStack::VUE,
                'category'    => 'folder_structure',
                'name'        => 'Vue 표준 폴더 구조',
                'description' => 'Vue 3 + TypeScript + Vite + Pinia 기반 프로젝트 표준 레이아웃',
                'definition'  => [
                    'root'      => 'frontend',
                    'structure' => [
                        'src/components/'  => '재사용 SFC (PascalCase 폴더, index.ts 배럴)',
                        'src/views/'       => '라우트 진입점 뷰 (XxxView.vue)',
                        'src/composables/' => 'Composition 함수 (useXxx.ts)',
                        'src/stores/'      => 'Pinia 스토어 (useXxxStore.ts)',
                        'src/services/'    => 'API 호출 함수 (xxxApi.ts)',
                        'src/utils/'       => '순수 유틸 함수',
                        'src/types/'       => 'TypeScript 인터페이스·타입',
                        'src/router/'      => 'Vue Router 설정 (index.ts + 모듈 분리)',
                        'src/assets/'      => '이미지·폰트·글로벌 CSS',
                    ],
                    'naming' => [
                        'component'   => 'PascalCase.vue   (예: UserProfileCard.vue)',
                        'view'        => 'XxxView.vue      (예: UserProfileView.vue)',
                        'composable'  => 'useXxx.ts        (예: useUserProfile.ts)',
                        'store'       => 'useXxxStore.ts   (예: useAuthStore.ts)',
                        'service'     => 'xxxApi.ts        (예: userApi.ts)',
                        'type'        => 'XxxTypes.ts      (예: UserTypes.ts)',
                    ],
                ],
                'examples' => [
                    'tree' => "frontend/\n└── src/\n    ├── components/\n    │   └── UserProfileCard/\n    │       ├── UserProfileCard.vue\n    │       └── index.ts\n    ├── views/UserProfileView.vue\n    ├── composables/useUserProfile.ts\n    ├── stores/useAuthStore.ts\n    ├── services/userApi.ts\n    └── router/index.ts",
                ],
            ],

            [
                'stack'       => FrontendStack::VUE,
                'category'    => 'naming',
                'name'        => 'Vue 명명 규칙',
                'description' => 'SFC·composable·store·타입 명명 표준',
                'definition'  => [
                    'component'      => 'PascalCase (예: UserProfileCard)',
                    'composable'     => 'use 접두어 + PascalCase (예: useUserProfile)',
                    'store_id'       => 'kebab-case 문자열 (예: "user-profile")',
                    'store_function' => 'useXxxStore (예: useAuthStore)',
                    'emit'           => 'kebab-case 이벤트명 (예: update:modelValue, form-submit)',
                    'prop'           => 'camelCase (예: userId, isLoading)',
                    'slot'           => 'kebab-case (예: #header, #default)',
                    'boolean_prop'   => 'is/has/can 접두어',
                    'css_class'      => 'BEM 또는 Tailwind 유틸리티 (scoped 권장)',
                ],
                'validation_rules' => [
                    'required_patterns'  => ['<script setup lang="ts">', 'defineProps<', 'defineEmits<'],
                    'forbidden_patterns' => ['Options API (export default {)', 'this.', 'data()', 'mounted()'],
                    'accessibility'      => ['v-bind:aria-', ':alt', 'role'],
                ],
            ],

            [
                'stack'       => FrontendStack::VUE,
                'category'    => 'component',
                'name'        => 'Vue 컴포넌트 패턴',
                'description' => 'Vue 3 Composition API + SFC + TypeScript 표준 패턴',
                'definition'  => [
                    'style_approach'   => 'Tailwind CSS 유틸리티 + <style scoped> (필요 시)',
                    'state_management' => '로컬: ref/reactive, 전역: Pinia (useXxxStore)',
                    'data_fetching'    => 'TanStack Query for Vue 또는 useAsyncData composable',
                    'form_handling'    => 'VeeValidate + Zod 또는 FormKit',
                    'props_pattern'    => 'defineProps<Interface>() + withDefaults()',
                    'emits_pattern'    => 'defineEmits<{(e: "update:modelValue", value: T): void}>() 타입 기반',
                    'component_skeleton' => "<script setup lang=\"ts\">\nimport { ref, computed } from 'vue';\n\ninterface Props {\n  userId: number;\n  className?: string;\n}\n\nconst props = withDefaults(defineProps<Props>(), {\n  className: '',\n});\n\nconst emit = defineEmits<{\n  (e: 'select', userId: number): void;\n}>();\n</script>\n\n<template>\n  <div :class=\"['rounded-lg border p-4', props.className]\">\n    <!-- content -->\n  </div>\n</template>\n\n<style scoped>\n/* 최소화 — Tailwind 우선 */\n</style>",
                ],
                'validation_rules' => [
                    'required_patterns'  => ['<script setup lang="ts">', 'defineProps', 'defineEmits'],
                    'forbidden_patterns' => ['Options API', ': any', '@ts-ignore', 'v-html'],
                    'accessibility'      => ['button :type', ':alt (img)', 'aria-label'],
                ],
            ],

            [
                'stack'       => FrontendStack::VUE,
                'category'    => 'styling',
                'name'        => 'Vue 스타일링 표준',
                'description' => 'Tailwind CSS + scoped 스타일 혼용 규칙',
                'definition'  => [
                    'framework'        => 'Tailwind CSS 3.x (PostCSS 통합)',
                    'scoped_usage'     => '<style scoped> — Tailwind으로 처리 어려운 복잡한 선택자만',
                    'global_styles'    => 'assets/css/main.css (@tailwind 지시어 + CSS 변수)',
                    'responsive'       => 'Mobile-first: sm → md → lg → xl',
                    'dark_mode'        => 'Tailwind class 전략 + useDark composable (VueUse)',
                    'animation'        => 'Tailwind transition + <Transition> 컴포넌트',
                    'css_variables'    => ':root 변수로 브랜드 색상 관리, Tailwind extend에 매핑',
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // BLADE (Laravel 12)
            // ─────────────────────────────────────────────────────────────────
            [
                'stack'       => FrontendStack::BLADE,
                'category'    => 'folder_structure',
                'name'        => 'Blade 표준 폴더 구조',
                'description' => 'Laravel 12 Blade + Alpine.js + Tailwind CSS 기반 표준 레이아웃',
                'definition'  => [
                    'root'      => 'resources',
                    'structure' => [
                        'views/layouts/'      => '공통 레이아웃 (app.blade.php, guest.blade.php)',
                        'views/components/'   => '익명/클래스 기반 Blade 컴포넌트 (kebab-case.blade.php)',
                        'views/partials/'     => '부분 템플릿 (헤더/푸터/사이드바)',
                        'views/{domain}/'     => '도메인별 뷰 (예: views/projects/, views/ai-agent/)',
                        'css/'                => 'Tailwind 엔트리 (app.css)',
                        'js/'                 => 'Vite 진입점 (app.js) + Alpine 컴포넌트',
                    ],
                    'naming' => [
                        'view'      => 'kebab-case.blade.php (예: project-detail.blade.php)',
                        'component' => 'kebab-case (예: <x-ai-agent.approval-gate />)',
                        'partial'   => '_접두어 없이 kebab-case (예: breadcrumb.blade.php)',
                        'layout'    => 'kebab-case (예: app.blade.php, ai-agent.blade.php)',
                    ],
                ],
                'examples' => [
                    'tree' => "resources/\n├── views/\n│   ├── layouts/app.blade.php\n│   ├── components/\n│   │   └── ai-agent/approval-gate.blade.php\n│   ├── ai-agent/dashboard.blade.php\n│   └── projects/show.blade.php\n├── css/app.css\n└── js/app.js",
                ],
            ],

            [
                'stack'       => FrontendStack::BLADE,
                'category'    => 'naming',
                'name'        => 'Blade 명명 규칙',
                'description' => 'Blade 파일·컴포넌트·CSS·Alpine 식별자 명명 표준',
                'definition'  => [
                    'view_file'    => 'kebab-case.blade.php',
                    'component'    => '<x-namespace.component-name /> (kebab-case)',
                    'slot_name'    => 'kebab-case (예: $slot, $header)',
                    'prop'         => 'camelCase (PHP) / kebab-case (HTML attribute)',
                    'directive'    => '@directiveName (예: @auth, @can)',
                    'css_class'    => 'Tailwind 유틸리티 우선, 커스텀은 BEM',
                    'alpine_data'  => 'camelCase (예: x-data="{ isOpen: false }")',
                    'route_name'   => 'dot.notation (예: ai-agent.projects.show)',
                ],
                'validation_rules' => [
                    'forbidden_patterns' => ['{!! $userInput !!}', 'eval(', 'unescaped output'],
                    'required_patterns'  => ['@csrf (form)', '{{ }} 이스케이프 출력', 'lang="ko"'],
                    'accessibility'      => ['aria-label', '<label for>', 'alt 필수'],
                ],
            ],

            [
                'stack'       => FrontendStack::BLADE,
                'category'    => 'component',
                'name'        => 'Blade 컴포넌트 패턴',
                'description' => '클래스/익명 Blade 컴포넌트 + Alpine.js 상호작용 표준',
                'definition'  => [
                    'style_approach'   => 'Tailwind CSS 유틸리티 우선, 복잡한 스타일은 @push(\'styles\')',
                    'interactivity'    => 'Alpine.js (x-data, x-show, x-on, x-bind), 단순한 상태는 인라인',
                    'props'            => '@props([\'variant\' => \'primary\', \'size\' => \'md\']) 명시 + 기본값',
                    'class_component'  => 'app/View/Components/* (복잡한 로직 필요할 때만)',
                    'anonymous'        => '단순한 UI는 익명 컴포넌트 (resources/views/components/*)',
                    'slot_pattern'     => '{{ $slot }} + named slots ({{ $header ?? \'\' }})',
                    'data_fetch'       => '컨트롤러에서 데이터 주입, 뷰에서는 출력만',
                    'component_skeleton' => "{{-- resources/views/components/ai-agent/info-card.blade.php --}}\n@props(['title', 'variant' => 'default'])\n\n<div\n    {{ \$attributes->class([\n        'rounded-lg border p-4',\n        'border-blue-200 bg-blue-50' => \$variant === 'info',\n        'border-gray-200 bg-white'   => \$variant === 'default',\n    ]) }}\n    x-data=\"{ collapsed: false }\"\n>\n    <h3 class=\"font-semibold text-gray-900\">{{ \$title }}</h3>\n    <div x-show=\"!collapsed\">{{ \$slot }}</div>\n</div>",
                ],
                'validation_rules' => [
                    'required_patterns'  => ['@props(', '{{ ', '$attributes'],
                    'forbidden_patterns' => ['{!! ', 'echo ', '<?php echo'],
                    'accessibility'      => ['role 명시', 'aria-* 속성', 'focus-visible'],
                ],
            ],

            [
                'stack'       => FrontendStack::BLADE,
                'category'    => 'styling',
                'name'        => 'Blade 스타일링 표준',
                'description' => 'Tailwind CSS + Vite 통합 + Alpine.js 인터랙션 스타일',
                'definition'  => [
                    'framework'      => 'Tailwind CSS 3.x (Vite 통합, @vite([\'resources/css/app.css\', \'resources/js/app.js\']))',
                    'entry'          => 'resources/css/app.css (@tailwind base/components/utilities)',
                    'responsive'     => 'Mobile-first: sm → md → lg → xl',
                    'dark_mode'      => 'Tailwind class 전략 (html.dark) + Alpine 토글',
                    'transition'     => 'Tailwind transition-* 유틸리티 + Alpine x-transition',
                    'css_variables'  => ':root 변수 → tailwind.config.js extend.colors 매핑',
                    'forms'          => '@tailwindcss/forms 플러그인 활용',
                ],
            ],
        ];
    }
}
