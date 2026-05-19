@extends('layouts.admin')

@section('title', __('admin.user_edit_title'))

@section('content')
<div class="max-w-lg pt-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.col_name') }}</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.col_email') }}</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.usr_new_password') }} {{ __('admin.usr_change_only') }}</label>
                <input type="password" name="password"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.password_confirm_label') }}</label>
                <input type="password" name="password_confirmation"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.col_role') }}</label>
                <select name="role" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="client" {{ old('role', $user->role) === 'client' ? 'selected' : '' }}>{{ __('admin.role_client') }}</option>
                    <option value="member" {{ old('role', $user->role) === 'member' ? 'selected' : '' }}>{{ __('admin.role_member') }}</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>{{ __('admin.role_admin') }}</option>
                </select>
            </div>

            <div>
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" name="is_sr_agent" value="1" {{ old('is_sr_agent', $user->is_sr_agent) ? 'checked' : '' }}
                           class="w-4 h-4 accent-indigo-600 cursor-pointer">
                    <span class="text-sm font-medium text-gray-700">{{ __('admin.sr_agent_label') }}</span>
                </label>
                <p class="text-xs text-gray-400 mt-1 ml-6">{{ __('admin.sr_agent_hint') }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.col_company') }}</label>
                    <input type="text" name="company" value="{{ old('company', $user->company) }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.usr_contact') }}</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('admin.usr_affiliated_group') }}</label>
                <select name="company_group_id" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">{{ __('admin.unassigned') }}</option>
                    @foreach($groups as $group)
                    <option value="{{ $group->id }}" {{ old('company_group_id', $user->company_group_id) == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>

            @php
                $selectedProjectIds = collect(old('project_ids', $assignedProjectIds ?? []))->map(fn($v) => (int) $v)->all();
            @endphp
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-medium text-gray-700">{{ __('admin.mgmt_project_assign') }}</label>
                    <span id="user-proj-count" class="text-xs text-indigo-600 font-medium"></span>
                </div>
                <div class="border border-gray-200 rounded-lg max-h-64 overflow-y-auto p-2 bg-white">
                    @forelse($projects as $proj)
                        @php
                            $statusColor = match($proj->status) { 'active' => '#22c55e', 'completed' => '#94a3b8', default => '#f59e0b' };
                            $statusLbl   = match($proj->status) { 'active' => __('admin.maint_status_in_progress'), 'completed' => __('admin.maint_status_completed'), default => __('admin.status_pending') };
                            $checked     = in_array((int) $proj->id, $selectedProjectIds, true);
                        @endphp
                        <label class="flex items-center gap-2.5 px-2.5 py-2 rounded-md cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="project_ids[]" value="{{ $proj->id }}" {{ $checked ? 'checked' : '' }}
                                   class="user-proj-check w-4 h-4 accent-indigo-600 cursor-pointer">
                            <span class="text-sm text-gray-800 flex-1">{{ $proj->name }}</span>
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
                                  style="background: {{ $statusColor }}22; color: {{ $statusColor }};">{{ $statusLbl }}</span>
                        </label>
                    @empty
                        <p class="text-center text-sm text-gray-400 py-5">{{ __('admin.mgmt_no_projects') }}</p>
                    @endforelse
                </div>
                <input type="hidden" name="project_ids_present" value="1">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">{{ __('admin.usr_save') }}</button>
                <a href="{{ route('admin.users.index') }}" class="px-6 py-2.5 text-gray-600 text-sm font-medium rounded-lg border border-gray-200 hover:bg-gray-50">{{ __('admin.usr_cancel') }}</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const STR_SELECTED = '{{ __('admin.mgmt_selected_count') }}';
    const checks  = document.querySelectorAll('.user-proj-check');
    const counter = document.getElementById('user-proj-count');
    function update() {
        const n = document.querySelectorAll('.user-proj-check:checked').length;
        counter.textContent = n > 0 ? n + STR_SELECTED : '';
    }
    checks.forEach(cb => cb.addEventListener('change', update));
    update();
})();
</script>
@endsection
