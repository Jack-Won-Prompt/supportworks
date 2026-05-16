@extends('layouts.app')

@section('title', $project->name . ' - ' . __('projects.schedule_management'))

@section('header-actions')@endsection

@section('page-actions')
    <button onclick="openMilestoneModal()"
            style="padding:6px 14px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;"
            onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">{{ __('projects.add_milestone') }}</button>
@endsection

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('projects.schedule_management') }}</span>
@endsection

@section('content')
@include('partials.project-nav', ['project'=>$project, 'active'=>'schedules'])

@php
$_gc = [
    ['border'=>'#4f46e5','bg'=>'#eef2ff','title'=>'#4338ca','dot'=>'#6366f1'],  // indigo
    ['border'=>'#0891b2','bg'=>'#ecfeff','title'=>'#0e7490','dot'=>'#06b6d4'],  // cyan
    ['border'=>'#059669','bg'=>'#f0fdf4','title'=>'#047857','dot'=>'#10b981'],  // emerald
    ['border'=>'#d97706','bg'=>'#fffbeb','title'=>'#b45309','dot'=>'#f59e0b'],  // amber
    ['border'=>'#e11d48','bg'=>'#fff1f2','title'=>'#be123c','dot'=>'#f43f5e'],  // rose
    ['border'=>'#7c3aed','bg'=>'#faf5ff','title'=>'#6d28d9','dot'=>'#8b5cf6'],  // violet
    ['border'=>'#2563eb','bg'=>'#eff6ff','title'=>'#1d4ed8','dot'=>'#3b82f6'],  // blue
    ['border'=>'#0d9488','bg'=>'#f0fdfa','title'=>'#0f766e','dot'=>'#14b8a6'],  // teal
];
@endphp

<div class="pt-4" id="schedule-tree">

