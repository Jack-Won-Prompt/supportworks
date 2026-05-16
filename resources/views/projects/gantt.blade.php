@extends('layouts.app')
@section('title', $project->name . ' — ' . __('projects.gantt_chart'))

@section('header-actions')@endsection

@section('page-actions')
    <button onclick="openAddModal()"
       style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;font-size:13px;color:#fff;background:var(--t500);border-radius:8px;border:none;cursor:pointer;font-family:inherit;"
       onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>{{ __('projects.gantt_add_schedule') }}
    </button>
@endsection

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('projects.gantt') }}</span>
@endsection

@section('content')
<style>
:root {
    --lw: 460px;    /* left panel width */
    --row-h: 36px;  /* task row height */
    --grp-h: 40px;  /* group row height */
    --hdr-h: 52px;  /* header height */
    --bar-h: 22px;  /* bar height */
    --imp-bar-h: 0px; /* impersonate bar height (JS로 실측) */
}
/* ── 간트 전용: 브라우저 스크롤 완전 차단 ── */
html, body { overflow: hidden !important; height: 100% !important; }

/* 사이드바 + 콘텐츠 최외곽 래퍼 — 뷰포트에 고정
   #impersonate-bar 는 제외: body > div 에 100vh 적용되면 화면 전체를 덮어버림 */
body > div:not(#impersonate-bar), .min-h-screen { height: calc(100vh - var(--imp-bar-h)) !important; overflow: hidden !important; }

/* 사이드바 우측 flex 컬럼 영역 */
.flex-1.flex.flex-col { height: calc(100vh - var(--imp-bar-h)) !important; overflow: hidden !important; }

/* main 영역: 스크롤 제거 후 flex 컬럼으로 */
main { overflow: hidden !important; display: flex !important; flex-direction: column !important;
       padding-bottom: 4px !important; height: 0 !important; flex: 1 !important; }
main > nav { flex-shrink: 0; }  /* breadcrumb */

#g-wrap { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
#g-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-shrink: 0; }
#g-main { flex: 1; min-height: 0; display: flex; overflow: hidden; background: #fff; border: 1px solid #e4e4e7; border-radius: 12px; }

/* Left panel — all column widths via CSS vars; panel = sum of cols */
#g-left {
    --col-name:180px; --col-status:68px; --col-assignee:76px; --col-start:68px; --col-end:68px;
    width:calc(var(--col-name) + var(--col-status) + var(--col-assignee) + var(--col-start) + var(--col-end));
    flex-shrink:0; display:flex; flex-direction:column; border-right:2px solid #e4e4e7;
    overflow:hidden; /* 높이를 g-main에 맞게 제한 → 바디 스크롤 활성화 */
}
#g-left-hdr { display:flex; align-items:center; height:var(--hdr-h); background:#f8fafc; border-bottom:1px solid #e4e4e7; flex-shrink:0; }
#g-left-body { flex:1; min-height:0; overflow-y:auto; overflow-x:hidden; scrollbar-width:none; }
#g-left-body::-webkit-scrollbar { display:none; }

