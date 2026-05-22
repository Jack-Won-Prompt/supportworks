@extends('layouts.app')

@section('title', __('projects.projects'))

@section('header-actions')
    @if(!auth()->user()->isAdmin())
    <div style="display:flex;background:var(--color-bg-muted);border-radius:9px;padding:3px;gap:4px;">
        <a href="{{ route('projects.index') }}"
           style="padding:5px 14px;border-radius:7px;font-size:12px;font-weight:600;text-decoration:none;transition:all .12s;
                  {{ !$viewAll ? 'background:#fff;color:#4f46e5;box-shadow:0 1px 3px rgba(0,0,0,.1);' : 'color:#71717a;' }}">
            {{ __('projects.my_projects') }}
        </a>
        <a href="{{ route('projects.index', ['all' => 1] + request()->only('search','status')) }}"
           style="padding:5px 14px;border-radius:7px;font-size:12px;font-weight:600;text-decoration:none;transition:all .12s;
                  {{ $viewAll ? 'background:#fff;color:#4f46e5;box-shadow:0 1px 3px rgba(0,0,0,.1);' : 'color:#71717a;' }}">
            {{ __('projects.all_projects') }}
        </a>
    </div>
    @endif
    @if(auth()->user()->isAdmin())
    <a href="{{ route('projects.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        {{ __('projects.new_project') }}
    </a>
    @endif
@endsection

@section('content')
<div class="pt-4">
    <!-- 필터 -->
    <form method="GET" class="flex flex-wrap gap-3 mb-6">
        @if($viewAll)<input type="hidden" name="all" value="1">@endif
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="{{ __('projects.search_placeholder') }}"
               class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
        <select name="status" class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">{{ __('projects.status_all') }}</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('projects.status_active') }}</option>
            <option value="on_hold" {{ request('status') === 'on_hold' ? 'selected' : '' }}>{{ __('projects.status_on_hold') }}</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('projects.status_completed') }}</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('projects.status_cancelled') }}</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition-colors">{{ __('common.search') }}</button>
        @if(request()->hasAny(['search', 'status']))
        <a href="{{ route('projects.index', $viewAll ? ['all'=>1] : []) }}"
           class="px-4 py-2 text-gray-500 rounded-lg text-sm hover:bg-gray-100 transition-colors">{{ __('common.reset') }}</a>
        @endif
    </form>

    @if($projects->isEmpty())
    <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <p class="text-gray-400 text-sm">{{ $viewAll ? __('projects.no_company_projects') : __('projects.no_joined_projects') }}</p>
        @if(!$viewAll)
        <a href="{{ route('projects.index', ['all'=>1]) }}" class="mt-4 inline-block px-4 py-2 bg-indigo-50 text-indigo-600 text-sm rounded-lg hover:bg-indigo-100">{{ __('projects.view_all_projects') }}</a>
        @endif
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($projects as $project)
        @php
            $isMember = $project->myMembership->isNotEmpty();
            $joinUrl  = route('projects.join', $project);
            $leaveUrl = route('projects.leave', $project);
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow" id="proj-card-{{ $project->id }}">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-700 font-bold text-sm">
                        {{ mb_substr($project->name, 0, 1) }}
                    </div>
                    <div>
                        @if($isMember || auth()->user()->isAdmin())
                        <a href="{{ route('projects.show', $project) }}" class="font-semibold text-gray-900 hover:text-indigo-600 text-sm">
                            {{ $project->name }}
                        </a>
                        @else
                        <span class="font-semibold text-gray-900 text-sm">{{ $project->name }}</span>
                        @endif
                        <p class="text-xs text-gray-400">{{ $project->creator->name }}</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 text-xs font-medium rounded-full
                    {{ $project->status === 'active' ? 'bg-green-100 text-green-700' : '' }}
                    {{ $project->status === 'on_hold' ? 'bg-yellow-100 text-yellow-700' : '' }}
                    {{ $project->status === 'completed' ? 'bg-blue-100 text-blue-700' : '' }}
                    {{ $project->status === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}">
                    {{ $project->status_label }}
                </span>
            </div>

            @if($project->description)
            <p class="text-xs text-gray-500 mb-3 line-clamp-2">{{ $project->description }}</p>
            @endif

            <div class="flex items-center gap-4 text-xs text-gray-400 mb-4">
                @if($project->start_date)
                <span>{{ $project->start_date->format('Y.m.d') }}</span>
                @if($project->end_date)
                <span>~ {{ $project->end_date->format('Y.m.d') }}</span>
                @endif
                @endif
            </div>

            <div class="flex items-center gap-4 pt-3 border-t border-gray-50">
                <span class="flex items-center gap-1 text-xs text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ $project->schedules_count }} {{ __('projects.schedule') }}
                </span>
                <span class="flex items-center gap-1 text-xs text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $project->questions_count }} Q&A
                </span>
                @if($isMember || auth()->user()->isAdmin())
                <a href="{{ route('projects.files.index', $project) }}" class="flex items-center gap-1 text-xs text-gray-400 hover:text-indigo-600 transition-colors" style="text-decoration:none;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    {{ $project->files_count }} {{ __('projects.files') }}
                </a>
                @else
                <span class="flex items-center gap-1 text-xs text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    {{ $project->files_count }} {{ __('projects.files') }}
                </span>
                @endif
                @if(!auth()->user()->isAdmin())
                <div class="ml-auto">
                    <button onclick="toggleMembership({{ $project->id }}, this)"
                            data-joined="{{ $isMember ? '1' : '0' }}"
                            data-join-url="{{ $joinUrl }}"
                            data-leave-url="{{ $leaveUrl }}"
                            data-name="{{ $project->name }}"
                            data-viewall="{{ $viewAll ? '1' : '0' }}"
                            style="padding:4px 12px;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;transition:all .15s;border:1.5px solid;
                                   {{ $isMember
                                       ? ($viewAll ? 'background:var(--color-bg-success-subtle);color:var(--color-alert-success-500);border-color:#bbf7d0;' : 'background:#fff5f5;color:var(--color-alert-warning-500);border-color:#fca5a5;')
                                       : 'background:#eef2ff;color:#4f46e5;border-color:#c7d2fe;' }}"
                            onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
                        {{ $isMember ? ($viewAll ? __('projects.joined') : __('projects.leave')) : __('projects.join') }}
                    </button>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $projects->withQueryString()->links() }}</div>
    @endif