@forelse($tree['milestones'] as $milestone)
<div class="mb-4 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden milestone-block" data-milestone-id="{{ $milestone->id }}">
    {{-- Milestone header --}}
    <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-b border-gray-100 cursor-pointer select-none"
         onclick="toggleMilestone({{ $milestone->id }})">
        <div class="flex items-center gap-3">
            <svg id="ms-chevron-{{ $milestone->id }}" class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
            <span class="font-semibold text-gray-800">{{ $milestone->title }}</span>
            @php
                $statusColor = ['planned'=>'gray','in_progress'=>'blue','completed'=>'green','cancelled'=>'red'][$milestone->status] ?? 'gray';
                $statusLabel = \App\Models\Milestone::STATUS_LABELS[$milestone->status] ?? $milestone->status;
            @endphp
            <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700">{{ $statusLabel }}</span>
            @if($milestone->target_date)
                <span class="text-xs text-gray-400">{{ __('projects.milestone_target', ['date' => $milestone->target_date->format('Y-m-d')]) }}</span>
            @endif
        </div>
        <div class="flex items-center gap-2" onclick="event.stopPropagation()">
            <button onclick="openGroupModal({{ $milestone->id }})"
                    class="px-3 py-1 text-xs text-indigo-600 border border-indigo-200 rounded hover:bg-indigo-50">{{ __('projects.add_group') }}</button>
            <button onclick="openEditMilestone({{ $milestone->id }}, '{{ addslashes($milestone->title) }}', '{{ $milestone->status }}', '{{ $milestone->target_date?->format('Y-m-d') ?? '' }}')"
                    class="p-1 text-gray-400 hover:text-gray-600 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            </button>
            <button onclick="deleteMilestone({{ $milestone->id }})"
                    class="p-1 text-gray-400 hover:text-red-500 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    </div>

    {{-- Task groups --}}
    <div id="ms-body-{{ $milestone->id }}">
    @forelse($milestone->taskGroups as $group)
    @php $gc = $_gc[$group->id % count($_gc)]; @endphp
    <div class="border-b border-gray-50 group-block" data-group-id="{{ $group->id }}"
         style="border-left:3px solid {{ $gc['border'] }};">
        {{-- Group header --}}
        <div class="flex items-center justify-between px-5 py-2 cursor-pointer"
             style="background:{{ $gc['bg'] }};"
             onmouseover="this.style.filter='brightness(.97)'" onmouseout="this.style.filter=''"
             onclick="toggleGroup({{ $group->id }})">
            <div class="flex items-center gap-2 pl-5">
                <svg id="grp-chevron-{{ $group->id }}" class="w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     style="color:{{ $gc['dot'] }};">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $gc['dot'] }};"></span>
                <span class="text-sm font-semibold" style="color:{{ $gc['title'] }};">{{ $group->title }}</span>
                <span class="text-xs" style="color:{{ $gc['dot'] }};opacity:.7;">({{ __('projects.task_count', ['count' => $group->subTasks->count()]) }})</span>
            </div>
            <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                <button onclick="openTaskModal({{ $group->id }}, {{ $milestone->id }})"
                        class="px-2.5 py-1 text-xs text-green-600 border border-green-200 rounded hover:bg-green-50">{{ __('projects.add_task') }}</button>
                <button onclick="openEditGroup({{ $group->id }}, '{{ addslashes($group->title) }}', {{ $milestone->id }})"
                        class="p-1 text-gray-300 hover:text-gray-600 rounded">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </button>
                <button onclick="deleteGroup({{ $group->id }})"
                        class="p-1 text-gray-300 hover:text-red-500 rounded">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>

        {{-- Sub-tasks --}}
        <div id="grp-body-{{ $group->id }}" class="pl-10">
            @forelse($group->subTasks as $task)
            <div class="flex items-center gap-3 px-5 py-2.5 border-t border-gray-50 hover:bg-gray-50 subtask-row" data-task-id="{{ $task->id }}">
                @php
                    $sc = \App\Models\SubTask::STATUS_COLORS[$task->status] ?? 'gray';
                    $sl = \App\Models\SubTask::STATUS_LABELS[$task->status] ?? $task->status;
                @endphp
                <span class="w-2 h-2 rounded-full bg-{{ $sc }}-400 flex-shrink-0"></span>
                <div class="flex-1 min-w-0 flex items-center gap-1.5">
                    <span class="text-sm text-gray-800">{{ $task->title }}</span>
                    @if($task->files->count() > 0)
                    <span onclick="event.stopPropagation();openListFileChip({{ $task->id }}, event)"
                          title="{{ __('projects.view_attachments') }}"
                          style="display:inline-flex;align-items:center;gap:2px;padding:1px 5px;border-radius:8px;font-size:10px;font-weight:600;color:#4f46e5;background:#eef2ff;cursor:pointer;flex-shrink:0;white-space:nowrap;">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ $task->files->count() }}
                    </span>
                    @endif
                </div>
                <span class="text-xs text-gray-400 flex-shrink-0">{{ $task->start_date?->format('m/d') ?? '-' }} ~ {{ $task->end_date?->format('m/d') ?? '-' }}</span>
                <div class="w-20 bg-gray-100 rounded-full h-1.5 flex-shrink-0">
                    <div class="h-1.5 rounded-full" style="width:{{ $task->progress }}%;background:{{ $gc['dot'] }};"></div>
                </div>
                <span class="text-xs text-gray-500 w-8 text-right flex-shrink-0">{{ $task->progress }}%</span>
                <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $sc }}-100 text-{{ $sc }}-700 flex-shrink-0">{{ $sl }}</span>
                @if($task->assignee)
                    <span class="text-xs text-gray-500 flex-shrink-0">{{ $task->assignee->name }}</span>
                @else
                    <span class="text-xs text-gray-300 flex-shrink-0">{{ __('projects.unassigned') }}</span>
                @endif
                <div class="flex items-center gap-1 flex-shrink-0">
                    <button onclick="openEditTask({{ $task->id }}, {{ $group->id }}, {{ $milestone->id }})"
                            class="p-1 text-gray-300 hover:text-gray-600 rounded">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <button onclick="deleteTask({{ $task->id }})"
                            class="p-1 text-gray-300 hover:text-red-500 rounded">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            @empty
            <div class="px-5 py-2 text-xs text-gray-400 italic">{{ __('projects.no_tasks') }}</div>
            @endforelse
        </div>
    </div>
    @empty
    <div class="px-10 py-3 text-sm text-gray-400">{{ __('projects.no_groups_hint') }}</div>
    @endforelse
    </div>
