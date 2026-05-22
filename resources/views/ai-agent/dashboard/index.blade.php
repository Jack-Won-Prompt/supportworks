@extends('layouts.ai-agent')
@section('title', '웍스 개발 에이전트')

@push('styles')
<style>
/* ── Mode A: Project Selection ───────────────────────────────── */
.aad-wrap { max-width: 980px; margin: 0 auto; padding: 28px 20px 48px; }

.aad-hero { margin-bottom: 32px; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.aad-hero-text h1 { font-size: 24px; font-weight: 800; color: #1e1b2e; margin: 0 0 6px; display: flex; align-items: center; gap: 10px; }
.aad-hero-text p { font-size: 13.5px; color: #64748b; line-height: 1.7; margin: 0; }
.aad-new-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px; background: var(--t600, #7c3aed); color: #fff; border: none; border-radius: 10px; font-size: 13.5px; font-weight: 700; cursor: pointer; text-decoration: none; transition: background .15s; white-space: nowrap; flex-shrink: 0; }
.aad-new-btn:hover { background: var(--t700, #6d28d9); color: #fff; }

/* Section header */
.aad-section-hdr { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
.aad-section-title { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .07em; }
.aad-section-hdr::after { content: ''; flex: 1; height: 1px; background: #f1f5f9; }

/* Search */
.aad-search-wrap { margin-bottom: 16px; position: relative; }
.aad-search-wrap svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
.aad-search { width: 100%; padding: 8px 12px 8px 32px; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 13px; color: #374151; outline: none; transition: border-color .15s; }
.aad-search:focus { border-color: var(--t400, #a78bfa); }

/* Project cards grid */
.aad-proj-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
.aad-proj-card { background: #fff; border: 2px solid #ede8ff; border-radius: 16px; padding: 18px 20px; text-decoration: none; transition: all .18s; display: flex; flex-direction: column; gap: 10px; position: relative; overflow: hidden; }
.aad-proj-card:hover { border-color: var(--t400, #a78bfa); box-shadow: 0 8px 24px rgba(124,58,237,.1); transform: translateY(-2px); }
.aad-proj-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--t400), var(--t600)); opacity: 0; transition: opacity .18s; }
.aad-proj-card:hover::before { opacity: 1; }

.aad-proj-name { font-size: 14px; font-weight: 700; color: #1e1b2e; line-height: 1.4; }
.aad-proj-desc { font-size: 12px; color: #94a3b8; line-height: 1.5; }
.aad-stack-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 5px; background: var(--t100, #ede9fe); color: var(--t700, #6d28d9); }
.aad-progress-row { display: flex; align-items: center; gap: 8px; }
.aad-progress-bar { flex: 1; height: 5px; background: #f1f5f9; border-radius: 10px; overflow: hidden; }
.aad-progress-fill { height: 100%; background: linear-gradient(90deg, var(--t400), var(--t600)); border-radius: 10px; transition: width .3s; }
.aad-progress-pct { font-size: 11px; font-weight: 700; color: var(--t600, #7c3aed); flex-shrink: 0; }
.aad-stage-label { font-size: 11px; color: #64748b; }
.aad-proj-arrow { display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; color: var(--t600, #7c3aed); margin-top: 2px; }

/* Disabled project list */
.aad-disabled-list { display: flex; flex-direction: column; gap: 8px; }
.aad-disabled-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #fff; border: 1.5px solid #f1f5f9; border-radius: 12px; text-decoration: none; transition: border-color .12s; }
.aad-disabled-item:hover { border-color: #e2e8f0; }
.aad-disabled-name { flex: 1; font-size: 13px; font-weight: 500; color: #374151; }
.aad-disabled-meta { font-size: 11px; color: #94a3b8; }
.aad-enable-btn { font-size: 11.5px; font-weight: 600; color: var(--t600, #7c3aed); background: var(--t50, #f5f3ff); border: 1px solid var(--t200, #ddd6fe); padding: 4px 12px; border-radius: 7px; cursor: pointer; text-decoration: none; white-space: nowrap; transition: all .12s; }
.aad-enable-btn:hover { background: var(--t100, #ede9fe); }

/* Empty state */
.aad-empty { text-align: center; padding: 40px 24px; background: #fff; border: 2px dashed #ddd6fe; border-radius: 16px; }
.aad-empty h3 { font-size: 15px; font-weight: 700; color: #1e1b2e; margin: 12px 0 6px; }
.aad-empty p { font-size: 13px; color: #64748b; margin: 0 0 16px; }
</style>
@endpush

@section('ai-agent-content')
<div class="aad-wrap" x-data="dashboardHome()">

    {{-- 히어로 --}}
    <div class="aad-hero">
        <div class="aad-hero-text">
            <h1>
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="color:var(--t500,#8b5cf6);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M19 17v4M3 5h4M17 19h4"/>
                </svg>
                웍스 개발 에이전트
            </h1>
            <p>프로젝트를 선택하여 기획 → 디자인 → 개발 준비 → 개발 → 릴리즈까지 전 과정을 웍스와 함께 진행하세요.</p>
        </div>
        <button class="aad-new-btn" @click="openModal()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            새 웍스 Agent 프로젝트
        </button>
    </div>

    {{-- session 플래시는 전역 토스트(window.appToast)로 표시됨 --}}

    {{-- ── 웍스 Agent 활성 프로젝트 ── --}}
    @if($enabledProjects->isNotEmpty())
    <div style="margin-bottom:36px;">
        <div class="aad-section-hdr">
            <span class="aad-section-title">웍스 Agent 활성 프로젝트</span>
            <span style="font-size:11px;color:#94a3b8;">{{ $enabledProjects->count() }}개</span>
        </div>

        <div class="aad-search-wrap">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input class="aad-search" type="text" placeholder="프로젝트 검색..." x-model="search">
        </div>

        <div class="aad-proj-grid">
            @foreach($enabledProjects as $item)
            @php $p = $item['project']; $cfg = $item['config']; $prog = $item['progress']; @endphp
            <a href="{{ route('ai-agent.projects.home', $p) }}"
               class="aad-proj-card"
               x-show="!search || '{{ strtolower($p->name) }}'.includes(search.toLowerCase())">
                <div>
                    <div class="aad-proj-name">{{ $p->name }}</div>
                    @if($p->description)
                    <div class="aad-proj-desc" style="margin-top:3px;">{{ Str::limit($p->description, 55) }}</div>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    @if($cfg->frontend_stack)
                    <span class="aad-stack-badge">{{ $cfg->frontend_stack->label() }}</span>
                    @endif
                    @if($p->end_date)
                    <span style="font-size:11px;color:#94a3b8;">{{ \Carbon\Carbon::parse($p->end_date)->format('Y.m.d') }}</span>
                    @endif
                </div>
                <div>
                    <div class="aad-progress-row">
                        <div class="aad-progress-bar">
                            <div class="aad-progress-fill" style="width:{{ $prog }}%"></div>
                        </div>
                        <span class="aad-progress-pct">{{ $prog }}%</span>
                    </div>
                </div>
                <div class="aad-proj-arrow">
                    계속하기
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── 다른 Supportworks 프로젝트 ── --}}
    @if($disabledProjects->isNotEmpty())
    <div>
        <div class="aad-section-hdr">
            <span class="aad-section-title">다른 Supportworks 프로젝트</span>
            <span style="font-size:11px;color:#94a3b8;">웍스 Agent 미사용 · {{ $disabledProjects->count() }}개</span>
        </div>
        <div class="aad-disabled-list">
            @foreach($disabledProjects as $p)
            <div class="aad-disabled-item">
                <div>
                    <svg width="14" height="14" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div class="aad-disabled-name">{{ $p->name }}</div>
                @if($p->end_date)
                <span class="aad-disabled-meta">{{ \Carbon\Carbon::parse($p->end_date)->format('Y.m.d') }}</span>
                @endif
                <button class="aad-enable-btn" @click="openModalFor({{ $p->id }}, '{{ addslashes($p->name) }}')">
                    웍스 Agent 시작
                </button>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- 빈 상태 --}}
    @if($enabledProjects->isEmpty() && $disabledProjects->isEmpty())
    <div class="aad-empty">
        <svg width="40" height="40" fill="none" stroke="#ddd6fe" viewBox="0 0 24 24" style="margin:0 auto;display:block;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3>참여 중인 프로젝트가 없습니다</h3>
        <p>프로젝트에 참여하거나 새 프로젝트를 생성해주세요.</p>
        <a href="{{ route('dashboard') }}" style="display:inline-flex;align-items:center;gap:4px;font-size:13px;font-weight:600;color:var(--t600);">
            대시보드로 이동 →
        </a>
    </div>
    @endif

    {{-- 신규 프로젝트 모달 --}}
    @include('ai-agent.dashboard.partials.new-project-modal', ['selectableProjects' => $selectableProjects])

</div>
@endsection

@push('scripts')
<script>
function dashboardHome() {
    return {
        search: '',
        showModal: false,
        selectedProjectId: null,
        selectedStack: null,
        preselectedName: '',
        submitting: false,

        openModal(projectId = null, projectName = '') {
            this.selectedProjectId = projectId;
            this.preselectedName   = projectName;
            this.selectedStack     = null;
            this.showModal         = true;
        },

        openModalFor(id, name) {
            this.openModal(id, name);
        },

        closeModal() {
            this.showModal = false;
        },

        canSubmit() {
            return this.selectedProjectId && this.selectedStack;
        },
    };
}
</script>
@endpush
