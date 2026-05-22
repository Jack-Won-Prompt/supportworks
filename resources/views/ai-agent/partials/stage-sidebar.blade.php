@php
use App\Enums\Agent\StageStatus;
use App\Services\Agent\ApprovalGateHelper;

$sidebarStages = [
    [
        'value'      => 'planning',
        'section'    => 'planning',
        'label'      => '기획',
        'indexRoute' => 'ai-agent.projects.planning.index',
        'items'      => [
            ['label' => '작업 항목', 'route' => 'ai-agent.projects.planning.index', 'routePattern' => ['ai-agent.projects.planning.screens.*', 'ai-agent.projects.planning.sync-gantt*']],
            ['label' => 'AS-IS 분석',    'route' => 'ai-agent.projects.planning.as-is'],
            ['label' => 'TO-BE 분석',    'route' => 'ai-agent.projects.planning.to-be'],
            ['label' => 'Gap 분석',      'route' => 'ai-agent.projects.planning.gap'],
            ['label' => '웍스 기획서',     'route' => 'ai-agent.projects.planning.document'],
            ['label' => 'IA / 흐름도',   'route' => 'ai-agent.projects.planning.ia'],
            ['label' => '화면 프롬프트', 'route' => 'ai-agent.projects.planning.prompts'],
            ['label' => '웍스 목업',       'route' => 'ai-agent.projects.planning.mockups'],
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
            ['label' => '웍스 Output',     'route' => 'ai-agent.projects.pre-dev.ai-output'],
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
            ['label' => 'Frontend Code', 'route' => 'ai-agent.projects.dev.frontend-code'],
            ['label' => 'Output 검증',   'route' => 'ai-agent.projects.dev.code-validation'],
            ['label' => 'Backend 개발',  'route' => 'ai-agent.projects.dev.backend'],
            ['label' => 'API 연계',      'route' => 'ai-agent.projects.dev.api-connect'],
            ['label' => '웍스 코드 리뷰',  'route' => 'ai-agent.projects.dev.code-review', 'routePattern' => ['ai-agent.projects.dev.code-review.*']],
            ['label' => '웍스 추가 수정',  'route' => 'ai-agent.projects.dev.additional-fix', 'routePattern' => ['ai-agent.projects.dev.additional-fix.*']],
            ['label' => '승인 요청',     'route' => 'ai-agent.projects.dev.approval'],
        ],
    ],
    [
        'value'      => 'release',
        'section'    => 'release',
        'label'      => '릴리즈',
        'indexRoute' => 'ai-agent.projects.release',
        'items'      => [
            ['label' => '통합 패키지',        'route' => 'ai-agent.projects.release.package.index'],
            ['label' => '배포 가이드',        'route' => 'ai-agent.projects.release.deploy-guide.index'],
            ['label' => '사용자 매뉴얼',      'route' => 'ai-agent.projects.release.user-manual.index'],
            ['label' => '마이그레이션 가이드', 'route' => 'ai-agent.projects.release.migration-guide.index'],
            ['label' => '승인 요청',          'route' => 'ai-agent.projects.release.approval.index'],
        ],
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
                {!! ApprovalGateHelper::getStageStatusIcon($status) !!}

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
                        @php
                            $extraPatterns = $item['routePattern'] ?? [];
                            $isCurrent = request()->routeIs($item['route'])
                                || (!empty($extraPatterns) && request()->routeIs(...(array) $extraPatterns));
                        @endphp
                        <a href="{{ $isLocked ? '#' : route($item['route'], $aiProject) }}"
                           class="aia-sidebar-item{{ $isCurrent ? ' is-current' : '' }}{{ $isLocked ? ' is-disabled' : '' }}">
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
           style="padding-left:14px;">웍스 사용량</a>
        <a href="{{ route('ai-agent.projects.common.permissions', $aiProject) }}"
           class="aia-sidebar-item{{ request()->routeIs('ai-agent.projects.common.permissions') ? ' is-current' : '' }}"
           style="padding-left:14px;">권한 관리</a>
    </div>

    {{-- Settings section --}}
    <div style="margin: 4px 14px; border-top: 1px dashed #e2d9f3;"></div>
    <div style="padding: 4px 14px 8px;">
        <div style="font-size:10px;font-weight:700;color:#b0b8c9;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">설정</div>
        <button @click="$dispatch('figma-settings-open')"
                class="aia-sidebar-item{{ request()->routeIs('ai-agent.settings.figma*') ? ' is-current' : '' }}"
                style="padding-left:14px;display:flex;align-items:center;gap:4px;background:none;border:none;cursor:pointer;width:100%;text-align:left;">
            <svg width="11" height="11" viewBox="0 0 38 57" fill="none" style="flex-shrink:0;">
                <path d="M19 28.5a9.5 9.5 0 1 1 19 0 9.5 9.5 0 0 1-19 0z" fill="#1ABCFE"/>
                <path d="M0 47.5A9.5 9.5 0 0 1 9.5 38H19v9.5a9.5 9.5 0 0 1-19 0z" fill="#0ACF83"/>
                <path d="M19 0v19h9.5a9.5 9.5 0 0 0 0-19H19z" fill="#FF7262"/>
                <path d="M0 9.5A9.5 9.5 0 0 0 9.5 19H19V0H9.5A9.5 9.5 0 0 0 0 9.5z" fill="#F24E1E"/>
                <path d="M0 28.5A9.5 9.5 0 0 0 9.5 38H19V19H9.5A9.5 9.5 0 0 0 0 28.5z" fill="#A259FF"/>
            </svg>
            Figma 연동
        </button>
    </div>
</nav>