</div>
@empty
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-500">
    <p class="text-sm">{{ __('projects.no_milestones') }}</p>
    <button onclick="openMilestoneModal()" class="mt-3 px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{{ __('projects.milestone_add_btn') }}</button>
</div>
@endforelse

{{-- Ungrouped task groups --}}
@if($tree['ungrouped']->isNotEmpty())
<div class="mb-4 bg-white rounded-xl shadow-sm border border-orange-100 overflow-hidden">
    <div class="px-5 py-3 bg-orange-50 border-b border-orange-100">
        <span class="font-semibold text-orange-700 text-sm">{{ __('projects.ungrouped_groups') }}</span>
    </div>
    @foreach($tree['ungrouped'] as $group)
    @php $gc = $_gc[$group->id % count($_gc)]; @endphp
    <div class="border-b border-gray-50 group-block" data-group-id="{{ $group->id }}"
         style="border-left:3px solid {{ $gc['border'] }};">
        <div class="flex items-center justify-between px-5 py-2 cursor-pointer"
             style="background:{{ $gc['bg'] }};"
             onmouseover="this.style.filter='brightness(.97)'" onmouseout="this.style.filter=''"
             onclick="toggleGroup({{ $group->id }})">
            <div class="flex items-center gap-2 pl-5">
                <svg id="grp-chevron-{{ $group->id }}" class="w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     style="color:{{ $gc['dot'] }};">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $gc['dot'] }};"></span>
                <span class="text-sm font-semibold" style="color:{{ $gc['title'] }};">{{ $group->title }}</span>
                <span class="text-xs" style="color:{{ $gc['dot'] }};opacity:.7;">({{ __('projects.task_count', ['count' => $group->subTasks->count()]) }})</span>
            </div>
            <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                <button onclick="openTaskModal({{ $group->id }}, null)"
                        class="px-2.5 py-1 text-xs text-green-600 border border-green-200 rounded hover:bg-green-50">{{ __('projects.add_task') }}</button>
                <button onclick="deleteGroup({{ $group->id }})"
                        class="p-1 text-gray-300 hover:text-red-500 rounded">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>
        <div id="grp-body-{{ $group->id }}" class="pl-10">
            @foreach($group->subTasks as $task)
            <div class="flex items-center gap-3 px-5 py-2.5 border-t border-gray-50 hover:bg-gray-50">
                @php $sc = \App\Models\SubTask::STATUS_COLORS[$task->status] ?? 'gray'; $sl = \App\Models\SubTask::STATUS_LABELS[$task->status] ?? $task->status; @endphp
                <span class="w-2 h-2 rounded-full bg-{{ $sc }}-400 flex-shrink-0"></span>
                <div class="flex-1 min-w-0 flex items-center gap-1.5">
                    <span class="text-sm text-gray-800">{{ $task->title }}</span>
                    @if($task->files->count() > 0)
                    <span onclick="event.stopPropagation();openListFileChip({{ $task->id }}, event)"
                          title="{{ __('projects.view_attachments') }}"
                          style="display:inline-flex;align-items:center;gap:2px;padding:1px 5px;border-radius:8px;font-size:10px;font-weight:600;color:#4f46e5;background:#eef2ff;cursor:pointer;flex-shrink:0;white-space:nowrap;">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ $task->files->count() }}
                    </span>
                    @endif
                </div>
                <span class="text-xs text-gray-400">{{ $task->start_date?->format('m/d') ?? '-' }} ~ {{ $task->end_date?->format('m/d') ?? '-' }}</span>
                <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $sc }}-100 text-{{ $sc }}-700">{{ $sl }}</span>
                <div class="flex gap-1">
                    <button onclick="openEditTask({{ $task->id }}, {{ $group->id }}, null)" class="p-1 text-gray-300 hover:text-gray-600 rounded">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <button onclick="deleteTask({{ $task->id }})" class="p-1 text-gray-300 hover:text-red-500 rounded">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Loose sub-tasks (no TaskGroup) --}}
