@extends('layouts.app')
@section('title', 'SR 요청 — 간트')

@section('breadcrumb')
<span style="color:#374151;font-weight:500;">SR 요청</span>
<span>›</span>
<span style="color:#374151;font-weight:500;">간트 보기</span>
@endsection

@section('header-actions')@endsection

@section('content')
<style>
:root { --lw:380px; --row-h:34px; --grp-h:38px; --hdr-h:52px; --bar-h:20px; --imp-bar-h:0px; }
html, body { overflow: hidden !important; height: 100% !important; }
body > div:not(#impersonate-bar), .min-h-screen { height: calc(100vh - var(--imp-bar-h)) !important; overflow: hidden !important; }
.flex-1.flex.flex-col { height: calc(100vh - var(--imp-bar-h)) !important; overflow: hidden !important; }
main { overflow:hidden !important; display:flex !important; flex-direction:column !important; padding-bottom:4px !important; height:0 !important; flex:1 !important; }
main > nav { flex-shrink:0; }
#g-wrap { flex:1; min-height:0; display:flex; flex-direction:column; overflow:hidden; padding-top:16px; }

/* SR 리스트 필터바 — index 와 동일한 스타일 */
.maint-multi { position:relative; }
.maint-multi-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; font-size:13px; color:#374151; cursor:pointer; }
.maint-multi-btn:hover { background:#f9fafb; }
.maint-multi-btn.is-active { border-color:#a5b4fc; color:#4338ca; background:#eef2ff; }
.maint-multi-btn svg { color:#9ca3af; flex-shrink:0; }
.maint-multi-pop { display:none; position:absolute; top:calc(100% + 4px); left:0; z-index:50; min-width:180px; background:#fff; border:1px solid #e5e7eb; border-radius:9px; box-shadow:0 10px 28px rgba(0,0,0,.1); padding:8px; }
.maint-multi.open .maint-multi-pop { display:block; }
.maint-multi-actions { display:flex; gap:4px; padding-bottom:6px; margin-bottom:6px; border-bottom:1px solid #f3f4f6; }
.maint-multi-actions button { flex:1; padding:4px 8px; font-size:11.5px; border:1px solid #e5e7eb; background:#f9fafb; color:#6b7280; border-radius:6px; cursor:pointer; }
.maint-multi-actions button:hover { background:#f3f4f6; color:#374151; }
.maint-multi-list { max-height:240px; overflow-y:auto; display:flex; flex-direction:column; gap:1px; }
.maint-multi-list label { display:flex; align-items:center; gap:7px; padding:5px 8px; font-size:12.5px; color:#374151; border-radius:5px; cursor:pointer; }
.maint-multi-list label:hover { background:#f5f3ff; }
.maint-multi-list input[type=checkbox] { margin:0; cursor:pointer; accent-color:#7c3aed; }
.maint-multi-apply { width:100%; margin-top:6px; padding:6px; font-size:12px; font-weight:600; background:#4f46e5; color:#fff; border:none; border-radius:6px; cursor:pointer; }
.maint-multi-apply:hover { filter:brightness(.95); }

/* 간트 페이지의 필터바 — 줄어든 패딩 */
.sr-filter-bar { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:10px; flex-shrink:0; }
.sr-filter-bar input, .sr-filter-bar select { padding:7px 10px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; background:#fff; outline:none; }
.sr-filter-bar input:focus, .sr-filter-bar select:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.sr-filter-bar .btn-go { padding:7px 16px; background:#f3f4f6; color:#374151; border:none; border-radius:8px; font-size:13px; font-weight:500; cursor:pointer; }
.sr-filter-bar .btn-go:hover { background:#e5e7eb; }
.sr-view-tab { padding:7px 14px; font-size:13px; font-weight:600; border-radius:7px; border:1.5px solid #e5e7eb; text-decoration:none; color:#6b7280; background:#fff; transition:all .12s; }
.sr-view-tab.active { background:#eef2ff; color:#4f46e5; border-color:#c7d2fe; cursor:default; }
.sr-view-tab:hover:not(.active) { background:#f9fafb; }

#g-toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; flex-shrink:0; }
#g-main { flex:1; min-height:0; display:flex; overflow:hidden; background:#fff; border:1px solid #e4e4e7; border-radius:12px; }

#g-left { --col-name:200px; --col-status:64px; --col-assignee:60px; --col-start:56px;
    width:calc(var(--col-name) + var(--col-status) + var(--col-assignee) + var(--col-start));
    flex-shrink:0; display:flex; flex-direction:column; border-right:2px solid #e4e4e7; overflow:hidden; }
#g-left-hdr { display:flex; align-items:center; height:var(--hdr-h); background:#f8fafc; border-bottom:1px solid #e4e4e7; flex-shrink:0; }
#g-left-body { flex:1; min-height:0; overflow-y:auto; overflow-x:hidden; scrollbar-width:none; }
#g-left-body::-webkit-scrollbar { display:none; }

.lh { display:flex; align-items:center; font-size:11px; font-weight:600; color:#71717a; text-transform:uppercase; letter-spacing:.04em; padding:0 10px; height:100%; border-right:1px solid #e4e4e7; flex-shrink:0; position:relative; user-select:none; }
.lh:last-child { border-right:none; }
.lh-name { width:var(--col-name); } .lh-status { width:var(--col-status); } .lh-assignee { width:var(--col-assignee); } .lh-start { width:var(--col-start); }

/* Column resize handle */
.col-resizer { position:absolute; right:-4px; top:0; bottom:0; width:8px; cursor:col-resize; z-index:20; }
.col-resizer::after { content:''; position:absolute; left:50%; top:20%; bottom:20%; width:2px; transform:translateX(-50%); background:#d4d4d8; border-radius:1px; opacity:0; transition:opacity .15s; }
.col-resizer:hover::after, .col-resizing::after { opacity:1; background:#6366f1; }

/* Row drag reorder */
.lr-task[draggable="true"] { cursor: grab; }
.lr-task[draggable="true"]:active { cursor: grabbing; }
.lr-task.row-dragging { opacity:.35; background:#f0ebff!important; }
.lr-task.drop-above { box-shadow: inset 0 2px 0 #7c3aed; }
.lr-task.drop-below { box-shadow: inset 0 -2px 0 #7c3aed; }
.lr-group.drop-group { background:#eff6ff!important; outline:2px dashed #c4b5fd; outline-offset:-2px; }
.lr-group.row-dragging { opacity:.35; background:#e0f2fe!important; }
.lr-group.drop-above { box-shadow: inset 0 2px 0 #3b82f6; }
.lr-group.drop-below { box-shadow: inset 0 -2px 0 #3b82f6; }
.drag-handle { display:flex; align-items:center; justify-content:center; width:14px; height:100%; color:#d1d5db; cursor:grab; flex-shrink:0; opacity:0; transition:opacity .15s; }
.lr-task:hover .drag-handle { opacity:1; }
.drag-handle:hover { color:#9ca3af; }
.group-drag-handle { display:flex; align-items:center; justify-content:center; width:16px; height:100%; color:#c7d2fe; cursor:grab; flex-shrink:0; opacity:0; transition:opacity .15s; margin-right:2px; }
.lr-group:hover .group-drag-handle { opacity:1; }
.group-drag-handle:hover { color:#818cf8; }

/* Status dropdown */
#sd { display:none; position:fixed; z-index:10001; background:#fff; border:1px solid #e4e4e7; border-radius:10px; box-shadow:0 6px 24px rgba(0,0,0,.12); overflow:hidden; min-width:150px; padding:4px; }
.sd-opt { display:flex; align-items:center; gap:8px; padding:7px 12px; font-size:12.5px; font-weight:500; border-radius:6px; cursor:pointer; transition:background .1s; }
.sd-opt:hover { background:#f4f4f5; }
.sd-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
#sd-overlay { display:none; position:fixed; inset:0; z-index:10000; }
.sbadge { cursor:pointer; display:inline-flex; align-items:center; gap:3px; border:1px solid transparent; transition:border-color .12s; }
.sbadge:hover { border-color:currentColor; }
.lr { display:flex; align-items:center; border-bottom:1px solid #f4f4f5; }
.lr-group { background:#f8fafc; height:var(--grp-h); font-weight:600; border-bottom:1px solid #e4e4e7; }
.lr-group:hover { background:#f1f5f9; }
.lr-task { height:var(--row-h); }
.lr-task:hover { background:#fafafa; }
.lc { display:flex; align-items:center; padding:0 10px; height:100%; overflow:hidden; font-size:12.5px; color:#3f3f46; border-right:1px solid #f4f4f5; flex-shrink:0; }
.lc:last-child { border-right:none; }
.lc-name { width:var(--col-name); gap:6px; } .lc-status { width:var(--col-status); } .lc-assignee { width:var(--col-assignee); font-size:11.5px; color:#71717a; } .lc-start { width:var(--col-start); font-size:11.5px; color:#71717a; }

.group-toggle { background:none; border:none; cursor:pointer; padding:2px 4px; color:#71717a; display:flex; align-items:center; border-radius:3px; flex-shrink:0; }
.group-toggle:hover { background:#e2e8f0; }
.group-icon { width:9px; height:9px; fill:none; }
.group-label { flex:1; font-size:12.5px; font-weight:600; color:#18181b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.group-count { font-size:11px; font-weight:500; color:#94a3b8; background:#f1f5f9; padding:1px 6px; border-radius:10px; flex-shrink:0; }
.task-name { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:12.5px; color:#3f3f46; padding-left:14px; cursor:pointer; }
.task-name:hover { color:#4f46e5; }
.task-no { display:inline-block; font-family:monospace; font-size:10.5px; color:#9ca3af; padding-right:4px; }

.sbadge { display:inline-block; font-size:10.5px; font-weight:500; padding:1.5px 6px; border-radius:4px; white-space:nowrap; }
.s-ai_review .sbadge { background:#fef3c7; color:#a16207; }
.s-requested .sbadge { background:#f3f4f6; color:#6b7280; }
.s-in_progress .sbadge { background:#dbeafe; color:#1d4ed8; }
.s-additional_dev .sbadge { background:#ffedd5; color:#c2410c; }
.s-reviewing .sbadge { background:#f3e8ff; color:#7e22ce; }
.s-completed .sbadge { background:#dcfce7; color:#15803d; }

#g-right { flex:1; display:flex; flex-direction:column; overflow:hidden; min-width:0; }
#g-right-hdr { height:var(--hdr-h); overflow:hidden; flex-shrink:0; border-bottom:1px solid #e4e4e7; background:#f8fafc; }
#g-right-hdr-inner { display:flex; flex-direction:column; width:max-content; }
.th-row { display:flex; }
.th-major { display:flex; align-items:center; padding:0 10px; font-size:11.5px; font-weight:600; color:#52525b; border-right:1px solid #e4e4e7; border-bottom:1px solid #e4e4e7; height:22px; flex-shrink:0; white-space:nowrap; }
.th-minor { display:flex; align-items:center; justify-content:center; font-size:11px; color:#94a3b8; border-right:1px solid #f4f4f5; height:30px; flex-shrink:0; white-space:nowrap; }
.th-minor.th-today { color:#7c3aed; font-weight:700; background:#f5f3ff; }
.th-minor.th-saturday { color:#3b82f6; }
.th-minor.th-sunday { color:#ef4444; }
.g-col-weekend, .g-col-holiday-bg { position:absolute; top:0; pointer-events:none; }
.g-col-weekend { background:rgba(59,130,246,.04); }
.g-col-holiday-bg { background:rgba(239,68,68,.05); }

#g-right-body { flex:1; min-height:0; overflow:auto; position:relative; }
#g-canvas { position:relative; }

.g-row-bg { position:absolute; left:0; right:0; }
.g-row-bg.even { background:#fafafa; }
.g-row-group-bg { position:absolute; left:0; right:0; background:#f8fafc; border-bottom:1px solid #e9edf2; }
.g-today-line { position:absolute; top:0; width:2px; background:#ef4444; opacity:.6; pointer-events:none; z-index:5; }
.g-today-top { position:absolute; top:0; width:10px; height:10px; border-radius:50%; background:#ef4444; margin-left:-4px; z-index:6; }
.g-grid-line { position:absolute; top:0; width:1px; background:#f0f0f0; pointer-events:none; }

.g-group-bar { position:absolute; border-radius:3px; background:#94a3b8; pointer-events:none; z-index:2; }
.g-bar { position:absolute; border-radius:5px; cursor:pointer; z-index:3; overflow:hidden; display:flex; align-items:center; transition:filter .12s, box-shadow .12s; }
.g-bar:hover { filter:brightness(1.06); box-shadow:0 2px 8px rgba(0,0,0,.15); z-index:4; }
.g-bar.s-ai_review { background:#f59e0b; }
.g-bar.s-requested { background:#9ca3af; }
.g-bar.s-in_progress { background:#3b82f6; }
.g-bar.s-additional_dev { background:#fb923c; }
.g-bar.s-reviewing { background:#a855f7; }
.g-bar.s-completed { background:#22c55e; }
.g-bar-prog { position:absolute; left:0; top:0; height:100%; opacity:.25; background:#000; border-radius:5px; pointer-events:none; }
.g-bar-label { position:relative; z-index:1; padding:0 8px; font-size:11px; font-weight:500; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; pointer-events:none; }
.g-handle { position:absolute; top:0; height:100%; width:7px; cursor:ew-resize; z-index:5; opacity:0; transition:opacity .15s; background:rgba(255,255,255,.35); border-radius:3px; }
.g-bar:hover .g-handle { opacity:1; }
.g-handle-l { left:0; cursor:w-resize; } .g-handle-r { right:0; cursor:e-resize; }

.vm-group { display:flex; background:#f4f4f5; border-radius:8px; padding:3px; gap:2px; }
.vm-btn { padding:4px 12px; font-size:12px; font-weight:500; border:none; border-radius:6px; cursor:pointer; background:transparent; color:#71717a; transition:all .12s; }
.vm-btn.active { background:#fff; color:#18181b; box-shadow:0 1px 3px rgba(0,0,0,.1); }
.legend-pill { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:500; color:#52525b; padding:3px 8px; border-radius:14px; background:#fff; border:1px solid #e4e4e7; }
.legend-dot { width:9px; height:9px; border-radius:3px; }

#g-popup { display:none; position:fixed; z-index:9999; background:#fff; border:1px solid #e4e4e7; border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,.12); padding:16px; min-width:260px; max-width:340px; }
#g-overlay { display:none; position:fixed; inset:0; z-index:9998; }
</style>

<script>
(function() {
    var bar = document.getElementById('impersonate-bar');
    if (bar) document.documentElement.style.setProperty('--imp-bar-h', bar.offsetHeight + 'px');
})();
</script>

<div id="g-wrap">
    {{-- 통계 칩 (SR 리스트와 동일) --}}
    @php
        $total       = array_sum($statusCounts);
        $bucketDefs  = \App\Http\Controllers\MaintRequestController::bucketStatuses();
        $bucketCount = fn(array $statuses) => array_sum(array_map(fn($s) => $statusCounts[$s] ?? 0, $statuses));
        $inProgress  = $bucketCount($bucketDefs['in_progress']);
        $reviewingCnt= $bucketCount($bucketDefs['reviewing']);
        $completedCnt= $bucketCount($bucketDefs['completed']);
        $bucketUrl   = fn($b) => route('maint-requests.gantt', array_merge(request()->except(['bucket']), ['bucket' => $b]));
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5" style="flex-shrink:0;">
        <a href="{{ $bucketUrl('all') }}" class="flex items-center gap-3 p-4 rounded-xl border transition-all {{ $bucket==='all' ? 'bg-indigo-50 border-indigo-300 shadow-sm' : 'bg-white border-gray-100 hover:border-gray-200 hover:shadow-sm' }}">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $bucket==='all' ? 'bg-indigo-100 text-indigo-700' : 'bg-indigo-50 text-indigo-600' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div><div class="text-xs text-gray-400">전체</div><div class="text-lg font-semibold {{ $bucket==='all' ? 'text-indigo-700' : 'text-gray-900' }}">{{ number_format($total) }}</div></div>
        </a>
        <a href="{{ $bucketUrl('in_progress') }}" class="flex items-center gap-3 p-4 rounded-xl border transition-all {{ $bucket==='in_progress' ? 'bg-amber-50 border-amber-300 shadow-sm' : 'bg-white border-gray-100 hover:border-gray-200 hover:shadow-sm' }}">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $bucket==='in_progress' ? 'bg-amber-100 text-amber-700' : 'bg-amber-50 text-amber-600' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div><div class="text-xs text-gray-400">진행/예정</div><div class="text-lg font-semibold {{ $bucket==='in_progress' ? 'text-amber-700' : 'text-gray-900' }}">{{ number_format($inProgress) }}</div></div>
        </a>
        <a href="{{ $bucketUrl('reviewing') }}" class="flex items-center gap-3 p-4 rounded-xl border transition-all {{ $bucket==='reviewing' ? 'bg-rose-50 border-rose-300 shadow-sm' : 'bg-white border-gray-100 hover:border-gray-200 hover:shadow-sm' }}">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $bucket==='reviewing' ? 'bg-rose-100 text-rose-700' : 'bg-rose-50 text-rose-600' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div><div class="text-xs text-gray-400">확인/검토</div><div class="text-lg font-semibold {{ $bucket==='reviewing' ? 'text-rose-700' : 'text-gray-900' }}">{{ number_format($reviewingCnt) }}</div></div>
        </a>
        <a href="{{ $bucketUrl('completed') }}" class="flex items-center gap-3 p-4 rounded-xl border transition-all {{ $bucket==='completed' ? 'bg-emerald-50 border-emerald-300 shadow-sm' : 'bg-white border-gray-100 hover:border-gray-200 hover:shadow-sm' }}">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $bucket==='completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-emerald-50 text-emerald-600' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div><div class="text-xs text-gray-400">완료</div><div class="text-lg font-semibold {{ $bucket==='completed' ? 'text-emerald-700' : 'text-gray-900' }}">{{ number_format($completedCnt) }}</div></div>
        </a>
    </div>

    {{-- SR 리스트와 동일한 검색 영역 --}}
    @php
        $selPriority = array_values(array_filter((array) request('priority'), fn ($v) => $v !== null && $v !== ''));
        $selStatus   = array_values(array_filter((array) request('status'),   fn ($v) => $v !== null && $v !== ''));
        $selAssignee = array_values(array_filter(array_map('intval', (array) request('assignee_id')),  fn ($v) => $v > 0));
        $selColo     = array_values(array_filter(array_map('intval', (array) request('colo_user_id')), fn ($v) => $v > 0));
        $coloLabelMap = $coloUsers->pluck('name','id')->toArray();
        $assigneeLabelMap = $devUsers->pluck('name','id')->toArray();
        $labelFor = function (string $title, array $sel, array $labels): string {
            if (empty($sel)) return $title . ': 전체';
            if (count($sel) === 1) return $title . ': ' . ($labels[$sel[0]] ?? $sel[0]);
            return $title . ': ' . count($sel) . '개';
        };
    @endphp
    <form method="GET" class="sr-filter-bar" action="{{ route('maint-requests.gantt') }}">
        <a href="{{ route('maint-requests.index', request()->query()) }}" class="sr-view-tab">리스트</a>
        <a href="#" class="sr-view-tab active">간트 보기</a>

        @if($canFilterByCompany)
        <select name="company_group_id" onchange="this.form.submit()">
            <option value="">회사: 전체</option>
            @foreach($companyGroups as $cg)
                <option value="{{ $cg->id }}" {{ (int)request('company_group_id')===$cg->id ? 'selected' : '' }}>{{ $cg->name }}</option>
            @endforeach
        </select>
        @endif

        {{-- 우선순위 --}}
        <div class="maint-multi" data-maint-multi>
            <button type="button" class="maint-multi-btn {{ $selPriority ? 'is-active' : '' }}">
                <span>{{ $labelFor('우선순위', $selPriority, $priorityLabels) }}</span>
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="maint-multi-pop">
                <div class="maint-multi-actions"><button type="button" data-multi-all>전체 선택</button><button type="button" data-multi-none>해제</button></div>
                <div class="maint-multi-list">
                    @foreach($priorityLabels as $k => $v)
                        <label><input type="checkbox" name="priority[]" value="{{ $k }}" {{ in_array($k, $selPriority, true) ? 'checked' : '' }}><span>{{ $v }}</span></label>
                    @endforeach
                </div>
                <button type="submit" class="maint-multi-apply">적용</button>
            </div>
        </div>

        {{-- 요약·내용 검색 --}}
        <input type="text" name="q" value="{{ request('q') }}" placeholder="요약·내용 검색" style="width:240px;">

        {{-- 요청자 --}}
        <div class="maint-multi" data-maint-multi>
            <button type="button" class="maint-multi-btn {{ $selColo ? 'is-active' : '' }}">
                <span>{{ $labelFor('요청자', $selColo, $coloLabelMap) }}</span>
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="maint-multi-pop">
                <div class="maint-multi-actions"><button type="button" data-multi-all>전체 선택</button><button type="button" data-multi-none>해제</button></div>
                <div class="maint-multi-list">
                    @foreach($coloUsers as $u)
                        <label><input type="checkbox" name="colo_user_id[]" value="{{ $u->id }}" {{ in_array((int)$u->id, $selColo, true) ? 'checked' : '' }}><span>{{ $u->name }}</span></label>
                    @endforeach
                </div>
                <button type="submit" class="maint-multi-apply">적용</button>
            </div>
        </div>

        {{-- 요청일 범위 --}}
        <span class="text-gray-500" style="font-size:13px;color:#6b7280;">요청일</span>
        <input type="date" name="date_from" value="{{ request('date_from') }}" title="시작일" style="width:140px;">
        <span style="color:#9ca3af;">~</span>
        <input type="date" name="date_to" value="{{ request('date_to') }}" title="종료일" style="width:140px;">

        {{-- 담당자 --}}
        @if($canFilterByAssignee)
        <div class="maint-multi" data-maint-multi>
            <button type="button" class="maint-multi-btn {{ $selAssignee ? 'is-active' : '' }}">
                <span>{{ $labelFor('담당자', $selAssignee, $assigneeLabelMap) }}</span>
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="maint-multi-pop">
                <div class="maint-multi-actions"><button type="button" data-multi-all>전체 선택</button><button type="button" data-multi-none>해제</button></div>
                <div class="maint-multi-list">
                    @foreach($devUsers as $u)
                        <label><input type="checkbox" name="assignee_id[]" value="{{ $u->id }}" {{ in_array((int)$u->id, $selAssignee, true) ? 'checked' : '' }}><span>{{ $u->name }}</span></label>
                    @endforeach
                </div>
                <button type="submit" class="maint-multi-apply">적용</button>
            </div>
        </div>
        @endif

        {{-- 상태 --}}
        <div class="maint-multi" data-maint-multi>
            <button type="button" class="maint-multi-btn {{ $selStatus ? 'is-active' : '' }}">
                <span>{{ $labelFor('상태', $selStatus, $statusLabels) }}</span>
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="maint-multi-pop">
                <div class="maint-multi-actions"><button type="button" data-multi-all>전체 선택</button><button type="button" data-multi-none>해제</button></div>
                <div class="maint-multi-list">
                    @foreach($statusLabels as $k => $v)
                        <label><input type="checkbox" name="status[]" value="{{ $k }}" {{ in_array($k, $selStatus, true) ? 'checked' : '' }}><span>{{ $v }}</span></label>
                    @endforeach
                </div>
                <button type="submit" class="maint-multi-apply">적용</button>
            </div>
        </div>

        <input type="hidden" name="bucket" value="{{ $bucket }}">
        <button type="submit" class="btn-go">조회</button>
    </form>

    {{-- Toolbar: 일/주/월 + 상태 범례 --}}
    <div id="g-toolbar">
        <div style="display:flex;align-items:center;gap:8px;">
            <div class="vm-group">
                <button class="vm-btn" onclick="setView('day')">일</button>
                <button class="vm-btn active" onclick="setView('week')">주</button>
                <button class="vm-btn" onclick="setView('month')">월</button>
            </div>
            <button onclick="goToday()" style="padding:4px 12px;font-size:12px;font-weight:500;border:1px solid #e4e4e7;border-radius:6px;background:#fff;color:#52525b;cursor:pointer;">오늘</button>
            <span id="g-save-msg" style="font-size:12px;color:#10b981;display:none;">저장됨</span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="legend-pill"><span class="legend-dot" style="background:#9ca3af;"></span>요청</span>
            <span class="legend-pill"><span class="legend-dot" style="background:#3b82f6;"></span>진행중</span>
            <span class="legend-pill"><span class="legend-dot" style="background:#fb923c;"></span>추가 개발</span>
            <span class="legend-pill"><span class="legend-dot" style="background:#a855f7;"></span>검토</span>
            <span class="legend-pill"><span class="legend-dot" style="background:#22c55e;"></span>완료</span>
        </div>
    </div>

    {{-- Main --}}
    <div id="g-main">
        <div id="g-left">
            <div id="g-left-hdr">
                <div class="lh lh-name">SR / 요약<div class="col-resizer" data-col="name"></div></div>
                <div class="lh lh-status">상태<div class="col-resizer" data-col="status"></div></div>
                <div class="lh lh-assignee">담당자<div class="col-resizer" data-col="assignee"></div></div>
                <div class="lh lh-start">시작<div class="col-resizer" data-col="start"></div></div>
            </div>
            <div id="g-left-body"></div>
        </div>
        <div id="g-right">
            <div id="g-right-hdr"><div id="g-right-hdr-inner"></div></div>
            <div id="g-right-body">
                <div id="g-canvas"></div>
            </div>
        </div>
    </div>
</div>

{{-- Popup --}}
<div id="g-overlay" onclick="closePopup()"></div>
<div id="g-popup">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
        <div style="flex:1;min-width:0;">
            <p id="pp-group" style="font-size:11px;color:#94a3b8;margin:0 0 2px;"></p>
            <p id="pp-title" style="font-size:13.5px;font-weight:600;color:#18181b;margin:0;line-height:1.4;"></p>
        </div>
        <button onclick="closePopup()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:18px;padding:0 0 0 8px;line-height:1;">&times;</button>
    </div>
    <div id="pp-status" style="margin-bottom:8px;"></div>
    <div style="font-size:12px;color:#52525b;margin-bottom:4px;"><span id="pp-dates"></span></div>
    <div style="font-size:12px;color:#71717a;margin-bottom:12px;"><span id="pp-assignee"></span></div>
    <button id="pp-show" type="button" onclick="if(_ppTid){closePopup();openSr(_ppTid);}" style="display:block;width:100%;text-align:center;padding:7px;font-size:12px;border:1px solid #e4e4e7;border-radius:7px;color:#52525b;background:#fff;cursor:pointer;">SR 상세 보기</button>
</div>

{{-- SR 상세 모달 (iframe) --}}
<div id="maint-detail-overlay" onclick="maintCloseDetailModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:10900;"></div>
<div id="maint-detail-modal"
     style="display:none;flex-direction:column;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10901;background:#f5f3ff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:1100px;max-width:calc(100vw - 32px);height:calc(100vh - 60px);overflow:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 18px;background:#fff;border-bottom:1px solid #f3f4f6;flex:0 0 auto;">
        <h3 style="font-size:14px;font-weight:600;color:#111827;margin:0;">SR 요청 상세 <span id="maint-detail-id" style="font-family:monospace;font-size:12px;color:#9ca3af;margin-left:4px;"></span></h3>
        <button type="button" onclick="maintCloseDetailModal()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:22px;line-height:1;">&times;</button>
    </div>
    <iframe id="maint-detail-iframe" src="about:blank" style="flex:1 1 0;width:100%;border:0;background:#f5f3ff;"></iframe>
</div>

{{-- Status Dropdown (AI 검토 제외 — 5종) --}}
<div id="sd">
    <div class="sd-opt" data-val="requested"><span class="sd-dot" style="background:#9ca3af;"></span><span style="color:#6b7280;">요청</span></div>
    <div class="sd-opt" data-val="in_progress"><span class="sd-dot" style="background:#3b82f6;"></span><span style="color:#1d4ed8;">진행중</span></div>
    <div class="sd-opt" data-val="additional_dev"><span class="sd-dot" style="background:#fb923c;"></span><span style="color:#c2410c;">추가 개발</span></div>
    <div class="sd-opt" data-val="reviewing"><span class="sd-dot" style="background:#a855f7;"></span><span style="color:#7e22ce;">검토</span></div>
    <div class="sd-opt" data-val="completed"><span class="sd-dot" style="background:#22c55e;"></span><span style="color:#15803d;">완료</span></div>
</div>
<div id="sd-overlay" onclick="closeStatusDropdown()"></div>
@endsection

@section('scripts')
@php
    $_allHolidays = [];
    for ($_yr = 2024; $_yr <= 2028; $_yr++) {
        $_allHolidays = array_merge($_allHolidays, array_keys(\App\Helpers\KoreanHolidays::getHolidays($_yr)));
    }
    $_allHolidays = array_values(array_unique($_allHolidays));
@endphp
<script>
const ALL_TASKS = @json($ganttTasks);
const HOLIDAYS  = new Set(@json($_allHolidays));
const IS_PRIVILEGED = {{ $isSrPrivileged ? 'true' : 'false' }};
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const UPDATE_BASE = '{{ url("maint-requests") }}';
const SR_OPEN_BASE = '{{ route("maint-requests.index") }}';
const REORDER_URL  = '{{ route("maint-requests.gantt-reorder") }}';

const ROW_H = 34, GRP_H = 38, HDR_H = 52, BAR_H = 20, GRP_BAR_H = 8;
const BAR_PAD = (ROW_H - BAR_H) / 2;
const DAY_W = { day: 38, week: 18, month: 7 };

let viewMode = 'week';
let groups = {};
let flatRows = [];
let knownGroupOrder = [];
let tlStart, tlEnd;
let drag = null, _ppTid = null;

const leftBody = document.getElementById('g-left-body');
const rightHdrIn = document.getElementById('g-right-hdr-inner');
const rightBody = document.getElementById('g-right-body');
const canvas = document.getElementById('g-canvas');

function pdate(s) { const [y,m,d]=s.split('-').map(Number); return new Date(y, m-1, d); }
function fdate(d) { return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; }
function fshort(d) { return `${d.getMonth()+1}/${d.getDate()}`; }
function dayDiff(a, b) { return Math.round((b - a) / 86400000); }
function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
const today = new Date(); today.setHours(0,0,0,0);

ALL_TASKS.forEach(t => {
    const g = t.group_name || '(미지정)';
    if (!knownGroupOrder.includes(g)) knownGroupOrder.push(g);
});

function buildGroups() {
    const prevExpanded = {};
    Object.values(groups).forEach(g => { prevExpanded[g.name] = g.expanded; });
    groups = {};
    knownGroupOrder.forEach(name => { groups[name] = { name, tasks: [], expanded: prevExpanded[name] !== false }; });
    ALL_TASKS.forEach(t => {
        const g = t.group_name || '(미지정)';
        if (!groups[g]) { groups[g] = { name: g, tasks: [], expanded: true }; knownGroupOrder.push(g); }
        groups[g].tasks.push(t);
    });
}
function buildFlat() {
    flatRows = [];
    knownGroupOrder.forEach(name => {
        const g = groups[name]; if (!g) return;
        flatRows.push({ type:'group', group:g });
        if (g.expanded) g.tasks.forEach(t => flatRows.push({ type:'task', task:t }));
    });
}
function calcRange() {
    let mn = null, mx = null;
    ALL_TASKS.forEach(t => {
        const s = pdate(t.start), e = pdate(t.end);
        if (!mn || s < mn) mn = new Date(s);
        if (!mx || e > mx) mx = new Date(e);
    });
    if (!mn) { mn = new Date(today); mx = new Date(today); }
    mn.setDate(mn.getDate() - 14);
    mx.setDate(mx.getDate() + 28);
    const dow = mn.getDay();
    mn.setDate(mn.getDate() - (dow === 0 ? 6 : dow - 1));
    tlStart = mn; tlEnd = mx;
}
function totalDays() { return dayDiff(tlStart, tlEnd) + 1; }
function getX(d) { return dayDiff(tlStart, d) * DAY_W[viewMode]; }
function barW(s, e) { return Math.max(DAY_W[viewMode], (dayDiff(s, e) + 1) * DAY_W[viewMode]); }
function totalW() { return totalDays() * DAY_W[viewMode]; }
function totalH() { return flatRows.reduce((s, r) => s + (r.type === 'group' ? GRP_H : ROW_H), 0); }

function groupDateRange(g) {
    let mn = null, mx = null;
    g.tasks.forEach(t => {
        const s = pdate(t.start), e = pdate(t.end);
        if (!mn || s < mn) mn = new Date(s);
        if (!mx || e > mx) mx = new Date(e);
    });
    return { start: mn, end: mx };
}

function renderLeft() {
    const dotsSvg = '<svg width="10" height="14" viewBox="0 0 10 14" fill="currentColor"><circle cx="3" cy="2.5" r="1.2"/><circle cx="7" cy="2.5" r="1.2"/><circle cx="3" cy="7" r="1.2"/><circle cx="7" cy="7" r="1.2"/><circle cx="3" cy="11.5" r="1.2"/><circle cx="7" cy="11.5" r="1.2"/></svg>';
    leftBody.innerHTML = flatRows.map(r => {
        if (r.type === 'group') {
            const g = r.group;
            const dr = groupDateRange(g);
            const icon = g.expanded
                ? '<path d="M1 3l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                : '<path d="M3 1l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
            return `<div class="lr lr-group" data-g="${esc(g.name)}" draggable="true">
                <div class="lc lc-name">
                    <span class="group-drag-handle" title="그룹 이동">${dotsSvg}</span>
                    <button class="group-toggle" onclick="toggleGroup('${esc(g.name)}')">
                        <svg class="group-icon" width="10" height="10" viewBox="0 0 10 10" fill="none">${icon}</svg>
                    </button>
                    <span class="group-label">${esc(g.name)}</span>
                    <span class="group-count">${g.tasks.length}</span>
                </div>
                <div class="lc lc-status" style="color:#94a3b8;">—</div>
                <div class="lc lc-assignee">—</div>
                <div class="lc lc-start">${dr.start ? fshort(dr.start) : '—'}</div>
            </div>`;
        }
        const t = r.task;
        const sbadgeClick = IS_PRIVILEGED ? `onclick="openStatusDropdown(event, '${t.id}')"` : '';
        const chevron = IS_PRIVILEGED ? '<svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:.5;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>' : '';
        return `<div class="lr lr-task s-${t._status}" data-tid="${t.id}" ${IS_PRIVILEGED ? 'draggable="true"' : ''}>
            <div class="lc lc-name">
                ${IS_PRIVILEGED ? `<span class="drag-handle" title="행 이동">${dotsSvg}</span>` : ''}
                <span class="task-no">#${esc(t._excel_no || t.id)}</span>
                <span class="task-name" title="${esc(t.name)}" onclick="openSr('${t.id}')">${esc(t.name)}</span>
            </div>
            <div class="lc lc-status"><span class="sbadge" data-tid="${t.id}" ${sbadgeClick}>${esc(t._status_label)}${chevron}</span></div>
            <div class="lc lc-assignee" title="${esc(t._assignee)}">${esc(t._assignee)}</div>
            <div class="lc lc-start">${fshort(pdate(t.start))}</div>
        </div>`;
    }).join('');
    if (IS_PRIVILEGED) bindRowDrag();
}

function renderHeader() {
    const tw = totalW();
    const MO = ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'];
    let maj = '', min = '';

    if (viewMode === 'day') {
        let d = new Date(tlStart);
        while (d <= tlEnd) {
            const nm = new Date(d.getFullYear(), d.getMonth() + 1, 1);
            const days = Math.min(dayDiff(d, nm), dayDiff(d, tlEnd) + 1);
            maj += `<div class="th-major" style="width:${days*DAY_W.day}px;">${d.getFullYear()}.${String(d.getMonth()+1).padStart(2,'0')}</div>`;
            d = nm;
        }
        let day = new Date(tlStart);
        while (day <= tlEnd) {
            const isToday = day.toDateString() === today.toDateString();
            const dow = day.getDay(), ds = fdate(day);
            const isHol = HOLIDAYS.has(ds);
            let cls = '';
            if (isToday) cls = ' th-today';
            else if (isHol || dow === 0) cls = ' th-sunday';
            else if (dow === 6) cls = ' th-saturday';
            min += `<div class="th-minor${cls}" style="width:${DAY_W.day}px;">${day.getDate()}</div>`;
            day.setDate(day.getDate() + 1);
        }
    } else if (viewMode === 'week') {
        let d = new Date(tlStart);
        while (d <= tlEnd) {
            const nm = new Date(d.getFullYear(), d.getMonth() + 1, 1);
            const end = nm < tlEnd ? nm : new Date(tlEnd); end.setDate(end.getDate() + 1);
            const days = dayDiff(d, end);
            maj += `<div class="th-major" style="width:${days*DAY_W.week}px;">${d.getFullYear()}.${String(d.getMonth()+1).padStart(2,'0')}</div>`;
            d = nm;
        }
        let wk = new Date(tlStart);
        while (wk <= tlEnd) {
            const we = new Date(wk); we.setDate(we.getDate() + 6);
            const days = Math.min(7, dayDiff(wk, tlEnd) + 1);
            const isCur = wk <= today && today <= we;
            min += `<div class="th-minor${isCur?' th-today':''}" style="width:${days*DAY_W.week}px;">${wk.getMonth()+1}/${wk.getDate()}</div>`;
            wk.setDate(wk.getDate() + 7);
        }
    } else {
        let d = new Date(tlStart.getFullYear(), tlStart.getMonth(), 1);
        let curYear = -1;
        while (d <= tlEnd) {
            const yr = d.getFullYear();
            if (yr !== curYear) {
                const yrEnd = new Date(yr, 11, 31);
                const endD = yrEnd < tlEnd ? yrEnd : new Date(tlEnd);
                const days = dayDiff(new Date(yr, 0, 1), endD) + 1;
                maj += `<div class="th-major" style="width:${days*DAY_W.month}px;">${yr}년</div>`;
                curYear = yr;
            }
            const nm = new Date(d.getFullYear(), d.getMonth() + 1, 1);
            const days = dayDiff(d, nm < tlEnd ? nm : new Date(tlEnd.getFullYear(), tlEnd.getMonth()+1, 1));
            const isCur = d.getMonth() === today.getMonth() && d.getFullYear() === today.getFullYear();
            min += `<div class="th-minor${isCur?' th-today':''}" style="width:${days*DAY_W.month}px;">${MO[d.getMonth()]}</div>`;
            d = nm;
        }
    }
    rightHdrIn.innerHTML = `<div class="th-row" style="width:${tw}px;">${maj}</div><div class="th-row" style="width:${tw}px;">${min}</div>`;
}

function renderCanvas() {
    const tw = totalW(), th = totalH();
    canvas.style.width = tw + 'px'; canvas.style.height = th + 'px';
    let html = '';
    const dw = DAY_W[viewMode];

    if (viewMode === 'day') {
        let d = new Date(tlStart), x = 0;
        while (d <= tlEnd) {
            if (d.getDay() === 1) html += `<div class="g-grid-line" style="left:${x}px;height:${th}px;"></div>`;
            d.setDate(d.getDate() + 1); x += dw;
        }
        d = new Date(tlStart); x = 0;
        while (d <= tlEnd) {
            const dow = d.getDay(), ds = fdate(d);
            if (dow === 0 || dow === 6 || HOLIDAYS.has(ds)) {
                const cls = (dow === 6 && !HOLIDAYS.has(ds)) ? 'g-col-weekend' : 'g-col-holiday-bg';
                html += `<div class="${cls}" style="left:${x}px;height:${th}px;width:${dw}px;"></div>`;
            }
            d.setDate(d.getDate() + 1); x += dw;
        }
    } else if (viewMode === 'week') {
        let d = new Date(tlStart), x = 0;
        while (d <= tlEnd) { html += `<div class="g-grid-line" style="left:${x}px;height:${th}px;"></div>`; d.setDate(d.getDate() + 7); x += dw * 7; }
    } else {
        let d = new Date(tlStart.getFullYear(), tlStart.getMonth(), 1);
        while (d <= tlEnd) { const x = getX(d); html += `<div class="g-grid-line" style="left:${x}px;height:${th}px;"></div>`; d = new Date(d.getFullYear(), d.getMonth() + 1, 1); }
    }

    let y = 0;
    flatRows.forEach((r, i) => {
        const h = r.type === 'group' ? GRP_H : ROW_H;
        if (r.type === 'group') html += `<div class="g-row-group-bg" style="top:${y}px;height:${h}px;width:${tw}px;"></div>`;
        else if (i % 2 === 0) html += `<div class="g-row-bg even" style="top:${y}px;height:${h}px;width:${tw}px;"></div>`;
        y += h;
    });

    if (today >= tlStart && today <= tlEnd) {
        const tx = getX(today) + dw / 2;
        html += `<div class="g-today-line" style="left:${tx}px;height:${th}px;"></div>`;
        html += `<div class="g-today-top" style="left:${tx}px;"></div>`;
    }

    y = 0;
    flatRows.forEach(r => {
        if (r.type === 'group') {
            const dr = groupDateRange(r.group);
            if (dr.start && dr.end) {
                const gx = getX(dr.start), gw = barW(dr.start, dr.end);
                const gy = y + (GRP_H - GRP_BAR_H) / 2;
                html += `<div class="g-group-bar" style="left:${gx}px;top:${gy}px;width:${gw}px;height:${GRP_BAR_H}px;"></div>`;
            }
            y += GRP_H;
        } else {
            const t = r.task;
            const s = pdate(t.start), e = pdate(t.end);
            const bx = getX(s), bw = barW(s, e);
            const by = y + BAR_PAD;
            const handles = IS_PRIVILEGED
                ? `<div class="g-handle g-handle-l" data-tid="${t.id}" data-type="l"></div><div class="g-handle g-handle-r" data-tid="${t.id}" data-type="r"></div>`
                : '';
            html += `<div class="g-bar s-${t._status}" data-tid="${t.id}" style="left:${bx}px;top:${by}px;width:${bw}px;height:${BAR_H}px;" title="${esc(t.name)}" onclick="openPopup(event, '${t.id}')">
                <div class="g-bar-prog" style="width:${t.progress}%;"></div>
                <span class="g-bar-label">${esc(t.name)}</span>${handles}
            </div>`;
            y += ROW_H;
        }
    });
    canvas.innerHTML = html;
    if (IS_PRIVILEGED) bindDrag();
}

function bindDrag() {
    canvas.querySelectorAll('.g-bar').forEach(bar => bar.addEventListener('mousedown', onBarMouseDown));
    canvas.querySelectorAll('.g-handle').forEach(h => h.addEventListener('mousedown', onHandleMouseDown));
}
function onBarMouseDown(e) {
    if (e.target.classList.contains('g-handle')) return;
    e.preventDefault();
    const bar = e.currentTarget;
    drag = { type:'move', bar, tid:bar.dataset.tid, startX:e.clientX, origLeft:parseInt(bar.style.left), origW:parseInt(bar.style.width), moved:false };
    document.body.style.cursor = 'grabbing';
}
function onHandleMouseDown(e) {
    e.preventDefault(); e.stopPropagation();
    const bar = e.currentTarget.closest('.g-bar');
    const side = e.currentTarget.dataset.type;
    drag = { type: side === 'l' ? 'resize-l' : 'resize-r', bar, tid:bar.dataset.tid, startX:e.clientX, origLeft:parseInt(bar.style.left), origW:parseInt(bar.style.width), moved:false };
    document.body.style.cursor = 'ew-resize';
}
document.addEventListener('mousemove', e => {
    if (!drag) return;
    const dx = e.clientX - drag.startX;
    if (Math.abs(dx) > 3) drag.moved = true;
    const dw = DAY_W[viewMode];
    if (drag.type === 'move') drag.bar.style.left = Math.max(0, drag.origLeft + dx) + 'px';
    else if (drag.type === 'resize-l') { const nw = drag.origW - dx; if (nw >= dw) { drag.bar.style.left = (drag.origLeft + dx) + 'px'; drag.bar.style.width = nw + 'px'; } }
    else { const nw = drag.origW + dx; if (nw >= dw) drag.bar.style.width = nw + 'px'; }
});
document.addEventListener('mouseup', () => {
    if (!drag) return;
    if (!drag.moved) { drag = null; document.body.style.cursor=''; return; }   // click 보존
    const dw = DAY_W[viewMode];
    const left = parseInt(drag.bar.style.left), width = parseInt(drag.bar.style.width);
    const sDays = Math.round(left / dw), eDays = Math.round((left + width) / dw) - 1;
    const ns = new Date(tlStart); ns.setDate(ns.getDate() + sDays);
    const ne = new Date(tlStart); ne.setDate(ne.getDate() + eDays);
    const task = ALL_TASKS.find(t => t.id === String(drag.tid));
    if (task && (fdate(ns) !== task.start || fdate(ne) !== task.end)) {
        saveDates(drag.tid, fdate(ns), fdate(ne));
        task.start = fdate(ns); task.end = fdate(ne);
        const lr = leftBody.querySelector(`[data-tid="${drag.tid}"]`);
        if (lr) lr.querySelector('.lc-start').textContent = fshort(ns);
    }
    drag = null; document.body.style.cursor = '';
});
function saveDates(tid, start, end) {
    fetch(`${UPDATE_BASE}/${tid}/gantt-dates`, {
        method:'PATCH',
        headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':CSRF },
        body: JSON.stringify({ start, end }),
    })
    .then(r => r.json())
    .then(d => { if (d.ok) { const m = document.getElementById('g-save-msg'); m.style.display='inline'; setTimeout(()=>m.style.display='none', 2000); } else alert('저장 실패: ' + (d.message || '오류')); })
    .catch(err => alert('저장 실패: ' + err.message));
}

let syncing = false;
rightBody.addEventListener('scroll', () => { if (syncing) return; syncing=true; leftBody.scrollTop = rightBody.scrollTop; rightHdrIn.style.transform = `translateX(-${rightBody.scrollLeft}px)`; syncing=false; });
leftBody.addEventListener('scroll', () => { if (syncing) return; syncing=true; rightBody.scrollTop = leftBody.scrollTop; syncing=false; });

function setView(m) {
    viewMode = m;
    document.querySelectorAll('.vm-btn').forEach(b => b.classList.remove('active'));
    document.querySelector(`.vm-btn[onclick*="${m}"]`)?.classList.add('active');
    calcRange(); renderHeader(); renderCanvas();
}
function goToday() {
    const x = getX(today) - rightBody.clientWidth / 2 + DAY_W[viewMode] / 2;
    rightBody.scrollTo({ left: Math.max(0, x), behavior:'smooth' });
}
function toggleGroup(name) { if (groups[name]) { groups[name].expanded = !groups[name].expanded; buildFlat(); renderLeft(); renderCanvas(); } }

function openPopup(e, tid) {
    if (drag && drag.moved) return;   // 드래그 직후 클릭 무시
    e.stopPropagation();
    const t = ALL_TASKS.find(x => x.id === String(tid)); if (!t) return;
    _ppTid = tid;
    document.getElementById('pp-group').textContent = t.group_name;
    document.getElementById('pp-title').textContent = `#${t._excel_no || t.id} · ${t.name}`;
    document.getElementById('pp-status').innerHTML = `<span class="sbadge" style="background:#eef2ff;color:#4f46e5;">${esc(t._status_label)}</span>${t._paid_dev ? ' <span class="sbadge" style="background:#fef3c7;color:#a16207;">유상</span>' : ''}`;
    document.getElementById('pp-dates').textContent = `${t.start} ~ ${t.end}`;
    document.getElementById('pp-assignee').textContent = `담당자: ${t._assignee}`;
    const popup = document.getElementById('g-popup');
    const ov = document.getElementById('g-overlay');
    popup.style.left = (e.clientX + 10) + 'px';
    popup.style.top  = (e.clientY + 10) + 'px';
    popup.style.display = 'block'; ov.style.display = 'block';
}
function closePopup() { document.getElementById('g-popup').style.display = 'none'; document.getElementById('g-overlay').style.display = 'none'; _ppTid = null; }
function openSr(tid) { maintOpenDetailModal(tid); }
function maintOpenDetailModal(id) {
    document.getElementById('maint-detail-iframe').src = '{{ url('maint-requests') }}/' + id + '/embed';
    document.getElementById('maint-detail-id').textContent = '#' + id;
    document.getElementById('maint-detail-overlay').style.display = 'block';
    document.getElementById('maint-detail-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function maintCloseDetailModal() {
    document.getElementById('maint-detail-overlay').style.display = 'none';
    document.getElementById('maint-detail-modal').style.display = 'none';
    document.getElementById('maint-detail-iframe').src = 'about:blank';
    document.body.style.overflow = '';
}
window.maintHandleModalClose = function(reloadList) { maintCloseDetailModal(); if (reloadList) window.location.reload(); };

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closePopup(); closeStatusDropdown(); maintCloseDetailModal(); } });

// ─── Multi-select 필터바 드롭다운 ────────────────────────────────────────────
document.querySelectorAll('[data-maint-multi]').forEach(function(wrap) {
    var btn  = wrap.querySelector('.maint-multi-btn');
    var pop  = wrap.querySelector('.maint-multi-pop');
    var all  = wrap.querySelector('[data-multi-all]');
    var none = wrap.querySelector('[data-multi-none]');
    if (!btn || !pop) return;
    btn.addEventListener('click', function(e) {
        e.preventDefault(); e.stopPropagation();
        document.querySelectorAll('[data-maint-multi].open').forEach(function(w) { if (w !== wrap) w.classList.remove('open'); });
        wrap.classList.toggle('open');
    });
    if (all)  all.addEventListener('click',  function() { wrap.querySelectorAll('input[type=checkbox]').forEach(function(cb) { cb.checked = true; }); });
    if (none) none.addEventListener('click', function() { wrap.querySelectorAll('input[type=checkbox]').forEach(function(cb) { cb.checked = false; }); });
    pop.addEventListener('click', function(e) { e.stopPropagation(); });
});
document.addEventListener('click', function() { document.querySelectorAll('[data-maint-multi].open').forEach(function(w) { w.classList.remove('open'); }); });

// ─── Column resize ───────────────────────────────────────────────────────────
const COL_KEY = 'sr_gantt_cols_v1';
const COL_DEFAULTS = { name: 200, status: 64, assignee: 60, start: 56 };
const COL_MIN = { name: 100, status: 45, assignee: 45, start: 50 };
function loadColWidths() {
    const gLeft = document.getElementById('g-left');
    const saved = JSON.parse(localStorage.getItem(COL_KEY) || '{}');
    Object.entries(COL_DEFAULTS).forEach(([col, def]) => {
        gLeft.style.setProperty('--col-' + col, (saved[col] || def) + 'px');
    });
}
function saveColWidths() {
    const gLeft = document.getElementById('g-left');
    const widths = {};
    Object.keys(COL_DEFAULTS).forEach(c => {
        widths[c] = parseInt(getComputedStyle(gLeft).getPropertyValue('--col-' + c)) || COL_DEFAULTS[c];
    });
    localStorage.setItem(COL_KEY, JSON.stringify(widths));
}
function bindColResize() {
    const gLeft = document.getElementById('g-left');
    let active = null;
    document.querySelectorAll('.col-resizer').forEach(h => {
        h.addEventListener('mousedown', e => {
            e.preventDefault(); e.stopPropagation();
            const col = h.dataset.col;
            const startW = parseInt(getComputedStyle(gLeft).getPropertyValue('--col-' + col)) || COL_DEFAULTS[col];
            h.classList.add('col-resizing');
            active = { col, startX: e.clientX, startW, handle: h };
        });
    });
    document.addEventListener('mousemove', e => {
        if (!active) return;
        const dx = e.clientX - active.startX;
        const nw = Math.max(COL_MIN[active.col] || 45, active.startW + dx);
        gLeft.style.setProperty('--col-' + active.col, nw + 'px');
    });
    document.addEventListener('mouseup', () => {
        if (!active) return;
        active.handle.classList.remove('col-resizing');
        saveColWidths();
        active = null;
    });
}

// ─── Status dropdown ─────────────────────────────────────────────────────────
const STATUS_META = {
    ai_review:      { label:'AI 검토',    bar:'#f59e0b' },
    requested:      { label:'요청',       bar:'#9ca3af' },
    in_progress:    { label:'진행중',     bar:'#3b82f6' },
    additional_dev: { label:'추가 개발',  bar:'#fb923c' },
    reviewing:      { label:'검토',       bar:'#a855f7' },
    completed:      { label:'완료',       bar:'#22c55e' },
};
let sdCurrentTid = null;
window.openStatusDropdown = function(event, tid) {
    event.stopPropagation(); event.preventDefault();
    sdCurrentTid = String(tid);
    const sd = document.getElementById('sd');
    const rect = event.currentTarget.getBoundingClientRect();
    sd.style.left = Math.min(rect.left, window.innerWidth - 170) + 'px';
    sd.style.top  = (rect.bottom + 4) + 'px';
    sd.style.display = 'block';
    document.getElementById('sd-overlay').style.display = 'block';
};
window.closeStatusDropdown = function() {
    document.getElementById('sd').style.display = 'none';
    document.getElementById('sd-overlay').style.display = 'none';
    sdCurrentTid = null;
};
document.querySelectorAll('.sd-opt').forEach(opt => {
    opt.addEventListener('click', () => {
        const newStatus = opt.dataset.val;
        const tid = sdCurrentTid;
        closeStatusDropdown();
        if (!tid || !newStatus) return;
        const task = ALL_TASKS.find(t => t.id === String(tid));
        if (!task || task._status === newStatus) return;
        applyStatusChange(tid, newStatus);
    });
});
function applyStatusChange(tid, newStatus) {
    fetch(`${UPDATE_BASE}/${tid}/quick`, {
        method:'PATCH',
        headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':CSRF },
        body: JSON.stringify({ status: newStatus }),
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) { alert(d.message || '상태 변경 실패'); return; }
        const task = ALL_TASKS.find(t => t.id === String(tid));
        if (task) {
            task._status = newStatus;
            task._status_label = STATUS_META[newStatus]?.label || newStatus;
        }
        // 좌측 행 UI 갱신
        const lr = leftBody.querySelector(`[data-tid="${tid}"]`);
        if (lr) {
            lr.className = lr.className.replace(/s-\S+/, `s-${newStatus}`);
            const badge = lr.querySelector('.sbadge');
            if (badge) {
                const chev = badge.querySelector('svg') ? badge.querySelector('svg').outerHTML : '';
                badge.innerHTML = (STATUS_META[newStatus]?.label || newStatus) + chev;
            }
        }
        // 바 색상 갱신
        const bar = canvas.querySelector(`.g-bar[data-tid="${tid}"]`);
        if (bar) bar.className = `g-bar s-${newStatus}`;
        const msg = document.getElementById('g-save-msg'); msg.style.display='inline'; setTimeout(()=>msg.style.display='none', 2000);
    })
    .catch(err => alert('상태 저장 실패: ' + err.message));
}

// ─── Row drag reorder ────────────────────────────────────────────────────────
// 행 순서는 DB(maint_requests.gantt_sort_order)에, 회사 그룹 순서는 localStorage 에 저장.
const GORDER_KEY = 'sr_gantt_group_order_v1';
function loadGroupOrder() {
    const saved = JSON.parse(localStorage.getItem(GORDER_KEY) || '[]');
    if (saved.length) {
        const rest = knownGroupOrder.filter(n => !saved.includes(n));
        knownGroupOrder = [...saved.filter(n => knownGroupOrder.includes(n)), ...rest];
    }
}
function saveGroupOrder() { localStorage.setItem(GORDER_KEY, JSON.stringify(knownGroupOrder)); }

function saveTaskOrder() {
    const order = ALL_TASKS.map((t, i) => ({ id: parseInt(t.id), sort_order: i, group_name: t.group_name }));
    fetch(REORDER_URL, {
        method:'POST',
        headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':CSRF },
        body: JSON.stringify({ order }),
    })
    .then(r => r.json())
    .then(d => { if (!d.ok) alert('순서 저장 실패: ' + (d.message || '')); else flashSave(); })
    .catch(err => alert('순서 저장 실패: ' + err.message));
}

let rowDrag = null, rowDropInfo = null, groupDrag = null, groupDragAllowed = false;
function bindRowDrag() {
    leftBody.querySelectorAll('.lr-task').forEach(row => {
        row.addEventListener('dragstart', onRowDragStart);
        row.addEventListener('dragend',   onRowDragEnd);
        row.addEventListener('dragover',  onRowDragOver);
        row.addEventListener('drop',      onRowDrop);
    });
    leftBody.querySelectorAll('.lr-group').forEach(row => {
        row.addEventListener('dragstart', onGroupDragStart);
        row.addEventListener('dragend',   onGroupDragEnd);
        row.addEventListener('dragover',  onGroupDragOver);
        row.addEventListener('drop',      onGroupDrop);
        const handle = row.querySelector('.group-drag-handle');
        if (handle) {
            handle.addEventListener('mousedown', () => { groupDragAllowed = true; });
            handle.addEventListener('mouseup',   () => { groupDragAllowed = false; });
        }
    });
    leftBody.addEventListener('dragleave', e => { if (!leftBody.contains(e.relatedTarget)) clearRowDragUI(); });
}
function onRowDragStart(e) {
    rowDrag = { tid: this.dataset.tid, el: this };
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.dataset.tid);
    setTimeout(() => this.classList.add('row-dragging'), 0);
}
function onRowDragEnd() { this.classList.remove('row-dragging'); clearRowDragUI(); rowDrag = null; }
function onRowDragOver(e) {
    if (!rowDrag || this.dataset.tid === rowDrag.tid) return;
    e.preventDefault(); e.dataTransfer.dropEffect = 'move';
    const rect = this.getBoundingClientRect();
    const position = e.clientY < rect.top + rect.height / 2 ? 'before' : 'after';
    leftBody.querySelectorAll('.drop-above,.drop-below,.drop-group').forEach(r => r.classList.remove('drop-above','drop-below','drop-group'));
    this.classList.add(position === 'before' ? 'drop-above' : 'drop-below');
    rowDropInfo = { type:'task', tid: this.dataset.tid, position };
}
function onGroupDragStart(e) {
    if (!groupDragAllowed) { e.preventDefault(); return; }
    groupDragAllowed = false;
    groupDrag = { name: this.dataset.g, el: this };
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', 'group:' + this.dataset.g);
    setTimeout(() => this.classList.add('row-dragging'), 0);
}
function onGroupDragEnd() { this.classList.remove('row-dragging'); clearRowDragUI(); groupDrag = null; }
function onGroupDragOver(e) {
    if (groupDrag) {
        if (groupDrag.name === this.dataset.g) return;
        e.preventDefault(); e.dataTransfer.dropEffect = 'move';
        leftBody.querySelectorAll('.drop-above,.drop-below,.drop-group').forEach(r => r.classList.remove('drop-above','drop-below','drop-group'));
        const rect = this.getBoundingClientRect();
        const pos = e.clientY < rect.top + rect.height / 2 ? 'before' : 'after';
        this.classList.add(pos === 'before' ? 'drop-above' : 'drop-below');
        rowDropInfo = { type:'group-reorder', targetName: this.dataset.g, position: pos };
        return;
    }
    if (!rowDrag) return;
    e.preventDefault(); e.dataTransfer.dropEffect = 'move';
    leftBody.querySelectorAll('.drop-above,.drop-below,.drop-group').forEach(r => r.classList.remove('drop-above','drop-below','drop-group'));
    this.classList.add('drop-group');
    rowDropInfo = { type:'group', groupName: this.dataset.g };
}
function onGroupDrop(e) {
    e.preventDefault();
    // 그룹 순서 변경
    if (groupDrag && rowDropInfo && rowDropInfo.type === 'group-reorder') {
        const { name: fromName } = groupDrag;
        const { targetName: toName, position: pos } = rowDropInfo;
        clearRowDragUI(); groupDrag = null;
        const fromIdx = knownGroupOrder.indexOf(fromName);
        if (fromIdx === -1) return;
        knownGroupOrder.splice(fromIdx, 1);
        const toIdx = knownGroupOrder.indexOf(toName);
        if (toIdx === -1) { knownGroupOrder.push(fromName); }
        else knownGroupOrder.splice(pos === 'before' ? toIdx : toIdx + 1, 0, fromName);
        buildGroups(); buildFlat(); renderLeft(); renderCanvas();
        saveGroupOrder();
        flashSave();
        return;
    }
    // Task → Group 이동 (다른 그룹 이름으로 변경 안 함 — DB 미저장이라 localStorage 순서만 갱신)
    if (!rowDrag || !rowDropInfo || rowDropInfo.type !== 'group') { clearRowDragUI(); return; }
    const fromTid = rowDrag.tid;
    const targetGroup = rowDropInfo.groupName;
    clearRowDragUI();
    const t = ALL_TASKS.find(x => x.id === fromTid);
    if (!t) return;
    t.group_name = targetGroup;   // 화면상 그룹만 변경 (DB 미반영)
    buildGroups(); buildFlat(); renderLeft(); renderCanvas();
    saveTaskOrder();
    flashSave();
}
function onRowDrop(e) {
    e.preventDefault();
    if (!rowDrag || !rowDropInfo || rowDropInfo.type !== 'task' || rowDrag.tid === rowDropInfo.tid) { clearRowDragUI(); return; }
    const fromTid = rowDrag.tid;
    const { tid: toTid, position } = rowDropInfo;
    clearRowDragUI();
    const fromIdx = ALL_TASKS.findIndex(t => t.id === fromTid);
    if (fromIdx === -1) return;
    const [moved] = ALL_TASKS.splice(fromIdx, 1);
    const target = ALL_TASKS.find(t => t.id === toTid);
    if (target) moved.group_name = target.group_name;
    const toIdx = ALL_TASKS.findIndex(t => t.id === toTid);
    ALL_TASKS.splice(position === 'after' ? toIdx + 1 : toIdx, 0, moved);
    buildGroups(); buildFlat(); renderLeft(); renderCanvas();
    saveTaskOrder();
    flashSave();
}
function clearRowDragUI() {
    leftBody.querySelectorAll('.drop-above,.drop-below,.drop-group').forEach(r => r.classList.remove('drop-above','drop-below','drop-group'));
    rowDropInfo = null;
}
function flashSave() {
    const m = document.getElementById('g-save-msg');
    m.style.display = 'inline';
    setTimeout(() => m.style.display = 'none', 1500);
}

// 초기 렌더
loadColWidths();
loadGroupOrder();
// 행 순서는 서버에서 ORDER BY gantt_sort_order 로 정렬되어 옴 (localStorage 미사용)
buildGroups(); buildFlat(); calcRange();
renderLeft(); renderHeader(); renderCanvas();
bindColResize();
setTimeout(goToday, 50);
</script>
@endsection
