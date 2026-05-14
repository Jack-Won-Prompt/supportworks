@extends('layouts.ai-agent')
@section('title', '새 AI 작업 생성 — 웍스 Agent')

@section('ai-agent-content')

@include('ai-agent.agent-sessions.partials.page-header', [
    'badge'    => 'AI Agent',
    'title'    => '새 AI 작업 생성',
    'subtitle' => '이 프로젝트의 Output 유형은 ' . ($config?->frontend_stack?->label() ?? '미설정') . ' 으로 고정되어 있습니다.',
])

@if(!$config)
    <div class="ags-empty">
        이 프로젝트는 AI Agent 설정이 없습니다. 먼저 stack을 지정한 후 다시 시도하세요.
    </div>
@else
<form method="POST" action="{{ route('ai-agent.projects.agent-sessions.store', $project) }}" class="ags-card" style="max-width:560px;">
    @csrf

    <div style="margin-bottom:18px;">
        <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">작업명</label>
        <input type="text" name="title" required maxlength="255"
               placeholder="예: 로그인 화면 Vue 코드 생성"
               style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13.5px;outline:none;"
               value="{{ old('title') }}" />
        @error('title')<small style="color:#b91c1c;font-size:11.5px;">{{ $message }}</small>@enderror
    </div>

    <div style="margin-bottom:18px;">
        <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">Output 유형</label>
        <div style="padding:8px 12px;border:1.5px dashed #e2e8f0;border-radius:8px;font-size:13px;color:#64748b;background:#f8fafc;">
            {{ $config->frontend_stack->label() }} <small>(프로젝트 stack 고정)</small>
        </div>
    </div>

    <div style="margin-bottom:22px;">
        <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">AI Provider</label>
        <select name="ai_provider" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13.5px;background:#fff;">
            <option value="auto" {{ old('ai_provider', 'auto') === 'auto' ? 'selected' : '' }}>auto (기본값: {{ $defaultProvider }})</option>
            @foreach($availableProviders as $p)
                <option value="{{ $p }}" {{ old('ai_provider') === $p ? 'selected' : '' }}>{{ $p }}</option>
            @endforeach
        </select>
        @error('ai_provider')<small style="color:#b91c1c;font-size:11.5px;">{{ $message }}</small>@enderror
    </div>

    <div style="display:flex;gap:8px;">
        <button type="submit" class="ags-btn ags-btn-primary">세션 시작</button>
        <a href="{{ route('ai-agent.projects.agent-sessions.index', $project) }}" class="ags-btn ags-btn-ghost">취소</a>
    </div>
</form>
@endif

@endsection
