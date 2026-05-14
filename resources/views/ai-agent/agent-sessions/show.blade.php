@extends('layouts.ai-agent')
@section('title', $session->title . ' — AI Agent')

@section('ai-agent-content')

@include('ai-agent.agent-sessions.partials.page-header', [
    'badge'    => 'AI Agent · 세션',
    'title'    => $session->title,
    'subtitle' => 'Output 유형 ' . $session->output_type->label() . ' · 상태 ' . $session->status->label() . ' · 단계 ' . $session->current_step->label(),
])

<div style="display:flex;gap:20px;align-items:flex-start;">
    @include('ai-agent.agent-sessions.partials.session-nav', ['active' => 'overview'])

    <div style="flex:1;display:flex;flex-direction:column;gap:14px;">
        <div class="ags-card">
            <div class="ags-section-title">메타 정보</div>
            <dl style="display:grid;grid-template-columns:140px 1fr;gap:6px 18px;font-size:12.5px;color:#475569;margin:0;">
                <dt>세션 ID</dt><dd style="margin:0;color:#1e1b2e;">#{{ $session->id }}</dd>
                <dt>생성자</dt><dd style="margin:0;color:#1e1b2e;">{{ $session->user?->name ?? '—' }}</dd>
                <dt>AI Provider</dt><dd style="margin:0;color:#1e1b2e;">{{ $session->ai_provider }}</dd>
                <dt>마지막 활동</dt><dd style="margin:0;color:#1e1b2e;">{{ $session->last_activity_at?->diffForHumans() ?? '—' }}</dd>
                <dt>Figma 소스</dt><dd style="margin:0;color:#1e1b2e;">{{ $session->activeFigmaSource?->figma_file_key ?? '미연결' }}</dd>
                <dt>Output 버전</dt><dd style="margin:0;color:#1e1b2e;">{{ $session->outputs->count() }}개</dd>
            </dl>
        </div>

        <div class="ags-card">
            <div class="ags-section-title">다음 액션</div>
            <p style="font-size:13px;color:#475569;line-height:1.6;margin:0 0 12px;">
                @if($session->status->needsUserAction())
                    이 세션은 <b>사용자 확인이 필요</b>합니다. 해당 단계 화면으로 이동해 결정해 주세요.
                @elseif($session->status->isRunning())
                    백그라운드 작업이 진행 중입니다. 잠시 후 새로고침해 주세요.
                @elseif($session->status->isTerminal())
                    이 세션은 종료되었습니다.
                @else
                    Phase 4 이후 단계별 실제 액션이 활성화됩니다.
                @endif
            </p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('ai-agent.projects.agent-sessions.source', [$project, $session]) }}" class="ags-btn ags-btn-ghost">디자인 소스 보기</a>
                <a href="{{ route('ai-agent.projects.agent-sessions.outputs.index', [$project, $session]) }}" class="ags-btn ags-btn-ghost">Output 보기</a>
                @if($session->isEditable() && $session->user_id === auth()->id())
                    <form method="POST" action="{{ route('ai-agent.projects.agent-sessions.destroy', [$project, $session]) }}" onsubmit="return confirm('세션을 삭제하시겠습니까?');" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="ags-btn ags-btn-danger-ghost">삭제</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
