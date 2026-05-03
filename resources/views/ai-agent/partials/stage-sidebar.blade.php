@php
use App\Enums\Agent\StageStatus;

$sidebarStages = [
    [
        'value'      => 'planning',
        'section'    => 'planning',
        'label'      => '기획',
        'indexRoute' => 'ai-agent.projects.planning.index',
        'items'      => [
            ['label' => 'AS-IS 분석',    'route' => 'ai-agent.projects.planning.as-is'],
            ['label' => 'TO-BE 분석',    'route' => 'ai-agent.projects.planning.to-be'],
            ['label' => 'Gap 분석',      'route' => 'ai-agent.projects.planning.gap'],
            ['label' => 'AI 기획서',     'route' => 'ai-agent.projects.planning.document'],
            ['label' => 'IA / 흐름도',   'route' => 'ai-agent.projects.planning.ia'],
            ['label' => '화면 프롬프트', 'route' => 'ai-agent.projects.planning.prompts'],
            ['label' => 'AI 목업',       'route' => 'ai-agent.projects.planning.mockups'],
            ['label' => '승인 요청',     'route' => 'ai-agent.projects.planning.approval'],
        ],
    ],
    [
        'value'      => 'design',
        'section'    => 'design',
        'label'      => '디자인',
        'indexRoute' => 'ai-agent.projects.design.index',
        'items'      => [
            ['label' => 'Design Token',  'route' => 'ai-agent.projects.design.tokens'],
            ['label' => 'Component 명세','route' => 'ai-agent.projects.design.components'],
            ['label' => 'Layout / Grid', 'route' => 'ai-agent.projects.design.layout'],
            ['label' => '화면 매핑',     'route' => 'ai-agent.projects.design.screens'],
            ['label' => '디자인 검수',   'route' => 'ai-agent.projects.design.validation'],
            ['label' => '디자인 시스템', 'route' => 'ai-agent.projects.design.system'],
            ['label' => 'Figma Dev URL', 'route' => 'ai-agent.projects.design.figma-dev'],
            ['label' => '승인 요청',     'route' => 'ai-agent.projects.design.approval'],
        ],
    ],
    [
        'value'      => 'dev_prep',
        'section'    => 'pre-dev',
        'label'      => '개발 준비',
        'indexRoute' => 'ai-agent.projects.pre-dev.index',
        'items'      => [
            ['label' => 'ERD',           'route' => 'ai-agent.projects.pre-dev.erd'],
            ['label' => 'API 명세',      'route' => 'ai-agent.projects.pre-dev.api-spec'],
            ['label' => '권한 모델',     'route' => 'ai-agent.projects.pre-dev.rbac'],
            ['label' => '코드 프롬프트', 'route' => 'ai-agent.projects.pre-dev.code-prompts'],
            ['label' => 'AI Output',     'route' => 'ai-agent.projects.pre-dev.ai-output'],
            ['label' => 'Output 검증',   'route' => 'ai-agent.projects.pre-dev.validation'],
            ['label' => '승인 요청',     'route' => 'ai-agent.projects.pre-dev.approval'],
        ],
    ],
    [
        'value'      => 'development',
        'section'    => 'dev',
        'label'      => '개발',
        'indexRoute' => 'ai-agent.projects.dev.index',
        'items'      => [
            ['label' => 'Backend 개발',  'route' => 'ai-agent.projects.dev.backend'],
            ['label' => 'API 연계',      'route' => 'ai-agent.projects.dev.api-connect'],
            ['label' => 'AI 코드 리뷰',  'route' => 'ai-agent.projects.dev.code-review'],
            ['label' => 'AI 추가 수정',  'route' => 'ai-agent.projects.dev.ai-tasks'],
            ['label' => '승인 요청',     'route' => 'ai-agent.projects.dev.approval'],
        ],
    ],
    [
        'value'      => 'release',
        'section'    => 'release',
        'label'      => '릴리즈',
        'indexRoute' => 'ai-agent.projects.release',
        'items'      => [],
    ],
];
@endphp

