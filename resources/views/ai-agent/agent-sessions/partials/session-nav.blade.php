{{-- 세션 상세 페이지의 좌측 진행 네비게이션 partial --}}
{{-- 사용: @include('ai-agent.agent-sessions.partials.session-nav', ['project' => $project, 'session' => $session, 'active' => 'source']) --}}

@php
    $items = [
        ['key' => 'overview',  'label' => '개요',                'route' => 'ai-agent.projects.agent-sessions.show'],
        ['key' => 'source',    'label' => 'Figma / 디자인 소스', 'route' => 'ai-agent.projects.agent-sessions.source'],
        ['key' => 'analysis',  'label' => '디자인 구조 분석',     'route' => 'ai-agent.projects.agent-sessions.analysis'],
        ['key' => 'outputs',   'label' => 'Output 생성/버전',     'route' => 'ai-agent.projects.agent-sessions.outputs.index'],
        ['key' => 'conflicts', 'label' => '충돌 / 위험 검토',     'route' => 'ai-agent.projects.agent-sessions.conflicts.index'],
    ];
@endphp

<nav class="ags-card" style="padding:14px 16px;min-width:220px;">
    <div class="ags-section-title" style="margin-bottom:10px;">세션 진행</div>
    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:4px;">
        @foreach($items as $it)
            @php $isActive = ($active ?? '') === $it['key']; @endphp
            <li>
                <a href="{{ route($it['route'], [$project, $session]) }}"
                   style="display:block;padding:7px 10px;font-size:12.5px;text-decoration:none;border-radius:7px;
                          color:{{ $isActive ? 'var(--t700)' : '#475569' }};
                          background:{{ $isActive ? 'var(--t50)' : 'transparent' }};
                          font-weight:{{ $isActive ? 700 : 500 }};">
                    {{ $it['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