@if($tree['loose']->isNotEmpty())
<div class="mb-4 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
        <span class="font-semibold text-gray-600 text-sm">{{ __('projects.ungrouped_tasks') }}</span>
        <span class="ml-2 text-xs text-gray-400">{{ __('projects.no_group') }}</span>
    </div>
    @foreach($tree['loose'] as $task)
    @php $sc = \App\Models\SubTask::STATUS_COLORS[$task->status] ?? 'gray'; $sl = \App\Models\SubTask::STATUS_LABELS[$task->status] ?? $task->status; @endphp
    <div class="flex items-center gap-3 px-5 py-2.5 border-t border-gray-50 hover:bg-gray-50">
        <span class="w-2 h-2 rounded-full bg-{{ $sc }}-400 flex-shrink-0"></span>
        <div class="flex-1 min-w-0 flex items-center gap-1.5">
            <span class="text-sm text-gray-800">{{ $task->title }}</span>
            @if($task->files->count() > 0)
            <span onclick="openListFileChip({{ $task->id }}, event)"
                  title="{{ __('projects.view_attachments') }}"
                  style="display:inline-flex;align-items:center;gap:2px;padding:1px 5px;border-radius:8px;font-size:10px;font-weight:600;color:#4f46e5;background:#eef2ff;cursor:pointer;flex-shrink:0;white-space:nowrap;">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                {{ $task->files->count() }}
            </span>
            @endif
        </div>
        <span class="text-xs text-gray-400">{{ $task->start_date?->format('m/d') ?? '-' }} ~ {{ $task->end_date?->format('m/d') ?? '-' }}</span>
        <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $sc }}-100 text-{{ $sc }}-700">{{ $sl }}</span>
        @if($task->assignee)
            <span class="text-xs text-gray-500 flex-shrink-0">{{ $task->assignee->name }}</span>
        @endif
        <div class="flex gap-1">
            <button onclick="deleteTask({{ $task->id }})" class="p-1 text-gray-300 hover:text-red-500 rounded">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    </div>
    @endforeach
</div>
@endif

</div>{{-- /schedule-tree --}}

{{-- ============================================================ --}}
{{-- MODALS --}}
{{-- ============================================================ --}}

{{-- Milestone modal --}}
<div id="milestone-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
        <h3 id="ms-modal-title" class="text-sm font-semibold text-gray-800">{{ __('projects.milestone_add_modal') }}</h3>
        <button onclick="closeMilestoneModal()" class="p-1 text-gray-400 hover:text-gray-600 rounded-full">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <form id="milestone-form" onsubmit="submitMilestone(event)" class="px-5 py-4 space-y-3">
        <input type="hidden" id="ms-id" value="">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('projects.milestone_title') }} <span class="text-red-500">*</span></label>
            <input id="ms-title" type="text" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="{{ __('projects.milestone_title_placeholder') }}">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('projects.milestone_status') }}</label>
                <select id="ms-status" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="planned">{{ __('projects.ms_status_planned') }}</option>
                    <option value="in_progress">{{ __('projects.ms_status_in_progress') }}</option>
                    <option value="completed">{{ __('projects.ms_status_completed') }}</option>
                    <option value="cancelled">{{ __('projects.ms_status_cancelled') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('projects.milestone_target_date') }}</label>
                <input id="ms-target-date" type="date" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
        <div id="ms-error" class="hidden text-xs text-red-500"></div>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" onclick="closeMilestoneModal()" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">{{ __('common.cancel') }}</button>
            <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{{ __('common.save') }}</button>
        </div>
    </form>
