@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
.aia-stage-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; background: var(--t100); color: var(--t700); margin-bottom: 10px; }
.aia-title { font-size: 22px; font-weight: 800; color: #1e1b2e; margin: 0 0 8px; }
.aia-desc { font-size: 14px; color: #64748b; line-height: 1.7; margin: 0 0 28px; max-width: 640px; }
.aia-placeholder-box { background: #fff; border: 2px dashed #ddd6fe; border-radius: 16px; padding: 36px 32px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 14px; }
.aia-placeholder-icon { width: 52px; height: 52px; border-radius: 14px; background: var(--t50); display: flex; align-items: center; justify-content: center; }
.aia-placeholder-title { font-size: 15px; font-weight: 700; color: #1e1b2e; margin: 0; }
.aia-placeholder-msg { font-size: 13px; color: #64748b; line-height: 1.6; margin: 0; }
.aia-placeholder-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 20px; background: #fef3c7; color: #92400e; font-size: 12px; font-weight: 600; }
.aia-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
.aia-meta-item { font-size: 11.5px; padding: 3px 10px; border-radius: 6px; background: var(--t50); color: var(--t700); font-weight: 500; }
.aia-nav-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 28px; }
.aia-nav-card { background: #fff; border: 1.5px solid #ede8ff; border-radius: 12px; padding: 14px 16px; text-decoration: none; transition: all .15s; display: flex; flex-direction: column; gap: 5px; }
.aia-nav-card:hover { border-color: var(--t400); box-shadow: 0 4px 14px rgba(124,58,237,.08); transform: translateY(-1px); }
.aia-nav-card-label { font-size: 10px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
.aia-nav-card-title { font-size: 13px; font-weight: 700; color: #1e1b2e; }
.aia-nav-card-task { font-size: 11px; color: #a78bfa; font-weight: 600; }
</style>
@endpush

@section('ai-agent-content')

    {{-- Stage badge + page title --}}
    @if($stageLabel)
    <div class="aia-stage-badge">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        {{ $stageLabel }}
    </div>
    @endif

    <h1 class="aia-title">{{ $pageTitle }}</h1>
    <p class="aia-desc">{{ $description }}</p>

    {{-- 플레이스홀더 박스 --}}
    <div class="aia-placeholder-box">
        <div class="aia-placeholder-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="color:var(--t500);">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
            </svg>
        </div>
        <p class="aia-placeholder-title">구현 예정 화면</p>
        <p class="aia-placeholder-msg">
            이 화면은 현재 작업 계획에 따라 향후 구현될 예정입니다.<br>
            라우팅 골격이 완성된 상태로, 다음 작업에서 실제 기능이 추가됩니다.
        </p>
        <span class="aia-placeholder-badge">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $taskId }} 에서 구현 예정
        </span>
        <div class="aia-meta">
            <span class="aia-meta-item">작업 ID: {{ $taskId }}</span>
            <span class="aia-meta-item">spec.md §{{ $specSection }}</span>
            <span class="aia-meta-item">프로젝트: {{ $aiProject->name }}</span>
        </div>
    </div>

    {{-- 빠른 이동 --}}
    <div style="margin-top:36px;">
        <div style="font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;margin-bottom:12px;">빠른 이동</div>
        <div class="aia-nav-grid">
            <a href="{{ route('ai-agent.projects.planning.index', $aiProject) }}" class="aia-nav-card">
                <span class="aia-nav-card-label">단계 1</span>
                <span class="aia-nav-card-title">🎯 기획</span>
                <span class="aia-nav-card-task">T16–T26</span>
            </a>
            <a href="{{ route('ai-agent.projects.design.index', $aiProject) }}" class="aia-nav-card">
                <span class="aia-nav-card-label">단계 2</span>
                <span class="aia-nav-card-title">🎨 디자인</span>
                <span class="aia-nav-card-task">T27–T35</span>
            </a>
            <a href="{{ route('ai-agent.projects.pre-dev.index', $aiProject) }}" class="aia-nav-card">
                <span class="aia-nav-card-label">단계 3</span>
                <span class="aia-nav-card-title">⚙️ 개발 준비</span>
                <span class="aia-nav-card-task">T36–T42</span>
            </a>
            <a href="{{ route('ai-agent.projects.dev.index', $aiProject) }}" class="aia-nav-card">
                <span class="aia-nav-card-label">단계 4</span>
                <span class="aia-nav-card-title">💻 개발</span>
                <span class="aia-nav-card-task">T43–T47</span>
            </a>
            <a href="{{ route('ai-agent.projects.release', $aiProject) }}" class="aia-nav-card">
                <span class="aia-nav-card-label">최종</span>
                <span class="aia-nav-card-title">📦 릴리즈</span>
                <span class="aia-nav-card-task">T48</span>
            </a>
            <a href="{{ route('ai-agent.projects.common.traceability', $aiProject) }}" class="aia-nav-card">
                <span class="aia-nav-card-label">공통</span>
                <span class="aia-nav-card-title">🔧 공통 기능</span>
                <span class="aia-nav-card-task">T07, T08, T14, T49</span>
            </a>
        </div>
    </div>

@endsection
