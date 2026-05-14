@extends('layouts.ai-agent')
@section('title', '디자인 구조 분석 — ' . $session->title)

@section('ai-agent-content')

@include('ai-agent.agent-sessions.partials.page-header', [
    'badge'    => 'AI Agent · 세션',
    'title'    => '디자인 구조 분석',
    'subtitle' => 'AI가 제안한 분석 단계와 구현 범위를 선택합니다.',
])

<div style="display:flex;gap:20px;align-items:flex-start;">
    @include('ai-agent.agent-sessions.partials.session-nav', ['active' => 'analysis'])

    <div style="flex:1;">
        @if($steps->isEmpty())
            <div class="ags-empty">
                아직 생성된 분석 단계가 없습니다.<br>
                <small>Phase 5 이후 디자인 소스가 연결되면 AI가 자동으로 분석 단계를 제안합니다.</small>
            </div>
        @else
            <ol style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;">
                @foreach($steps as $step)
                    <li class="ags-card">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                            <div>
                                <strong style="font-size:13.5px;">{{ $loop->iteration }}. {{ $step->title }}</strong>
                                @if($step->description)
                                    <p style="font-size:12.5px;color:#475569;line-height:1.6;margin:6px 0 0;">{{ $step->description }}</p>
                                @endif
                            </div>
                            <span class="ags-tag ags-tag-{{ match($step->status) {
                                'done' => 'confirmed',
                                'user_input_required' => 'attention',
                                'in_progress' => 'running',
                                'failed' => 'failed',
                                default => 'draft',
                            } }}">{{ $step->status }}</span>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>

@endsection
