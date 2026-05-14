@extends('layouts.ai-agent')
@section('title', '충돌 / 위험 검토 — ' . $session->title)

@section('ai-agent-content')

@include('ai-agent.agent-sessions.partials.page-header', [
    'badge'    => 'AI Agent · 세션',
    'title'    => '충돌 / 위험 검토',
    'subtitle' => '확정 산출물과의 충돌 또는 위험 요소를 검토하고 결정합니다.',
])

<div style="display:flex;gap:20px;align-items:flex-start;">
    @include('ai-agent.agent-sessions.partials.session-nav', ['active' => 'conflicts'])

    <div style="flex:1;">
        @if($conflicts->isEmpty())
            <div class="ags-empty">
                감지된 충돌이 없습니다.<br>
                <small>Phase 9 이후 ConflictDetectionService가 Output 생성 시 자동 감지합니다.</small>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($conflicts as $c)
                    <div class="ags-card">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                            <div>
                                <strong style="font-size:13.5px;">{{ $c->conflict_type->label() }}</strong>
                                @if($c->output)
                                    <small style="color:#94a3b8;"> · Output v{{ $c->output->version_no }}</small>
                                @endif
                                <p style="font-size:12.5px;color:#475569;margin:6px 0 0;line-height:1.6;">{{ $c->description }}</p>
                            </div>
                            <span class="ags-tag ags-tag-{{ $c->severity->blocksConfirmation() ? 'failed' : 'attention' }}">
                                {{ $c->severity->label() }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@endsection
