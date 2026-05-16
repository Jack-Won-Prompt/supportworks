@extends('layouts.app')

@section('title', $project->name)

@section('header-actions')
@endsection

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ $project->name }}</span>
@endsection

@section('content')
@include('partials.project-nav', ['project'=>$project, 'active'=>'overview'])
<div class="space-y-6">
    <!-- 프로젝트 정보 -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">{{ $project->name }}</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full
                {{ $project->status === 'active' ? 'bg-green-100 text-green-700' : '' }}
                {{ $project->status === 'on_hold' ? 'bg-yellow-100 text-yellow-700' : '' }}
                {{ $project->status === 'completed' ? 'bg-blue-100 text-blue-700' : '' }}
                {{ $project->status === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}">
                {{ $project->status_label }}
            </span>
            @if($isManager)
            <div class="ml-auto flex items-center gap-1.5">
                <button onclick="openProjectEditModal()" title="{{ __('common.edit') }}"
                        class="flex items-center gap-1.5 px-2.5 py-1 text-xs text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors border border-transparent hover:border-indigo-200">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    {{ __('common.edit') }}
                </button>
                <button onclick="openProjectDeleteModal()" title="{{ __('common.delete') }}"
                        class="flex items-center gap-1.5 px-2.5 py-1 text-xs text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-200">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    {{ __('common.delete') }}
                </button>
            </div>
            @endif
        </div>
        @if($project->description)
        <p class="text-sm text-gray-500 mb-2">{{ $project->description }}</p>
        @endif

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-50">
            <div>
                <p class="text-xs text-gray-400 mb-1">{{ __('projects.creator') }}</p>
                <p class="text-sm font-medium text-gray-700">{{ $project->creator->name }}</p>
            </div>
            @if($project->start_date)
            <div>
                <p class="text-xs text-gray-400 mb-1">{{ __('projects.start_date') }}</p>
                <p class="text-sm font-medium text-gray-700">{{ $project->start_date->format('Y.m.d') }}</p>
            </div>
            @endif
            @if($project->end_date)
            <div>
                <p class="text-xs text-gray-400 mb-1">{{ __('projects.end_date') }}</p>
                <p class="text-sm font-medium text-gray-700">{{ $project->end_date->format('Y.m.d') }}</p>
            </div>
            @endif
            @if($project->client_name)
            <div>
                <p class="text-xs text-gray-400 mb-1">{{ __('projects.client_company') }}</p>
                <p class="text-sm font-medium text-gray-700">{{ $project->client_name }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- 일정 진행률 -->
    @if($scheduleStats['total'] > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-900">{{ __('projects.schedule_progress') }}</h3>
            <a href="{{ route('projects.schedules.index', $project) }}" class="text-xs text-indigo-600 hover:text-indigo-700">{{ __('projects.view_all') }}</a>
        </div>
        @php $progress = round($scheduleStats['completed'] / $scheduleStats['total'] * 100); @endphp
        <div class="flex items-center gap-3">
            <div class="flex-1 bg-gray-100 rounded-full h-2">
                <div class="bg-indigo-600 h-2 rounded-full transition-all" style="width: {{ $progress }}%"></div>
            </div>
            <span class="text-sm font-medium text-gray-700">{{ $progress }}%</span>
        </div>
        <div class="flex gap-4 mt-2 text-xs text-gray-400">
            <span>{{ __('projects.total_count', ['count' => $scheduleStats['total']]) }}</span>
            <span class="text-blue-500">{{ __('projects.in_progress_count', ['count' => $scheduleStats['in_progress']]) }}</span>
            <span class="text-green-500">{{ __('projects.completed_count', ['count' => $scheduleStats['completed']]) }}</span>
        </div>
    </div>
    @endif

    <!-- 기획서 -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                {{ __('projects.planning') }}
            </h3>
            <button onclick="openPlanningModal()" class="text-xs text-indigo-600 hover:text-indigo-700">{{ __('projects.view_all') }}</button>
        </div>
        @if($project->planningDocs->isEmpty())
        <p class="text-xs text-gray-400 text-center py-4">{{ __('projects.no_planning_docs') }}</p>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach($project->planningDocs as $doc)
            @php
                $statusColors = ['draft'=>['#f3f4f6','#6b7280'],'ai_processed'=>['#eff6ff','#3b82f6'],'pending_review'=>['#fef3c7','#d97706'],'approved'=>['#d1fae5','#059669'],'rejected'=>['#fee2e2','#dc2626']];
                [$sbg,$stc] = $statusColors[$doc->status] ?? ['#f3f4f6','#6b7280'];
            @endphp
            <button onclick="openPlanningModal('{{ route('projects.planning.show', [$project, $doc]) }}?popup=1')"
                class="text-left p-3 rounded-lg border border-gray-100 hover:border-indigo-200 hover:bg-indigo-50 transition-colors w-full">
                <div class="flex items-start justify-between gap-2 mb-1.5">
                    <span class="text-sm font-medium text-gray-800 truncate leading-snug">{{ $doc->title }}</span>
                    <span style="flex-shrink:0;padding:1px 7px;font-size:11px;font-weight:600;border-radius:20px;background:{{ $sbg }};color:{{ $stc }};">{{ $doc->status_label }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span>v{{ $doc->version }} · {{ $doc->creator->name }}</span>
                    <span>{{ $doc->updated_at->format('m.d') }}</span>
                </div>
            </button>
            @endforeach
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 최근 일정 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('projects.recent_schedules') }}</h3>
                <div class="flex items-center gap-2">
                    <button onclick="document.getElementById('modal-schedule').style.display='flex'" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">{{ __('projects.add') }}</button>
                    <span class="text-gray-200">|</span>
                    <a href="{{ route('projects.schedules.index', $project) }}" class="text-xs text-gray-500 hover:text-gray-700">{{ __('projects.view_all_short') }}</a>
                </div>
            </div>
            @forelse($project->schedules->take(5) as $schedule)
            <div class="py-2.5 border-b border-gray-50 last:border-0">
                <div class="flex items-center justify-between">
                    <a href="{{ route('schedules.show', $schedule) }}" class="text-sm text-gray-800 hover:text-indigo-600 truncate">
                        {{ $schedule->title }}
                    </a>
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full
                        {{ $schedule->status === 'pending'          ? 'bg-yellow-100 text-yellow-700'  : '' }}
                        {{ $schedule->status === 'in_progress'      ? 'bg-blue-100 text-blue-700'      : '' }}
                        {{ $schedule->status === 'completed'        ? 'bg-green-100 text-green-700'    : '' }}
                        {{ $schedule->status === 'cancelled'        ? 'bg-red-100 text-red-700'        : '' }}
                        {{ $schedule->status === 'review_submitted' ? 'bg-orange-100 text-orange-700'  : '' }}
                        {{ $schedule->status === 'review_completed' ? 'bg-purple-100 text-purple-700'  : '' }}">
                        {{ $schedule->status_label }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">{{ $schedule->start_date->format('m/d') }}</p>
            </div>
            @empty
            <p class="text-xs text-gray-400 text-center py-4">{{ __('projects.no_schedules') }}</p>
            @endforelse
        </div>

        <!-- 최근 Q&A -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('projects.recent_qa') }}</h3>
                <div class="flex items-center gap-2">
                    <button onclick="document.getElementById('modal-question').style.display='flex'" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">{{ __('projects.add') }}</button>
                    <span class="text-gray-200">|</span>
                    <a href="{{ route('projects.questions.index', $project) }}" class="text-xs text-gray-500 hover:text-gray-700">{{ __('projects.view_all_short') }}</a>
                </div>
            </div>
            @forelse($project->questions->take(5) as $question)
            <div class="py-2.5 border-b border-gray-50 last:border-0">
                <a href="{{ route('questions.show', $question) }}" class="text-sm text-gray-800 hover:text-indigo-600 line-clamp-1">
                    {{ $question->title }}
                </a>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs text-gray-400">{{ $question->user->name }}</span>
                    <span class="px-1.5 py-0.5 text-xs rounded
                        {{ $question->status === 'open' ? 'bg-blue-100 text-blue-600' : '' }}
                        {{ $question->status === 'answered' ? 'bg-green-100 text-green-600' : '' }}
                        {{ $question->status === 'closed' ? 'bg-gray-100 text-gray-500' : '' }}">
                        {{ $question->status_label }}
                    </span>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 text-center py-4">{{ __('projects.no_questions') }}</p>
            @endforelse
        </div>

        <!-- 최근 파일 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('projects.recent_files') }}</h3>
                <div class="flex items-center gap-2">
                    <button onclick="document.getElementById('modal-file').style.display='flex'" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">{{ __('projects.add') }}</button>
                    <span class="text-gray-200">|</span>
                    <a href="{{ route('projects.files.index', $project) }}" class="text-xs text-gray-500 hover:text-gray-700">{{ __('projects.view_all_short') }}</a>
                </div>
            </div>
            @forelse($project->files->take(5) as $file)
            <div class="py-2.5 border-b border-gray-50 last:border-0">
                <div class="flex items-center gap-2">
                    <span class="text-lg">{{ $file->icon }}</span>
                    <div class="flex-1 min-w-0">
                        @if($file->previewType())
                        <button onclick="openPreview({{ $file->id }}, {{ $project->id }})"
                                class="text-sm text-gray-800 hover:text-indigo-600 block text-left w-full break-all leading-snug">
                            {{ $file->original_name }}
                        </button>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs text-indigo-400">{{ __('projects.click_preview') }}</span>
                            @if($file->comments_count > 0)
                            <button id="file-comment-badge-{{ $file->id }}"
                                    onclick="openComments({{ $file->id }}, '{{ addslashes($file->original_name) }}', {{ $project->id }})"
                                    class="inline-flex items-center gap-1 text-xs text-violet-600 hover:text-violet-800 font-semibold">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                {{ __('projects.opinion') }} {{ $file->comments_count }}
                            </button>
                            @else
                            <span id="file-comment-badge-{{ $file->id }}" style="display:none"
                                  class="inline-flex items-center gap-1 text-xs text-violet-600 hover:text-violet-800 font-semibold cursor-pointer"
                                  onclick="openComments({{ $file->id }}, '{{ addslashes($file->original_name) }}', {{ $project->id }})"></span>
                            @endif
                        </div>
                        @else
                        <a href="{{ route('projects.files.download', [$project, $file]) }}"
                           class="text-sm text-gray-800 hover:text-indigo-600 block break-all leading-snug">
                            {{ $file->original_name }}
                        </a>
                        <p class="text-xs text-gray-400">{{ $file->formatted_size }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 text-center py-4">{{ __('projects.no_files') }}</p>
            @endforelse
        </div>
    </div>

    <!-- 멤버 목록 (팝업 트리거) -->
    <button onclick="openMembersModal()" class="w-full text-left bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:border-indigo-200 transition-colors">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ __('projects.project_members') }}
                <span class="text-xs font-normal text-gray-400">({{ $project->projectMembers->count() }}{{ __('common.member') }})</span>
            </h3>
            <span class="text-xs text-indigo-500">{{ __('projects.member_manage') }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach($project->projectMembers as $member)
            <div class="flex items-center gap-2 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-100">
                <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center text-xs font-bold text-indigo-600 flex-shrink-0">
                    {{ mb_substr($member->user->name, 0, 1) }}
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-800 leading-tight">{{ $member->user->name }}</p>
                    <p class="text-xs text-gray-400 leading-tight">{{ $member->role_label }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </button>
</div>

{{-- 일정 추가 모달 --}}
<div id="modal-schedule" onclick="if(event.target===this)this.style.display='none'"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:8000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:480px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f4f4f5;">
            <h3 style="font-size:15px;font-weight:700;color:#18181b;">{{ __('projects.modal_add_schedule') }}</h3>
            <button onclick="document.getElementById('modal-schedule').style.display='none'" style="background:none;border:none;cursor:pointer;color:#9ca3af;"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div style="padding:18px 22px 22px;display:flex;flex-direction:column;gap:13px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.schedule_title_required') }} <span style="color:#ef4444;">*</span></label>
                <input id="sch-title" type="text" placeholder="{{ __('projects.schedule_title') }}" style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.start_date') }} <span style="color:#ef4444;">*</span></label>
                    <input id="sch-start" type="date" style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.end_date') }}</label>
                    <input id="sch-end" type="date" style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.status') }}</label>
                    <select id="sch-status" style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                        <option value="pending">{{ __('projects.sched_status_pending') }}</option>
                        <option value="in_progress">{{ __('projects.sched_status_in_progress') }}</option>
                        <option value="completed">{{ __('projects.sched_status_completed') }}</option>
                        <option value="cancelled">{{ __('projects.sched_status_cancelled') }}</option>
                        <option value="review_submitted">{{ __('projects.sched_status_review_submitted') }}</option>
                        <option value="review_completed">{{ __('projects.sched_status_review_completed') }}</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.priority') }}</label>
                    <select id="sch-priority" style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                        <option value="medium">{{ __('projects.priority_normal') }}</option>
                        <option value="high">{{ __('projects.priority_high') }}</option>
                        <option value="low">{{ __('projects.priority_low') }}</option>
                    </select>
                </div>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.assignee') }}</label>
                <select id="sch-assignee" style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                    <option value="">{{ __('projects.no_assignee') }}</option>
                    @foreach($project->projectMembers as $m)
                    <option value="{{ $m->user->id }}">{{ $m->user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:4px;">
                <button onclick="document.getElementById('modal-schedule').style.display='none'" style="padding:8px 16px;font-size:13px;color:#6b7280;background:#f9fafb;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button onclick="submitSchedule()" style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:#4f46e5;border:none;border-radius:8px;cursor:pointer;">{{ __('common.register') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Q&A 추가 모달 --}}
<div id="modal-question" onclick="if(event.target===this)this.style.display='none'"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:8000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:480px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f4f4f5;">
            <h3 style="font-size:15px;font-weight:700;color:#18181b;">{{ __('projects.modal_add_question') }}</h3>
            <button onclick="document.getElementById('modal-question').style.display='none'" style="background:none;border:none;cursor:pointer;color:#9ca3af;"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div style="padding:18px 22px 22px;display:flex;flex-direction:column;gap:13px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.question_title') }} <span style="color:#ef4444;">*</span></label>
                <input id="q-title" type="text" placeholder="{{ __('projects.question_title_placeholder') }}" style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.question_content') }} <span style="color:#ef4444;">*</span></label>
                <textarea id="q-content" rows="4" placeholder="{{ __('projects.question_content_placeholder2') }}" style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box;"></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:4px;">
                <button onclick="document.getElementById('modal-question').style.display='none'" style="padding:8px 16px;font-size:13px;color:#6b7280;background:#f9fafb;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button onclick="submitQuestion()" style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:#4f46e5;border:none;border-radius:8px;cursor:pointer;">{{ __('common.register') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- 파일 추가 모달 --}}
<div id="modal-file" onclick="if(event.target===this)this.style.display='none'"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:8000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:460px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f4f4f5;">
            <h3 style="font-size:15px;font-weight:700;color:#18181b;">{{ __('projects.modal_file_url') }}</h3>
            <button onclick="document.getElementById('modal-file').style.display='none'" style="background:none;border:none;cursor:pointer;color:#9ca3af;"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        {{-- 탭 --}}
        <div style="display:flex;gap:0;border-bottom:1px solid #f4f4f5;">
            <button id="tab-file-btn" onclick="switchFileTab('file')"
                style="flex:1;padding:10px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:#f0f0ff;color:#4f46e5;border-bottom:2px solid #4f46e5;transition:all .15s;">
                {{ __('projects.tab_file_upload') }}
            </button>
            <button id="tab-url-btn" onclick="switchFileTab('url')"
                style="flex:1;padding:10px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:#fff;color:#9ca3af;border-bottom:2px solid transparent;transition:all .15s;">
                {{ __('projects.tab_url') }}
            </button>
        </div>
        {{-- 파일 업로드 탭 --}}
        <div id="tab-file-panel" style="padding:18px 22px 22px;display:flex;flex-direction:column;gap:13px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:8px;">{{ __('common.file') }} <span style="color:#ef4444;">*</span></label>
                <label style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1.5px dashed #d1d5db;border-radius:8px;cursor:pointer;background:#fafafa;" onmouseover="this.style.borderColor='#6366f1'" onmouseout="this.style.borderColor='#d1d5db'">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#9ca3af;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    <span id="file-modal-label" style="font-size:13px;color:#6b7280;">{{ __('projects.file_select_placeholder') }}</span>
                    <input id="f-file" type="file" style="display:none;" onchange="document.getElementById('file-modal-label').textContent=this.files[0]?this.files[0].name:'{{ __('projects.file_select_placeholder') }}'">
                </label>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('common.description') }}</label>
                <input id="f-desc" type="text" placeholder="{{ __('projects.description_placeholder') }}" style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:4px;">
                <button onclick="document.getElementById('modal-file').style.display='none'" style="padding:8px 16px;font-size:13px;color:#6b7280;background:#f9fafb;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button onclick="submitFile()" id="btn-file-submit" style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:#4f46e5;border:none;border-radius:8px;cursor:pointer;">{{ __('common.upload') }}</button>
            </div>
        </div>
        {{-- URL 등록 탭 --}}
        <div id="tab-url-panel" style="display:none;padding:18px 22px 22px;display:none;flex-direction:column;gap:13px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">URL <span style="color:#ef4444;">*</span></label>
                <input id="f-url" type="url" placeholder="https://..." style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                <p style="margin:4px 0 0;font-size:11px;color:#9ca3af;">{{ __('projects.url_support_hint') }}</p>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('common.title') }} <span style="color:#ef4444;">*</span></label>
                <input id="f-url-title" type="text" placeholder="{{ __('projects.display_name_placeholder') }}" style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('common.description') }}</label>
                <input id="f-url-desc" type="text" placeholder="{{ __('projects.description_placeholder') }}" style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:4px;">
                <button onclick="document.getElementById('modal-file').style.display='none'" style="padding:8px 16px;font-size:13px;color:#6b7280;background:#f9fafb;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button onclick="submitUrl()" id="btn-url-submit" style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:#4f46e5;border:none;border-radius:8px;cursor:pointer;">{{ __('common.register') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
const _CSRF = document.querySelector('meta[name="csrf-token"]').content;
const _STR = {
    scheduleRegistered: '{{ __("projects.schedule_registered") }}',
    questionRegistered: '{{ __("projects.question_registered") }}',
    urlRegistered:      '{{ __("projects.url_registered") }}',
    fileUploaded:       '{{ __("projects.file_uploaded") }}',
    titleStartRequired: '{{ __("projects.title_start_required") }}',
    titleContentRequired:'{{ __("projects.title_content_required") }}',
    enterUrl:           '{{ __("projects.enter_url") }}',
    enterTitle:         '{{ __("projects.enter_title") }}',
    selectFile:         '{{ __("projects.select_file") }}',
    registerFail:       '{{ __("projects.register_fail") }}',
    uploadFail:         '{{ __("projects.upload_fail") }}',
    uploading:          '{{ __("projects.uploading") }}',
    registering:        '{{ __("projects.registering") }}',
    register:           '{{ __("common.register") }}',
    upload:             '{{ __("common.upload") }}',
};

async function showToast(msg, ok) {
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:10px 18px;border-radius:8px;font-size:13px;font-weight:600;z-index:99999;color:#fff;background:' + (ok ? '#059669' : '#dc2626');
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2500);
}

async function submitSchedule() {
    const title = document.getElementById('sch-title').value.trim();
    const start = document.getElementById('sch-start').value;
    if (!title || !start) { showToast(_STR.titleStartRequired, false); return; }
    fetch('{{ route("projects.schedules.store", $project) }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': _CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({
            title,
            start_date: start,
            end_date: document.getElementById('sch-end').value || null,
            status:   document.getElementById('sch-status').value,
            priority: document.getElementById('sch-priority').value,
            assigned_to: document.getElementById('sch-assignee').value || null,
        }),
    }).then(r => r.json()).then(d => {
        if (d.ok) { showToast(_STR.scheduleRegistered, true); document.getElementById('modal-schedule').style.display = 'none'; setTimeout(() => location.reload(), 800); }
        else showToast(d.message || _STR.registerFail, false);
    });
}