</div>
</div>

{{-- Task Group modal --}}
<div id="group-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-2xl w-full max-w-xs">
    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
        <h3 id="grp-modal-title" class="text-sm font-semibold text-gray-800">{{ __('projects.group_add_modal') }}</h3>
        <button onclick="closeGroupModal()" class="p-1 text-gray-400 hover:text-gray-600 rounded-full">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <form onsubmit="submitGroup(event)" class="px-5 py-4 space-y-3">
        <input type="hidden" id="grp-id" value="">
        <input type="hidden" id="grp-milestone-id" value="">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('projects.group_name_label') }} <span class="text-red-500">*</span></label>
            <input id="grp-title" type="text" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="{{ __('projects.group_name_placeholder') }}">
        </div>
        <div id="grp-error" class="hidden text-xs text-red-500"></div>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" onclick="closeGroupModal()" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">{{ __('common.cancel') }}</button>
            <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{{ __('common.save') }}</button>
        </div>
    </form>
</div>
</div>

{{-- SubTask modal --}}
<div id="task-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
        <h3 id="task-modal-title" class="text-sm font-semibold text-gray-800">{{ __('projects.task_add_modal') }}</h3>
        <button onclick="closeTaskModal()" class="p-1 text-gray-400 hover:text-gray-600 rounded-full">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <form onsubmit="submitTask(event)" class="px-5 py-4 space-y-3">
        <input type="hidden" id="task-id" value="">
        <input type="hidden" id="task-group-id" value="">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('projects.task_title') }} <span class="text-red-500">*</span></label>
            <input id="task-title" type="text" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="{{ __('projects.task_title_placeholder') }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('projects.task_description') }}</label>
            <textarea id="task-desc" rows="2" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('projects.task_start_date') }} <span class="text-red-500">*</span></label>
                <input id="task-start" type="date" required class="w-full px-2 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('projects.task_end_date') }} <span class="text-red-500">*</span></label>
                <input id="task-end" type="date" required class="w-full px-2 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('projects.milestone_status') }}</label>
                <select id="task-status" class="w-full px-2 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="not_started">{{ __('projects.gantt_status_not_started') }}</option>
                    <option value="in_progress">{{ __('projects.gantt_status_in_progress') }}</option>
                    <option value="completed">{{ __('projects.gantt_status_completed') }}</option>
                    <option value="blocked">{{ __('projects.gantt_status_blocked') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('projects.assignee') }}</label>
                <select id="task-assignee" class="w-full px-2 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">{{ __('projects.unassigned') }}</option>
                    @foreach($members as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('projects.task_progress') }} <span class="text-gray-400">{{ __('projects.task_progress_hint') }}</span></label>
            <input id="task-progress" type="number" min="0" max="100" value="0" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div id="task-error" class="hidden text-xs text-red-500"></div>
        <div class="flex justify-end gap-2 pt-1">
            <button type="button" onclick="closeTaskModal()" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">{{ __('common.cancel') }}</button>
            <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{{ __('common.save') }}</button>
        </div>
    </form>
</div>
</div>

{{-- 파일 목록 팝업 --}}
<div id="lf-picker" style="display:none;position:fixed;z-index:10200;background:#fff;border:1px solid #e4e4e7;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.14);padding:6px;min-width:240px;max-width:320px;max-height:300px;overflow-y:auto;">
    <div id="lf-picker-list"></div>
</div>
<div id="lf-picker-overlay" style="display:none;position:fixed;inset:0;z-index:10199;" onclick="closeListFilePicker()"></div>

@include('partials.file-preview-modal')

@endsection

