@extends('layouts.ai-agent')
@section('title', $screen->screen_id . ' — 웍스 Agent')

@push('styles')
<style>
.psc-show-wrap  { max-width:800px; }
.psc-show-hdr   { display:flex; align-items:flex-start; gap:14px; margin-bottom:24px; flex-wrap:wrap; }
.psc-id-chip    { font-size:13px; font-weight:800; color:var(--t700,#6d28d9); background:var(--t50,#f5f3ff); border:1.5px solid var(--t200,#ddd6fe); border-radius:8px; padding:6px 14px; font-family:monospace; white-space:nowrap; flex-shrink:0; }
.psc-show-title { font-size:22px; font-weight:800; color:#1e1b2e; margin:0; }
.psc-show-badges { display:flex; gap:7px; flex-wrap:wrap; margin-top:6px; }

.psc-card       { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:16px; }
.psc-card-title { font-size:12px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.07em; margin-bottom:14px; display:flex; align-items:center; gap:7px; }
.psc-card-title::after { content:''; flex:1; height:1px; background:#f1f5f9; }
.psc-meta-grid  { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:12px; }
.psc-meta-item  { display:flex; flex-direction:column; gap:3px; }
.psc-meta-label { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; }
.psc-meta-value { font-size:13.5px; color:#374151; font-weight:500; }

.psc-source-badge { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; padding:3px 9px; border-radius:5px; }
.psc-source-badge.gantt  { background:#eff6ff; color:#1d4ed8; }
.psc-source-badge.manual { background:#f0fdf4; color:#166534; }
.psc-status-badge { display:inline-flex; align-items:center; gap:3px; font-size:11px; font-weight:600; padding:3px 9px; border-radius:5px; }
.psc-status-badge.draft    { background:#f8fafc; color:#64748b; }
.psc-status-badge.designed { background:#eff6ff; color:#1d4ed8; }
.psc-status-badge.approved { background:#f0fdf4; color:#166534; }

.psc-form-group { display:flex; flex-direction:column; gap:5px; margin-bottom:14px; }
.psc-form-group label { font-size:11.5px; font-weight:700; color:#475569; }
.psc-form-input { padding:8px 11px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; color:#374151; outline:none; transition:border-color .15s; }
.psc-form-input:focus { border-color:var(--t400,#a78bfa); }
.psc-form-row   { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.psc-btn        { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; transition:all .15s; border:none; text-decoration:none; }
.psc-btn-primary { background:var(--t600,#7c3aed); color:#fff; }
.psc-btn-primary:hover { background:var(--t700,#6d28d9); }
.psc-btn-outline { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.psc-btn-outline:hover { border-color:#a78bfa; color:var(--t600,#7c3aed); }
.psc-btn-danger  { background:#fff; color:#dc2626; border:1.5px solid #fca5a5; }
.psc-btn-danger:hover  { background:#fef2f2; }

.psc-gantt-link { display:flex; align-items:center; gap:8px; padding:10px 14px; background:#eff6ff; border:1.5px solid #bfdbfe; border-radius:10px; font-size:13px; color:#1d4ed8; text-decoration:none; font-weight:500; }
.psc-gantt-link:hover { background:#dbeafe; }

.psc-figma-box  { border:1.5px solid #ede8ff; border-radius:10px; padding:12px 14px; background:#faf8ff; }
.psc-figma-row  { display:flex; gap:6px; align-items:baseline; margin-bottom:6px; font-size:12.5px; }
.psc-figma-row:last-child { margin-bottom:0; }
.psc-figma-lbl  { color:#94a3b8; font-weight:600; min-width:72px; flex-shrink:0; }
.psc-figma-val  { color:#374151; font-weight:500; word-break:break-all; }
.psc-figma-link { display:inline-flex; align-items:center; gap:4px; color:#7c3aed; text-decoration:none; font-weight:600; }
.psc-figma-link:hover { text-decoration:underline; }
.psc-layout-chip{ display:inline-flex; align-items:center; gap:4px; background:#f5f3ff; color:#6d28d9; border:1px solid #ddd6fe; border-radius:6px; padding:3px 9px; font-size:11.5px; font-weight:600; margin:2px; }
</style>
@endpush

@section('page-actions')
<a href="{{ route('ai-agent.projects.planning.index', $project) }}" class="psc-btn psc-btn-outline">
    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    목록으로
</a>
@endsection

@section('ai-agent-content')
<div class="psc-show-wrap">

    {{-- 플래시 --}}
    @if(session('success'))
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:11px 16px;font-size:13px;color:#166534;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- 헤더 --}}
    <div class="psc-show-hdr">
        <div class="psc-id-chip">{{ $screen->screen_id }}</div>
        <div>
            <h1 class="psc-show-title">{{ $screen->title }}</h1>
            <div class="psc-show-badges">
                <span class="psc-source-badge {{ $screen->source }}">
                    {{ $screen->source === 'gantt' ? '📅 간트 연동' : '✏️ 수동 추가' }}
                </span>
                <span class="psc-status-badge {{ $screen->status }}">
                    {{ match($screen->status) { 'draft' => '초안', 'designed' => '디자인됨', 'approved' => '승인됨', default => $screen->status } }}
                </span>
                @if($screen->isArchived())
                <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 9px;border-radius:5px;background:#f1f5f9;color:#64748b;">
                    🗄️ 아카이브됨
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- 메타 정보 --}}
    <div class="psc-card">
        <div class="psc-card-title">화면 정보</div>
        <div class="psc-meta-grid">
            <div class="psc-meta-item">
                <span class="psc-meta-label">화면 ID</span>
                <span class="psc-meta-value" style="font-family:monospace;color:var(--t700,#6d28d9);">{{ $screen->screen_id }}</span>
            </div>
            <div class="psc-meta-item">
                <span class="psc-meta-label">담당자</span>
                <span class="psc-meta-value">{{ $screen->assignee?->name ?? '미지정' }}</span>
            </div>
            <div class="psc-meta-item">
                <span class="psc-meta-label">시작일</span>
                <span class="psc-meta-value">{{ $screen->scheduled_start?->format('Y.m.d') ?? '—' }}</span>
            </div>
            <div class="psc-meta-item">
                <span class="psc-meta-label">종료일</span>
                <span class="psc-meta-value">{{ $screen->scheduled_end?->format('Y.m.d') ?? '—' }}</span>
            </div>
            <div class="psc-meta-item">
                <span class="psc-meta-label">등록일</span>
                <span class="psc-meta-value">{{ $screen->created_at->format('Y.m.d') }}</span>
            </div>
        </div>
        @if($screen->description)
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid #f1f5f9;">
            <div class="psc-meta-label" style="margin-bottom:5px;">설명</div>
            <div style="font-size:13.5px;color:#374151;line-height:1.7;white-space:pre-line;">{{ $screen->description }}</div>
        </div>
        @endif
    </div>

    {{-- 간트 연동 정보 --}}
    @if($screen->ganttTask)
    <div class="psc-card">
        <div class="psc-card-title">간트 작업 연동</div>
        <a href="{{ route('schedules.show', $screen->ganttTask) }}" class="psc-gantt-link" target="_blank">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            {{ $screen->ganttTask->title }}
            <span style="margin-left:auto;font-size:11.5px;opacity:.7;">
                {{ $screen->ganttTask->status_label }} · {{ $screen->ganttTask->priority_label }}
            </span>
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
    </div>
    @endif

    {{-- 편집 폼 --}}
    @if(!$screen->isArchived())
    <div class="psc-card" x-data="{ editing: false }">
        <div class="psc-card-title">
            정보 편집
            <button @click="editing = !editing" class="psc-btn psc-btn-outline" style="padding:4px 12px;font-size:12px;margin-left:8px;">
                <span x-text="editing ? '취소' : '편집'"></span>
            </button>
        </div>

        <div x-show="!editing" class="psc-meta-grid">
            <div class="psc-meta-item">
                <span class="psc-meta-label">화면명</span>
                <span class="psc-meta-value">{{ $screen->title }}</span>
            </div>
            @if($screen->source === 'manual')
            <div class="psc-meta-item" style="grid-column:1/-1;">
                <span class="psc-meta-label">설명</span>
                <span class="psc-meta-value">{{ $screen->description ?: '—' }}</span>
            </div>
            @endif
        </div>

        <form x-show="editing" x-cloak method="POST" action="{{ route('ai-agent.projects.planning.screens.update', [$project, $screen]) }}">
            @csrf
            @method('PUT')
            <div class="psc-form-group">
                <label>화면명 <span style="color:#dc2626;">*</span></label>
                <input class="psc-form-input" type="text" name="title" required value="{{ old('title', $screen->title) }}">
            </div>
            <div class="psc-form-group">
                <label>설명</label>
                <textarea class="psc-form-input" name="description" rows="3">{{ old('description', $screen->description) }}</textarea>
            </div>
            <div class="psc-form-row" style="margin-bottom:16px;">
                <div class="psc-form-group" style="margin-bottom:0;">
                    <label>시작일</label>
                    <input class="psc-form-input" type="date" name="scheduled_start" value="{{ old('scheduled_start', $screen->scheduled_start?->format('Y-m-d')) }}">
                </div>
                <div class="psc-form-group" style="margin-bottom:0;">
                    <label>종료일</label>
                    <input class="psc-form-input" type="date" name="scheduled_end" value="{{ old('scheduled_end', $screen->scheduled_end?->format('Y-m-d')) }}">
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="psc-btn psc-btn-primary">저장</button>
                <button type="button" @click="editing = false" class="psc-btn psc-btn-outline">취소</button>
            </div>
        </form>
    </div>
    @endif

    {{-- Figma 매핑 (T31) --}}
    <div class="psc-card">
        <div class="psc-card-title">Figma 연결</div>
        @if($screen->hasFigmaMapping())
        <div class="psc-figma-box">
            <div class="psc-figma-row">
                <span class="psc-figma-lbl">프레임</span>
                <span class="psc-figma-val">{{ $screen->figma_frame_name }}</span>
            </div>
            <div class="psc-figma-row">
                <span class="psc-figma-lbl">노드 ID</span>
                <span class="psc-figma-val" style="font-family:monospace;font-size:11.5px;">{{ $screen->figma_frame_id }}</span>
            </div>
            <div class="psc-figma-row">
                <span class="psc-figma-lbl">매핑일</span>
                <span class="psc-figma-val">{{ $screen->figma_mapped_at?->format('Y.m.d H:i') ?? '—' }}</span>
            </div>
            <div class="psc-figma-row">
                <span class="psc-figma-lbl">링크</span>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <a href="{{ $screen->getFigmaViewUrl() }}" target="_blank" class="psc-figma-link">
                        Figma 보기
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    @if($screen->hasDevModeUrl())
                    <a href="{{ $screen->getFigmaDevModeUrl() }}" target="_blank" class="psc-figma-link" style="color:#0369a1;">
                        Dev Mode
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    @endif
                </div>
            </div>
        </div>

        @php $appliedLayouts = $screen->getAppliedLayouts(); @endphp
        @if(!empty($appliedLayouts))
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid #f1f5f9;">
            <div class="psc-meta-label" style="margin-bottom:8px;">적용된 표준 레이아웃</div>
            <div>
                @foreach($appliedLayouts as $layout)
                <span class="psc-layout-chip">{{ $layout['name'] }}</span>
                @endforeach
            </div>
        </div>
        @endif

        <div style="margin-top:12px;">
            <a href="{{ route('ai-agent.projects.design.screens', $project) }}" class="psc-btn psc-btn-outline" style="font-size:12px;padding:5px 12px;">
                매핑 관리
            </a>
        </div>
        @else
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <span style="font-size:13px;color:#94a3b8;">Figma 프레임이 연결되지 않았습니다.</span>
            <a href="{{ route('ai-agent.projects.design.screens', $project) }}" class="psc-btn psc-btn-outline" style="font-size:12px;padding:5px 12px;">
                매핑하러 가기
            </a>
        </div>
        @endif
    </div>

    {{-- 추적성 뷰어 (T14 컴포넌트) --}}
    <div class="psc-card">
        <div class="psc-card-title">추적성</div>
        <x-ai-agent.traceability-viewer
            source-type="screen"
            :source-id="$screen->id"
            :source-ref="$screen->screen_id"
            :links-url="route('ai-agent.projects.traceability.links', [$project, 'screen', $screen->id])"
            :impact-url="route('ai-agent.projects.traceability.impact', [$project, 'screen', $screen->id])"
        />
    </div>

    {{-- 아카이브 / 복원 --}}
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">
        @if(!$screen->isArchived())
        <form method="POST" action="{{ route('ai-agent.projects.planning.screens.archive', [$project, $screen]) }}"
              onsubmit="return confirm('이 화면을 아카이브하시겠습니까? 연결된 산출물 참조는 보존됩니다.')">
            @csrf
            <button type="submit" class="psc-btn psc-btn-danger">🗄️ 아카이브</button>
        </form>
        @else
        <form method="POST" action="{{ route('ai-agent.projects.planning.screens.restore', [$project, $screen]) }}">
            @csrf
            <button type="submit" class="psc-btn psc-btn-outline">↩️ 복원</button>
        </form>
        @endif
    </div>

</div>
@endsection
