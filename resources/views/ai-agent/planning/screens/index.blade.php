@extends('layouts.ai-agent')
@section('title', '작업 항목 — 웍스 Agent')

@push('styles')
<style>
/* ── Screen list ──────────────────────────────────────────── */
.psc-header      { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.psc-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.psc-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.psc-actions     { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.psc-btn         { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; transition:all .15s; border:none; text-decoration:none; }
.psc-btn-primary { background:var(--t600,#7c3aed); color:#fff; }
.psc-btn-primary:hover { background:var(--t700,#6d28d9); color:#fff; }
.psc-btn-outline { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.psc-btn-outline:hover { border-color:#a78bfa; color:var(--t600,#7c3aed); }

/* ── Stats row ──────────────────────────────────────────── */
.psc-stats       { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.psc-stat        { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:12px 18px; display:flex; align-items:center; gap:10px; min-width:130px; }
.psc-stat-num    { font-size:24px; font-weight:800; color:var(--t600,#7c3aed); }
.psc-stat-label  { font-size:11.5px; color:#64748b; line-height:1.4; }

/* ── Filters ─────────────────────────────────────────────── */
.psc-filters     { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; align-items:center; }
.psc-filter-btn  { padding:5px 14px; border-radius:20px; font-size:12px; font-weight:600; cursor:pointer; border:1.5px solid #e2e8f0; background:#fff; color:#64748b; transition:all .12s; }
.psc-filter-btn.active { background:var(--t600,#7c3aed); border-color:var(--t600,#7c3aed); color:#fff; }
.psc-search      { flex:1; min-width:180px; padding:7px 12px 7px 32px; border:1.5px solid #e2e8f0; border-radius:9px; font-size:13px; color:#374151; outline:none; transition:border-color .15s; }
.psc-search:focus { border-color:var(--t400,#a78bfa); }
.psc-search-wrap { position:relative; min-width:180px; flex:1; max-width:280px; }
.psc-search-wrap svg { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:#94a3b8; pointer-events:none; }

/* ── Table ───────────────────────────────────────────────── */
.psc-table-wrap  { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; overflow:hidden; margin-bottom:24px; }
.psc-table       { width:100%; border-collapse:collapse; }
.psc-table th    { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; padding:10px 14px; background:#faf5ff; border-bottom:1.5px solid #ede8ff; text-align:left; }
.psc-table td    { padding:11px 14px; border-bottom:1px solid #f8f5ff; vertical-align:middle; font-size:13px; color:#374151; }
.psc-table tr:last-child td { border-bottom:none; }
.psc-table tr:hover td { background:#faf5ff; }

.psc-scr-id      { font-size:11.5px; font-weight:800; color:var(--t700,#6d28d9); font-family:monospace; white-space:nowrap; }
.psc-title-link  { color:#1e1b2e; font-weight:600; text-decoration:none; transition:color .12s; }
.psc-title-link:hover { color:var(--t600,#7c3aed); }
.psc-source-badge { display:inline-flex; align-items:center; gap:4px; font-size:10.5px; font-weight:600; padding:2px 8px; border-radius:5px; white-space:nowrap; }
.psc-source-badge.gantt  { background:#eff6ff; color:#1d4ed8; }
.psc-source-badge.manual { background:#f0fdf4; color:#166534; }
.psc-status-badge { display:inline-flex; align-items:center; gap:3px; font-size:10.5px; font-weight:600; padding:2px 8px; border-radius:5px; white-space:nowrap; }
.psc-status-badge.draft    { background:#f8fafc; color:#64748b; }
.psc-status-badge.designed { background:#eff6ff; color:#1d4ed8; }
.psc-status-badge.approved { background:#f0fdf4; color:#166534; }

.psc-actions-cell { display:flex; gap:6px; align-items:center; flex-shrink:0; }
.psc-act-btn      { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:6px; font-size:11.5px; font-weight:600; cursor:pointer; border:none; text-decoration:none; transition:all .12s; white-space:nowrap; flex-shrink:0; }
.psc-act-view     { background:var(--t50,#f5f3ff); color:var(--t700,#6d28d9); }
.psc-act-view:hover { background:var(--t100,#ede9fe); }
.psc-act-archive  { background:#fff; color:#94a3b8; border:1px solid #e2e8f0; }
.psc-act-archive:hover { color:#dc2626; border-color:#fca5a5; }

/* ── Archived section ────────────────────────────────────── */
.psc-archived-hdr { display:flex; align-items:center; gap:8px; margin-bottom:10px; cursor:pointer; }
.psc-archived-title { font-size:12px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; }
.psc-archived-hdr::after { content:''; flex:1; height:1px; background:#f1f5f9; }
.psc-act-restore { background:#fff; color:#64748b; border:1px solid #e2e8f0; }
.psc-act-restore:hover { color:#16a34a; border-color:#86efac; }

/* ── Empty state ─────────────────────────────────────────── */
.psc-empty { text-align:center; padding:40px 24px; }
.psc-empty-icon { font-size:36px; margin-bottom:12px; }
.psc-empty h3 { font-size:15px; font-weight:700; color:#1e1b2e; margin:0 0 6px; }
.psc-empty p { font-size:13px; color:#64748b; margin:0 0 16px; }

/* ── Add form ────────────────────────────────────────────── */
.psc-form-row    { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.psc-form-group  { display:flex; flex-direction:column; gap:5px; }
.psc-form-group label { font-size:11.5px; font-weight:700; color:#475569; }
.psc-form-input  { padding:8px 11px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; color:#374151; outline:none; transition:border-color .15s; }
.psc-form-input:focus { border-color:var(--t400,#a78bfa); }
.psc-sync-info   { display:flex; align-items:center; gap:7px; font-size:12px; color:#94a3b8; }
</style>
@endpush

@section('ai-agent-content')
<div x-data="screenList()">

    {{-- 헤더 --}}
    <div class="psc-header">
        <div class="psc-header-left">
            <div style="font-size:11px;font-weight:700;color:var(--t600,#7c3aed);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">단계 1: 기획</div>
            <h1>작업 항목 (화면 목록)</h1>
            <p>Supportworks 간트에서 화면 단위 작업을 가져오거나 직접 추가하여 SCR-XXX 화면 ID를 관리합니다.</p>
        </div>
        <div class="psc-actions">
            <a href="{{ route('ai-agent.projects.planning.sync-gantt.preview', $project) }}" class="psc-btn psc-btn-outline">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                간트에서 동기화
            </a>
            <button class="psc-btn psc-btn-primary" @click="showAddModal = true">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                화면 추가
            </button>
        </div>
    </div>

    {{-- 마지막 동기화 시간 --}}
    @if($lastSyncedAt)
    <div class="psc-sync-info" style="margin-bottom:16px;">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        마지막 간트 동기화: {{ \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() }}
    </div>
    @endif

    {{-- 플래시 --}}
    @if(session('success'))
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:11px 16px;font-size:13px;color:#166534;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- 통계 --}}
    <div class="psc-stats">
        <div class="psc-stat">
            <div>
                <div class="psc-stat-num">{{ $activeScreens->count() }}</div>
                <div class="psc-stat-label">활성 화면</div>
            </div>
        </div>
        <div class="psc-stat">
            <div>
                <div class="psc-stat-num" style="color:#1d4ed8;">{{ $activeScreens->where('source','gantt')->count() }}</div>
                <div class="psc-stat-label">간트 연동</div>
            </div>
        </div>
        <div class="psc-stat">
            <div>
                <div class="psc-stat-num" style="color:#166534;">{{ $activeScreens->where('source','manual')->count() }}</div>
                <div class="psc-stat-label">수동 추가</div>
            </div>
        </div>
        @if($archivedScreens->count() > 0)
        <div class="psc-stat">
            <div>
                <div class="psc-stat-num" style="color:#94a3b8;">{{ $archivedScreens->count() }}</div>
                <div class="psc-stat-label">아카이브</div>
            </div>
        </div>
        @endif
    </div>

    {{-- 필터 + 검색 --}}
    <div class="psc-filters">
        <button class="psc-filter-btn" :class="{ active: sourceFilter === 'all' }"   @click="sourceFilter = 'all'">전체</button>
        <button class="psc-filter-btn" :class="{ active: sourceFilter === 'gantt' }" @click="sourceFilter = 'gantt'">간트</button>
        <button class="psc-filter-btn" :class="{ active: sourceFilter === 'manual'}" @click="sourceFilter = 'manual'">수동</button>
        <div class="psc-search-wrap">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input class="psc-search" type="text" placeholder="화면 검색..." x-model="search">
        </div>
    </div>

    {{-- 활성 화면 테이블 --}}
    <div class="psc-table-wrap">
        @if($activeScreens->isNotEmpty())
        <table class="psc-table">
            <thead>
                <tr>
                    <th style="width:90px;">ID</th>
                    <th>화면명</th>
                    <th style="width:90px;">출처</th>
                    <th style="width:90px;">상태</th>
                    <th style="width:120px;">담당자</th>
                    <th style="width:140px;">일정</th>
                    <th style="width:160px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($activeScreens as $screen)
                <tr x-show="matchesFilter('{{ $screen->source }}', '{{ strtolower($screen->title) }}')">
                    <td><span class="psc-scr-id">{{ $screen->screen_id }}</span></td>
                    <td>
                        <a href="{{ route('ai-agent.projects.planning.screens.show', [$project, $screen]) }}" class="psc-title-link">
                            {{ $screen->title }}
                        </a>
                        @if($screen->description)
                        <div style="font-size:11.5px;color:#94a3b8;margin-top:2px;">{{ Str::limit($screen->description, 50) }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="psc-source-badge {{ $screen->source }}">
                            @if($screen->source === 'gantt')
                            <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            간트
                            @else
                            <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            수동
                            @endif
                        </span>
                    </td>
                    <td>
                        <span class="psc-status-badge {{ $screen->status }}">
                            {{ match($screen->status) { 'draft' => '초안', 'designed' => '디자인됨', 'approved' => '승인됨', default => $screen->status } }}
                        </span>
                    </td>
                    <td style="font-size:12.5px;color:#475569;">
                        {{ $screen->assignee?->name ?? '—' }}
                    </td>
                    <td style="font-size:12px;color:#64748b;white-space:nowrap;">
                        @if($screen->scheduled_start)
                            {{ $screen->scheduled_start->format('m/d') }}
                            @if($screen->scheduled_end) – {{ $screen->scheduled_end->format('m/d') }} @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <div class="psc-actions-cell">
                            <a href="{{ route('ai-agent.projects.planning.screens.show', [$project, $screen]) }}"
                               class="psc-act-btn psc-act-view">보기</a>
                            <form method="POST" action="{{ route('ai-agent.projects.planning.screens.archive', [$project, $screen]) }}"
                                  onsubmit="return confirm('{{ $screen->screen_id }}를 아카이브하시겠습니까?')">
                                @csrf
                                <button type="submit" class="psc-act-btn psc-act-archive">아카이브</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="psc-empty">
            <div class="psc-empty-icon">🖥️</div>
            <h3>등록된 화면이 없습니다</h3>
            <p>간트에서 동기화하거나 직접 화면을 추가해주세요.</p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="{{ route('ai-agent.projects.planning.sync-gantt.preview', $project) }}" class="psc-btn psc-btn-outline">
                    ↻ 간트에서 동기화
                </a>
                <button class="psc-btn psc-btn-primary" @click="showAddModal = true">
                    + 화면 추가
                </button>
            </div>
        </div>
        @endif
    </div>

    {{-- 아카이브된 화면 --}}
    @if($archivedScreens->isNotEmpty())
    <div x-data="{ showArchived: false }">
        <div class="psc-archived-hdr" @click="showArchived = !showArchived">
            <svg :style="showArchived ? 'transform:rotate(90deg)' : ''"
                 style="transition:transform .15s;flex-shrink:0;"
                 width="12" height="12" fill="none" stroke="#94a3b8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="psc-archived-title">아카이브된 화면 ({{ $archivedScreens->count() }}건)</span>
        </div>
        <div x-show="showArchived" x-cloak>
            <div class="psc-table-wrap">
                <table class="psc-table">
                    <thead>
                        <tr>
                            <th style="width:90px;">ID</th>
                            <th>화면명</th>
                            <th style="width:90px;">출처</th>
                            <th style="width:160px;">아카이브 일시</th>
                            <th style="width:80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($archivedScreens as $screen)
                        <tr>
                            <td><span class="psc-scr-id" style="color:#94a3b8;">{{ $screen->screen_id }}</span></td>
                            <td style="color:#94a3b8;">{{ $screen->title }}</td>
                            <td>
                                <span class="psc-source-badge {{ $screen->source }}">
                                    {{ $screen->source === 'gantt' ? '간트' : '수동' }}
                                </span>
                            </td>
                            <td style="font-size:12px;color:#94a3b8;">
                                {{ $screen->archived_at->format('Y.m.d H:i') }}
                            </td>
                            <td>
                                <form method="POST" action="{{ route('ai-agent.projects.planning.screens.restore', [$project, $screen]) }}">
                                    @csrf
                                    <button type="submit" class="psc-act-btn psc-act-restore">복원</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- 화면 추가 모달 --}}
    <div x-show="showAddModal" x-cloak
         style="position:fixed;inset:0;z-index:1050;display:flex;align-items:center;justify-content:center;padding:16px;"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">

        <div style="position:absolute;inset:0;background:rgba(0,0,0,.45);" @click="showAddModal=false"></div>

        <div style="position:relative;background:#fff;border-radius:16px;width:100%;max-width:500px;box-shadow:0 20px 60px rgba(0,0,0,.18);overflow:hidden;">
            <div style="padding:20px 24px 0;display:flex;align-items:center;justify-content:space-between;">
                <div style="font-size:16px;font-weight:800;color:#1e1b2e;">화면 추가</div>
                <button @click="showAddModal=false" style="width:28px;height:28px;border:none;background:#f8fafc;color:#64748b;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('ai-agent.projects.planning.screens.store', $project) }}">
                @csrf
                <div style="padding:20px 24px;display:flex;flex-direction:column;gap:12px;">
                    <div class="psc-form-group" style="grid-column:1/-1;">
                        <label>화면명 <span style="color:#dc2626;">*</span></label>
                        <input class="psc-form-input" type="text" name="title" required placeholder="예: 로그인 화면" value="{{ old('title') }}">
                        @error('title')<div style="font-size:11.5px;color:#dc2626;margin-top:3px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="psc-form-group" style="grid-column:1/-1;">
                        <label>설명</label>
                        <textarea class="psc-form-input" name="description" rows="2" placeholder="화면 설명 (선택)">{{ old('description') }}</textarea>
                    </div>
                    <div class="psc-form-row">
                        <div class="psc-form-group">
                            <label>시작일</label>
                            <input class="psc-form-input" type="date" name="scheduled_start" value="{{ old('scheduled_start') }}">
                        </div>
                        <div class="psc-form-group">
                            <label>종료일</label>
                            <input class="psc-form-input" type="date" name="scheduled_end" value="{{ old('scheduled_end') }}">
                        </div>
                    </div>
                </div>
                <div style="padding:14px 24px 20px;display:flex;justify-content:flex-end;gap:8px;border-top:1.5px solid #f1f5f9;">
                    <button type="button" @click="showAddModal=false" class="psc-btn psc-btn-outline">취소</button>
                    <button type="submit" class="psc-btn psc-btn-primary">화면 추가</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
async function screenList() {
    return {
        showAddModal: {{ $errors->isNotEmpty() ? 'true' : 'false' }},
        sourceFilter: 'all',
        search: '',

        matchesFilter(source, title) {
            if (this.sourceFilter !== 'all' && source !== this.sourceFilter) return false;
            if (this.search && !title.includes(this.search.toLowerCase())) return false;
            return true;
        },
    };
}
</script>
@endpush
