@extends('layouts.app')

@section('title', 'Tasks')

@section('header-actions')
<button onclick="taskModal(true)"
    style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;background:var(--t600);color:#fff;font-size:13px;font-weight:500;border-radius:8px;border:none;cursor:pointer;">
    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    {{ __('work.task_new') }}
</button>
@endsection

@section('content')

{{-- 기간 필터 탭 --}}
@php
$filters = [
    'all'       => ['label' => __('common.all'),                    'icon' => null],
    'overdue'   => ['label' => __('work.task_filter_overdue'),      'icon' => null],
    'today'     => ['label' => __('work.task_filter_today'),        'icon' => null],
    'this_week' => ['label' => __('work.task_filter_this_week'),    'icon' => null],
    'next_week' => ['label' => __('work.task_filter_next_week'),    'icon' => null],
];
@endphp
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;flex-wrap:wrap;">

    {{-- 기간 필터 탭 --}}
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        @foreach($filters as $key => $f)
        <a href="{{ route('tasks.index', ['filter' => $key, 'project_id' => $projectId]) }}"
            style="display:inline-flex;align-items:center;gap:4px;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:500;text-decoration:none;transition:all .15s;
                   {{ $filter === $key
                       ? 'background:var(--t600);color:#fff;box-shadow:0 2px 8px rgba(0,0,0,.15);'
                       : 'background:#fff;color:#6b7280;border:1px solid #e5e7eb;' }}">
            {{ $f['label'] }}
            @if($key === 'overdue' && $overdueCount > 0)
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:17px;height:17px;padding:0 4px;background:{{ $filter==='overdue' ? 'rgba(255,255,255,.3)' : '#ef4444' }};color:#fff;font-size:10px;font-weight:700;border-radius:10px;">{{ $overdueCount }}</span>
            @endif
        </a>
        @endforeach
    </div>

    {{-- 프로젝트 필터 드롭다운 --}}
    <div x-data="{ open: false }" style="position:relative;">
        <button @click="open=!open"
            style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:500;border:1px solid {{ $projectId ? 'var(--t400)' : '#e5e7eb' }};background:{{ $projectId ? 'var(--t50)' : '#fff' }};color:{{ $projectId ? 'var(--tText)' : '#6b7280' }};cursor:pointer;transition:all .15s;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
            {{ $selectedProject ? $selectedProject->name : __('work.task_project_label') }}
            @if($projectId)
            <a href="{{ route('tasks.index', ['filter' => $filter]) }}"
                @click.stop
                style="display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;border-radius:50%;background:var(--t300);color:#fff;text-decoration:none;font-size:10px;line-height:1;">×</a>
            @endif
            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform .15s;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="open" @click.outside="open=false" x-transition
            style="position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.1);z-index:50;min-width:220px;padding:6px;overflow:hidden;">
            <a href="{{ route('tasks.index', ['filter' => $filter]) }}"
                style="display:block;padding:7px 12px;font-size:12px;border-radius:7px;text-decoration:none;color:{{ !$projectId ? 'var(--tText)' : '#374151' }};background:{{ !$projectId ? 'var(--t50)' : 'transparent' }};">
                {{ __('common.all') }}
            </a>
            @if($projects->count())
            <div style="height:1px;background:#f3f4f6;margin:4px 0;"></div>
            <div style="padding:3px 10px 2px;font-size:10px;font-weight:600;color:#9ca3af;letter-spacing:.04em;">{{ __('work.task_project_joined') }}</div>
            @foreach($projects as $proj)
            <div style="display:flex;align-items:center;gap:8px;padding:4px 6px 4px 12px;border-radius:7px;background:{{ $projectId == $proj->id ? 'var(--t50)' : 'transparent' }};">
                <a href="{{ route('tasks.index', ['filter' => $filter, 'project_id' => $proj->id]) }}"
                    @click="open=false"
                    style="display:flex;align-items:center;gap:8px;flex:1;min-width:0;padding:3px 0;font-size:12px;text-decoration:none;color:{{ $projectId == $proj->id ? 'var(--tText)' : '#374151' }};">
                    <span style="width:7px;height:7px;border-radius:2px;flex-shrink:0;background:var(--t400);"></span>
                    <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj->name }}</span>
                    @php $cnt = $todo->where('project_id', $proj->id)->count() + $inProgress->where('project_id', $proj->id)->count(); @endphp
                    @if($cnt > 0)
                    <span style="font-size:10px;background:#f3f4f6;color:#6b7280;padding:1px 6px;border-radius:8px;">{{ $cnt }}</span>
                    @endif
                </a>
                @if(!auth()->user()->isAdmin())
                <button onclick="taskPageLeave({{ $proj->id }}, '{{ addslashes($proj->name) }}', this)"
                    data-leave-url="{{ route('projects.leave', $proj) }}"
                    title="{{ __('work.task_leave_project') }}"
                    style="padding:2px 6px;font-size:10px;font-weight:600;border-radius:5px;border:1px solid #fca5a5;background:#fff;color:#dc2626;cursor:pointer;flex-shrink:0;opacity:.7;"
                    onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='.7'">{{ __('work.task_leave_project') }}</button>
                @endif
            </div>
            @endforeach
            @endif
            @if(!auth()->user()->isAdmin() && $allProjects->count())
            <div style="height:1px;background:#f3f4f6;margin:4px 0;"></div>
            <div style="padding:3px 10px 2px;font-size:10px;font-weight:600;color:#9ca3af;letter-spacing:.04em;">{{ __('work.task_project_not_joined') }}</div>
            @foreach($allProjects as $proj)
            <div style="display:flex;align-items:center;gap:8px;padding:6px 12px;border-radius:7px;">
                <span style="width:7px;height:7px;border-radius:2px;flex-shrink:0;background:#d1d5db;"></span>
                <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;color:#9ca3af;">{{ $proj->name }}</span>
                <button onclick="taskPageJoin({{ $proj->id }}, '{{ addslashes($proj->name) }}', this)"
                    data-join-url="{{ route('projects.join', $proj) }}"
                    style="padding:2px 8px;font-size:10px;font-weight:600;border-radius:6px;border:1.5px solid #c7d2fe;background:#eef2ff;color:#4f46e5;cursor:pointer;white-space:nowrap;flex-shrink:0;">
                    {{ __('work.task_project_join') }}
                </button>
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>