async function submitQuestion() {
    const title   = document.getElementById('q-title').value.trim();
    const content = document.getElementById('q-content').value.trim();
    if (!title || !content) { showToast(_STR.titleContentRequired, false); return; }
    fetch('{{ route("projects.questions.store", $project) }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': _CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ title, content }),
    }).then(r => r.json()).then(d => {
        if (d.ok) { showToast(_STR.questionRegistered, true); document.getElementById('modal-question').style.display = 'none'; setTimeout(() => location.reload(), 800); }
        else showToast(d.message || _STR.registerFail, false);
    });
}

async function switchFileTab(tab) {
    const isFile = tab === 'file';
    document.getElementById('tab-file-panel').style.display = isFile ? 'flex' : 'none';
    document.getElementById('tab-url-panel').style.display  = isFile ? 'none'  : 'flex';
    document.getElementById('tab-file-btn').style.background     = isFile ? '#f0f0ff' : '#fff';
    document.getElementById('tab-file-btn').style.color          = isFile ? '#4f46e5' : '#9ca3af';
    document.getElementById('tab-file-btn').style.borderBottom   = isFile ? '2px solid #4f46e5' : '2px solid transparent';
    document.getElementById('tab-url-btn').style.background      = isFile ? '#fff' : '#f0f0ff';
    document.getElementById('tab-url-btn').style.color           = isFile ? '#9ca3af' : '#4f46e5';
    document.getElementById('tab-url-btn').style.borderBottom    = isFile ? '2px solid transparent' : '2px solid #4f46e5';
}

