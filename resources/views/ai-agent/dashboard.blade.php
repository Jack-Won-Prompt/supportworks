@extends('layouts.app')
@section('title', '웍스 Agent')

@push('styles')
<style>
.aia-dash { max-width: 920px; margin: 0 auto; }
.aia-dash-hero { margin-bottom: 32px; }
.aia-dash-hero h1 { font-size: 24px; font-weight: 800; color: #1e1b2e; margin: 0 0 8px; display: flex; align-items: center; gap: 10px; }
.aia-dash-hero p { font-size: 14px; color: #64748b; line-height: 1.7; margin: 0; max-width: 580px; }
.aia-proj-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
.aia-proj-card { background: #fff; border: 2px solid #ede8ff; border-radius: 16px; padding: 20px; text-decoration: none; transition: all .18s; display: flex; flex-direction: column; gap: 10px; position: relative; overflow: hidden; }
.aia-proj-card:hover { border-color: var(--t400); box-shadow: 0 8px 24px rgba(124,58,237,.1); transform: translateY(-2px); }
.aia-proj-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--t400), var(--t600)); opacity: 0; transition: opacity .18s; }
.aia-proj-card:hover::before { opacity: 1; }
.aia-proj-name { font-size: 15px; font-weight: 700; color: #1e1b2e; line-height: 1.4; }
.aia-proj-meta { font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px; }
.aia-proj-status { font-size: 10.5px; padding: 2px 8px; border-radius: 5px; font-weight: 600; }
.aia-proj-status.active { background: #dcfce7; color: #16a34a; }
.aia-proj-status.on_hold { background: #fef9c3; color: #ca8a04; }
.aia-proj-status.completed { background: #f1f5f9; color: #64748b; }
.aia-proj-action { display: flex; align-items: center; gap: 5px; font-size: 12.5px; font-weight: 600; color: var(--t600); margin-top: 4px; }
.aia-empty { text-align: center; padding: 48px 24px; background: #fff; border: 2px dashed #ddd6fe; border-radius: 16px; }
.aia-empty h3 { font-size: 16px; font-weight: 700; color: #1e1b2e; margin: 12px 0 6px; }
.aia-empty p { font-size: 13px; color: #64748b; margin: 0; }
.aia-section-title { font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; margin: 0 0 14px; display: flex; align-items: center; gap: 8px; }
</style>
@endpush

@section('ai-agent-content')
<div class="aia-dash">

    {{-- 히어로 --}}
    <div class="aia-dash-hero">
        <h1>
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="color:var(--t500);">
                <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                <path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/>
            </svg>
            웍스 개발 에이전트
        </h1>
        <p>프로젝트를 선택하여 웍스 Agent 개발 워크플로우를 시작하세요.<br>기획 → 디자인 → 개발 준비 → 개발 → 릴리즈까지 전 과정을 웍스가 지원합니다.</p>
    </div>

    {{-- 프로젝트 목록 --}}
    <div class="aia-section-title">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        내 프로젝트
    </div>

    @if($projects->isEmpty())
    <div class="aia-empty">
        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#ddd6fe;margin:0 auto;display:block;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <h3>참여 중인 프로젝트가 없습니다</h3>
        <p>프로젝트에 참여하거나 새 프로젝트를 생성하세요.</p>
    </div>
    @else
    <div class="aia-proj-grid">
        @foreach($projects as $project)
        <a href="{{ route('ai-agent.projects.home', $project) }}" class="aia-proj-card">
            <div>
                <div class="aia-proj-name">{{ $project->name }}</div>
                @if($project->description)
                <div class="aia-proj-meta" style="margin-top:4px;">{{ Str::limit($project->description, 60) }}</div>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="aia-proj-status {{ $project->status }}">
                    @if($project->status === 'active') 진행중
                    @elseif($project->status === 'on_hold') 보류
                    @elseif($project->status === 'completed') 완료
                    @else {{ $project->status }}
                    @endif
                </span>
                @if($project->end_date)
                <span class="aia-proj-meta">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ \Carbon\Carbon::parse($project->end_date)->format('Y.m.d') }}
                </span>
                @endif
            </div>
            <div class="aia-proj-action">
                웍스 Agent 시작
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>
        @endforeach
    </div>
    @endif

    {{-- 안내 --}}
    <div style="margin-top:36px;padding:20px 24px;background:#faf5ff;border:1.5px solid var(--t100);border-radius:14px;display:flex;gap:12px;align-items:flex-start;">
        <svg width="20" height="20" fill="none" stroke="var(--t500)" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <div style="font-size:13px;font-weight:700;color:var(--t700);margin-bottom:4px;">웍스 Agent 개발 워크플로우 안내</div>
            <div style="font-size:12.5px;color:#64748b;line-height:1.7;">
                프로젝트를 선택하면 <strong>기획 → 디자인 → 개발 준비 → 개발 → 릴리즈</strong> 순서로 진행됩니다.<br>
                각 단계는 이전 단계 승인 후 활성화됩니다. 현재 화면은 <strong>T15</strong>에서 전체 구현 예정입니다.
            </div>
        </div>
    </div>
</div>
@endsection