<nav class="aia-sidebar">
    @foreach($sidebarStages as $stage)
        @php
            $rec        = $aiStages->get($stage['value']) ?? null;
            $status     = $rec?->status;
            $isLocked   = !$rec || $status === StageStatus::LOCKED;
            $isPending  = $status === StageStatus::PENDING_APPROVAL;
            $isApproved = $status === StageStatus::APPROVED;
            $isActive   = $aiCurrentSection === $stage['section'];
            $hasItems   = count($stage['items']) > 0;
        @endphp

        <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }"
             class="aia-sidebar-stage{{ $isLocked ? ' is-locked' : '' }}{{ $isActive ? ' is-active' : '' }}">

            {{-- Stage header button --}}
            @if(!$hasItems && !$isLocked)
                <a href="{{ route($stage['indexRoute'], $aiProject) }}" class="aia-sidebar-stage-btn">
            @else
                <button type="button"
                        @if(!$isLocked && $hasItems) x-on:click="open = !open" @endif
                        class="aia-sidebar-stage-btn">
            @endif

                {{-- Status icon --}}
                @if($isApproved)
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @elseif($isPending)
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @elseif(!$isLocked)
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z"/></svg>
                @else
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                @endif

                <span class="aia-sidebar-stage-label">{{ $stage['label'] }}</span>

                @if(!$isLocked && $hasItems)
                    <svg x-bind:style="open ? 'transform:rotate(180deg)' : ''"
                         style="transition:transform .18s;flex-shrink:0;"
                         width="11" height="11" fill="none" stroke="#94a3b8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                @endif

            @if(!$hasItems && !$isLocked)
                </a>
            @else
                </button>
            @endif

            {{-- Sub-items --}}
            @if($hasItems)
                <div x-show="open" x-cloak class="aia-sidebar-subnav">
                    @foreach($stage['items'] as $item)
                        <a href="{{ $isLocked ? '#' : route($item['route'], $aiProject) }}"
                           class="aia-sidebar-item{{ request()->routeIs($item['route']) ? ' is-current' : '' }}{{ $isLocked ? ' is-disabled' : '' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif

        </div>
    @endforeach

    {{-- Common section divider --}}
    <div style="margin: 8px 14px; border-top: 1px dashed #e2d9f3;"></div>
    <div style="padding: 4px 14px 4px;">
        <div style="font-size:10px;font-weight:700;color:#b0b8c9;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">공통 기능</div>
        <a href="{{ route('ai-agent.projects.common.traceability', $aiProject) }}"
           class="aia-sidebar-item{{ request()->routeIs('ai-agent.projects.common.traceability') ? ' is-current' : '' }}"
           style="padding-left:14px;">추적성 매트릭스</a>
        <a href="{{ route('ai-agent.projects.common.versions', $aiProject) }}"
           class="aia-sidebar-item{{ request()->routeIs('ai-agent.projects.common.versions') ? ' is-current' : '' }}"
           style="padding-left:14px;">버전 이력</a>
        <a href="{{ route('ai-agent.projects.common.prompts', $aiProject) }}"
           class="aia-sidebar-item{{ request()->routeIs('ai-agent.projects.common.prompts') ? ' is-current' : '' }}"
           style="padding-left:14px;">프롬프트 라이브러리</a>
        <a href="{{ route('ai-agent.projects.common.usage', $aiProject) }}"
           class="aia-sidebar-item{{ request()->routeIs('ai-agent.projects.common.usage') ? ' is-current' : '' }}"
           style="padding-left:14px;">AI 사용량</a>
        <a href="{{ route('ai-agent.projects.common.permissions', $aiProject) }}"
           class="aia-sidebar-item{{ request()->routeIs('ai-agent.projects.common.permissions') ? ' is-current' : '' }}"
           style="padding-left:14px;">권한 관리</a>
    </div>
</nav>
