@extends('layouts.app')

@push('styles')
<style>
[x-cloak] { display: none !important; }

/* ── 웍스 Agent layout ─────────────────────────────────────── */
.aia-layout { display: flex; flex-direction: column; }

/* ── Top navigation ──────────────────────────────────────── */
.aia-topnav { background: #fff; border-bottom: 1.5px solid #ede8ff; flex-shrink: 0; }

.aia-topnav-row1 {
    display: flex; align-items: center; gap: 0;
    padding: 0 20px; height: 46px;
}

.aia-topnav-brand {
    display: flex; align-items: center; gap: 7px;
    font-size: 13px; font-weight: 800; color: #1e1b2e;
    text-decoration: none; white-space: nowrap;
    padding-right: 16px; border-right: 1.5px solid #ede8ff;
    margin-right: 16px; flex-shrink: 0;
    transition: color .15s;
}
.aia-topnav-brand:hover { color: var(--t600); }
.aia-topnav-brand svg { flex-shrink: 0; }

.aia-topnav-sep {
    color: #d1c9f0; font-size: 16px; margin: 0 10px; flex-shrink: 0;
}
.aia-topnav-proj {
    display: flex; align-items: center; gap: 7px;
    font-size: 13px; font-weight: 700; color: #1e1b2e;
    text-decoration: none; transition: color .15s;
    overflow: hidden; white-space: nowrap; text-overflow: ellipsis;
}
.aia-topnav-proj:hover { color: var(--t600); }
.aia-topnav-proj-status {
    display: inline-block; width: 7px; height: 7px;
    border-radius: 50%; flex-shrink: 0;
}
.aia-topnav-proj-status.active    { background: #16a34a; }
.aia-topnav-proj-status.on_hold   { background: #ca8a04; }
.aia-topnav-proj-status.completed { background: #94a3b8; }

.aia-topnav-meta {
    margin-left: auto; display: flex; align-items: center;
    gap: 8px; flex-shrink: 0;
}
.aia-stack-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10.5px; font-weight: 700; padding: 2px 8px;
    border-radius: 5px; background: var(--t100); color: var(--t700);
}

/* ── Stage tabs row ──────────────────────────────────────── */
.aia-stage-tabs {
    display: flex; align-items: stretch;
    border-top: 1px solid #f3eeff; padding: 0 20px;
    overflow-x: auto; scrollbar-width: none;
}
.aia-stage-tabs::-webkit-scrollbar { display: none; }

.aia-stage-tab {
    display: flex; align-items: center; gap: 6px;
    padding: 7px 16px; font-size: 12px; font-weight: 600;
    color: #64748b; text-decoration: none; white-space: nowrap;
    border-bottom: 2.5px solid transparent;
    transition: all .15s; flex-shrink: 0;
}
.aia-stage-tab:hover:not(.is-locked) { color: var(--t600); background: #f8f4ff; }
.aia-stage-tab.is-active  { color: var(--t700); border-bottom-color: var(--t500); }
.aia-stage-tab.is-locked  { color: #c8d0db; cursor: default; pointer-events: none; }

.aia-stage-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #cbd5e1; flex-shrink: 0;
}
.aia-stage-tab.is-approved .aia-stage-dot  { background: #16a34a; }
.aia-stage-tab.is-pending  .aia-stage-dot  { background: #f59e0b; }
.aia-stage-tab.is-active:not(.is-approved):not(.is-pending) .aia-stage-dot { background: var(--t500); }

.aia-stage-sep {
    width: 1px; background: #f0ebff; margin: 10px 0; flex-shrink: 0;
}

/* ── Body (sidebar + main) ──────────────────────────────── */
.aia-layout-body { display: flex; flex: 1; min-height: 0; }

/* ── Stage sidebar ──────────────────────────────────────── */
.aia-sidebar {
    width: 210px; flex-shrink: 0; background: #faf5ff;
    border-right: 1.5px solid #ede8ff; overflow-y: auto;
}
.aia-sidebar-stage { border-bottom: 1px solid #f0ebff; }
.aia-sidebar-stage-btn {
    width: 100%; display: flex; align-items: center; gap: 7px;
    padding: 10px 14px; border: none; background: none;
    cursor: pointer; text-align: left; transition: background .12s;
}
.aia-sidebar-stage-btn:hover { background: #f3eeff; }
.aia-sidebar-stage.is-active > .aia-sidebar-stage-btn { background: #ede8ff; }
.aia-sidebar-stage.is-locked > .aia-sidebar-stage-btn { cursor: default; }
.aia-sidebar-stage-label { font-size: 12.5px; font-weight: 700; color: #1e1b2e; flex: 1; line-height: 1; }
.aia-sidebar-stage.is-locked .aia-sidebar-stage-label { color: #b0b8c9; }
.aia-sidebar-subnav { padding: 2px 0 6px; }
.aia-sidebar-item {
    display: block; padding: 5px 14px 5px 34px;
    font-size: 11.5px; color: #64748b; text-decoration: none;
    transition: all .1s; border-left: 2px solid transparent;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.aia-sidebar-item:hover:not(.is-disabled) { color: var(--t600); background: #f3eeff; }
.aia-sidebar-item.is-current { color: var(--t700); font-weight: 700; border-left-color: var(--t500); background: #ede8ff; }
.aia-sidebar-item.is-disabled { pointer-events: none; color: #c8d0db; }

/* ── Main content ───────────────────────────────────────── */
.aia-main { flex: 1; min-width: 0; padding: 22px 26px; overflow-y: auto; }

/* ── Breadcrumb ─────────────────────────────────────────── */
.aia-breadcrumb { display: flex; align-items: center; gap: 5px; font-size: 12px; color: #94a3b8; margin-bottom: 18px; flex-wrap: wrap; }
.aia-breadcrumb a { color: #94a3b8; text-decoration: none; transition: color .12s; }
.aia-breadcrumb a:hover { color: var(--t600); }

/* ── Page actions bar ───────────────────────────────────── */
.aia-page-actions { display: flex; gap: 8px; align-items: center; margin-bottom: 18px; flex-wrap: wrap; }
</style>
@endpush

@php
use App\Enums\Agent\StageStatus;
use App\Services\Agent\ApprovalGateHelper;

$_ataStages = [
    ['value' => 'planning',     'section' => 'planning',     'label' => '기획',     'indexRoute' => 'ai-agent.projects.planning.index',     'type' => 'stage'],
    ['value' => 'design',       'section' => 'design',       'label' => '디자인',   'indexRoute' => 'ai-agent.projects.design.index',       'type' => 'stage'],
    ['value' => 'dev_prep',     'section' => 'pre-dev',      'label' => '개발 준비','indexRoute' => 'ai-agent.projects.pre-dev.index',      'type' => 'stage'],
    ['value' => 'development',  'section' => 'dev',          'label' => '개발',     'indexRoute' => 'ai-agent.projects.dev.index',          'type' => 'stage'],
    ['value' => 'release',         'section' => 'release',         'label' => '릴리즈',   'indexRoute' => 'ai-agent.projects.release',                'type' => 'stage'],
    ['value' => 'deliverables',    'section' => 'deliverables',    'label' => '산출물',   'indexRoute' => 'ai-agent.projects.deliverables.index',     'type' => 'feature'],
    ['value' => 'agent-sessions',  'section' => 'agent-sessions',  'label' => 'AI Agent', 'indexRoute' => 'ai-agent.projects.agent-sessions.index',   'type' => 'feature'],
];
@endphp

@section('content')
<div class="aia-layout" style="height:calc(100vh - 56px);overflow:hidden;">

    {{-- ── 상단 네비게이션 ────────────────────────────────── --}}
    <div class="aia-topnav">
        <div class="aia-topnav-row1">

            {{-- 브랜드 --}}
            <a href="{{ route('ai-agent.dashboard') }}" class="aia-topnav-brand">
                <svg width="15" height="15" fill="none" stroke="var(--t500)" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                </svg>
                웍스 Agent
            </a>

            {{-- 프로젝트명 + 전환 드롭다운 --}}
            @if($aiProject ?? null)
                <svg width="14" height="14" fill="none" stroke="#d1c9f0" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <div x-data="{ open: false }" @click.outside="open = false" style="position:relative;display:flex;align-items:center;">
                    <button type="button" @click="open = !open" class="aia-topnav-proj" style="background:none;border:none;cursor:pointer;padding:0;">
                        <span class="aia-topnav-proj-status {{ $aiProject->status }}"></span>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;">{{ $aiProject->name }}</span>
                        <svg x-bind:style="open ? 'transform:rotate(180deg)' : ''"
                             style="transition:transform .15s;flex-shrink:0;opacity:.45;margin-left:2px;"
                             width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak
                         style="position:absolute;top:calc(100% + 8px);left:0;z-index:300;background:#fff;border:1.5px solid #ede8ff;border-radius:12px;min-width:230px;box-shadow:0 8px 28px rgba(0,0,0,.12);overflow:hidden;">
                        <div style="padding:8px 14px;font-size:10.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #f1f5f9;">
                            프로젝트 전환
                        </div>
                        @forelse($aiSwitchProjects ?? [] as $sp)
                            <a href="{{ route('ai-agent.projects.home', $sp) }}"
                               style="display:flex;align-items:center;gap:9px;padding:9px 14px;font-size:13px;color:#374151;text-decoration:none;transition:background .1s;"
                               onmouseover="this.style.background='#faf5ff'" onmouseout="this.style.background=''">
                                <span style="width:6px;height:6px;border-radius:50%;flex-shrink:0;background:{{ $sp->status === 'active' ? '#16a34a' : ($sp->status === 'on_hold' ? '#ca8a04' : '#94a3b8') }};"></span>
                                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">{{ $sp->name }}</span>
                            </a>
                        @empty
                            <div style="padding:10px 14px;font-size:12px;color:#94a3b8;">다른 웍스 Agent 프로젝트 없음</div>
                        @endforelse
                        <div style="border-top:1px solid #f1f5f9;padding:4px;">
                            <a href="{{ route('ai-agent.dashboard', ['force_home' => 1]) }}"
                               style="display:flex;align-items:center;gap:7px;padding:7px 10px;font-size:12px;color:#94a3b8;text-decoration:none;border-radius:8px;transition:all .1s;"
                               onmouseover="this.style.background='#f8f4ff';this.style.color='var(--t600)'" onmouseout="this.style.background='';this.style.color='#94a3b8'">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                                전체 프로젝트 목록
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 메타 정보 (우측) --}}
                <div class="aia-topnav-meta">
                    @if(($aiConfig ?? null)?->frontend_stack)
                        <span class="aia-stack-badge">
                            <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ $aiConfig->frontend_stack->label() }}
                        </span>
                    @endif
                    @if($aiProject->end_date)
                        <span style="font-size:11px;color:#94a3b8;">{{ \Carbon\Carbon::parse($aiProject->end_date)->format('Y.m.d') }}</span>
                    @endif
                    <button @click="$dispatch('figma-settings-open')"
                            style="display:flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:6px;border:1.5px solid #ede8ff;color:#94a3b8;transition:all .15s;background:none;cursor:pointer;"
                            title="Figma 설정"
                            onmouseover="this.style.borderColor='var(--t400)';this.style.color='var(--t600)'"
                            onmouseout="this.style.borderColor='#ede8ff';this.style.color='#94a3b8'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            @else
                {{-- 프로젝트 없을 때 우측 설정 버튼 --}}
                <div class="aia-topnav-meta">
                    <button @click="$dispatch('figma-settings-open')"
                            style="font-size:12px;color:#94a3b8;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;transition:color .15s;"
                            onmouseover="this.style.color='var(--t600)'" onmouseout="this.style.color='#94a3b8'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                        설정
                    </button>
                </div>
            @endif
        </div>

        {{-- ── 단계 탭 (프로젝트 선택 시) ─────────────────── --}}
        @if($aiProject ?? null)
        <div class="aia-stage-tabs">
            @foreach($_ataStages as $st)
            @php
                $isFeature  = ($st['type'] ?? 'stage') === 'feature';
                $rec        = $isFeature ? null : ($aiStages ?? collect())->get($st['value']);
                $status     = $rec?->status;
                $isLocked   = !$isFeature && (!$rec || $status === StageStatus::LOCKED);
                $isApproved = !$isFeature && $status === StageStatus::APPROVED;
                $isPending  = !$isFeature && $status === StageStatus::PENDING_APPROVAL;
                $isCurrent  = ($aiCurrentSection ?? '') === $st['section'];

                $tabClass = 'aia-stage-tab';
                if ($isCurrent)  $tabClass .= ' is-active';
                if ($isLocked)   $tabClass .= ' is-locked';
                if ($isApproved) $tabClass .= ' is-approved';
                if ($isPending)  $tabClass .= ' is-pending';
                if ($isFeature)  $tabClass .= ' is-feature';
            @endphp
            {{-- 기능 탭(산출물 등) 앞에 구분선 --}}
            @if($isFeature && !$loop->first)
                <div class="aia-stage-sep" style="margin-left:4px;margin-right:4px;"></div>
            @endif
            <a href="{{ $isLocked ? '#' : route($st['indexRoute'], $aiProject) }}"
               class="{{ $tabClass }}"
               @if($isFeature) style="color:{{ $isCurrent ? 'var(--t700)' : '#64748b' }};" @endif>
                @if($isFeature)
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                @else
                    <span class="aia-stage-dot"></span>
                @endif
                {{ $st['label'] }}
                @if($isApproved)
                    <svg width="10" height="10" fill="#16a34a" viewBox="0 0 20 20" style="flex-shrink:0;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                @elseif($isPending)
                    <svg width="10" height="10" fill="none" stroke="#f59e0b" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @elseif($isLocked)
                    <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                @endif
            </a>
            @if(!$loop->last && !$isFeature && ($loop->remaining > 1 || ($loop->remaining === 1 && ($_ataStages[$loop->index + 1]['type'] ?? 'stage') !== 'feature')))
                <div class="aia-stage-sep"></div>
            @endif
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── 바디 (사이드바 + 메인) ─────────────────────────── --}}
    <div class="aia-layout-body" style="flex:1;overflow:hidden;">
        @if($aiProject ?? null)
            @include('ai-agent.partials.stage-sidebar')
        @endif

        <main class="aia-main">
            @include('ai-agent.partials.breadcrumb')

            @hasSection('page-actions')
                <div class="aia-page-actions">@yield('page-actions')</div>
            @endif

            @yield('ai-agent-content')
        </main>
    </div>

</div>

@include('ai-agent.partials.figma-settings-modal')
@endsection