async function submitUrl() {
    const url   = document.getElementById('f-url').value.trim();
    const title = document.getElementById('f-url-title').value.trim();
    if (!url)   { showToast(_STR.enterUrl, false); return; }
    if (!title) { showToast(_STR.enterTitle, false); return; }
    const btn = document.getElementById('btn-url-submit');
    btn.disabled = true; btn.textContent = _STR.registering;
    fetch('{{ route("projects.files.store", $project) }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': _CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({
            file_type: 'url',
            source_url: url,
            original_name: title,
            description: document.getElementById('f-url-desc').value,
        }),
    }).then(r => r.json()).then(d => {
        if (d.ok) { showToast(_STR.urlRegistered, true); document.getElementById('modal-file').style.display = 'none'; setTimeout(() => location.reload(), 800); }
        else { showToast(d.message || _STR.registerFail, false); btn.disabled = false; btn.textContent = _STR.register; }
    }).catch(() => { btn.disabled = false; btn.textContent = _STR.register; });
}

async function submitFile() {
    const fileEl = document.getElementById('f-file');
    if (!fileEl.files[0]) { showToast(_STR.selectFile, false); return; }
    const btn = document.getElementById('btn-file-submit');
    btn.disabled = true; btn.textContent = _STR.uploading;
    const fd = new FormData();
    fd.append('file', fileEl.files[0]);
    fd.append('description', document.getElementById('f-desc').value);
    fetch('{{ route("projects.files.store", $project) }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': _CSRF, 'Accept': 'application/json' },
        body: fd,
    }).then(r => r.json()).then(d => {
        if (d.ok) { showToast(_STR.fileUploaded, true); document.getElementById('modal-file').style.display = 'none'; setTimeout(() => location.reload(), 800); }
        else { showToast(d.message || _STR.uploadFail, false); btn.disabled = false; btn.textContent = _STR.upload; }
    }).catch(() => { btn.disabled = false; btn.textContent = _STR.upload; });
}
</script>