@push('scripts')
@php
$_listFilesMap = [];
foreach ($tree['milestones'] as $ms) {
    foreach ($ms->taskGroups as $tg) {
        foreach ($tg->subTasks as $st) {
            if ($st->files->count()) {
                $_listFilesMap[$st->id] = $st->files->map(fn($f) => [
                    'id'           => $f->id,
                    'name'         => $f->original_name ?? $f->name,
                    'size'         => $f->size ?? 0,
                    'preview_type' => $f->preview_type ?? null,
                ])->values()->all();
            }
        }
    }
}
foreach ($tree['ungrouped'] as $tg) {
    foreach ($tg->subTasks as $st) {
        if ($st->files->count()) {
            $_listFilesMap[$st->id] = $st->files->map(fn($f) => [
                'id' => $f->id, 'name' => $f->original_name ?? $f->name,
                'size' => $f->size ?? 0, 'preview_type' => $f->preview_type ?? null,
            ])->values()->all();
        }
    }
}
foreach ($tree['loose'] as $st) {
    if ($st->files->count()) {
        $_listFilesMap[$st->id] = $st->files->map(fn($f) => [
            'id' => $f->id, 'name' => $f->original_name ?? $f->name,
            'size' => $f->size ?? 0, 'preview_type' => $f->preview_type ?? null,
        ])->values()->all();
    }
}
@endphp
<script>
const SCHED_STR = {
    milestoneAdd:           @json(__('projects.milestone_add_modal')),
    milestoneEdit:          @json(__('projects.milestone_edit_modal')),
    groupAdd:               @json(__('projects.group_add_modal')),
    groupEdit:              @json(__('projects.group_edit_modal')),
    taskAdd:                @json(__('projects.task_add_modal')),
    taskEdit:               @json(__('projects.task_edit_modal')),
    error:                  @json(__('projects.error_occurred')),
    deleteMilestoneConfirm: @json(__('projects.delete_milestone_confirm')),
    deleteGroupConfirm:     @json(__('projects.delete_group_confirm')),
    deleteTaskConfirm:      @json(__('projects.delete_subtask_confirm')),
};
const PROJECT_ID    = {{ $project->id }};
const LIST_FILES    = @json($_listFilesMap);
const MS_STORE_URL  = '{{ route('projects.milestones.store', $project) }}';
const GRP_STORE_URL = '{{ route('projects.task-groups.store', $project) }}';
const TASK_STORE_URL= '{{ route('projects.sub-tasks.store', $project) }}';
const MS_BASE_URL   = '{{ url("projects/" . $project->id . "/milestones") }}';
const GRP_BASE_URL  = '{{ url("projects/" . $project->id . "/task-groups") }}';
const TASK_BASE_URL = '{{ url("projects/" . $project->id . "/sub-tasks") }}';
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

// ── Collapse / Expand ──────────────────────────────────────────
async function toggleMilestone(id) {
    const body = document.getElementById(`ms-body-${id}`);
    const chev = document.getElementById(`ms-chevron-${id}`);
    body.classList.toggle('hidden');
    chev.classList.toggle('rotate-180');
}
async function toggleGroup(id) {
    const body = document.getElementById(`grp-body-${id}`);
    const chev = document.getElementById(`grp-chevron-${id}`);
    body?.classList.toggle('hidden');
    chev?.classList.toggle('rotate-180');
}