{{-- 활성 필터 요약 --}}
@if($filter !== 'all' || $projectId)
<div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;padding:8px 14px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;font-size:12px;color:#6b7280;">
    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
    <span>{{ __('work.task_filter_label') }}</span>
    @if($filter !== 'all')
        <span style="background:var(--t100);color:var(--tText);padding:2px 8px;border-radius:6px;font-weight:500;">
            {{ ['overdue'=>__('work.task_filter_overdue'),'today'=>__('work.task_filter_today'),'this_week'=>__('work.task_filter_this_week'),'next_week'=>__('work.task_filter_next_week')][$filter] }}
        </span>
    @endif
    @if($selectedProject)
        <span style="background:#ede9fe;color:#7c3aed;padding:2px 8px;border-radius:6px;font-weight:500;">{{ $selectedProject->name }}</span>
    @endif
    <span style="margin-left:auto;color:#374151;font-weight:500;">{{ __('work.task_col_todo') }} {{ $todo->count() }} · {{ __('work.task_col_in_progress') }} {{ $inProgress->count() }}</span>
</div>
@endif

{{-- 태스크 추가 모달 --}}
<div id="task-add-modal" style="display:none;position:fixed;inset:0;z-index:9000;align-items:center;justify-content:center;background:rgba(0,0,0,.3);">
    <div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <span style="font-size:15px;font-weight:600;color:#111827;">{{ __('work.task_add_modal_heading') }}</span>
            <button onclick="taskModal(false)"
                style="width:28px;height:28px;border:none;background:transparent;cursor:pointer;border-radius:6px;font-size:18px;color:#6b7280;">×</button>
        </div>
        <form action="{{ route('tasks.store') }}" method="POST">
            @csrf
            <div style="display:flex;flex-direction:column;gap:12px;">
                <input type="text" name="title" placeholder="{{ __('work.task_title_placeholder') }}" required
                    style="padding:9px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;">
                <textarea name="description" placeholder="{{ __('work.task_desc_placeholder') }}" rows="2"
                    style="padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;resize:vertical;"></textarea>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:4px;">{{ __('common.priority') }}</label>
                        <select name="priority" style="width:100%;padding:7px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;">
                            <option value="high">{{ __('work.task_priority_high') }}</option>
                            <option value="medium" selected>{{ __('work.task_priority_medium') }}</option>
                            <option value="low">{{ __('work.task_priority_low') }}</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:4px;">{{ __('work.task_due_date_label') }}</label>
                        <input type="date" name="due_date"
                            style="width:100%;padding:7px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;box-sizing:border-box;">
                    </div>
                </div>
                @if($projects->count())
                <div>
                    <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:4px;">{{ __('work.task_project_link') }}</label>
                    <select name="project_id" style="width:100%;padding:7px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;">
                        <option value="">{{ __('work.task_project_none') }}</option>
                        @foreach($projects as $proj)
                        <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <div style="display:flex;gap:8px;margin-top:16px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('task-add-modal').classList.add('hidden')"
                    style="padding:8px 16px;font-size:13px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;color:#6b7280;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button type="submit"
                    style="padding:8px 20px;font-size:13px;background:var(--t600);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:500;">{{ __('common.add') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- 칸반 보드 --}}
