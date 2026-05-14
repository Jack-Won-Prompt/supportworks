@extends('layouts.ai-agent')
@section('title', 'Figma 소스 — ' . $session->title)

@section('ai-agent-content')

@include('ai-agent.agent-sessions.partials.page-header', [
    'badge'    => 'AI Agent · 세션',
    'title'    => 'Figma / 디자인 소스',
    'subtitle' => '세션에 연결된 Figma 파일/노드 또는 디자인 자료를 관리합니다.',
])

<div style="display:flex;gap:20px;align-items:flex-start;">
    @include('ai-agent.agent-sessions.partials.session-nav', ['active' => 'source'])

    <div style="flex:1;">
        @if($sources->isEmpty())
            <div class="ags-empty">
                연결된 디자인 소스가 없습니다.<br>
                <small>Phase 4 이후 Figma URL 입력 폼이 활성화됩니다 (FigmaSourceService).</small>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($sources as $src)
                    <div class="ags-card">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                            <div>
                                <strong style="font-size:13.5px;">{{ $src->figma_file_key ?? '(file_key 미설정)' }}</strong>
                                @if($src->figma_node_id)
                                    <small style="color:#94a3b8;"> · node {{ $src->figma_node_id }}</small>
                                @endif
                                <div style="font-size:11.5px;color:#64748b;margin-top:4px;word-break:break-all;">
                                    {{ $src->figma_url ?? '—' }}
                                </div>
                            </div>
                            <span class="ags-tag ags-tag-{{ $src->isConnected() ? 'confirmed' : 'attention' }}">{{ $src->status }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@endsection