{{-- 기획서 팝업 모달 --}}
<div id="planning-modal" onclick="if(event.target===this)closePlanningModal()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9000;align-items:center;justify-content:center;">
    <div style="width:95vw;height:95vh;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.3);display:flex;flex-direction:column;">
        <iframe id="planning-iframe" src="" style="flex:1;border:none;width:100%;height:100%;"></iframe>
    </div>
</div>
<script>
async function openPlanningModal(url) {
    const iframe = document.getElementById('planning-iframe');
    iframe.src = url || '{{ route("projects.planning.index", $project) }}?popup=1';
    document.getElementById('planning-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
async function closePlanningModal() {
    document.getElementById('planning-modal').style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePlanningModal(); });
</script>

@php
$_mmData = $project->projectMembers->map(function($m) {
    return [
        'id'         => $m->id,
        'user_id'    => $m->user_id,
        'name'       => $m->user->name,
        'email'      => $m->user->email,
        'role'       => $m->role,
        'role_label' => $m->role_label,
        'is_self'    => $m->user_id === auth()->id(),
    ];
})->values();
@endphp
{{-- 멤버 관리 팝업 --}}
<div id="modal-members" onclick="if(event.target===this)closeMembersModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:9100;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:560px;max-width:96vw;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.22);">

        {{-- 헤더 --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f1f5f9;flex-shrink:0;">
            <div>
                <h3 style="font-size:15px;font-weight:700;color:#0f172a;margin:0 0 2px;">{{ __('projects.project_members') }}</h3>
                <p id="mm-subtitle" style="font-size:12px;color:#94a3b8;margin:0;"></p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                @if($isManager)
                <button onclick="toggleMemberAddForm()" id="mm-add-btn"
                        style="display:flex;align-items:center;gap:5px;padding:6px 13px;background:#6366f1;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    {{ __('projects.member_add') }}
                </button>
                @endif
                <button onclick="closeMembersModal()"
                        style="background:transparent;border:none;cursor:pointer;font-size:20px;color:#94a3b8;line-height:1;padding:2px 4px;"
                        onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#94a3b8'">✕</button>
            </div>
        </div>

        @if($isManager)
        {{-- 멤버 추가 폼 (토글) --}}
        <div id="mm-add-form" style="display:none;padding:14px 22px;border-bottom:1px solid #f1f5f9;background:#fafafa;flex-shrink:0;">
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <div style="flex:1;">
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;">{{ __('projects.user_label') }}</label>
                    <select id="mm-user-select"
                            style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;color:#334155;outline:none;background:#fff;">
                        <option value="">{{ __('projects.select_user') }}</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;">{{ __('projects.role_label') }}</label>
                    <select id="mm-role-select"
                            style="padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;color:#334155;outline:none;background:#fff;">
                        <option value="member">{{ __('projects.role_member') }}</option>
                        <option value="manager">{{ __('projects.role_manager') }}</option>
                        <option value="viewer">{{ __('projects.role_viewer') }}</option>
                    </select>
                </div>
                <button onclick="addMember()"
                        style="padding:7px 16px;background:#6366f1;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">{{ __('projects.add_member_btn') }}</button>
            </div>
        </div>
        @endif

        {{-- 멤버 목록 --}}
        <div id="mm-list" style="flex:1;overflow-y:auto;padding:8px 0;"></div>

        {{-- 로딩 --}}
        <div id="mm-loading" style="flex:1;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:13px;padding:40px;">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation:spin 1s linear infinite;margin-right:8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            {{ __('projects.loading_members') }}
        </div>

    </div>
</div>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<script>
(async function() {
    const CSRF         = document.querySelector('meta[name="csrf-token"]').content;
    const IS_MANAGER   = {{ $isManager ? 'true' : 'false' }};
    const JSON_URL     = '{{ route("projects.members.json", $project) }}';
    const STORE_URL    = '{{ route("projects.members.store", $project) }}';

    const ROLES = {
        manager: '{{ __("projects.role_manager") }}',
        member:  '{{ __("projects.role_member") }}',
        viewer:  '{{ __("projects.role_viewer") }}',
    };
    const ROLE_COLORS = {
        manager: { bg: '#eef2ff', color: '#4f46e5' },
        member:  { bg: '#f0fdf4', color: '#16a34a' },
        viewer:  { bg: '#f8fafc', color: '#64748b' },
    };
    const STR_MM = {
        memberAdded:        '{{ __("projects.member_added") }}',
        memberRemoved:      '{{ __("projects.member_removed") }}',
        memberNone:         '{{ __("projects.member_none") }}',
        removeConfirm:      '{{ __("projects.member_remove_confirm") }}',
        roleChanged:        '{{ __("projects.role_changed") }}',
        selectUser:         '{{ __("projects.select_user") }}',
        loadingFailed:      '{{ __("projects.loading_failed") }}',
        totalMembers:       '{{ __("projects.total_members", ["count" => "COUNT_PLACEHOLDER"]) }}',
        meBadge:            '{{ __("projects.me_badge") }}',
    };

    let _mmData = null;

    window.openMembersModal = async function() {
        document.getElementById('modal-members').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        _mmLoad();
    };

    window.closeMembersModal = async function() {
        document.getElementById('modal-members').style.display = 'none';
        document.body.style.overflow = '';
        const addForm = document.getElementById('mm-add-form');
        if (addForm) addForm.style.display = 'none';
    };

    window.toggleMemberAddForm = async function() {
        const form = document.getElementById('mm-add-form');
        if (!form) return;
        const isVisible = form.style.display !== 'none';
        form.style.display = isVisible ? 'none' : 'flex';
        if (!isVisible && _mmData) _mmPopulateSelect(_mmData.availableUsers);
    };

    async function _mmLoad() {
        const list    = document.getElementById('mm-list');
        const loading = document.getElementById('mm-loading');
        list.style.display    = 'none';
        loading.style.display = 'flex';

        if (!IS_MANAGER) {
            _mmRenderReadonly();
            return;
        }

        fetch(JSON_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                _mmData = data;
                loading.style.display = 'none';
                list.style.display    = 'block';
                _mmRenderList(data.members);
                document.getElementById('mm-subtitle').textContent = STR_MM.totalMembers.replace('COUNT_PLACEHOLDER', data.members.length);
            })
            .catch(() => {
                loading.textContent = STR_MM.loadingFailed;
            });
    }

    async function _mmRenderReadonly() {
        const members = @json($_mmData);
        _mmData = { members };
        document.getElementById('mm-loading').style.display = 'none';
        document.getElementById('mm-list').style.display    = 'block';
        _mmRenderList(members);
        document.getElementById('mm-subtitle').textContent = STR_MM.totalMembers.replace('COUNT_PLACEHOLDER', members.length);
    }

    async function _mmRenderList(members) {
        const list = document.getElementById('mm-list');
        if (!members.length) {
            list.innerHTML = '<div style="text-align:center;color:#94a3b8;font-size:13px;padding:32px;">' + STR_MM.memberNone + '</div>';
            return;
        }
        list.innerHTML = members.map(m => {
            const rc = ROLE_COLORS[m.role] || ROLE_COLORS.member;
            const roleSelect = IS_MANAGER && !m.is_self
                ? `<select onchange="changeMemberRole(${m.id}, this.value)"
                           style="padding:5px 8px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;font-weight:600;color:#334155;outline:none;cursor:pointer;background:#fff;">
                       ${Object.entries(ROLES).map(([v,l]) =>
                           `<option value="${v}"${m.role===v?' selected':''}>${l}</option>`
                       ).join('')}
                   </select>`
                : `<span style="padding:3px 10px;border-radius:8px;font-size:11px;font-weight:700;background:${rc.bg};color:${rc.color};">${m.role_label}</span>`;

            const deleteBtn = IS_MANAGER && !m.is_self
                ? `<button onclick="removeMember(${m.id})"
                           style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:7px;background:transparent;border:1px solid #fecaca;color:#ef4444;cursor:pointer;flex-shrink:0;transition:background .12s;"
                           onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'"
                           title="{{ __('projects.member_remove_confirm') }}">
                       <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                   </button>`
                : '';

            const selfBadge = m.is_self
                ? `<span style="font-size:10px;padding:1px 6px;border-radius:4px;background:#fef3c7;color:#d97706;font-weight:600;">${STR_MM.meBadge}</span>`
                : '';

            return `<div style="display:flex;align-items:center;gap:12px;padding:11px 22px;border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#c4b5fd,#a78bfa);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;">
                    ${m.name.charAt(0)}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                        <span style="font-size:13px;font-weight:600;color:#0f172a;">${m.name}</span>
                        ${selfBadge}
                    </div>
                    <div style="font-size:11px;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${m.email}</div>
                </div>
                <div style="display:flex;align-items:center;gap:7px;flex-shrink:0;">
                    ${roleSelect}
                    ${deleteBtn}
                </div>
            </div>`;
        }).join('');
    }

    async function _mmPopulateSelect(users) {
        const sel = document.getElementById('mm-user-select');
        if (!sel) return;
        sel.innerHTML = '<option value="">' + STR_MM.selectUser + '</option>' +
            users.map(u => `<option value="${u.id}">${u.name} (${u.email})</option>`).join('');
    }

    window.addMember = async function() {
        const userId = document.getElementById('mm-user-select')?.value;
        const role   = document.getElementById('mm-role-select')?.value || 'member';
        if (!userId) { _mmToast(STR_MM.selectUser, false); return; }

        const res = await fetch(STORE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, role }),
        });
        const data = await res.json();
        if (data.ok) {
            _mmToast(STR_MM.memberAdded, true);
            document.getElementById('mm-add-form').style.display = 'none';
            _mmLoad();
        } else {
            _mmToast(data.message || '{{ __("projects.register_fail") }}', false);
        }
    };

    window.changeMemberRole = async function(memberId, role) {
        const url = '{{ url("projects/" . $project->id . "/members") }}/' + memberId;
        const res = await fetch(url, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ role }),
        });
        const data = await res.json();
        if (data.ok) { _mmToast(STR_MM.roleChanged, true); _mmLoad(); }
        else _mmToast(data.message || '{{ __("common.error") }}', false);
    };

    window.removeMember = async function(memberId) {
        if (!await __confirm(STR_MM.removeConfirm)) return;
        const url = '{{ url("projects/" . $project->id . "/members") }}/' + memberId;
        const res = await fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        });
        const data = await res.json();
        if (data.ok) { _mmToast(STR_MM.memberRemoved, true); _mmLoad(); }
        else _mmToast(data.message || '{{ __("common.error") }}', false);
    };

    async function _mmToast(msg, ok) {
        const t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = `position:fixed;bottom:24px;right:24px;padding:10px 18px;border-radius:8px;font-size:13px;font-weight:600;z-index:99999;color:#fff;background:${ok?'#059669':'#dc2626'};transition:opacity .3s;`;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity='0'; setTimeout(() => t.remove(), 300); }, 2500);
    }

    document.addEventListener('keydown', async function(e) {
        if (e.key === 'Escape' && document.getElementById('modal-members').style.display !== 'none') {
            closeMembersModal();
        }
    });
})();
</script>