<style>
.task-card { cursor: grab; transition: opacity .15s, box-shadow .15s; }
.task-card:active { cursor: grabbing; }
.task-card.dragging { opacity: .35; box-shadow: 0 4px 16px rgba(109,40,217,.2); }
.kanban-col { transition: background .15s, border-color .15s; border: 2px dashed transparent; border-radius: 10px; padding: 2px; min-height: 60px; }
.kanban-col.drag-over { background: rgba(109,40,217,.05); border-color: #a78bfa; }
</style>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;align-items:start;">

    {{-- 컬럼 정의 --}}
    @php
    $columns = [
        ['key'=>'todo',        'label'=>__('work.task_col_todo'),        'color'=>'#e5e7eb', 'dot'=>'#6b7280',  'next'=>'in_progress', 'nextLabel'=>__('work.task_move_start')],
        ['key'=>'in_progress', 'label'=>__('work.task_col_in_progress'), 'color'=>'#dbeafe', 'dot'=>'#3b82f6',  'next'=>'done',        'nextLabel'=>__('work.task_move_done')],
        ['key'=>'done',        'label'=>__('work.task_col_done'),        'color'=>'#dcfce7', 'dot'=>'#16a34a',  'next'=>null,          'nextLabel'=>null],
    ];
    $colData = ['todo'=>$todo, 'in_progress'=>$inProgress, 'done'=>$done];
    @endphp

    @foreach($columns as $col)
    @php $colItems = $colData[$col['key']]; @endphp
    <div>
        {{-- 컬럼 헤더 --}}
        <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:{{ $col['color'] }};border-radius:10px;margin-bottom:10px;position:sticky;top:0;z-index:1;">
            <span style="width:8px;height:8px;border-radius:50%;background:{{ $col['dot'] }};flex-shrink:0;"></span>
            <span style="font-size:13px;font-weight:600;color:#374151;">{{ $col['label'] }}</span>
            <span data-col-count="{{ $col['key'] }}" style="margin-left:auto;font-size:11px;font-weight:600;background:rgba(0,0,0,.08);color:#374151;padding:1px 7px;border-radius:10px;">{{ $colItems->count() }}</span>
            @if($filter !== 'all' && $col['key'] === 'done')
            <span style="font-size:10px;color:#9ca3af;font-weight:400;">{{ __('work.task_filter_not_applied') }}</span>
            @endif
        </div>

        {{-- 태스크 카드 --}}
        <div class="kanban-col" data-col="{{ $col['key'] }}" style="display:flex;flex-direction:column;gap:8px;max-height:calc(100vh - 200px);overflow-y:auto;padding-right:4px;">
            @forelse($colItems as $task)
            <div class="task-card" draggable="true" data-task-id="{{ $task->id }}" data-status="{{ $col['key'] }}" style="background:#fff;border:1px solid #f3f4f6;border-radius:10px;padding:12px 14px;box-shadow:0 1px 4px rgba(0,0,0,.05);">
                <div style="display:flex;align-items:flex-start;gap:8px;">
                    <span style="width:8px;height:8px;border-radius:50%;margin-top:4px;flex-shrink:0;background:{{ $task->priority==='high'?'#ef4444':($task->priority==='medium'?'#f59e0b':'#10b981') }};"></span>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:13px;font-weight:500;color:#111827;word-break:break-word;">{{ $task->title }}</p>
                        @if($task->description)
                        <p style="font-size:11px;color:#9ca3af;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $task->description }}</p>
                        @endif
                        <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:6px;">
                            @if($task->due_date)
                            <span style="font-size:11px;padding:1px 7px;border-radius:8px;
                                {{ $task->due_date->lt(now()->startOfDay()) && $task->status!=='done' ? 'background:#fee2e2;color:#dc2626;' : 'background:#f3f4f6;color:#6b7280;' }}">
                                {{ $task->due_date->format('m/d') }}
                            </span>
                            @endif
                            @if($task->project)
                            <span style="font-size:11px;padding:1px 7px;border-radius:8px;background:#ede9fe;color:#7c3aed;">{{ $task->project->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 액션 버튼 --}}
                <div style="display:flex;gap:4px;margin-top:10px;padding-top:8px;border-top:1px solid #f3f4f6;">
                    @if($col['next'])
                    <form action="{{ route('tasks.status', $task) }}" method="POST" style="flex:1;">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $col['next'] }}">
                        <button type="submit" style="width:100%;padding:4px;font-size:11px;font-weight:500;background:var(--t50);color:var(--tText);border:1px solid var(--t200);border-radius:6px;cursor:pointer;">{{ $col['nextLabel'] }}</button>
                    </form>
                    @else
                    <form action="{{ route('tasks.status', $task) }}" method="POST" style="flex:1;">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="in_progress">
                        <button type="submit" style="width:100%;padding:4px;font-size:11px;font-weight:500;background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;">{{ __('work.task_move_revert') }}</button>
                    </form>
                    @endif
                    <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('{{ __('work.task_confirm_delete') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" style="padding:4px 8px;font-size:11px;background:#fff;color:#d1d5db;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444'" onmouseout="this.style.background='#fff';this.style.color='#d1d5db'">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="kanban-empty" style="padding:20px;text-align:center;background:#fafafa;border:1px dashed #e5e7eb;border-radius:10px;">
                <p style="font-size:12px;color:#9ca3af;">{{ __('work.task_empty') }}</p>
            </div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>

<script>
const _TASK_CSRF = document.querySelector('meta[name="csrf-token"]').content;
const _TASK_STR = {
    joinFailed:    '{{ __("work.task_join_failed") }}',
    leaveFailed:   '{{ __("work.task_leave_failed") }}',
    leaveConfirm:  '{{ __("work.task_leave_confirm") }}',
    empty:         '{{ __("work.task_empty") }}',
};

async function taskPageJoin(projectId, projectName, btn) {
    btn.disabled = true;
    const res = await fetch(btn.dataset.joinUrl, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _TASK_CSRF },
    });
    const d = await res.json().catch(() => ({}));
    btn.disabled = false;
    if (!res.ok || !d.ok) { alert(d.message || _TASK_STR.joinFailed); return; }
    location.reload();
}