// ── Milestone modal ────────────────────────────────────────────
async function openMilestoneModal() {
    document.getElementById('ms-id').value = '';
    document.getElementById('ms-title').value = '';
    document.getElementById('ms-status').value = 'planned';
    document.getElementById('ms-target-date').value = '';
    document.getElementById('ms-modal-title').textContent = SCHED_STR.milestoneAdd;
    document.getElementById('ms-error').classList.add('hidden');
    document.getElementById('milestone-modal').classList.remove('hidden');
}
async function openEditMilestone(id, title, status, targetDate) {
    document.getElementById('ms-id').value = id;
    document.getElementById('ms-title').value = title;
    document.getElementById('ms-status').value = status;
    document.getElementById('ms-target-date').value = targetDate;
    document.getElementById('ms-modal-title').textContent = SCHED_STR.milestoneEdit;
    document.getElementById('ms-error').classList.add('hidden');
    document.getElementById('milestone-modal').classList.remove('hidden');
}
async function closeMilestoneModal() {
    document.getElementById('milestone-modal').classList.add('hidden');
}
async function submitMilestone(e) {
    e.preventDefault();
    const id = document.getElementById('ms-id').value;
    const body = {
        title:       document.getElementById('ms-title').value,
        status:      document.getElementById('ms-status').value,
        target_date: document.getElementById('ms-target-date').value || null,
    };
    const url    = id ? `${MS_BASE_URL}/${id}` : MS_STORE_URL;
    const method = id ? 'PATCH' : 'POST';
    const r = await fetch(url, {
        method,
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify(body),
    });
    if (!r.ok) {
        const d = await r.json();
        document.getElementById('ms-error').textContent = d.message || SCHED_STR.error;
        document.getElementById('ms-error').classList.remove('hidden');
        return;
    }
    location.reload();
}
async function deleteMilestone(id) {
    if (!await __confirm(SCHED_STR.deleteMilestoneConfirm)) return;
    const r = await fetch(`${MS_BASE_URL}/${id}`, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
    });
    if (r.ok) location.reload();
}

// ── Group modal ────────────────────────────────────────────────
async function openGroupModal(milestoneId) {
    document.getElementById('grp-id').value = '';
    document.getElementById('grp-title').value = '';
    document.getElementById('grp-milestone-id').value = milestoneId;
    document.getElementById('grp-modal-title').textContent = SCHED_STR.groupAdd;
    document.getElementById('grp-error').classList.add('hidden');
    document.getElementById('group-modal').classList.remove('hidden');
}
async function openEditGroup(id, title, milestoneId) {
    document.getElementById('grp-id').value = id;
    document.getElementById('grp-title').value = title;
    document.getElementById('grp-milestone-id').value = milestoneId;
    document.getElementById('grp-modal-title').textContent = SCHED_STR.groupEdit;
    document.getElementById('grp-error').classList.add('hidden');
    document.getElementById('group-modal').classList.remove('hidden');
}
async function closeGroupModal() {
    document.getElementById('group-modal').classList.add('hidden');
}
async function submitGroup(e) {
    e.preventDefault();
    const id          = document.getElementById('grp-id').value;
    const milestoneId = document.getElementById('grp-milestone-id').value;
    const body = {
        title:        document.getElementById('grp-title').value,
        milestone_id: milestoneId || null,
    };
    const url    = id ? `${GRP_BASE_URL}/${id}` : GRP_STORE_URL;
    const method = id ? 'PATCH' : 'POST';
    const r = await fetch(url, {
        method,
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify(body),
    });
    if (!r.ok) {
        const d = await r.json();
        document.getElementById('grp-error').textContent = d.message || SCHED_STR.error;
        document.getElementById('grp-error').classList.remove('hidden');
        return;
    }
    location.reload();
}
async function deleteGroup(id) {
    if (!await __confirm(SCHED_STR.deleteGroupConfirm)) return;
    const r = await fetch(`${GRP_BASE_URL}/${id}`, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
    });
    if (r.ok) location.reload();
}