@include('partials.file-preview-modal')

{{-- 프로젝트 삭제 확인 모달 --}}
@if($isManager)
<div id="modal-project-delete" onclick="if(event.target===this)closeProjectDeleteModal()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9300;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:440px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f4f4f5;">
            <h3 style="font-size:15px;font-weight:700;color:#18181b;">{{ __('projects.delete_modal_title') }}</h3>
            <button onclick="closeProjectDeleteModal()" style="background:none;border:none;cursor:pointer;color:#9ca3af;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div style="padding:20px 22px 22px;display:flex;flex-direction:column;gap:14px;">
            <div style="display:flex;align-items:flex-start;gap:12px;">
                <div style="flex-shrink:0;width:36px;height:36px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;">
                    <svg width="18" height="18" fill="none" stroke="#dc2626" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </div>
                <div>
                    <p style="font-size:14px;font-weight:600;color:#111827;margin:0 0 4px;">{{ __('projects.delete_confirm_heading') }}</p>
                    <p style="font-size:13px;color:#6b7280;margin:0;line-height:1.5;">
                        {!! __('projects.delete_modal_warning', ['name' => '<strong style="color:#374151;">' . e($project->name) . '</strong>']) !!}<br>{{ __('projects.delete_modal_irreversible') }}
                    </p>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:4px;">
                <button onclick="closeProjectDeleteModal()" style="padding:8px 16px;font-size:13px;color:#6b7280;background:#f9fafb;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;">{{ __('common.cancel') }}</button>
                <form method="POST" action="{{ route('projects.destroy', $project) }}" style="margin:0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:#dc2626;border:none;border-radius:8px;cursor:pointer;"
                            onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">{{ __('projects.delete_confirm_btn') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function openProjectDeleteModal() {
    document.getElementById('modal-project-delete').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeProjectDeleteModal() {
    document.getElementById('modal-project-delete').style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('modal-project-delete').style.display !== 'none') {
        closeProjectDeleteModal();
    }
});
</script>
@endif

