@extends('layouts.ai-agent')
@section('title', '피드백 — Output v' . $output->version_no)

@section('ai-agent-content')

@include('ai-agent.agent-sessions.partials.page-header', [
    'badge'    => 'AI Agent · 피드백',
    'title'    => '검수 / 피드백',
    'subtitle' => 'Output v' . $output->version_no . '의 검수 결과를 입력합니다.',
])

<div style="display:flex;gap:20px;align-items:flex-start;">
    @include('ai-agent.agent-sessions.partials.session-nav', ['active' => 'outputs'])

    <div style="flex:1;display:flex;flex-direction:column;gap:12px;">
        <div class="ags-card">
            <div class="ags-section-title">기존 피드백 ({{ $feedbacks->count() }})</div>
            @if($feedbacks->isEmpty())
                <div style="font-size:12.5px;color:#94a3b8;padding:10px 0;">아직 작성된 피드백이 없습니다.</div>
            @else
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;">
                    @foreach($feedbacks as $fb)
                        <li style="font-size:12.5px;border-left:3px solid var(--t300);padding:6px 10px;background:#f8fafc;border-radius:0 8px 8px 0;">
                            <div style="display:flex;justify-content:space-between;gap:8px;color:#475569;">
                                <strong>{{ $fb->feedback_type->label() }}</strong>
                                <small style="color:#94a3b8;">{{ $fb->created_at->diffForHumans() }}</small>
                            </div>
                            @if($fb->message)
                                <p style="margin:4px 0 0;color:#1e1b2e;line-height:1.5;">{{ $fb->message }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="ags-card" style="border-style:dashed;">
            <p style="font-size:12.5px;color:#94a3b8;margin:0;">
                피드백 입력 폼은 Phase 8 (FeedbackAnalysisService 구현 후) 활성화됩니다.
            </p>
        </div>
    </div>
</div>

@endsection