</div>

@if(!auth()->user()->isAdmin())
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const STR = {
    joined:       '{{ __("projects.joined") }}',
    join:         '{{ __("projects.join") }}',
    leave:        '{{ __("projects.leave") }}',
    action_failed:'{{ __("projects.action_failed") }}',
};

async function toggleMembership(projectId, btn) {
    const joined  = btn.dataset.joined === '1';
    const viewAll = btn.dataset.viewall === '1';
    const url     = joined ? btn.dataset.leaveUrl : btn.dataset.joinUrl;
    const method  = joined ? 'DELETE' : 'POST';
    const name    = btn.dataset.name;

    if (joined && !await __confirm(`"${name}" {{ __("projects.confirm_leave") }}`)) return;

    btn.disabled = true;
    const res = await fetch(url, {
        method,
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
    });
    const d = await res.json().catch(() => ({}));
    btn.disabled = false;

    if (!res.ok || !d.ok) { alert(d.message || STR.action_failed); return; }

    const nowJoined = d.joined;
    btn.dataset.joined = nowJoined ? '1' : '0';

    if (viewAll) {
        btn.textContent       = nowJoined ? STR.joined : STR.join;
        btn.style.background  = nowJoined ? '#f0fdf4' : '#eef2ff';
        btn.style.color       = nowJoined ? '#16a34a' : '#4f46e5';
        btn.style.borderColor = nowJoined ? '#bbf7d0' : '#c7d2fe';
    } else {
        btn.textContent       = nowJoined ? STR.leave : STR.join;
        btn.style.background  = nowJoined ? '#fff5f5' : '#eef2ff';
        btn.style.color       = nowJoined ? '#dc2626' : '#4f46e5';
        btn.style.borderColor = nowJoined ? '#fca5a5' : '#c7d2fe';
    }

    // 프로젝트명 링크 ↔ 텍스트 전환
    const card = document.getElementById('proj-card-' + projectId);
    if (card) {
        const nameEl = card.querySelector('.font-semibold');
        if (nameEl) {
            if (nowJoined && nameEl.tagName !== 'A') {
                const a = document.createElement('a');
                a.href        = btn.dataset.joinUrl.replace('/join', '');
                a.className   = nameEl.className + ' hover:text-indigo-600';
                a.textContent = nameEl.textContent.trim();
                nameEl.replaceWith(a);
            } else if (!nowJoined && nameEl.tagName === 'A') {
                const span = document.createElement('span');
                span.className   = nameEl.className.replace(' hover:text-indigo-600', '');
                span.textContent = nameEl.textContent.trim();
                nameEl.replaceWith(span);
            }
        }
    }

    // 사이드바 즉시 업데이트
    if (nowJoined && d.project) {
        _sidebarAddProject(d.project);
    } else if (!nowJoined) {
        _sidebarRemoveProject(projectId);
    }
}

async function _sidebarAddProject(proj) {
    const colors = ['#a394f9','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6'];
    const projList = document.getElementById('proj-list');

    // 이미 있으면 건너뜀
    if (projList && projList.querySelector(`[data-proj-id="${proj.id}"]`)) return;

    const statusBadge = proj.status === 'active'
        ? '<span class="gsb-hide" style="font-size:10px;padding:1px 5px;border-radius:3px;background:#dcfce7;color:var(--color-alert-success-500);flex-shrink:0;">{{ __("projects.badge_active") }}</span>'
        : proj.status === 'on_hold'
        ? '<span class="gsb-hide" style="font-size:10px;padding:1px 5px;border-radius:3px;background:#fef9c3;color:#ca8a04;flex-shrink:0;">{{ __("projects.badge_on_hold") }}</span>'
        : '';

    const idx   = projList ? projList.querySelectorAll('[data-proj-id]').length : 0;
    const color = colors[idx % colors.length];

    if (projList) {
        // 빈 상태 메시지 제거
        const empty = projList.querySelector('div.gsb-hide');
        if (empty) empty.remove();

        const a = document.createElement('a');
        a.href = proj.show_url;
        a.className = 'project-item';
        a.dataset.projId = proj.id;
        a.innerHTML = `<span class="project-dot" style="background:${color};"></span>`
            + `<span class="gsb-hide" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${proj.name}</span>`
            + statusBadge;
        // "전체 보기" 링크 바로 앞에 삽입
        const viewAll = projList.querySelector('a.gsb-hide');
        viewAll ? projList.insertBefore(a, viewAll) : projList.appendChild(a);
    }
}

async function _sidebarRemoveProject(projectId) {
    const list = document.getElementById('proj-list');
    if (!list) return;
    const item = list.querySelector(`[data-proj-id="${projectId}"]`);
    if (item) item.remove();
}
</script>
@endif
@endsection
