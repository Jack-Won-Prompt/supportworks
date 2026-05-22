@extends('layouts.ai-agent')
@section('title', 'AI Agent 설정 — ' . $project->name)

@section('ai-agent-content')

@include('ai-agent.agent-sessions.partials.page-header', [
    'badge'    => 'AI Agent',
    'title'    => 'AI Agent 설정',
    'subtitle' => '이 프로젝트의 AI Agent 세션 기본 설정입니다.',
])

<div class="ags-card" style="max-width:640px;">
    <div class="ags-section-title">현재 설정</div>
    <dl style="display:grid;grid-template-columns:180px 1fr;gap:8px 20px;font-size:13px;color:#475569;margin:0;">
        <dt>Output 유형 (frontend_stack)</dt>
        <dd style="margin:0;color:#1e1b2e;">{{ $config?->frontend_stack?->label() ?? '미설정' }}</dd>

        <dt>사용 가능한 AI Provider</dt>
        <dd style="margin:0;color:#1e1b2e;">{{ implode(', ', $availableProviders) }}</dd>

        <dt>기본 Provider</dt>
        <dd style="margin:0;color:#1e1b2e;">{{ $defaultProvider }}</dd>

        <dt>API 키 미설정 시 Mock</dt>
        <dd style="margin:0;color:#1e1b2e;">{{ $mockEnabled ? '활성화' : '비활성화' }}</dd>
    </dl>
</div>

<p style="font-size:11.5px;color:#94a3b8;margin-top:16px;">
    설정 변경 폼은 Phase 11 이후 활성화됩니다. 현재는 .env 와 config/ai-agent.php 로만 변경 가능합니다.
</p>

@endsection