async function taskPageLeave(projectId, projectName, btn) {
    if (!await __confirm(_TASK_STR.leaveConfirm.replace(':name', projectName))) return;
    btn.disabled = true;
    const res = await fetch(btn.dataset.leaveUrl, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _TASK_CSRF },
    });
    const d = await res.json().catch(() => ({}));
    btn.disabled = false;
    if (!res.ok || !d.ok) { alert(d.message || _TASK_STR.leaveFailed); return; }
    location.reload();
}

async function taskModal(open) {
    var el = document.getElementById('task-add-modal');
    el.style.display = open ? 'flex' : 'none';
}
document.getElementById('task-add-modal').addEventListener('click', async function(e) {
    if (e.target === this) taskModal(false);
});

// ── 칸반 드래그 & 드롭 ────────────────────────────────────
(async function() {
    let dragged = null;

    function getColCount(key) {
        return document.querySelector(`[data-col-count="${key}"]`);
    }
    async function updateCount(key) {
        const col = document.querySelector(`.kanban-col[data-col="${key}"]`);
        if (!col) return;
        const n = col.querySelectorAll('.task-card').length;
        const badge = getColCount(key);
        if (badge) badge.textContent = n;
        // 빈 상태 placeholder 관리
        let empty = col.querySelector('.kanban-empty');
        if (n === 0 && !empty) {
            empty = document.createElement('div');
            empty.className = 'kanban-empty';
            empty.style.cssText = 'padding:20px;text-align:center;background:#fafafa;border:1px dashed #e5e7eb;border-radius:10px;';
            empty.innerHTML = `<p style="font-size:12px;color:#9ca3af;">${_TASK_STR.empty}</p>`;
            col.appendChild(empty);
        } else if (n > 0 && empty) {
            empty.remove();
        }
    }

    document.addEventListener('dragstart', async function(e) {
        const card = e.target.closest('.task-card');
        if (!card) return;
        dragged = card;
        setTimeout(() => card.classList.add('dragging'), 0);
        e.dataTransfer.effectAllowed = 'move';
    });
    document.addEventListener('dragend', async function(e) {
        const card = e.target.closest('.task-card');
        if (card) card.classList.remove('dragging');
        document.querySelectorAll('.kanban-col').forEach(c => c.classList.remove('drag-over'));
        dragged = null;
    });
    document.querySelectorAll('.kanban-col').forEach(async function(col) {
        col.addEventListener('dragover', async function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            col.classList.add('drag-over');
        });
        col.addEventListener('dragleave', async function(e) {
            if (!col.contains(e.relatedTarget)) col.classList.remove('drag-over');
        });
        col.addEventListener('drop', async function(e) {
            e.preventDefault();
            col.classList.remove('drag-over');
            if (!dragged) return;
            const newStatus = col.dataset.col;
            const oldStatus = dragged.dataset.status;
            if (newStatus === oldStatus) return;

            const taskId   = dragged.dataset.taskId;
            const csrf     = document.querySelector('meta[name="csrf-token"]').content;

            // 낙관적 UI 업데이트
            col.appendChild(dragged);
            dragged.dataset.status = newStatus;
            updateCount(oldStatus);
            updateCount(newStatus);

            fetch(`/tasks/${taskId}/status`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: newStatus }),
            })
            .then(r => r.json())
            .then(d => { if (!d.ok) location.reload(); })
            .catch(() => location.reload());
        });
    });
})();
</script>
@endsection