// ── Task modal ────────────────────────────────────────────────
async function openTaskModal(groupId, milestoneId) {
    document.getElementById('task-id').value = '';
    document.getElementById('task-group-id').value = groupId;
    document.getElementById('task-title').value = '';
    document.getElementById('task-desc').value = '';
    document.getElementById('task-start').value = '';
    document.getElementById('task-end').value = '';
    document.getElementById('task-status').value = 'not_started';
    document.getElementById('task-progress').value = '0';
    document.getElementById('task-assignee').value = '';
    document.getElementById('task-modal-title').textContent = SCHED_STR.taskAdd;
    document.getElementById('task-error').classList.add('hidden');
    document.getElementById('task-modal').classList.remove('hidden');
}
async function openEditTask(taskId, groupId, milestoneId) {
    const r = await fetch(`${TASK_BASE_URL}/${taskId}`, {
        headers: {'Accept':'application/json'},
    });
    const t = await r.json();
    document.getElementById('task-id').value = taskId;
    document.getElementById('task-group-id').value = groupId;
    document.getElementById('task-title').value = t.title;
    document.getElementById('task-desc').value = t.description ?? '';
    document.getElementById('task-start').value = t.start_date?.substring(0,10) ?? '';
    document.getElementById('task-end').value = t.end_date?.substring(0,10) ?? '';
    document.getElementById('task-status').value = t.status;
    document.getElementById('task-progress').value = t.progress;
    document.getElementById('task-assignee').value = t.assignee_id ?? '';
    document.getElementById('task-modal-title').textContent = SCHED_STR.taskEdit;
    document.getElementById('task-error').classList.add('hidden');
    document.getElementById('task-modal').classList.remove('hidden');
}
async function closeTaskModal() {
    document.getElementById('task-modal').classList.add('hidden');
}
async function submitTask(e) {
    e.preventDefault();
    const id      = document.getElementById('task-id').value;
    const groupId = document.getElementById('task-group-id').value;
    const body = {
        task_group_id: groupId,
        title:         document.getElementById('task-title').value,
        description:   document.getElementById('task-desc').value || null,
        start_date:    document.getElementById('task-start').value,
        end_date:      document.getElementById('task-end').value,
        status:        document.getElementById('task-status').value,
        progress:      parseInt(document.getElementById('task-progress').value, 10),
        assignee_id:   document.getElementById('task-assignee').value || null,
    };
    const url    = id ? `${TASK_BASE_URL}/${id}` : TASK_STORE_URL;
    const method = id ? 'PATCH' : 'POST';
    const r = await fetch(url, {
        method,
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify(body),
    });
    if (!r.ok) {
        const d = await r.json();
        document.getElementById('task-error').textContent = d.message || SCHED_STR.error;
        document.getElementById('task-error').classList.remove('hidden');
        return;
    }
    location.reload();
}
async function deleteTask(id) {
    if (!await __confirm(SCHED_STR.deleteTaskConfirm)) return;
    const r = await fetch(`${TASK_BASE_URL}/${id}`, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
    });
    if (r.ok) location.reload();
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeMilestoneModal();
        closeGroupModal();
        closeTaskModal();
    }
});

// ─── 리스트 파일 칩 ──────────────────────────────────────────────────────────
async function openListFileChip(tid, event) {
    const files = LIST_FILES[tid];
    if (!files || files.length === 0) return;
    if (files.length === 1) {
        openListFilePreview(files[0].id);
        return;
    }
    showListFilePicker(files, event);
}

async function showListFilePicker(files, event) {
    const list = document.getElementById('lf-picker-list');
    list.innerHTML = files.map(f => {
        const can = !!f.preview_type;
        return `<div onclick="${can ? `closeListFilePicker();openListFilePreview(${f.id})` : ''}"
                     style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:8px;font-size:12px;cursor:${can ? 'pointer' : 'default'};"
                     onmouseover="this.style.background='${can ? '#f4f4f5' : ''}'"
                     onmouseout="this.style.background=''">
            <svg style="width:13px;height:13px;flex-shrink:0;color:#6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:${can ? '#4f46e5' : '#374151'};">${escHtml(f.name)}</span>
            ${can ? `<svg width="12" height="12" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>` : ''}
        </div>`;
    }).join('');

    const picker = document.getElementById('lf-picker');
    picker.style.left = Math.min(event.clientX, window.innerWidth - 340) + 'px';
    picker.style.top  = Math.min(event.clientY + 6, window.innerHeight - 320) + 'px';
    picker.style.display = 'block';
    document.getElementById('lf-picker-overlay').style.display = 'block';
}

async function closeListFilePicker() {
    document.getElementById('lf-picker').style.display = 'none';
    document.getElementById('lf-picker-overlay').style.display = 'none';
}

async function openListFilePreview(fileId) {
    closeListFilePicker();
    openPreview(fileId, PROJECT_ID);
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