@if($isManager)
{{-- 프로젝트 개요 수정 모달 --}}
<div id="modal-project-edit" onclick="if(event.target===this)closeProjectEditModal()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9200;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:520px;max-width:96vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f4f4f5;position:sticky;top:0;background:#fff;z-index:1;">
            <h3 style="font-size:15px;font-weight:700;color:#18181b;">{{ __('common.edit') }}</h3>
            <button onclick="closeProjectEditModal()" style="background:none;border:none;cursor:pointer;color:#9ca3af;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div style="padding:18px 22px 22px;display:flex;flex-direction:column;gap:14px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.project_name') }} <span style="color:#ef4444;">*</span></label>
                <input id="pe-name" type="text" value="{{ $project->name }}"
                       style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('common.description') }}</label>
                <textarea id="pe-description" rows="3"
                          style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box;">{{ $project->description }}</textarea>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.status') }}</label>
                <select id="pe-status" style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                    <option value="active"    {{ $project->status === 'active'    ? 'selected' : '' }}>{{ __('projects.status_active') }}</option>
                    <option value="on_hold"   {{ $project->status === 'on_hold'   ? 'selected' : '' }}>{{ __('projects.status_on_hold') }}</option>
                    <option value="completed" {{ $project->status === 'completed' ? 'selected' : '' }}>{{ __('projects.status_completed') }}</option>
                    <option value="cancelled" {{ $project->status === 'cancelled' ? 'selected' : '' }}>{{ __('projects.status_cancelled') }}</option>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.start_date') }}</label>
                    <input id="pe-start-date" type="date" value="{{ $project->start_date?->format('Y-m-d') }}"
                           style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.end_date') }}</label>
                    <input id="pe-end-date" type="date" value="{{ $project->end_date?->format('Y-m-d') }}"
                           style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.client_company') }}</label>
                <input id="pe-client-name" type="text" value="{{ $project->client_name }}"
                       style="width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:4px;">
                <button onclick="closeProjectEditModal()" style="padding:8px 16px;font-size:13px;color:#6b7280;background:#f9fafb;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button id="pe-submit-btn" onclick="submitProjectEdit()" style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:#4f46e5;border:none;border-radius:8px;cursor:pointer;">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