.lh { display:flex; align-items:center; font-size:11.5px; font-weight:600; color:#71717a; text-transform:uppercase; letter-spacing:.04em; padding:0 10px; height:100%; border-right:1px solid #e4e4e7; position:relative; user-select:none; flex-shrink:0; }
.lh:last-child { border-right:none; }
.lh-name     { width:var(--col-name); }
.lh-status   { width:var(--col-status); }
.lh-assignee { width:var(--col-assignee); }
.lh-start    { width:var(--col-start); }
.lh-end      { width:var(--col-end); }

/* Left rows */
.lr { display:flex; align-items:center; border-bottom:1px solid #f4f4f5; cursor:default; }
.lr:hover { background:#fafafa; }
.lr-group { background:#f8fafc; height:var(--grp-h); font-weight:600; border-bottom:1px solid #e4e4e7; }
.lr-group:hover { background:#f1f5f9; }
.lr-task { height:var(--row-h); }
.lc { display:flex; align-items:center; padding:0 10px; height:100%; overflow:hidden; font-size:13px; color:#3f3f46; border-right:1px solid #f4f4f5; flex-shrink:0; }
.lc:last-child { border-right:none; }
.lc-name     { width:var(--col-name); gap:6px; }
.lc-status   { width:var(--col-status); }
.lc-assignee { width:var(--col-assignee); font-size:12px; color:#71717a; cursor:pointer; }
.lc-start    { width:var(--col-start); font-size:12px; color:#71717a; cursor:pointer; }
.lc-end      { width:var(--col-end); font-size:12px; color:#71717a; cursor:pointer; }
.lc-assignee:hover, .lc-start:hover, .lc-end:hover { color:var(--tText); }

/* Column resize handle — right edge of each header cell */
.col-resizer { position:absolute; right:-4px; top:0; bottom:0; width:8px; cursor:col-resize; z-index:20; }
.col-resizer::after { content:''; position:absolute; left:50%; top:20%; bottom:20%; width:2px; transform:translateX(-50%); background:#d4d4d8; border-radius:1px; opacity:0; transition:opacity .15s; }
.col-resizer:hover::after, .col-resizing::after { opacity:1; background:var(--t400,#6366f1); }

.group-toggle { background:none; border:none; cursor:pointer; padding:2px 4px; color:#71717a; display:flex; align-items:center; border-radius:3px; flex-shrink:0; }
.group-toggle:hover { background:#e2e8f0; }
.group-icon { width:9px; height:9px; fill:none; }
.group-label { flex:1; font-size:13px; font-weight:600; color:#18181b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.group-count { font-size:11px; font-weight:500; color:#94a3b8; background:#f1f5f9; padding:1px 6px; border-radius:10px; flex-shrink:0; }
.task-name { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:13px; color:#3f3f46; padding-left:18px; }
.task-name:hover { color:var(--tText); cursor:pointer; }

.sbadge { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:500; padding:2px 7px; border-radius:4px; white-space:nowrap; cursor:pointer; border:1px solid transparent; transition:border-color .12s; }
.sbadge:hover { border-color:currentColor; }
.s-pending .sbadge          { background:#fef3c7; color:#b45309; }
.s-in_progress .sbadge      { background:#dbeafe; color:#1d4ed8; }
.s-completed .sbadge        { background:#dcfce7; color:#15803d; }
.s-cancelled .sbadge        { background:#f3f4f6; color:#6b7280; }
.s-review_submitted .sbadge { background:#ffedd5; color:#c2410c; }
.s-review_completed .sbadge { background:#f3e8ff; color:#7e22ce; }
.s-not_started .sbadge      { background:#f3f4f6; color:#6b7280; }
.s-blocked .sbadge          { background:#fee2e2; color:#b91c1c; }

/* Status dropdown */
#sd { display:none; position:fixed; z-index:10001; background:#fff; border:1px solid #e4e4e7; border-radius:10px; box-shadow:0 6px 24px rgba(0,0,0,.12); overflow:hidden; min-width:140px; padding:4px; }
.sd-opt { display:flex; align-items:center; gap:8px; padding:7px 12px; font-size:13px; font-weight:500; border-radius:6px; cursor:pointer; transition:background .1s; }
.sd-opt:hover { background:#f4f4f5; }
.sd-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }

/* Right panel */
#g-right { flex:1; display:flex; flex-direction:column; overflow:hidden; min-width:0; }
#g-right-hdr { height:var(--hdr-h); overflow:hidden; flex-shrink:0; border-bottom:1px solid #e4e4e7; background:#f8fafc; }
#g-right-hdr-inner { display:flex; flex-direction:column; width:max-content; }
.th-row { display:flex; }
.th-major { display:flex; align-items:center; padding:0 10px; font-size:11.5px; font-weight:600; color:#52525b; border-right:1px solid #e4e4e7; border-bottom:1px solid #e4e4e7; height:22px; flex-shrink:0; white-space:nowrap; background:#f8fafc; }
.th-minor { display:flex; align-items:center; justify-content:center; font-size:11px; color:#94a3b8; border-right:1px solid #f4f4f5; height:30px; flex-shrink:0; white-space:nowrap; }
.th-minor.th-today    { color:var(--tText); font-weight:700; background:var(--t50); }
.th-minor.th-saturday { color:#3b82f6; font-weight:500; }
.th-minor.th-sunday   { color:#ef4444; font-weight:500; }
.g-col-weekend    { position:absolute; top:0; background:rgba(59,130,246,.04); pointer-events:none; }
.g-col-holiday-bg { position:absolute; top:0; background:rgba(239,68,68,.05); pointer-events:none; }

#g-right-body { flex:1; min-height:0; overflow:auto; position:relative; }
#g-canvas { position:relative; }

/* Bars */
.g-row-bg { position:absolute; left:0; right:0; }
.g-row-bg.even { background:#fafafa; }
.g-row-group-bg { position:absolute; left:0; right:0; background:#f8fafc; border-bottom:1px solid #e9edf2; }
.g-today-line { position:absolute; top:0; width:2px; background:#ef4444; opacity:.6; pointer-events:none; z-index:5; }
.g-today-top { position:absolute; top:0; width:10px; height:10px; border-radius:50%; background:#ef4444; margin-left:-4px; z-index:6; }
.g-grid-line { position:absolute; top:0; width:1px; background:#f0f0f0; pointer-events:none; }

.g-group-bar { position:absolute; border-radius:3px; background:#94a3b8; pointer-events:none; z-index:2; }
.g-group-bar::before, .g-group-bar::after { content:''; position:absolute; bottom:0; width:7px; height:7px; background:inherit; clip-path:polygon(0 0,100% 0,0 100%); }
.g-group-bar::after { right:0; clip-path:polygon(100% 0,100% 100%,0 0); }

.g-bar { position:absolute; border-radius:5px; cursor:pointer; z-index:3; overflow:hidden; display:flex; align-items:center; transition:filter .12s, box-shadow .12s; }
.g-bar:hover { filter:brightness(1.06); box-shadow:0 2px 8px rgba(0,0,0,.15); z-index:4; }
.g-bar.s-pending          { background:#f59e0b; }
.g-bar.s-in_progress      { background:#3b82f6; }
.g-bar.s-completed        { background:#22c55e; }
.g-bar.s-cancelled        { background:#9ca3af; }
.g-bar.s-review_submitted { background:#fb923c; }
.g-bar.s-review_completed { background:#a855f7; }
.g-bar.s-not_started      { background:#9ca3af; }
.g-bar.s-blocked          { background:#ef4444; }
.g-bar-prog { position:absolute; left:0; top:0; height:100%; opacity:.3; background:#000; border-radius:5px; pointer-events:none; }
.g-bar-label { position:relative; z-index:1; padding:0 8px; font-size:11.5px; font-weight:500; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; pointer-events:none; }
.g-bar.s-cancelled .g-bar-label, .g-bar.s-not_started .g-bar-label { color:#fff; }
.g-handle { position:absolute; top:0; height:100%; width:7px; cursor:ew-resize; z-index:5; opacity:0; transition:opacity .15s; background:rgba(255,255,255,.35); border-radius:3px; }
.g-bar:hover .g-handle { opacity:1; }
.g-handle-l { left:0; cursor:w-resize; }
.g-handle-r { right:0; cursor:e-resize; }

/* Toolbar */
.vm-group { display:flex; background:#f4f4f5; border-radius:8px; padding:3px; gap:2px; }
.vm-btn { padding:4px 12px; font-size:12px; font-weight:500; border:none; border-radius:6px; cursor:pointer; background:transparent; color:#71717a; transition:all .12s; }
.vm-btn.active { background:#fff; color:#18181b; box-shadow:0 1px 3px rgba(0,0,0,.1); }
.legend-dot { width:10px; height:10px; border-radius:3px; display:inline-block; }
.legend-btn {
    display:inline-flex; align-items:center; gap:0;
    padding:0; font-size:12px; font-weight:500; color:#71717a;
    background:#fff; border:1.5px solid #e4e4e7; border-radius:20px;
    cursor:default; transition:border-color .15s,box-shadow .15s; user-select:none;
    overflow:hidden;
}
.legend-btn:hover { border-color:#a1a1aa; }
.legend-btn.active { border-color:var(--dot); box-shadow:0 0 0 2px var(--dot); color:#18181b; background:#f4f4f5; }
.legend-color-dot {
    width:28px; height:28px; display:flex; align-items:center; justify-content:center;
    cursor:pointer; flex-shrink:0; transition:filter .12s;
    border-right:1px solid #e4e4e7;
}
.legend-color-dot::before {
    content:''; width:11px; height:11px; border-radius:3px;
    background:var(--dot);
}
.legend-color-dot:hover { filter:brightness(.85); }
.legend-color-dot:hover::before { outline:2px solid rgba(0,0,0,.25); outline-offset:1px; }
.legend-label {
    padding:4px 10px 4px 8px; cursor:pointer; transition:color .12s;
}
.legend-label:hover { color:#18181b; }

/* Row drag reorder */
.lr-task[draggable="true"] { cursor: grab; }
.lr-task[draggable="true"]:active { cursor: grabbing; }
.lr-task.row-dragging { opacity:.35; background:#f0ebff!important; }
.lr-task.drop-above { box-shadow: inset 0 2px 0 var(--t500); }
.lr-task.drop-below { box-shadow: inset 0 -2px 0 var(--t500); }
.lr-group.drop-group { background:#eff6ff!important; outline:2px dashed var(--t300); outline-offset:-2px; }
.lr-group.row-dragging { opacity:.35; background:#e0f2fe!important; }
.lr-group.drop-above { box-shadow: inset 0 2px 0 #3b82f6; }
.lr-group.drop-below { box-shadow: inset 0 -2px 0 #3b82f6; }
.drag-handle { display:flex;align-items:center;justify-content:center;width:14px;height:100%;color:#d1d5db;cursor:grab;flex-shrink:0;opacity:0;transition:opacity .15s; }
.lr-task:hover .drag-handle { opacity:1; }
.drag-handle:hover { color:#9ca3af; }
.group-drag-handle { display:flex;align-items:center;justify-content:center;width:16px;height:100%;color:#c7d2fe;cursor:grab;flex-shrink:0;opacity:0;transition:opacity .15s;margin-right:2px; }
.lr-group:hover .group-drag-handle { opacity:1; }
.group-drag-handle:hover { color:#818cf8; }

/* Popup */
#g-popup { display:none; position:fixed; z-index:9999; background:#fff; border:1px solid #e4e4e7; border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,.12); padding:16px; min-width:260px; max-width:320px; }
</style>
<script>
(async function() {
    var bar = document.getElementById('impersonate-bar');
    if (bar) document.documentElement.style.setProperty('--imp-bar-h', bar.offsetHeight + 'px');
})();
</script>

@include('partials.project-nav', ['project'=>$project, 'active'=>'gantt'])
{{-- Toolbar --}}
<div id="g-toolbar">
    <div style="display:flex;align-items:center;gap:8px;">
        <div class="vm-group">
            <button class="vm-btn" onclick="setView('day')">{{ __('projects.view_day') }}</button>
            <button class="vm-btn active" onclick="setView('week')">{{ __('projects.view_week') }}</button>
            <button class="vm-btn" onclick="setView('month')">{{ __('projects.view_month') }}</button>
        </div>
        <button onclick="goToday()" style="padding:4px 12px;font-size:12px;font-weight:500;border:1px solid #e4e4e7;border-radius:6px;background:#fff;color:#52525b;cursor:pointer;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('projects.today') }}</button>
        <span id="g-save-msg" style="font-size:12px;color:#10b981;display:none;">{{ __('projects.saved') }}</span>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
        <span class="legend-btn" id="legend-not_started" data-status="not_started" style="--dot:#9ca3af;"><span class="legend-color-dot" title="{{ __('projects.change_color') }}" onclick="openColorPicker('not_started',event)"></span><span class="legend-label" onclick="toggleStatusFilter('not_started')">{{ __('projects.gantt_status_not_started') }}</span></span>
        <span class="legend-btn" id="legend-in_progress"  data-status="in_progress"  style="--dot:#3b82f6;"><span class="legend-color-dot" title="{{ __('projects.change_color') }}" onclick="openColorPicker('in_progress',event)"></span><span class="legend-label" onclick="toggleStatusFilter('in_progress')">{{ __('projects.gantt_status_in_progress') }}</span></span>
        <span class="legend-btn" id="legend-completed"    data-status="completed"    style="--dot:#22c55e;"><span class="legend-color-dot" title="{{ __('projects.change_color') }}" onclick="openColorPicker('completed',event)"></span><span class="legend-label" onclick="toggleStatusFilter('completed')">{{ __('projects.gantt_status_completed') }}</span></span>
        <span class="legend-btn" id="legend-blocked"      data-status="blocked"      style="--dot:#ef4444;"><span class="legend-color-dot" title="{{ __('projects.change_color') }}" onclick="openColorPicker('blocked',event)"></span><span class="legend-label" onclick="toggleStatusFilter('blocked')">{{ __('projects.gantt_status_blocked') }}</span></span>
        <input type="color" id="g-color-picker" style="position:fixed;opacity:0;pointer-events:none;width:0;height:0;">
        <style id="g-dyn-colors"></style>
        <div style="width:1px;height:18px;background:#e4e4e7;margin:0 2px;flex-shrink:0;"></div>
        <button id="excel-dl-btn" onclick="downloadGanttExcel(event)"
                title="{{ __('projects.excel_download') }}"
                style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;font-size:12px;font-weight:500;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#374151;cursor:pointer;white-space:nowrap;transition:background .12s;"
                onmouseover="this.style.background='#f0fdf4';this.style.borderColor='#86efac';this.style.color='#15803d'"
                onmouseout="this.style.background='#fff';this.style.borderColor='#d1d5db';this.style.color='#374151'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Excel
        </button>
    </div>
</div>

{{-- Main --}}
<div id="g-main">
    {{-- Left Panel --}}
    <div id="g-left">
        <div id="g-left-hdr">
            <div class="lh lh-name">{{ __('projects.col_task_name') }}<div class="col-resizer" data-col="name"></div></div>
            <div class="lh lh-status">{{ __('projects.col_status') }}<div class="col-resizer" data-col="status"></div></div>
            <div class="lh lh-assignee">{{ __('projects.col_assignee') }}<div class="col-resizer" data-col="assignee"></div></div>
            <div class="lh lh-start">{{ __('projects.col_start') }}<div class="col-resizer" data-col="start"></div></div>
            <div class="lh lh-end">{{ __('projects.col_end') }}<div class="col-resizer" data-col="end"></div></div>
        </div>
        <div id="g-left-body"></div>
    </div>

    {{-- Right Panel --}}
    <div id="g-right">
        <div id="g-right-hdr"><div id="g-right-hdr-inner"></div></div>
        <div id="g-right-body">
            <div id="g-canvas"></div>
        </div>
    </div>
</div>

{{-- Task Popup --}}
<div id="g-popup">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
        <div style="flex:1;min-width:0;">
            <p id="pp-group" style="font-size:11px;color:#94a3b8;margin:0 0 2px;"></p>
            <p id="pp-title" style="font-size:14px;font-weight:600;color:#18181b;margin:0;"></p>
        </div>
        <button onclick="closePopup()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:18px;padding:0 0 0 8px;line-height:1;">&times;</button>
    </div>
    <div id="pp-status" style="margin-bottom:8px;"></div>
    <div style="font-size:12px;color:#52525b;margin-bottom:4px;">
        <span id="pp-dates"></span>
    </div>
    <div style="font-size:12px;color:#71717a;margin-bottom:12px;">
        <span id="pp-assignee"></span> · <span id="pp-priority"></span>
    </div>
    <div style="display:flex;gap:6px;">
        <button id="pp-show" type="button" onclick="openEditModal(_ppTid)" style="flex:1;text-align:center;padding:6px;font-size:12px;border:1px solid #e4e4e7;border-radius:7px;color:#52525b;background:#fff;cursor:pointer;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('projects.detail_edit') }}</button>
        <form id="pp-del-form" method="POST" style="flex:1;">
            @csrf @method('DELETE')
            <button type="submit" data-confirm="{{ __('projects.delete_confirm') }}" style="width:100%;padding:6px;font-size:12px;border:1px solid #fecaca;border-radius:7px;color:#ef4444;background:#fff;cursor:pointer;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">{{ __('common.delete') }}</button>
        </form>
    </div>
</div>
<div id="g-overlay" onclick="closePopup()" style="display:none;position:fixed;inset:0;z-index:9998;"></div>

{{-- Status Dropdown --}}
<div id="sd">
    <div class="sd-opt" data-val="not_started">
        <span class="sd-dot" style="background:#9ca3af;"></span>
        <span style="color:#6b7280;">{{ __('projects.gantt_status_not_started') }}</span>
    </div>
    <div class="sd-opt" data-val="in_progress">
        <span class="sd-dot" style="background:#3b82f6;"></span>
        <span style="color:#1d4ed8;">{{ __('projects.gantt_status_in_progress') }}</span>
    </div>
    <div class="sd-opt" data-val="completed">
        <span class="sd-dot" style="background:#22c55e;"></span>
        <span style="color:#15803d;">{{ __('projects.gantt_status_completed') }}</span>
    </div>
    <div class="sd-opt" data-val="blocked">
        <span class="sd-dot" style="background:#ef4444;"></span>
        <span style="color:#b91c1c;">{{ __('projects.gantt_status_blocked') }}</span>
    </div>
</div>
<div id="sd-overlay" style="display:none;position:fixed;inset:0;z-index:10000;" onclick="closeStatusDropdown()"></div>

{{-- 담당자 드롭다운 --}}
<div id="ad" style="display:none;position:fixed;z-index:10001;background:#fff;border:1px solid #e4e4e7;border-radius:10px;box-shadow:0 6px 24px rgba(0,0,0,.12);overflow:hidden;min-width:150px;max-height:220px;overflow-y:auto;padding:4px;"></div>
<div id="ad-overlay" style="display:none;position:fixed;inset:0;z-index:10000;" onclick="closeAssigneeDropdown()"></div>

{{-- 날짜 팝업 --}}
<div id="dp" style="display:none;position:fixed;z-index:10001;background:#fff;border:1px solid #e4e4e7;border-radius:10px;box-shadow:0 6px 24px rgba(0,0,0,.12);padding:14px;min-width:190px;">
    <div style="font-size:11px;font-weight:700;color:#52525b;margin-bottom:8px;letter-spacing:.04em;text-transform:uppercase;" id="dp-label"></div>
    <input type="date" id="dp-input"
           style="width:100%;padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;outline:none;box-sizing:border-box;"
           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"
           onchange="saveDatePopup()">
</div>
<div id="dp-overlay" style="display:none;position:fixed;inset:0;z-index:10000;" onclick="closeDatePopup()"></div>

{{-- 일정 추가 모달 --}}
<div id="add-modal-overlay" onclick="closeAddModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10100;"></div>
<div id="add-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10101;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:620px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;overflow-x:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;">
        <div>
            <p style="font-size:11px;color:#94a3b8;margin:0 0 2px;">{{ $project->name }}</p>
            <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('projects.gantt_add_schedule') }}</h3>
        </div>
        <button onclick="closeAddModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:20px;padding:0;line-height:1;">&times;</button>
    </div>

    <form id="add-schedule-form" style="padding:20px 22px 22px;display:flex;flex-direction:column;gap:14px;">
        @csrf
        {{-- 제목 + 그룹명 --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.schedule_title_required') }} <span style="color:#ef4444;">*</span></label>
                <input type="text" name="title" required placeholder="{{ __('projects.schedule_title') }}"
                       style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                       onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.group_name') }}</label>
                <input type="text" name="group_name" list="am-group-list" placeholder="{{ __('projects.group_placeholder') }}"
                       style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                       onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                <datalist id="am-group-list">
                    @foreach($groupNames as $g)
                    <option value="{{ $g }}">
                    @endforeach
                </datalist>
            </div>
        </div>

        {{-- 설명 --}}
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('common.description') }}</label>
            <textarea name="description" rows="2" placeholder="{{ __('projects.detail_content') }}"
                      style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;font-family:inherit;"
                      onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
        </div>

        {{-- 시작일 / 종료일 --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.start_datetime') }} <span style="color:#ef4444;">*</span></label>
                <div style="display:flex;gap:5px;">
                    <input type="date" id="am-start-d" required
                           style="flex:1;min-width:0;padding:8px 8px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                    <input type="time" id="am-start-t"
                           style="width:110px;flex-shrink:0;padding:8px 6px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.end_datetime') }}</label>
                <div style="display:flex;gap:5px;">
                    <input type="date" id="am-end-d"
                           style="flex:1;min-width:0;padding:8px 8px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                    <input type="time" id="am-end-t"
                           style="width:110px;flex-shrink:0;padding:8px 6px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
            </div>
        </div>

        {{-- 상태 / 우선순위 --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.status') }}</label>
                <select name="status"
                        style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;font-family:inherit;"
                        onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                    <option value="not_started">{{ __('projects.gantt_status_not_started') }}</option>
                    <option value="in_progress">{{ __('projects.gantt_status_in_progress') }}</option>
                    <option value="completed">{{ __('projects.gantt_status_completed') }}</option>
                    <option value="blocked">{{ __('projects.gantt_status_blocked') }}</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.gantt_progress_percent') }}</label>
                <input type="number" name="progress" min="0" max="100"
                       style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;font-family:inherit;"
                       onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
            </div>
        </div>

        {{-- 담당자 --}}
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.assignee') }}</label>
            <select name="assignee_id"
                    style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;font-family:inherit;"
                    onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                <option value="">{{ __('projects.no_assignee') }}</option>
                @foreach($members as $member)
                <option value="{{ $member->id }}">{{ $member->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- 파일 첨부 --}}
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.files') }}</label>
            <div style="border:1.5px dashed #d1d5db;border-radius:8px;padding:10px 12px;text-align:center;cursor:pointer;background:#fafafa;transition:border-color .15s;"
                 onclick="document.getElementById('am-file-input').click()"
                 ondragover="event.preventDefault();this.style.borderColor='var(--t500)'"
                 ondragleave="this.style.borderColor='#d1d5db'"
                 ondrop="event.preventDefault();this.style.borderColor='#d1d5db';amFileDrop(event)">
                <svg style="width:16px;height:16px;color:#9ca3af;display:inline-block;vertical-align:middle;margin-right:5px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <span style="font-size:12px;color:#6b7280;">{{ __('projects.file_upload_hint') }}</span>
            </div>
            <input type="file" id="am-file-input" multiple style="display:none;" onchange="renderAmFiles()">
            <ul id="am-file-list" style="list-style:none;margin:6px 0 0;padding:0;display:flex;flex-direction:column;gap:4px;"></ul>
        </div>

        {{-- 에러 메시지 --}}
        <div id="add-modal-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:9px 12px;font-size:12.5px;color:#dc2626;"></div>

        {{-- 버튼 --}}
        <div style="display:flex;gap:8px;padding-top:4px;">
            <button type="submit" id="add-modal-submit"
                    style="flex:1;padding:9px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:9px;cursor:pointer;transition:background .12s;font-family:inherit;"
                    onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">
                {{ __('common.register') }}
            </button>
            <button type="button" onclick="closeAddModal()"
                    style="padding:9px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">
                {{ __('common.cancel') }}
            </button>
        </div>
    </form>
</div>

{{-- 일정 수정 모달 --}}
<div id="edit-modal-overlay" onclick="closeEditModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10100;"></div>
<div id="edit-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10101;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:620px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;overflow-x:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;">
        <div>
            <p style="font-size:11px;color:#94a3b8;margin:0 0 2px;">{{ $project->name }}</p>
            <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('projects.gantt_edit_schedule') }}</h3>
        </div>
        <button onclick="closeEditModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:20px;padding:0;line-height:1;">&times;</button>
    </div>
    <div style="padding:20px 22px 22px;display:flex;flex-direction:column;gap:14px;">
        <input type="hidden" id="em-tid">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.schedule_title_required') }} <span style="color:#ef4444;">*</span></label>
                <input type="text" id="em-title" required placeholder="{{ __('projects.schedule_title') }}"
                       style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                       onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.group_name') }}</label>
                <input type="text" id="em-group" list="em-group-list" placeholder="{{ __('projects.group_placeholder') }}"
                       style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                       onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                <datalist id="em-group-list">
                    @foreach($groupNames as $g)
                    <option value="{{ $g }}">
                    @endforeach
                </datalist>
            </div>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('common.description') }}</label>
            <textarea id="em-desc" rows="2" placeholder="{{ __('projects.detail_content') }}"
                      style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;font-family:inherit;"
                      onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.start_datetime') }} <span style="color:#ef4444;">*</span></label>
                <div style="display:flex;gap:5px;">
                    <input type="date" id="em-start-d" required
                           style="flex:1;min-width:0;padding:8px 8px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                    <input type="time" id="em-start-t"
                           style="width:110px;flex-shrink:0;padding:8px 6px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.end_datetime') }}</label>
                <div style="display:flex;gap:5px;">
                    <input type="date" id="em-end-d"
                           style="flex:1;min-width:0;padding:8px 8px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                    <input type="time" id="em-end-t"
                           style="width:110px;flex-shrink:0;padding:8px 6px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.status') }}</label>
                <select id="em-status"
                        style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;font-family:inherit;"
                        onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                    <option value="not_started">{{ __('projects.gantt_status_not_started') }}</option>
                    <option value="in_progress">{{ __('projects.gantt_status_in_progress') }}</option>
                    <option value="completed">{{ __('projects.gantt_status_completed') }}</option>
                    <option value="blocked">{{ __('projects.gantt_status_blocked') }}</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.gantt_progress_percent') }}</label>
                <input type="number" id="em-progress" min="0" max="100"
                       style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;font-family:inherit;"
                       onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
            </div>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.assignee') }}</label>
            <select id="em-assignee"
                    style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;font-family:inherit;"
                    onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                <option value="">{{ __('projects.no_assignee') }}</option>
                @foreach($members as $member)
                <option value="{{ $member->id }}">{{ $member->name }}</option>
                @endforeach
            </select>
        </div>
        {{-- 파일 첨부 --}}
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.files') }}</label>
            <ul id="em-existing-files" style="list-style:none;margin:0 0 6px;padding:0;display:flex;flex-direction:column;gap:4px;"></ul>
            <div style="border:1.5px dashed #d1d5db;border-radius:8px;padding:10px 12px;text-align:center;cursor:pointer;background:#fafafa;transition:border-color .15s;"
                 onclick="document.getElementById('em-file-input').click()"
                 ondragover="event.preventDefault();this.style.borderColor='var(--t500)'"
                 ondragleave="this.style.borderColor='#d1d5db'"
                 ondrop="event.preventDefault();this.style.borderColor='#d1d5db';emFileDrop(event)">
                <svg style="width:16px;height:16px;color:#9ca3af;display:inline-block;vertical-align:middle;margin-right:5px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <span style="font-size:12px;color:#6b7280;">{{ __('projects.file_upload_hint') }}</span>
            </div>
            <input type="file" id="em-file-input" multiple style="display:none;" onchange="addEmNewFiles(this.files);this.value=''">
            <ul id="em-new-files" style="list-style:none;margin:6px 0 0;padding:0;display:flex;flex-direction:column;gap:4px;"></ul>
        </div>

        <div id="edit-modal-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:9px 12px;font-size:12.5px;color:#dc2626;"></div>
        <div style="display:flex;gap:8px;padding-top:4px;">
            <button type="button" id="em-submit" onclick="saveEditModal()"
                    style="flex:1;padding:9px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:9px;cursor:pointer;transition:background .12s;font-family:inherit;"
                    onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">{{ __('common.save') }}</button>
            <button type="button" onclick="closeEditModal()"
                    style="padding:9px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('common.cancel') }}</button>
            <button type="button" id="em-delete" onclick="deleteFromEditModal()"
                    style="padding:9px 16px;font-size:13px;font-weight:600;color:#ef4444;background:#fff;border:1.5px solid #fecaca;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">{{ __('common.delete') }}</button>
        </div>
    </div>
</div>

{{-- 파일 목록 팝업 (간트 파일 칩 클릭 시) --}}
<div id="gf-picker" style="display:none;position:fixed;z-index:10200;background:#fff;border:1px solid #e4e4e7;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.14);padding:6px;min-width:240px;max-width:320px;max-height:300px;overflow-y:auto;">
    <div id="gf-picker-list"></div>
</div>
<div id="gf-picker-overlay" style="display:none;position:fixed;inset:0;z-index:10199;" onclick="closeGanttFilePicker()"></div>
@endsection

@section('scripts')
@php
$_allHolidays = [];
for ($_yr = 2023; $_yr <= 2028; $_yr++) {
    $_allHolidays = array_merge($_allHolidays, array_keys(\App\Helpers\KoreanHolidays::getHolidays($_yr)));
}
$_allHolidays = array_values(array_unique($_allHolidays));
@endphp
<script>
const GANTT_STR = {
    yearSuffix:      '{{ __("projects.gantt_year_suffix") }}',
    saveFailed:      '{{ __("projects.save_failed") }}',
    unclassified:    '{{ __("projects.unclassified") }}',
    viewAttachments: @json(__('projects.view_attachments')),
    preview:         @json(__('projects.preview')),
    generating:      @json(__('projects.generating')),
    deleteTaskFailed:@json(__('projects.delete_task_failed')),
    networkError:    @json(__('projects.network_error')),
    excelSheetName:  @json(__('projects.excel_sheet_name')),
    excelFileSuffix: @json(__('projects.excel_file_suffix')),
    statusLabels:    @json([
        'not_started' => __('projects.gantt_status_not_started'),
        'in_progress' => __('projects.gantt_status_in_progress'),
        'completed'   => __('projects.gantt_status_completed'),
        'blocked'     => __('projects.gantt_status_blocked'),
    ]),
    excelCols:       @json([
        __('projects.excel_col_no'),
        __('projects.excel_col_group'),
        __('projects.excel_col_task'),
        __('projects.excel_col_status'),
        __('projects.excel_col_assignee'),
        __('projects.excel_col_start'),
        __('projects.excel_col_end'),
        __('projects.excel_col_progress'),
        __('projects.excel_col_duration'),
    ]),
};
// ─── Data ────────────────────────────────────────────────────────────────────
const ALL_TASKS          = @json($ganttTasks);
const CSRF               = document.querySelector('meta[name="csrf-token"]').content;
const HOLIDAYS           = new Set(@json($_allHolidays));
const SCHEDULE_BASE_URL  = '{{ url("projects/" . $project->id . "/sub-tasks") }}';
const FILES_STORE_URL    = '{{ route("projects.files.store", $project) }}';
const FILE_DEL_BASE      = '{{ url("projects/" . $project->id . "/files") }}';
const PROJECT_ID         = {{ $project->id }};

// 그룹 표시 순서 — 빈 그룹도 새로고침 후 유지 (localStorage)
const GROUP_STORAGE_KEY = 'gantt_groups_{{ $project->id }}';
const knownGroupOrder = JSON.parse(localStorage.getItem(GROUP_STORAGE_KEY) || '[]');
// ALL_TASKS에 있는 그룹 중 localStorage에 없는 것은 뒤에 추가
ALL_TASKS.forEach(t => {
    const g = t.group_name || '{{ __("projects.unclassified") }}';
    if (!knownGroupOrder.includes(g)) knownGroupOrder.push(g);
});
async function saveGroupOrder() {
    localStorage.setItem(GROUP_STORAGE_KEY, JSON.stringify(knownGroupOrder));
}
const UPDATE_URL = '{{ url("projects/" . $project->id . "/gantt") }}';

// ─── Constants ───────────────────────────────────────────────────────────────
const ROW_H  = 36;
const GRP_H  = 40;
const HDR_H  = 52;
const BAR_H  = 22;
const BAR_PAD = (ROW_H - BAR_H) / 2;
const GRP_BAR_H = 10;
const DAY_W  = { day: 38, week: 18, month: 7 };

// ─── State ───────────────────────────────────────────────────────────────────
let viewMode = 'week';
let groups   = {};   // { name: { name, tasks[], expanded } }
let flatRows = [];   // [{ type:'group'|'task', group?|task? }]
let tlStart, tlEnd;
let drag    = null;  // current drag state
let _ppTid  = null;  // task id shown in popup

// ─── DOM ─────────────────────────────────────────────────────────────────────
const leftBody   = document.getElementById('g-left-body');
const rightHdrIn = document.getElementById('g-right-hdr-inner');
const rightBody  = document.getElementById('g-right-body');
const canvas     = document.getElementById('g-canvas');

// ─── Utilities ───────────────────────────────────────────────────────────────
function pdate(s) {
    const [y,m,d] = s.split('-').map(Number);
    return new Date(y, m-1, d);
}
function fdate(d) {
    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}
function fshort(d) { return `${d.getMonth()+1}/${d.getDate()}`; }
function dayDiff(a, b) { return Math.round((b - a) / 86400000); }
function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function escHtml(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function escAttr(s) { return String(s??'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/'/g,'&#39;'); }

const today = new Date(); today.setHours(0,0,0,0);

// ─── Status colors (user-customizable, persisted to localStorage) ────────────
const COLOR_KEY = 'gantt_colors_{{ $project->id }}';
const DEFAULT_STATUS_COLORS = {
    not_started: '#9ca3af',
    in_progress: '#3b82f6',
    completed:   '#22c55e',
    blocked:     '#ef4444',
};
const STATUS_COLORS = Object.assign(
    {...DEFAULT_STATUS_COLORS},
    JSON.parse(localStorage.getItem(COLOR_KEY) || 'null') || {}
);

function hexToRgb(hex) {
    const n = parseInt(hex.replace('#',''), 16);
    return { r:(n>>16)&255, g:(n>>8)&255, b:n&255 };
}
function tintHex(hex, ratio) {   // mix toward white
    const {r,g,b} = hexToRgb(hex);
    const t = n => Math.round(n*ratio + 255*(1-ratio));
    return `rgb(${t(r)},${t(g)},${t(b)})`;
}
function shadeHex(hex, ratio) {  // mix toward black
    const {r,g,b} = hexToRgb(hex);
    const s = n => Math.round(n*ratio);
    return `rgb(${s(r)},${s(g)},${s(b)})`;
}

function applyStatusColors() {
    let css = '';
    Object.entries(STATUS_COLORS).forEach(([st, color]) => {
        const bg   = tintHex(color, 0.18);
        const text = shadeHex(color, 0.55);
        css += `.g-bar.s-${st}{background:${color}!important}\n`;
        css += `.s-${st} .sbadge{background:${bg};color:${text}}\n`;
    });
    document.getElementById('g-dyn-colors').textContent = css;

    // update legend --dot vars
    Object.entries(STATUS_COLORS).forEach(([st, color]) => {
        const btn = document.getElementById(`legend-${st}`);
        if (btn) btn.style.setProperty('--dot', color);
    });

    // keep STATUS_META in sync (bar key used nowhere critical, but keep tidy)
    Object.entries(STATUS_COLORS).forEach(([st, color]) => {
        if (STATUS_META[st]) STATUS_META[st].bar = color;
    });
}

// color picker
let _cpTarget = null;
async function openColorPicker(status, event) {
    event.stopPropagation();
    _cpTarget = status;
    const inp = document.getElementById('g-color-picker');
    inp.value = STATUS_COLORS[status] || '#000000';
    inp.click();
}
document.getElementById('g-color-picker').addEventListener('input', async function() {
    if (!_cpTarget) return;
    STATUS_COLORS[_cpTarget] = this.value;
    applyStatusColors();
    localStorage.setItem(COLOR_KEY, JSON.stringify(STATUS_COLORS));
    // re-render so bar colors update immediately
    renderLeft(); renderCanvas();
});
document.getElementById('g-color-picker').addEventListener('change', async function() {
    _cpTarget = null;
});

// ─── Status filter ───────────────────────────────────────────────────────────
const activeFilters = new Set();

async function toggleStatusFilter(status) {
    if (activeFilters.has(status)) {
        activeFilters.delete(status);
    } else {
        activeFilters.add(status);
    }
    // 버튼 active 상태 갱신
    document.querySelectorAll('.legend-btn').forEach(btn => {
        btn.classList.toggle('active', activeFilters.has(btn.dataset.status));
    });
    buildGroups(); buildFlat(); calcRange();
    renderLeft(); renderHeader(); renderCanvas();
    bindRowDrag();
}

function filteredTasks() {
    if (activeFilters.size === 0) return ALL_TASKS;
    return ALL_TASKS.filter(t => activeFilters.has(t._status));
}

// ─── Build groups ────────────────────────────────────────────────────────────
function buildGroups() {
    // 이전 expanded 상태 보존
    const prevExpanded = {};
    Object.values(groups).forEach(g => { prevExpanded[g.name] = g.expanded; });

    groups = {};

    // 기존에 알려진 그룹 먼저 빈 상태로 생성 (태스크가 없어도 행 유지)
    knownGroupOrder.forEach(name => {
        groups[name] = { name, tasks: [], expanded: prevExpanded[name] !== false };
    });

    // 태스크를 그룹에 배치하고, 새로운 그룹명이 생기면 knownGroupOrder에 추가
    filteredTasks().forEach(t => {
        const g = t.group_name || '{{ __("projects.unclassified") }}';
        if (!groups[g]) {
            groups[g] = { name: g, tasks: [], expanded: true };
            knownGroupOrder.push(g);
        }
        groups[g].tasks.push(t);
    });
}

function buildFlat() {
    flatRows = [];
    // knownGroupOrder 순서대로 렌더 (빈 그룹 포함)
    knownGroupOrder.forEach(name => {
        const g = groups[name];
        if (!g) return;
        flatRows.push({ type: 'group', group: g });
        if (g.expanded) g.tasks.forEach(t => flatRows.push({ type: 'task', task: t }));
    });
}

// ─── Timeline range ──────────────────────────────────────────────────────────
function calcRange() {
    let mn = null, mx = null;
    (filteredTasks().length ? filteredTasks() : ALL_TASKS).forEach(t => {
        const s = pdate(t.start), e = pdate(t.end);
        if (!mn || s < mn) mn = new Date(s);
        if (!mx || e > mx) mx = new Date(e);
    });
    if (!mn) { mn = new Date(today); mx = new Date(today); }
    mn.setDate(mn.getDate() - 14);
    mx.setDate(mx.getDate() + 28);
    // align to Monday
    const dow = mn.getDay();
    mn.setDate(mn.getDate() - (dow === 0 ? 6 : dow - 1));
    tlStart = mn; tlEnd = mx;
}

function totalDays() { return dayDiff(tlStart, tlEnd) + 1; }
function getX(d) { return dayDiff(tlStart, d) * DAY_W[viewMode]; }
function barW(s, e) { return Math.max(DAY_W[viewMode], (dayDiff(s, e) + 1) * DAY_W[viewMode]); }
function totalW() { return totalDays() * DAY_W[viewMode]; }

function rowY(idx) {
    let y = 0;
    for (let i = 0; i < idx; i++) y += flatRows[i].type === 'group' ? GRP_H : ROW_H;
    return y;
}
function totalH() { return flatRows.reduce((s, r) => s + (r.type === 'group' ? GRP_H : ROW_H), 0); }

// ─── Left panel render ───────────────────────────────────────────────────────
async function renderLeft() {
    leftBody.innerHTML = flatRows.map((r, i) => {
        if (r.type === 'group') {
            const g = r.group;
            const dates = groupDateRange(g);
            const icon = g.expanded
                ? '<path d="M1 3l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
                : '<path d="M3 1l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
            return `<div class="lr lr-group" data-g="${esc(g.name)}" draggable="true">
                <div class="lc lc-name">
                    <span class="group-drag-handle" title="{{ __('projects.col_task_name') }}">
                        <svg width="10" height="14" viewBox="0 0 10 14" fill="currentColor"><circle cx="3" cy="2.5" r="1.2"/><circle cx="7" cy="2.5" r="1.2"/><circle cx="3" cy="7" r="1.2"/><circle cx="7" cy="7" r="1.2"/><circle cx="3" cy="11.5" r="1.2"/><circle cx="7" cy="11.5" r="1.2"/></svg>
                    </span>
                    <button class="group-toggle" onclick="toggleGroup('${esc(g.name)}')">
                        <svg class="group-icon" width="10" height="10" viewBox="0 0 10 10" fill="none">${icon}</svg>
                    </button>
                    <span class="group-label">${esc(g.name)}</span>
                    <span class="group-count">${g.tasks.length}</span>
                </div>
                <div class="lc lc-status" style="color:#94a3b8;font-size:12px;">—</div>
                <div class="lc lc-assignee">—</div>
                <div class="lc lc-start">${dates.start ? fshort(dates.start) : '—'}</div>
                <div class="lc lc-end">${dates.end ? fshort(dates.end) : '—'}</div>
            </div>`;
        } else {
            const t = r.task;
            return `<div class="lr lr-task s-${t._status}" data-tid="${t.id}" draggable="true" ondblclick="if(!event.target.closest('.sbadge')&&!event.target.closest('.lc-status')&&!event.target.closest('.lc-assignee')&&!event.target.closest('.lc-start')&&!event.target.closest('.lc-end')&&!event.target.closest('.drag-handle'))openEditModal(${t.id})">
                <div class="lc lc-name">
                    <span class="drag-handle" title="{{ __('projects.col_task_name') }}">
                        <svg width="10" height="14" viewBox="0 0 10 14" fill="currentColor"><circle cx="3" cy="2.5" r="1.2"/><circle cx="7" cy="2.5" r="1.2"/><circle cx="3" cy="7" r="1.2"/><circle cx="7" cy="7" r="1.2"/><circle cx="3" cy="11.5" r="1.2"/><circle cx="7" cy="11.5" r="1.2"/></svg>
                    </span>
                    <span class="task-name" title="${esc(t.name)}">${esc(t.name)}</span>
                    ${t._files_count > 0 ? `<span onclick="event.stopPropagation();openGanttFileChip(${t.id},event)" title="${escAttr(GANTT_STR.viewAttachments)}" style="display:inline-flex;align-items:center;gap:2px;padding:1px 5px;border-radius:8px;font-size:10px;font-weight:600;color:#4f46e5;background:#eef2ff;cursor:pointer;flex-shrink:0;white-space:nowrap;"><svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>${t._files_count}</span>` : ''}
                </div>
                <div class="lc lc-status"><span class="sbadge" data-tid="${t.id}" onclick="openStatusDropdown(event, ${t.id})">${t._status_label}<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:.5;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg></span></div>
                <div class="lc lc-assignee" onclick="openAssigneeDropdown(event,${t.id})" title="{{ __('projects.col_assignee') }}">${esc(t._assignee)}</div>
                <div class="lc lc-start" onclick="openDatePopup(event,${t.id},'start')" title="{{ __('projects.col_start') }}">${fshort(pdate(t.start))}</div>
                <div class="lc lc-end" onclick="openDatePopup(event,${t.id},'end')" title="{{ __('projects.col_end') }}">${fshort(pdate(t.end))}</div>
            </div>`;
        }
    }).join('');
}

function groupDateRange(g) {
    let mn = null, mx = null;
    g.tasks.forEach(t => {
        const s = pdate(t.start), e = pdate(t.end);
        if (!mn || s < mn) mn = new Date(s);
        if (!mx || e > mx) mx = new Date(e);
    });
    return { start: mn, end: mx };
}

// ─── Right header render ─────────────────────────────────────────────────────
async function renderHeader() {
    const tw = totalW();
    const MO = @json(__('projects.month_names'));
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
            const isToday  = day.toDateString() === today.toDateString();
            const dow7     = day.getDay();
            const ds7      = fdate(day);
            const isHol7   = HOLIDAYS.has(ds7);
            let dayClass = '';
            if (isToday)              dayClass = ' th-today';
            else if (isHol7 || dow7 === 0) dayClass = ' th-sunday';
            else if (dow7 === 6)      dayClass = ' th-saturday';
            min += `<div class="th-minor${dayClass}" style="width:${DAY_W.day}px;">${day.getDate()}</div>`;
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
                const days = dayDiff(d.getFullYear() !== yr ? d : new Date(yr,0,1), endD) + 1;
                maj += `<div class="th-major" style="width:${days*DAY_W.month}px;">${yr}${GANTT_STR.yearSuffix}</div>`;
                curYear = yr;
            }
            const nm = new Date(d.getFullYear(), d.getMonth() + 1, 1);
            const days = dayDiff(d, nm < tlEnd ? nm : new Date(tlEnd.getFullYear(), tlEnd.getMonth()+1, 1));
            const isCur = d.getMonth() === today.getMonth() && d.getFullYear() === today.getFullYear();
            min += `<div class="th-minor${isCur?' th-today':''}" style="width:${days*DAY_W.month}px;">${MO[d.getMonth()]}</div>`;
            d = nm;
        }
    }

    rightHdrIn.innerHTML = `
        <div class="th-row" style="width:${tw}px;">${maj}</div>
        <div class="th-row" style="width:${tw}px;">${min}</div>`;
}

// ─── Canvas / bars render ─────────────────────────────────────────────────────
async function renderCanvas() {
    const tw = totalW(), th = totalH();
    canvas.style.width  = tw + 'px';
    canvas.style.height = th + 'px';

    let html = '';
    const dw = DAY_W[viewMode];

    // Grid lines
    if (viewMode === 'day') {
        let d = new Date(tlStart), x = 0;
        while (d <= tlEnd) {
            if (d.getDay() === 1) html += `<div class="g-grid-line" style="left:${x}px;height:${th}px;"></div>`;
            d.setDate(d.getDate() + 1); x += dw;
        }
    } else if (viewMode === 'week') {
        let d = new Date(tlStart), x = 0;
        while (d <= tlEnd) {
            html += `<div class="g-grid-line" style="left:${x}px;height:${th}px;"></div>`;
            d.setDate(d.getDate() + 7); x += dw * 7;
        }
    } else {
        let d = new Date(tlStart.getFullYear(), tlStart.getMonth(), 1);
        while (d <= tlEnd) {
            const x = getX(d);
            html += `<div class="g-grid-line" style="left:${x}px;height:${th}px;"></div>`;
            d = new Date(d.getFullYear(), d.getMonth() + 1, 1);
        }
    }

    // Weekend/holiday column backgrounds (day view only)
    if (viewMode === 'day') {
        let d = new Date(tlStart), x = 0;
        while (d <= tlEnd) {
            const dow = d.getDay();
            const ds  = fdate(d);
            if (dow === 0 || dow === 6 || HOLIDAYS.has(ds)) {
                const cls = (dow === 6 && !HOLIDAYS.has(ds)) ? 'g-col-weekend' : 'g-col-holiday-bg';
                html += `<div class="${cls}" style="left:${x}px;height:${th}px;width:${dw}px;"></div>`;
            }
            d.setDate(d.getDate() + 1); x += dw;
        }
    }

    // Row backgrounds
    let y = 0;
    flatRows.forEach((r, i) => {
        const h = r.type === 'group' ? GRP_H : ROW_H;
        if (r.type === 'group') {
            html += `<div class="g-row-group-bg" style="top:${y}px;height:${h}px;width:${tw}px;"></div>`;
        } else if (i % 2 === 0) {
            html += `<div class="g-row-bg even" style="top:${y}px;height:${h}px;width:${tw}px;"></div>`;
        }
        y += h;
    });

    // Today line
    if (today >= tlStart && today <= tlEnd) {
        const tx = getX(today) + dw / 2;
        html += `<div class="g-today-line" style="left:${tx}px;height:${th}px;"></div>`;
        html += `<div class="g-today-top" style="left:${tx}px;"></div>`;
    }

    // Group bars + task bars
    y = 0;
    flatRows.forEach((r, i) => {
        if (r.type === 'group') {
            const dr = groupDateRange(r.group);
            if (dr.start && dr.end) {
                const gx = getX(dr.start);
                const gw = barW(dr.start, dr.end);
                const gy = y + (GRP_H - GRP_BAR_H) / 2;
                html += `<div class="g-group-bar" style="left:${gx}px;top:${gy}px;width:${gw}px;height:${GRP_BAR_H}px;" title="${esc(r.group.name)}"></div>`;
            }
            y += GRP_H;
        } else {
            const t = r.task;
            const s = pdate(t.start), e = pdate(t.end);
            const bx = getX(s), bw = barW(s, e);
            const by = y + BAR_PAD;
            html += `<div class="g-bar s-${t._status}"
                         data-tid="${t.id}"
                         style="left:${bx}px;top:${by}px;width:${bw}px;height:${BAR_H}px;"
                         title="${esc(t.name)}"
                         ondblclick="openEditModal(${t.id})">
                         <div class="g-bar-prog" style="width:${t.progress}%;"></div>
                         <span class="g-bar-label">${esc(t.name)}</span>
                         <div class="g-handle g-handle-l" data-tid="${t.id}" data-type="l"></div>
                         <div class="g-handle g-handle-r" data-tid="${t.id}" data-type="r"></div>
                     </div>`;
            y += ROW_H;
        }
    });

    canvas.innerHTML = html;
    bindDrag();
}

// ─── Drag handling ────────────────────────────────────────────────────────────
async function bindDrag() {
    canvas.querySelectorAll('.g-bar').forEach(bar => {
        bar.addEventListener('mousedown', onBarMouseDown);
    });
    canvas.querySelectorAll('.g-handle').forEach(h => {
        h.addEventListener('mousedown', onHandleMouseDown);
    });
}

async function onBarMouseDown(e) {
    if (e.target.classList.contains('g-handle')) return;
    e.preventDefault();
    const bar = e.currentTarget;
    const tid = parseInt(bar.dataset.tid);
    drag = { type: 'move', bar, tid, startX: e.clientX, origLeft: parseInt(bar.style.left), origW: parseInt(bar.style.width) };
    document.body.style.cursor = 'grabbing';
}

async function onHandleMouseDown(e) {
    e.preventDefault(); e.stopPropagation();
    const bar = e.currentTarget.closest('.g-bar');
    const tid = parseInt(e.currentTarget.dataset.tid);
    const side = e.currentTarget.dataset.type;
    drag = { type: side === 'l' ? 'resize-l' : 'resize-r', bar, tid, startX: e.clientX, origLeft: parseInt(bar.style.left), origW: parseInt(bar.style.width) };
    document.body.style.cursor = 'ew-resize';
}

document.addEventListener('mousemove', e => {
    if (!drag) return;
    const dx = e.clientX - drag.startX;
    const dw = DAY_W[viewMode];
    if (drag.type === 'move') {
        drag.bar.style.left = Math.max(0, drag.origLeft + dx) + 'px';
    } else if (drag.type === 'resize-l') {
        const nw = drag.origW - dx;
        if (nw >= dw) { drag.bar.style.left = (drag.origLeft + dx) + 'px'; drag.bar.style.width = nw + 'px'; }
    } else {
        const nw = drag.origW + dx;
        if (nw >= dw) drag.bar.style.width = nw + 'px';
    }
});

document.addEventListener('mouseup', e => {
    if (!drag) return;
    const dw = DAY_W[viewMode];
    const left = parseInt(drag.bar.style.left);
    const width = parseInt(drag.bar.style.width);
    const startDays = Math.round(left / dw);
    const endDays   = Math.round((left + width) / dw) - 1;

    const ns = new Date(tlStart); ns.setDate(ns.getDate() + startDays);
    const ne = new Date(tlStart); ne.setDate(ne.getDate() + endDays);

    const task = ALL_TASKS.find(t => t.id === String(drag.tid));
    if (task && (fdate(ns) !== task.start || fdate(ne) !== task.end)) {
        saveTaskDates(drag.tid, fdate(ns), fdate(ne));
        task.start = fdate(ns); task.end = fdate(ne);
        // update left panel dates
        const lr = leftBody.querySelector(`[data-tid="${drag.tid}"]`);
        if (lr) {
            lr.querySelector('.lc-start').textContent = fshort(ns);
            lr.querySelector('.lc-end').textContent   = fshort(ne);
        }
    }
    drag = null;
    document.body.style.cursor = '';
});

async function saveTaskDates(tid, start, end) {
    fetch(`${UPDATE_URL}/${tid}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ start_date: start, end_date: end }),
    })
    .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
    .then(() => { const m = document.getElementById('g-save-msg'); m.style.display='inline'; setTimeout(()=>m.style.display='none', 2500); })
    .catch(err => { console.error('date save failed', err); alert(GANTT_STR.saveFailed); });
}

// ─── Scroll sync ──────────────────────────────────────────────────────────────
let syncing = false;
rightBody.addEventListener('scroll', () => {
    if (syncing) return; syncing = true;
    leftBody.scrollTop = rightBody.scrollTop;
    rightHdrIn.style.transform = `translateX(-${rightBody.scrollLeft}px)`;
    syncing = false;
});
leftBody.addEventListener('scroll', () => {
    if (syncing) return; syncing = true;
    rightBody.scrollTop = leftBody.scrollTop;
    syncing = false;
});

// ─── View mode ────────────────────────────────────────────────────────────────
async function setView(mode) {
    viewMode = mode;
    document.querySelectorAll('.vm-btn').forEach(b => b.classList.remove('active'));
    const map = { day:0, week:1, month:2 };
    document.querySelectorAll('.vm-btn')[map[mode]].classList.add('active');
    renderHeader(); renderCanvas();
    scrollToToday();
}

async function goToday() { scrollToToday(); }

async function scrollToToday() {
    if (today >= tlStart && today <= tlEnd) {
        const x = getX(today) - rightBody.clientWidth / 2;
        rightBody.scrollLeft = Math.max(0, x);
    }
}

// ─── Group toggle ─────────────────────────────────────────────────────────────
async function toggleGroup(name) {
    if (groups[name]) {
        groups[name].expanded = !groups[name].expanded;
        buildFlat(); renderLeft(); renderCanvas();
        bindRowDrag();
    }
}

// ─── Popup ───────────────────────────────────────────────────────────────────
async function showPopup(tid, event) {
    event.stopPropagation();
    const t = ALL_TASKS.find(t => t.id === String(tid));
    if (!t) return;
    document.getElementById('pp-group').textContent   = t.group_name;
    document.getElementById('pp-title').textContent   = t.name;
    const baseColor = STATUS_COLORS[t._status] || '#9ca3af';
    const popBg     = tintHex(baseColor, 0.18);
    const popText   = shadeHex(baseColor, 0.55);
    document.getElementById('pp-status').innerHTML    = `<span style="background:${popBg};color:${popText};padding:3px 9px;border-radius:4px;font-size:12px;font-weight:500;">${t._status_label}</span>`;
    document.getElementById('pp-dates').textContent   = `${t.start} ~ ${t.end}`;
    document.getElementById('pp-assignee').textContent = '{{ __("projects.assigned_label") }}' + t._assignee;
    document.getElementById('pp-priority').textContent = t._priority;
    _ppTid = tid;
    document.getElementById('pp-del-form').action     = t._delete_url;

    const pop = document.getElementById('g-popup');
    const x = Math.min(event.clientX + 12, window.innerWidth - 340);
    const y = Math.min(event.clientY + 12, window.innerHeight - 220);
    pop.style.left = x + 'px'; pop.style.top = y + 'px'; pop.style.display = 'block';
    document.getElementById('g-overlay').style.display = 'block';
}
async function closePopup() {
    document.getElementById('g-popup').style.display = 'none';
    document.getElementById('g-overlay').style.display = 'none';
}

// ─── Status dropdown ──────────────────────────────────────────────────────────
const STATUS_META = {
    not_started: { label:GANTT_STR.statusLabels.not_started, bg:'#f3f4f6', color:'#6b7280', bar:'#9ca3af' },
    in_progress: { label:GANTT_STR.statusLabels.in_progress, bg:'#dbeafe', color:'#1d4ed8', bar:'#3b82f6' },
    completed:   { label:GANTT_STR.statusLabels.completed,   bg:'#dcfce7', color:'#15803d', bar:'#22c55e' },
    blocked:     { label:GANTT_STR.statusLabels.blocked,     bg:'#fee2e2', color:'#b91c1c', bar:'#ef4444' },
};

// ─── Members ─────────────────────────────────────────────────────────────────
const MEMBERS = @json($members->map(fn($m) => ['id' => $m->id, 'name' => $m->name]));

// ─── Assignee dropdown ────────────────────────────────────────────────────────
let adCurrentTid = null;

async function openAssigneeDropdown(event, tid) {
    event.stopPropagation();
    adCurrentTid = tid;
    const task = ALL_TASKS.find(t => t.id === String(tid));
    const ad = document.getElementById('ad');

    let html = `<div class="sd-opt" onclick="selectAssignee(null)"><span class="sd-dot" style="background:#d1d5db;"></span><span style="color:#9ca3af;">{{ __("projects.unassigned") }}</span></div>`;
    MEMBERS.forEach(m => {
        const active = task._assignee_id === m.id;
        html += `<div class="sd-opt" onclick="selectAssignee(${m.id})">
            <span class="sd-dot" style="background:${active ? 'var(--t500)' : '#d1d5db'};"></span>
            <span style="color:#18181b;${active ? 'font-weight:600;' : ''}">${esc(m.name)}</span>
        </div>`;
    });
    ad.innerHTML = html;

    const rect = event.currentTarget.getBoundingClientRect();
    ad.style.left = Math.min(rect.left, window.innerWidth - 170) + 'px';
    ad.style.top  = (rect.bottom + 4) + 'px';
    ad.style.display = 'block';
    document.getElementById('ad-overlay').style.display = 'block';
}

async function closeAssigneeDropdown() {
    document.getElementById('ad').style.display = 'none';
    document.getElementById('ad-overlay').style.display = 'none';
    adCurrentTid = null;
}

async function selectAssignee(memberId) {
    const tid = adCurrentTid;
    closeAssigneeDropdown();
    const task = ALL_TASKS.find(t => t.id === String(tid));
    if (!task) return;

    const member = memberId ? MEMBERS.find(m => m.id === memberId) : null;
    const name = member ? member.name : '{{ __("projects.unassigned") }}';

    fetch(`${UPDATE_URL}/${tid}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ assignee_id: memberId }),
    })
    .then(r => { if (!r.ok) throw new Error(); return r.json(); })
    .then(() => {
        task._assignee = name;
        task._assignee_id = memberId;
        const lr = leftBody.querySelector(`[data-tid="${tid}"]`);
        if (lr) lr.querySelector('.lc-assignee').textContent = name;
        const msg = document.getElementById('g-save-msg');
        msg.style.display = 'inline'; setTimeout(() => msg.style.display = 'none', 2500);
    })
    .catch(() => alert('{{ __("projects.assignee_save_failed") }}'));
}

// ─── Date popup ───────────────────────────────────────────────────────────────
let dpCurrentTid = null, dpCurrentField = null;

async function openDatePopup(event, tid, field) {
    event.stopPropagation();
    dpCurrentTid = tid; dpCurrentField = field;
    const task = ALL_TASKS.find(t => t.id === String(tid));
    if (!task) return;

    document.getElementById('dp-label').textContent = field === 'start' ? '{{ __("projects.start_date_label") }}' : '{{ __("projects.end_date_label") }}';
    document.getElementById('dp-input').value = field === 'start' ? task.start : task.end;

    const dp = document.getElementById('dp');
    const rect = event.currentTarget.getBoundingClientRect();
    dp.style.left = Math.min(rect.left, window.innerWidth - 210) + 'px';
    dp.style.top  = (rect.bottom + 4) + 'px';
    dp.style.display = 'block';
    document.getElementById('dp-overlay').style.display = 'block';
    setTimeout(() => document.getElementById('dp-input').focus(), 50);
}

async function closeDatePopup() {
    document.getElementById('dp').style.display = 'none';
    document.getElementById('dp-overlay').style.display = 'none';
    dpCurrentTid = null; dpCurrentField = null;
}

async function saveDatePopup() {
    const tid = dpCurrentTid, field = dpCurrentField;
    const newDate = document.getElementById('dp-input').value;
    if (!newDate || !tid) return;

    const task = ALL_TASKS.find(t => t.id === String(tid));
    if (!task) return;

    const startDate = field === 'start' ? newDate : task.start;
    const endDate   = field === 'end'   ? newDate : task.end;
    if (startDate > endDate) { alert('{{ __("projects.start_before_end") }}'); return; }

    closeDatePopup();

    const payload = field === 'start' ? { start_date: newDate } : { end_date: newDate };

    fetch(`${UPDATE_URL}/${tid}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(payload),
    })
    .then(r => { if (!r.ok) throw new Error(); return r.json(); })
    .then(() => {
        if (field === 'start') task.start = newDate;
        else task.end = newDate;

        const lr = leftBody.querySelector(`[data-tid="${tid}"]`);
        if (lr) {
            const cell = lr.querySelector(field === 'start' ? '.lc-start' : '.lc-end');
            if (cell) cell.textContent = fshort(pdate(newDate));
        }
        buildFlat(); renderCanvas();

        const msg = document.getElementById('g-save-msg');
        msg.style.display = 'inline'; setTimeout(() => msg.style.display = 'none', 2500);
    })
    .catch(() => alert('{{ __("projects.date_save_failed") }}'));
}

// Enter 키로 날짜 저장
document.getElementById('dp-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') saveDatePopup();
    if (e.key === 'Escape') closeDatePopup();
});

let sdCurrentTid = null;

async function openStatusDropdown(event, tid) {
    event.stopPropagation();
    event.preventDefault();
    sdCurrentTid = tid;

    const sd = document.getElementById('sd');
    const rect = event.currentTarget.getBoundingClientRect();
    const x = Math.min(rect.left, window.innerWidth - 160);
    const y = rect.bottom + 4;
    sd.style.left    = x + 'px';
    sd.style.top     = y + 'px';
    sd.style.display = 'block';
    document.getElementById('sd-overlay').style.display = 'block';
}

async function closeStatusDropdown() {
    document.getElementById('sd').style.display = 'none';
    document.getElementById('sd-overlay').style.display = 'none';
    sdCurrentTid = null;
}

document.querySelectorAll('.sd-opt').forEach(opt => {
    opt.addEventListener('click', () => {
        const newStatus = opt.dataset.val;
        const tid = sdCurrentTid;
        if (!tid || !newStatus) return;
        closeStatusDropdown();
        applyStatusChange(tid, newStatus);
    });
});

async function applyStatusChange(tid, newStatus) {
    fetch(`${UPDATE_URL}/${tid}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ status: newStatus }),
    })
    .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
    .then(() => {
        const task = ALL_TASKS.find(t => t.id === String(tid));
        if (!task) return;
        const meta = STATUS_META[newStatus];
        task._status       = newStatus;
        task._status_label = meta.label;

        try {
            // Update left panel badge
            const lr = leftBody.querySelector(`[data-tid="${tid}"]`);
            if (lr) {
                lr.className = lr.className.replace(/s-\S+/, `s-${newStatus}`);
                const badge = lr.querySelector('.sbadge');
                if (badge) {
                    const chevron = badge.querySelector('svg') ? badge.querySelector('svg').outerHTML : '';
                    badge.innerHTML = meta.label + chevron;
                    badge.setAttribute('data-tid', tid);
                    badge.setAttribute('onclick', `openStatusDropdown(event, ${tid})`);
                }
            }

            // Update bar color on canvas
            const bar = canvas.querySelector(`.g-bar[data-tid="${tid}"]`);
            if (bar) {
                bar.className = `g-bar s-${newStatus}`;
                bar.setAttribute('data-tid', tid);
                bar.setAttribute('onclick', `showPopup(${tid}, event)`);
            }
        } catch(e) { console.warn('UI 업데이트 오류', e); }

        const msg = document.getElementById('g-save-msg');
        msg.style.display = 'inline';
        setTimeout(() => msg.style.display = 'none', 2500);
    })
    .catch(err => { console.error('{{ __("projects.status_save_failed") }}', err); alert('{{ __("projects.status_save_failed") }}'); });
}

// ─── Row Drag Reorder ────────────────────────────────────────────────────────
const REORDER_URL = '{{ route("projects.gantt.reorder", $project) }}';
let rowDrag = null;        // { tid, el }
let rowDropInfo = null;    // { el, position: 'before'|'after' }
let groupDrag = null;      // { name, el } — group being dragged
let groupDragAllowed = false; // mousedown이 핸들에서 시작됐을 때만 true

async function bindRowDrag() {
    leftBody.querySelectorAll('.lr-task').forEach(row => {
        row.addEventListener('dragstart', onRowDragStart);
        row.addEventListener('dragend',   onRowDragEnd);
        row.addEventListener('dragover',  onRowDragOver);
        row.addEventListener('drop',      onRowDrop);
    });
    leftBody.querySelectorAll('.lr-group').forEach(row => {
        row.addEventListener('dragstart', onGroupRowDragStart);
        row.addEventListener('dragend',   onGroupRowDragEnd);
        row.addEventListener('dragover',  onGroupDragOver);
        row.addEventListener('drop',      onGroupDrop);
        const handle = row.querySelector('.group-drag-handle');
        if (handle) {
            handle.addEventListener('mousedown', () => { groupDragAllowed = true; });
            handle.addEventListener('mouseup',   () => { groupDragAllowed = false; });
        }
    });
    leftBody.addEventListener('dragleave', e => {
        if (!leftBody.contains(e.relatedTarget)) clearRowDragUI();
    });
}

async function onGroupRowDragStart(e) {
    if (!groupDragAllowed) { e.preventDefault(); return; }
    groupDragAllowed = false;
    groupDrag = { name: this.dataset.g, el: this };
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', 'group:' + this.dataset.g);
    setTimeout(() => this.classList.add('row-dragging'), 0);
}

async function onGroupRowDragEnd() {
    this.classList.remove('row-dragging');
    clearRowDragUI();
    groupDrag = null;
}

async function onRowDragStart(e) {
    rowDrag = { tid: this.dataset.tid, el: this };
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.dataset.tid);
    setTimeout(() => this.classList.add('row-dragging'), 0);
}

async function onRowDragEnd() {
    this.classList.remove('row-dragging');
    clearRowDragUI();
    rowDrag = null;
}

async function onRowDragOver(e) {
    if (!rowDrag || this.dataset.tid === rowDrag.tid) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const rect = this.getBoundingClientRect();
    const position = e.clientY < rect.top + rect.height / 2 ? 'before' : 'after';
    leftBody.querySelectorAll('.drop-above,.drop-below,.drop-group').forEach(r => r.classList.remove('drop-above','drop-below','drop-group'));
    this.classList.add(position === 'before' ? 'drop-above' : 'drop-below');
    rowDropInfo = { type: 'task', el: this, tid: this.dataset.tid, position };
}

async function onGroupDragOver(e) {
    if (groupDrag) {
        if (groupDrag.name === this.dataset.g) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        leftBody.querySelectorAll('.drop-above,.drop-below,.drop-group').forEach(r => r.classList.remove('drop-above','drop-below','drop-group'));
        const rect = this.getBoundingClientRect();
        const pos = e.clientY < rect.top + rect.height / 2 ? 'before' : 'after';
        this.classList.add(pos === 'before' ? 'drop-above' : 'drop-below');
        rowDropInfo = { type: 'group-reorder', targetName: this.dataset.g, position: pos };
        return;
    }
    if (!rowDrag) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    leftBody.querySelectorAll('.drop-above,.drop-below,.drop-group').forEach(r => r.classList.remove('drop-above','drop-below','drop-group'));
    this.classList.add('drop-group');
    rowDropInfo = { type: 'group', groupName: this.dataset.g };
}

async function onGroupDrop(e) {
    e.preventDefault();

    // ── Group reorder drop ────────────────────────────────────────────────
    if (groupDrag && rowDropInfo && rowDropInfo.type === 'group-reorder') {
        const fromName  = groupDrag.name;
        const toName    = rowDropInfo.targetName;
        const pos       = rowDropInfo.position;
        clearRowDragUI();
        groupDrag = null;

        const fromIdx = knownGroupOrder.indexOf(fromName);
        if (fromIdx === -1) return;
        knownGroupOrder.splice(fromIdx, 1);

        let toIdx = knownGroupOrder.indexOf(toName);
        if (toIdx === -1) return;
        knownGroupOrder.splice(pos === 'before' ? toIdx : toIdx + 1, 0, fromName);

        // ALL_TASKS도 새 그룹 순서에 맞게 재정렬 (하위 업무 포함)
        const reordered = [];
        knownGroupOrder.forEach(gName => {
            ALL_TASKS.filter(t => (t.group_name || GANTT_STR.unclassified) === gName).forEach(t => reordered.push(t));
        });
        ALL_TASKS.splice(0, ALL_TASKS.length, ...reordered);

        buildGroups(); buildFlat();
        renderLeft(); renderCanvas();
        bindRowDrag();

        saveGroupOrder();

        const order = ALL_TASKS.map((t, i) => ({ id: parseInt(t.id), sort_order: i, group_name: t.group_name }));
        fetch(REORDER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ order }),
        })
        .then(r => { if (!r.ok) throw new Error(); })
        .then(() => {
            const m = document.getElementById('g-save-msg');
            m.style.display = 'inline';
            setTimeout(() => m.style.display = 'none', 2000);
        })
        .catch(() => console.error('order save failed'));
        return;
    }

    // ── Task-to-group drop ────────────────────────────────────────────────
    if (!rowDrag || !rowDropInfo || rowDropInfo.type !== 'group') { clearRowDragUI(); return; }
    const fromTid      = rowDrag.tid;
    const targetGroup  = rowDropInfo.groupName;
    clearRowDragUI();

    const fromIdx = ALL_TASKS.findIndex(t => t.id === fromTid);
    if (fromIdx === -1) return;
    const [moved] = ALL_TASKS.splice(fromIdx, 1);
    moved.group_name = targetGroup;

    const targetOrder = knownGroupOrder.indexOf(targetGroup);
    let insertIdx = ALL_TASKS.length;
    for (let i = 0; i < ALL_TASKS.length; i++) {
        const gOrder = knownGroupOrder.indexOf(ALL_TASKS[i].group_name || GANTT_STR.unclassified);
        if (gOrder > targetOrder) { insertIdx = i; break; }
    }
    ALL_TASKS.splice(insertIdx, 0, moved);

    buildGroups(); buildFlat();
    renderLeft(); renderCanvas();
    bindRowDrag();

    const order = ALL_TASKS.map((t, i) => ({ id: parseInt(t.id), sort_order: i, group_name: t.group_name }));
    fetch(REORDER_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ order }),
    })
    .then(r => { if (!r.ok) throw new Error(); })
    .then(() => {
        saveGroupOrder();
        const m = document.getElementById('g-save-msg');
        m.style.display = 'inline';
        setTimeout(() => m.style.display = 'none', 2000);
    })
    .catch(() => console.error('order save failed'));
}

async function onRowDrop(e) {
    e.preventDefault();
    if (!rowDrag || !rowDropInfo || rowDropInfo.type !== 'task' || rowDrag.tid === rowDropInfo.tid) { clearRowDragUI(); return; }
    const fromTid = rowDrag.tid;
    const { tid: toTid, position } = rowDropInfo;
    clearRowDragUI();

    // Reorder ALL_TASKS
    const fromIdx = ALL_TASKS.findIndex(t => t.id === fromTid);
    if (fromIdx === -1) return;
    const [moved] = ALL_TASKS.splice(fromIdx, 1);

    // 드롭 대상 위치의 group_name을 이동 항목에 적용 (그룹 간 이동)
    const targetTask = ALL_TASKS.find(t => t.id === toTid);
    if (targetTask) moved.group_name = targetTask.group_name;

    const toIdx = ALL_TASKS.findIndex(t => t.id === toTid);
    ALL_TASKS.splice(position === 'after' ? toIdx + 1 : toIdx, 0, moved);

    buildGroups(); buildFlat();
    renderLeft(); renderCanvas();
    bindRowDrag();

    // 서버에 순서 저장
    const order = ALL_TASKS.map((t, i) => ({ id: parseInt(t.id), sort_order: i, group_name: t.group_name }));
    fetch(REORDER_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ order }),
    })
    .then(r => { if (!r.ok) throw new Error(); })
    .then(() => {
        saveGroupOrder();
        const m = document.getElementById('g-save-msg');
        m.style.display = 'inline';
        setTimeout(() => m.style.display = 'none', 2000);
    })
    .catch(() => console.warn('order save failed'));
}

async function clearRowDragUI() {
    leftBody.querySelectorAll('.drop-above,.drop-below,.drop-group').forEach(r => r.classList.remove('drop-above','drop-below','drop-group'));
    rowDropInfo = null;
    groupDrag = null;
}

// ─── Column resize ────────────────────────────────────────────────────────────
const COL_STORAGE_KEY = 'gantt_cols_{{ $project->id }}';
const COL_DEFAULTS = { name: 180, status: 68, assignee: 76, start: 68, end: 68 };

function loadColWidths() {
    const gLeft = document.getElementById('g-left');
    const saved = JSON.parse(localStorage.getItem(COL_STORAGE_KEY) || '{}');
    Object.entries(COL_DEFAULTS).forEach(([col, def]) => {
        gLeft.style.setProperty('--col-' + col, (saved[col] || def) + 'px');
    });
}

async function saveColWidths() {
    const gLeft = document.getElementById('g-left');
    const widths = {};
    Object.keys(COL_DEFAULTS).forEach(col => {
        widths[col] = parseInt(getComputedStyle(gLeft).getPropertyValue('--col-' + col)) || COL_DEFAULTS[col];
    });
    localStorage.setItem(COL_STORAGE_KEY, JSON.stringify(widths));
}

async function bindColResize() {
    const gLeft = document.getElementById('g-left');
    const minW = { name: 80, status: 45, assignee: 55, start: 50, end: 50 };
    let active = null;

    document.querySelectorAll('.col-resizer').forEach(handle => {
        handle.addEventListener('mousedown', e => {
            e.preventDefault(); e.stopPropagation();
            const col = handle.dataset.col;
            const startX = e.clientX;
            const startW = parseInt(getComputedStyle(gLeft).getPropertyValue('--col-' + col)) || COL_DEFAULTS[col] || 80;
            handle.classList.add('col-resizing');
            active = { col, startX, startW, handle };
        });
    });

    document.addEventListener('mousemove', e => {
        if (!active) return;
        const dx = e.clientX - active.startX;
        const newW = Math.max(minW[active.col] || 45, active.startW + dx);
        gLeft.style.setProperty('--col-' + active.col, newW + 'px');
    });

    document.addEventListener('mouseup', () => {
        if (!active) return;
        active.handle.classList.remove('col-resizing');
        saveColWidths();
        active = null;
    });
}

// ─── Init ─────────────────────────────────────────────────────────────────────
loadColWidths();
applyStatusColors();
buildGroups(); buildFlat(); calcRange();
renderLeft(); renderHeader(); renderCanvas();
bindRowDrag();
bindColResize();

// ─── 간트 높이 맞춤: 페이지 스크롤 차단, g-wrap을 뷰포트 잔여 높이에 딱 맞춤 ──
var _ganttTop = 0;
async function fitGantt() {
    var main = document.querySelector('main');
    var wrap = document.getElementById('g-wrap');
    if (!main || !wrap) return;
    main.style.overflowY     = 'hidden';
    main.style.overflowX     = 'hidden';
    main.style.paddingBottom = '0';
    // g-wrap 상단이 뷰포트에서 몇 px 아래인지 측정 (레이아웃 강제 계산)
    if (!_ganttTop) _ganttTop = Math.round(wrap.getBoundingClientRect().top);
    wrap.style.height = Math.max(300, window.innerHeight - _ganttTop) + 'px';
}
fitGantt();
window.addEventListener('resize', async function() {
    _ganttTop = 0; // 재측정
    fitGantt();
    calcRange(); renderLeft(); renderHeader(); renderCanvas();
    bindRowDrag();
});

setTimeout(scrollToToday, 200);

// 간트 페이지 떠날 때 html/body overflow 복원
window.addEventListener('pagehide', () => {
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
});

// ─── 일정 수정 모달 ───────────────────────────────────────────────────────────
async function openEditModal(tid) {
    closePopup();
    if (!tid) return;
    const t = ALL_TASKS.find(t => t.id === String(tid));
    if (!t) return;

    document.getElementById('em-tid').value      = tid;
    document.getElementById('em-title').value    = t.name;
    const emGroup = document.getElementById('em-group');
    if (emGroup) emGroup.value = t._group_raw || '';
    document.getElementById('em-desc').value     = t._description || '';
    _setDt('em-start-d', 'em-start-t', t._start_dt || '');
    _setDt('em-end-d',   'em-end-t',   t._end_dt   || '');
    document.getElementById('em-status').value   = t._status;
    const emProgress = document.getElementById('em-progress');
    if (emProgress) emProgress.value = t.progress ?? 0;
    document.getElementById('em-assignee').value = t._assignee_id || '';
    document.getElementById('edit-modal-error').style.display = 'none';

    // 파일 초기화
    _emNewFiles = [];
    document.getElementById('em-file-input').value = '';
    document.getElementById('em-new-files').innerHTML = '';
    renderEmExistingFiles(t._files || []);

    document.getElementById('edit-modal').style.display         = 'block';
    document.getElementById('edit-modal-overlay').style.display = 'block';
}

async function closeEditModal() {
    document.getElementById('edit-modal').style.display         = 'none';
    document.getElementById('edit-modal-overlay').style.display = 'none';
}

async function saveEditModal() {
    const tid = document.getElementById('em-tid').value;
    if (!tid) return;
    const btn   = document.getElementById('em-submit');
    const errEl = document.getElementById('edit-modal-error');
    btn.disabled = true; btn.textContent = '{{ __("projects.saving") }}';
    errEl.style.display = 'none';

    const payload = {
        title:       document.getElementById('em-title').value.trim(),
        description: document.getElementById('em-desc').value,
        start_date:  _dtStr('em-start-d', 'em-start-t'),
        end_date:    _dtStr('em-end-d',   'em-end-t') || null,
        status:      document.getElementById('em-status').value,
        progress:    parseInt(document.getElementById('em-progress')?.value ?? 0, 10),
        assignee_id: document.getElementById('em-assignee').value || null,
    };

    try {
        const res = await fetch(`${SCHEDULE_BASE_URL}/${tid}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        if (res.ok) {
            // 새 파일 업로드
            const uploadedFiles = [];
            if (_emNewFiles.length > 0) {
                for (const file of _emNewFiles) {
                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('sub_task_id', tid);
                    const upRes = await fetch(FILES_STORE_URL, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: fd,
                    });
                    const upData = await upRes.json().catch(() => ({}));
                    if (upData.file) uploadedFiles.push(upData.file);
                }
            }

            const task = ALL_TASKS.find(t => t.id === String(tid));
            if (task) {
                const meta   = STATUS_META[payload.status] || {};
                const member = payload.assignee_id ? MEMBERS.find(m => m.id == payload.assignee_id) : null;
                task.name          = payload.title;
                task._description  = payload.description;
                task._start_dt     = payload.start_date;
                task._end_dt       = payload.end_date || '';
                task.start         = (payload.start_date || '').split('T')[0];
                task.end           = payload.end_date ? payload.end_date.split('T')[0] : task.start;
                task._status       = payload.status;
                task._status_label = meta.label || payload.status;
                task._assignee     = member ? member.name : '{{ __("projects.unassigned") }}';
                task._assignee_id  = payload.assignee_id ? parseInt(payload.assignee_id) : null;
                task.progress      = payload.progress;
                if (!task._files) task._files = [];
                task._files.push(...uploadedFiles);
                task._files_count  = task._files.length;
            }
            _emNewFiles = [];
            closeEditModal();
            buildGroups(); buildFlat(); calcRange();
            renderLeft(); renderHeader(); renderCanvas();
            bindRowDrag();
            const msg = document.getElementById('g-save-msg');
            msg.style.display = 'inline';
            setTimeout(() => msg.style.display = 'none', 2500);
        } else {
            const data = await res.json().catch(() => ({}));
            const msgs = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message || '{{ __("projects.save_failed") }}');
            errEl.textContent = msgs;
            errEl.style.display = 'block';
        }
    } catch(err) {
        errEl.textContent = '{{ __("projects.network_error") }}';
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = '{{ __("common.save") }}';
    }
}

async function deleteFromEditModal() {
    const tid = document.getElementById('em-tid').value;
    if (!tid) return;
    const task = ALL_TASKS.find(t => t.id === String(tid));
    if (!task) return;
    if (!await __confirm(@json(__('projects.delete_task_confirm')).replace(':name', task.name))) return;

    const btn = document.getElementById('em-delete');
    btn.disabled = true;

    try {
        const res = await fetch(task._delete_url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (res.ok) {
            const idx = ALL_TASKS.findIndex(t => t.id === String(tid));
            if (idx !== -1) ALL_TASKS.splice(idx, 1);
            closeEditModal();
            buildGroups(); buildFlat(); calcRange();
            renderLeft(); renderHeader(); renderCanvas();
            bindRowDrag();
        } else {
            const data = await res.json().catch(() => ({}));
            alert(data.message || GANTT_STR.deleteTaskFailed);
        }
    } catch {
        alert(GANTT_STR.networkError);
    } finally {
        btn.disabled = false;
    }
}

// ─── 일정 추가 모달 ───────────────────────────────────────────────────────────
const ADD_STORE_URL = '{{ route("projects.schedules.store", $project) }}';

async function openAddModal() {
    document.getElementById('add-modal').style.display = 'block';
    document.getElementById('add-modal-overlay').style.display = 'block';
    document.getElementById('add-modal-error').style.display = 'none';
    document.getElementById('add-schedule-form').reset();
    // 오늘 날짜 기본값
    const now = new Date();
    const pad = n => String(n).padStart(2,'0');
    document.getElementById('am-start-d').value = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
    document.getElementById('am-start-t').value = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
    document.getElementById('am-end-d').value = '';
    document.getElementById('am-end-t').value = '';
    document.getElementById('am-file-input').value = '';
    document.getElementById('am-file-list').innerHTML = '';
}

async function _dtStr(dateId, timeId) {
    const d = document.getElementById(dateId).value;
    const t = document.getElementById(timeId).value || '00:00';
    return d ? d + 'T' + t : '';
}
async function _setDt(dateId, timeId, dtStr) {
    if (!dtStr) {
        document.getElementById(dateId).value = '';
        document.getElementById(timeId).value = '';
        return;
    }
    const sep = dtStr.indexOf('T');
    document.getElementById(dateId).value = sep >= 0 ? dtStr.slice(0, sep) : dtStr;
    document.getElementById(timeId).value = sep >= 0 ? dtStr.slice(sep + 1, sep + 6) : '';
}

async function closeAddModal() {
    document.getElementById('add-modal').style.display = 'none';
    document.getElementById('add-modal-overlay').style.display = 'none';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeAddModal(); closeEditModal(); } });

// ─── 파일 헬퍼 ────────────────────────────────────────────────────────────────
function fmtBytes(b) {
    if (!b) return '';
    if (b < 1024) return b + 'B';
    if (b < 1048576) return Math.round(b / 1024) + 'KB';
    return (b / 1048576).toFixed(1) + 'MB';
}

function fileItemHtml(name, size, onRemove) {
    return `<div style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:#f8fafc;border:1px solid #e4e4e7;border-radius:7px;font-size:12px;">
        <svg style="width:13px;height:13px;flex-shrink:0;color:#6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${esc(name)}</span>
        <span style="color:#94a3b8;flex-shrink:0;font-size:11px;">${fmtBytes(size)}</span>
        <button type="button" onclick="${onRemove}" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:16px;line-height:1;padding:0;flex-shrink:0;">&times;</button>
    </div>`;
}

// ─── 추가 모달 파일 ───────────────────────────────────────────────────────────
async function amFileDrop(e) {
    const dt = new DataTransfer();
    const input = document.getElementById('am-file-input');
    for (const f of input.files) dt.items.add(f);
    for (const f of e.dataTransfer.files) dt.items.add(f);
    input.files = dt.files;
    renderAmFiles();
}

async function renderAmFiles() {
    const input = document.getElementById('am-file-input');
    const ul = document.getElementById('am-file-list');
    ul.innerHTML = '';
    Array.from(input.files).forEach((file, i) => {
        const li = document.createElement('li');
        li.innerHTML = fileItemHtml(file.name, file.size, `removeAmFile(${i})`);
        ul.appendChild(li);
    });
}

async function removeAmFile(index) {
    const input = document.getElementById('am-file-input');
    const dt = new DataTransfer();
    Array.from(input.files).forEach((f, i) => { if (i !== index) dt.items.add(f); });
    input.files = dt.files;
    renderAmFiles();
}

// ─── 수정 모달 파일 ───────────────────────────────────────────────────────────
let _emNewFiles = [];

async function renderEmExistingFiles(files) {
    const ul = document.getElementById('em-existing-files');
    ul.innerHTML = '';
    (files || []).forEach(f => {
        const li = document.createElement('li');
        li.id = `em-ef-${f.id}`;
        const canPreview = !!f.preview_type;
        li.innerHTML = `<div style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:#f8fafc;border:1px solid #e4e4e7;border-radius:7px;font-size:12px;">
            <svg style="width:13px;height:13px;flex-shrink:0;color:#6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;${canPreview ? 'cursor:pointer;color:#4f46e5;' : ''}" ${canPreview ? `onclick="openGanttFilePreview(${f.id})"` : ''}>${escHtml(f.name)}</span>
            <span style="color:#94a3b8;flex-shrink:0;font-size:11px;">${fmtBytes(f.size)}</span>
            ${canPreview ? `<button type="button" onclick="openGanttFilePreview(${f.id})" style="background:none;border:none;cursor:pointer;color:#7c3aed;padding:0 2px;flex-shrink:0;" title="${escAttr(GANTT_STR.preview)}"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>` : ''}
            <button type="button" onclick="deleteScheduleFile(${f.id},'${escAttr(f.name)}')" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:16px;line-height:1;padding:0;flex-shrink:0;">&times;</button>
        </div>`;
        ul.appendChild(li);
    });
}

async function openGanttFilePreview(fileId) {
    closeEditModal();
    closeGanttFilePicker();
    openPreview(fileId, PROJECT_ID);
}

async function openGanttFileChip(tid, event) {
    const task = ALL_TASKS.find(t => t.id === String(tid));
    if (!task || !task._files || task._files.length === 0) return;

    // 파일이 1개이고 미리보기 가능 → 바로 리뷰 모달 열기
    if (task._files.length === 1 && task._files[0].preview_type) {
        openGanttFilePreview(task._files[0].id);
        return;
    }

    // 여러 파일 또는 미리보기 불가 → 파일 목록 팝업
    showGanttFilePicker(task._files, event);
}

async function showGanttFilePicker(files, event) {
    const list = document.getElementById('gf-picker-list');
    list.innerHTML = files.map(f => {
        const can = !!f.preview_type;
        return `<div onclick="${can ? `closeGanttFilePicker();openGanttFilePreview(${f.id})` : ''}"
                     style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:8px;font-size:12px;cursor:${can ? 'pointer' : 'default'};"
                     onmouseover="this.style.background='${can ? '#f4f4f5' : ''}'"
                     onmouseout="this.style.background=''">
            <svg style="width:13px;height:13px;flex-shrink:0;color:#6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:${can ? '#4f46e5' : '#374151'};">${escHtml(f.name)}</span>
            ${can ? `<svg width="12" height="12" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>` : ''}
        </div>`;
    }).join('');

    const picker = document.getElementById('gf-picker');
    picker.style.left = Math.min(event.clientX, window.innerWidth - 340) + 'px';
    picker.style.top  = Math.min(event.clientY + 6, window.innerHeight - 320) + 'px';
    picker.style.display = 'block';
    document.getElementById('gf-picker-overlay').style.display = 'block';
}

async function closeGanttFilePicker() {
    document.getElementById('gf-picker').style.display = 'none';
    document.getElementById('gf-picker-overlay').style.display = 'none';
}

async function emFileDrop(e) {
    addEmNewFiles(e.dataTransfer.files);
}

async function addEmNewFiles(fileList) {
    for (const f of fileList) _emNewFiles.push(f);
    renderEmNewFiles();
}

async function renderEmNewFiles() {
    const ul = document.getElementById('em-new-files');
    ul.innerHTML = '';
    _emNewFiles.forEach((file, i) => {
        const li = document.createElement('li');
        li.style.cssText = 'background:#eff6ff;border-color:#bfdbfe;';
        li.innerHTML = fileItemHtml(file.name, file.size, `removeEmNewFile(${i})`);
        ul.appendChild(li);
    });
}

async function removeEmNewFile(index) {
    _emNewFiles.splice(index, 1);
    renderEmNewFiles();
}

async function deleteScheduleFile(fileId, fileName) {
    if (!await __confirm(@json(__('projects.delete_file_confirm')).replace(':name', escHtml(fileName)))) return;
    const tid = document.getElementById('em-tid').value;
    const res = await fetch(`${FILE_DEL_BASE}/${fileId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    if (res.ok) {
        const li = document.getElementById(`em-ef-${fileId}`);
        if (li) li.remove();
        const task = ALL_TASKS.find(t => t.id === String(tid));
        if (task) {
            if (task._files) task._files = task._files.filter(f => f.id !== fileId);
            task._files_count = Math.max(0, (task._files_count || 1) - 1);
        }
    }
}

document.getElementById('add-schedule-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('add-modal-submit');
    const errEl = document.getElementById('add-modal-error');
    btn.disabled = true; btn.textContent = '{{ __("projects.saving") }}';
    errEl.style.display = 'none';

    const fd = new FormData(this);
    const amStart = _dtStr('am-start-d', 'am-start-t');
    const amEnd   = _dtStr('am-end-d',   'am-end-t');
    if (amStart) fd.set('start_date', amStart); else fd.delete('start_date');
    if (amEnd)   fd.set('end_date',   amEnd);   else fd.delete('end_date');
    try {
        const res = await fetch(ADD_STORE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: fd,
        });
        if (res.ok) {
            const data = await res.json().catch(() => ({}));
            const fileInput = document.getElementById('am-file-input');
            if (data.id && fileInput.files.length > 0) {
                for (const file of Array.from(fileInput.files)) {
                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('sub_task_id', data.id);
                    await fetch(FILES_STORE_URL, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: fd,
                    });
                }
            }
            closeAddModal();
            location.reload();
        } else {
            const data = await res.json().catch(() => ({}));
            const msgs = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message || '{{ __("projects.save_failed") }}');
            errEl.textContent = msgs;
            errEl.style.display = 'block';
        }
    } catch(err) {
        errEl.textContent = '{{ __("projects.network_error") }}';
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = '{{ __("projects.register") }}';
    }
});

// ── 간트 엑셀 다운로드 ────────────────────────────────────────────────────────
async function downloadGanttExcel(e) {
    const btn = document.getElementById('excel-dl-btn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> ' + escHtml(GANTT_STR.generating);

    try {
        if (typeof ExcelJS === 'undefined') {
            await new Promise((res, rej) => {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js';
                s.onload = res; s.onerror = rej;
                document.head.appendChild(s);
            });
        }

        const wb  = new ExcelJS.Workbook();
        wb.creator = '{{ addslashes($project->name) }}';
        wb.created = new Date();

        const ws = wb.addWorksheet(GANTT_STR.excelSheetName, {
            views: [{ state: 'frozen', ySplit: 4 }],
            pageSetup: { orientation: 'landscape', fitToPage: true, fitToWidth: 1, paperSize: 9 }
        });

        const COLS = 9;
        const COL_W     = [5, 22, 38, 13, 15, 13, 13, 10, 10];
        const COL_HDR   = GANTT_STR.excelCols;
        const COL_ALIGN = ['center','left','left','center','center','center','center','center','center'];
        COL_W.forEach((w, i) => ws.getColumn(i + 1).width = w);

        const STATUS_KO = GANTT_STR.statusLabels;
        const STATUS_BG = { not_started:'FFF3F4F6', in_progress:'FFDBEAFE', completed:'FFD1FAE5', blocked:'FFFEE2E2' };
        const STATUS_FG = { not_started:'FF6B7280', in_progress:'FF1D4ED8', completed:'FF065F46', blocked:'FF991B1B' };

        async function setCell(cell, val, font, fill, align, border) {
            cell.value = val ?? '';
            if (font)   cell.font      = font;
            if (fill)   cell.fill      = { type:'pattern', pattern:'solid', fgColor:{ argb: fill } };
            if (align)  cell.alignment = align;
            if (border) cell.border    = border;
        }
        async function mergeRow(rowNum, fillArgb, font, height) {
            ws.mergeCells(rowNum, 1, rowNum, COLS);
            if (fillArgb) ws.getRow(rowNum).eachCell({ includeEmpty:true }, c => {
                c.fill = { type:'pattern', pattern:'solid', fgColor:{ argb: fillArgb } };
                if (font) c.font = font;
            });
            if (height) ws.getRow(rowNum).height = height;
        }
        const thin = { style:'thin', color:{ argb:'FFE5E7EB' } };
        const medTop = { style:'medium', color:{ argb:'FF94A3B8' } };

        // ── Row 1: 제목 ──────────────────────────────────────
        ws.addRow([@json(__('projects.excel_doc_title', ['name' => $project->name]))]);
        mergeRow(1, 'FF3730A3', { bold:true, size:15, color:{ argb:'FFFFFFFF' } }, 38);
        ws.getCell('A1').alignment = { vertical:'middle', indent:1 };

        // ── Row 2: 메타 ──────────────────────────────────────
        const total = ALL_TASKS.length;
        const done  = ALL_TASKS.filter(t => t._status === 'completed').length;
        ws.addRow([@json(__('projects.excel_export_meta'))
            .replace(':datetime', new Date().toLocaleString('{{ app()->getLocale() }}'))
            .replace(':total', total)
            .replace(':done', done)
            .replace(':percent', Math.round(done/total*100||0))]);
        mergeRow(2, 'FFF8FAFC', { size:9, color:{ argb:'FF6B7280' } }, 20);
        ws.getCell('A2').alignment = { vertical:'middle', indent:1 };

        // ── Row 3: 구분선 ─────────────────────────────────────
        ws.addRow([]);
        ws.getRow(3).height = 4;

        // ── Row 4: 헤더 ──────────────────────────────────────
        const hRow = ws.addRow(COL_HDR);
        hRow.height = 26;
        hRow.eachCell({ includeEmpty:true }, (cell, ci) => {
            cell.font      = { bold:true, size:10, color:{ argb:'FFFFFFFF' } };
            cell.fill      = { type:'pattern', pattern:'solid', fgColor:{ argb:'FF1E293B' } };
            cell.alignment = { horizontal:'center', vertical:'middle' };
            cell.border    = { bottom:{ style:'medium', color:{ argb:'FF334155' } } };
        });

        // ── Data ─────────────────────────────────────────────
        // 그룹 순서 유지
        const seen = new Set(), orderedGroups = [];
        knownGroupOrder.forEach(g => {
            const tasks = ALL_TASKS.filter(t => (t.group_name || GANTT_STR.unclassified) === g);
            if (tasks.length && !seen.has(g)) { seen.add(g); orderedGroups.push({ g, tasks }); }
        });
        ALL_TASKS.forEach(t => {
            const g = t.group_name || GANTT_STR.unclassified;
            if (!seen.has(g)) { seen.add(g); orderedGroups.push({ g, tasks: ALL_TASKS.filter(tt => (tt.group_name||GANTT_STR.unclassified) === g) }); }
        });

        let no = 1;
        orderedGroups.forEach(({ g: gName, tasks }) => {
            // 그룹 헤더
            ws.addRow([gName]);
            const gRowNum = ws.rowCount;
            ws.mergeCells(gRowNum, 1, gRowNum, COLS);
            const gCell = ws.getCell(gRowNum, 1);
            gCell.value     = '▸  ' + gName + '  (' + @json(__('projects.excel_group_count')).replace(':count', tasks.length) + ')';
            gCell.font      = { bold:true, size:10, color:{ argb:'FF312E81' } };
            gCell.fill      = { type:'pattern', pattern:'solid', fgColor:{ argb:'FFE0E7FF' } };
            gCell.alignment = { vertical:'middle', indent:1 };
            gCell.border    = { top:{ style:'thin', color:{ argb:'FFC7D2FE' } }, bottom:{ style:'thin', color:{ argb:'FFC7D2FE' } } };
            ws.getRow(gRowNum).height = 22;

            // 태스크 행
            tasks.forEach((t, ti) => {
                const ms  = new Date(t.end) - new Date(t.start);
                const dur = Math.round(ms / 86400000) + 1;
                const bg  = ti % 2 === 0 ? 'FFFFFFFF' : 'FFF8FAFC';
                const stBg = STATUS_BG[t._status] || 'FFFFFFFF';
                const stFg = STATUS_FG[t._status] || 'FF374151';

                const row = ws.addRow([
                    no++,
                    t.group_name || GANTT_STR.unclassified,
                    t.name,
                    STATUS_KO[t._status] || t._status,
                    t._assignee || '-',
                    t.start,
                    t.end,
                    (t.progress || 0) + '%',
                    dur,
                ]);
                row.height = 20;

                row.eachCell({ includeEmpty:true }, (cell, ci) => {
                    const isStatus = ci === 4;
                    cell.font      = { size:10, color:{ argb: isStatus ? stFg : 'FF374151' } };
                    cell.fill      = { type:'pattern', pattern:'solid', fgColor:{ argb: isStatus ? stBg : bg } };
                    cell.alignment = { horizontal: COL_ALIGN[ci-1] || 'left', vertical:'middle' };
                    cell.border    = { bottom: thin };
                });

                // 진행률 시각적 표시
                const pct = t.progress || 0;
                if (pct === 100) row.getCell(8).font = { size:10, bold:true, color:{ argb:'FF065F46' } };
            });
        });

        // ── 합계 행 ──────────────────────────────────────────
        ws.addRow([]);
        ws.addRow([@json(__('projects.excel_summary'))
            .replace(':total', total)
            .replace(':done', done)
            .replace(':percent', Math.round(done/total*100||0))]);
        const sumRowNum = ws.rowCount;
        ws.mergeCells(sumRowNum, 1, sumRowNum, COLS);
        const sumCell = ws.getCell(sumRowNum, 1);
        sumCell.font      = { bold:true, size:10, color:{ argb:'FF1E293B' } };
        sumCell.fill      = { type:'pattern', pattern:'solid', fgColor:{ argb:'FFF1F5F9' } };
        sumCell.alignment = { vertical:'middle', indent:1 };
        sumCell.border    = { top:{ style:'medium', color:{ argb:'FFCBD5E1' } } };
        ws.getRow(sumRowNum).height = 22;

        // ── 다운로드 ──────────────────────────────────────────
        const fname  = '{{ addslashes($project->name) }}-' + GANTT_STR.excelFileSuffix + '-' + new Date().toISOString().slice(0,10) + '.xlsx';
        const buffer = await wb.xlsx.writeBuffer();
        const blob   = new Blob([buffer], { type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url    = URL.createObjectURL(blob);
        const a      = document.createElement('a');
        a.href = url; a.download = fname;
        document.body.appendChild(a); a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

    } catch(err) {
        alert(@json(__('projects.excel_gen_error')).replace(':message', err.message));
    } finally {
        btn.disabled = false; btn.innerHTML = origHtml;
    }
}
</script>

@include('partials.file-preview-modal')

@endsection
