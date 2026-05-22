@extends('layouts.ai-agent')
@section('title', 'AI Agent 작업 대시보드 — 웍스 Agent')

@section('ai-agent-content')

@include('ai-agent.agent-sessions.partials.page-header', [
    'badge'    => 'AI Agent',
    'title'    => '작업 대시보드',
    'subtitle' => '디자인 소스에서 코드 Output까지 — 세션 단위로 진행 상황을 관리합니다.',
])

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <p class="ags-section-title" style="margin:0;">진행 중인 세션 ({{ $sessions->count() }})</p>
    <a href="{{ route('ai-agent.projects.agent-sessions.create', $project) }}" class="ags-btn ags-btn-primary">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        새 AI 작업 생성
    </a>
</div>

@if($sessions->isEmpty())
    <div class="ags-empty">
        아직 생성된 AI Agent 세션이 없습니다.<br>
        <small>오른쪽 위의 <b>새 AI 작업 생성</b> 버튼으로 시작하세요.</small>
    </div>
@else
    <div class="ags-grid">
        @foreach($sessions as $s)
            @php
                $status = $s->status;
                $tag    = match (true) {
                    $status->isTerminal() && $status->value === 'confirmed' => 'ags-tag-confirmed',
                    $status->value === 'failed'        => 'ags-tag-failed',
                    $status->isRunning()               => 'ags-tag-running',
                    $status->needsUserAction()         => 'ags-tag-attention',
                    default                            => 'ags-tag-draft',
                };
            @endphp
            <a href="{{ route('ai-agent.projects.agent-sessions.show', [$project, $s]) }}" class="ags-card" style="text-decoration:none;color:inherit;display:block;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px;">
                    <strong style="font-size:14px;color:#1e1b2e;line-height:1.4;">{{ $s->title }}</strong>
                    <span class="ags-tag {{ $tag }}">{{ $status->label() }}</span>
                </div>
                <div style="font-size:11.5px;color:#64748b;margin-bottom:8px;">
                    {{ $s->output_type->label() }} · 단계: {{ $s->current_step->label() }}
                </div>
                <div style="font-size:11px;color:#94a3b8;">
                    @if($s->user) {{ $s->user->name }} @endif
                    @if($s->last_activity_at) · {{ $s->last_activity_at->diffForHumans() }} @endif
                </div>
            </a>
        @endforeach
    </div>
@endif

@endsection