<script>
async function openProjectEditModal() {
    document.getElementById('modal-project-edit').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
async function closeProjectEditModal() {
    document.getElementById('modal-project-edit').style.display = 'none';
    document.body.style.overflow = '';
}
async function submitProjectEdit() {
    const name = document.getElementById('pe-name').value.trim();
    if (!name) { showToast('{{ __("projects.schedule_title_required") }}', false); return; }
    const btn = document.getElementById('pe-submit-btn');
    btn.disabled = true;
    btn.textContent = '{{ __("projects.registering") }}';
    try {
        const res = await fetch('{{ route("projects.update", $project) }}', {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': _CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name,
                description:  document.getElementById('pe-description').value,
                status:       document.getElementById('pe-status').value,
                start_date:   document.getElementById('pe-start-date').value  || null,
                end_date:     document.getElementById('pe-end-date').value    || null,
                client_name:  document.getElementById('pe-client-name').value,
            }),
        });
        const data = await res.json();
        if (data.ok) {
            showToast('{{ __("projects.schedule_registered") }}', true);
            closeProjectEditModal();
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.message || '{{ __("projects.register_fail") }}', false);
            btn.disabled = false;
            btn.textContent = '{{ __("common.save") }}';
        }
    } catch {
        btn.disabled = false;
        btn.textContent = '{{ __("common.save") }}';
    }
}
document.addEventListener('keydown', async function(e) {
    if (e.key === 'Escape' && document.getElementById('modal-project-edit').style.display !== 'none') {
        closeProjectEditModal();
    }
});
</script>
@endif

@endsection
