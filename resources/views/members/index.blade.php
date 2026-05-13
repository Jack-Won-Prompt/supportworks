@extends('layouts.app')

@section('title', $project->name . ' - ' . __('projects.member_management'))

@section('header-actions')@endsection

@section('content')
@include('partials.project-nav', ['project'=>$project, 'active'=>'members'])
@php $isManager = auth()->user()->isAdmin() || $project->getMemberRole(auth()->user()) === 'manager'; @endphp
<div class="max-w-3xl pt-4 space-y-5">
    <!-- 멤버 추가 -->
    @if($isManager && $availableUsers->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('projects.member_add_section') }}</h3>
        <form method="POST" action="{{ route('projects.members.bulk-store', $project) }}" id="bulk-member-form"
              onsubmit="return validateBulkForm()">
            @csrf
            <div class="flex gap-3 items-start">
                <!-- Multi-user selector -->
                <div class="flex-1 relative" id="user-select-wrap">
                    <div id="user-select-display" onclick="toggleUserDropdown()"
                         class="min-h-[38px] px-3 py-1.5 border border-gray-200 rounded-lg cursor-pointer flex flex-wrap gap-1 items-center bg-white">
                        <span id="no-selection-hint" class="text-sm text-gray-400 pointer-events-none select-none">{{ __('projects.select_user_multi') }}</span>
                    </div>
                    <div id="user-dropdown" style="display:none"
                         class="absolute z-50 left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg">
                        <div class="px-3 py-2 border-b border-gray-100">
                            <input type="text" id="user-search" placeholder="{{ __('projects.search_name_email') }}"
                                   oninput="filterUsers(this.value)"
                                   onclick="event.stopPropagation()"
                                   class="w-full text-sm outline-none text-gray-700 placeholder-gray-400">
                        </div>
                        <div class="max-h-48 overflow-y-auto">
                            @foreach($availableUsers as $u)
                            <label class="user-option flex items-center gap-2 px-3 py-2 hover:bg-gray-50 cursor-pointer"
                                   data-name="{{ strtolower($u->name) }}" data-email="{{ strtolower($u->email) }}">
                                <input type="checkbox" name="user_ids[]" value="{{ $u->id }}"
                                       onchange="updateSelection()" class="rounded border-gray-300 text-indigo-600 flex-shrink-0">
                                <span class="text-sm font-medium text-gray-900">{{ $u->name }}</span>
                                <span class="text-xs text-gray-400 ml-auto">{{ $u->email }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!-- Role -->
                <select name="role" class="w-32 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-shrink-0">
                    <option value="member">{{ __('projects.role_member') }}</option>
                    <option value="manager">{{ __('projects.role_manager') }}</option>
                    <option value="viewer">{{ __('projects.role_viewer') }}</option>
                </select>
                <!-- Submit -->
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 whitespace-nowrap flex-shrink-0">{{ __('projects.add_btn') }}</button>
            </div>
        </form>
    </div>
    @endif

    <!-- 멤버 목록 -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900">{{ __('projects.current_members', ['count' => $project->projectMembers->count()]) }}</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($project->projectMembers as $member)
            <div class="flex items-center justify-between px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-indigo-100 rounded-full flex items-center justify-center text-sm font-bold text-indigo-700">
                        {{ mb_substr($member->user->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $member->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $member->user->email }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @if($isManager)
                    <form method="POST" action="{{ route('projects.members.update', [$project, $member]) }}" class="flex items-center gap-2">
                        @csrf @method('PATCH')
                        <select name="role" onchange="this.form.submit()"
                                class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                {{ $member->user_id === auth()->id() ? 'disabled' : '' }}>
                            <option value="manager" {{ $member->role === 'manager' ? 'selected' : '' }}>{{ __('projects.role_manager') }}</option>
                            <option value="member" {{ $member->role === 'member' ? 'selected' : '' }}>{{ __('projects.role_member') }}</option>
                            <option value="viewer" {{ $member->role === 'viewer' ? 'selected' : '' }}>{{ __('projects.role_viewer') }}</option>
                        </select>
                    </form>
                    @if($member->user_id !== auth()->id())
                    <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}"
                          onsubmit="return confirm('{{ __('projects.confirm_remove_member') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 text-gray-300 hover:text-red-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </form>
                    @endif
                    @else
                    <span class="px-2.5 py-1 text-xs font-medium rounded-full
                        {{ $member->role === 'manager' ? 'bg-indigo-100 text-indigo-700' : '' }}
                        {{ $member->role === 'member'  ? 'bg-gray-100 text-gray-600' : '' }}
                        {{ $member->role === 'viewer'  ? 'bg-green-100 text-green-700' : '' }}">
                        {{ __('projects.role_' . $member->role) }}
                    </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<script>
const STR_MEM = {
    addUserSelect: '{{ __("projects.add_user_select") }}',
};

async function toggleUserDropdown() {
    const dd = document.getElementById('user-dropdown');
    const open = dd.style.display === 'block';
    dd.style.display = open ? 'none' : 'block';
    if (!open) {
        setTimeout(() => document.getElementById('user-search').focus(), 50);
    }
}

async function filterUsers(query) {
    query = query.toLowerCase();
    document.querySelectorAll('.user-option').forEach(opt => {
        const match = !query || opt.dataset.name.includes(query) || opt.dataset.email.includes(query);
        opt.style.display = match ? 'flex' : 'none';
    });
}

async function updateSelection() {
    const checked = [...document.querySelectorAll('input[name="user_ids[]"]:checked')];
    const display = document.getElementById('user-select-display');
    const hint    = document.getElementById('no-selection-hint');

    display.querySelectorAll('.sel-badge').forEach(b => b.remove());

    if (checked.length === 0) {
        hint.style.display = '';
    } else {
        hint.style.display = 'none';
        checked.forEach(cb => {
            const label = cb.closest('label');
            const name  = label.querySelector('.text-sm.font-medium').textContent.trim();
            const badge = document.createElement('span');
            badge.className = 'sel-badge inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs font-medium';
            badge.innerHTML = name + ' <button type="button" data-uid="' + cb.value + '" onclick="deselectUser(this)" class="text-indigo-400 hover:text-indigo-700 font-bold leading-none">&times;</button>';
            display.appendChild(badge);
        });
    }
}

async function deselectUser(btn) {
    event.stopPropagation();
    const uid = btn.dataset.uid;
    const cb  = document.querySelector('input[name="user_ids[]"][value="' + uid + '"]');
    if (cb) { cb.checked = false; updateSelection(); }
}

async function validateBulkForm() {
    const checked = document.querySelectorAll('input[name="user_ids[]"]:checked');
    if (checked.length === 0) {
        alert(STR_MEM.addUserSelect);
        return false;
    }
    return true;
}

// Close dropdown on outside click
document.addEventListener('click', async function(e) {
    const wrap = document.getElementById('user-select-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('user-dropdown').style.display = 'none';
    }
});
</script>
@endsection
