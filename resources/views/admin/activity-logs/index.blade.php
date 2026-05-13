@extends('layouts.admin')

@section('title', __('admin.activity_logs'))

@section('header-actions')
@if(auth('admin')->user()?->isSuperAdmin())
<form method="POST" action="{{ route('admin.reset.activity-logs') }}"
      onsubmit="return confirm('{{ __('admin.actlog_reset_all_confirm') }}')">
    @csrf @method('DELETE')
    <button type="submit" style="font-size:12px;padding:6px 14px;background:#dc2626;color:#fff;border:1px solid #dc2626;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"
            onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        {{ __('admin.actlog_reset_all') }}
    </button>
</form>
@endif
@endsection

@section('content')
<div class="pt-4">

    {{-- 필터 --}}
    <form method="GET" class="flex gap-3 mb-5 flex-wrap items-end">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="{{ __('admin.actlog_search_content') }}"
               class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-52">

        <select name="user_id" onchange="this.form.submit()"
                class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">{{ __('admin.actlog_all_users') }}</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>

        <select name="action" onchange="this.form.submit()"
                class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">{{ __('admin.actlog_all_actions') }}</option>
            <option value="created" {{ request('action') === 'created' ? 'selected' : '' }}>{{ __('admin.actlog_action_created') }}</option>
            <option value="updated" {{ request('action') === 'updated' ? 'selected' : '' }}>{{ __('admin.actlog_action_updated') }}</option>
            <option value="deleted" {{ request('action') === 'deleted' ? 'selected' : '' }}>{{ __('admin.actlog_action_deleted') }}</option>
        </select>

        <select name="subject_type" onchange="this.form.submit()"
                class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">{{ __('admin.actlog_all_types') }}</option>
            @foreach(\App\Models\ActivityLog::$modelLabels as $class => $label)
            <option value="{{ $class }}" {{ request('subject_type') === $class ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <span class="text-gray-400 text-sm self-center">~</span>
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">

        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">{{ __('admin.search_btn') }}</button>
        <a href="{{ route('admin.activity-logs.index') }}" class="px-4 py-2 text-gray-400 text-sm hover:text-gray-600">{{ __('admin.actlog_reset_filter') }}</a>
    </form>

    {{-- 총 건수 --}}
    <div class="text-xs text-gray-400 mb-3">{{ __('admin.actlog_total_count', ['count' => number_format($logs->total())]) }}</div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm" style="table-layout:fixed;">
            <colgroup>
                <col style="width:130px;">
                <col style="width:130px;">
                <col style="width:90px;">
                <col style="width:130px;">
                <col style="width:60px;">
                <col style="width:auto;">
                <col style="width:190px;">
                <col style="width:110px;">
            </colgroup>
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.actlog_col_time') }}</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.col_user') }}</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.actlog_col_screen') }}</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.log_type') }}</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.log_action') }}</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.actlog_col_content') }}</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.actlog_col_changes') }}</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.col_ip') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($logs as $log)
                @php
                    $color = $log->actionColor();
                    $colorMap = [
                        'emerald' => 'bg-emerald-100 text-emerald-700',
                        'blue'    => 'bg-blue-100 text-blue-700',
                        'red'     => 'bg-red-100 text-red-700',
                        'gray'    => 'bg-gray-100 text-gray-600',
                    ];
                @endphp
                <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleChanges({{ $log->id }})">
                    <td class="px-5 py-3 text-gray-500 text-xs whitespace-nowrap">
                        {{ $log->created_at->format('Y.m.d') }}
                        <span class="text-gray-400 ml-1">{{ $log->created_at->format('H:i:s') }}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($log->user)
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-content:center text-xs font-bold flex-shrink-0" style="display:flex;align-items:center;justify-content:center;">
                                {{ mb_substr($log->user->name, 0, 1) }}
                            </div>
                            <span class="text-gray-700 text-xs font-medium truncate">{{ $log->user->name }}</span>
                        </div>
                        @else
                        <span class="text-gray-400 text-xs">{{ __('admin.actlog_deleted_user') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="text-xs text-gray-500 font-medium">{{ $log->screenName() }}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="text-xs text-gray-600 bg-gray-100 px-2 py-0.5 rounded">{{ $log->modelLabel() }}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $colorMap[$color] ?? $colorMap['gray'] }}">
                            {{ $log->actionLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700 text-xs" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $log->subject_label }}">
                        {{ $log->subject_label ?: __('admin.actlog_no_content') }}
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        @if($log->action === 'updated' && $log->changes)
                            <span class="text-indigo-500">{{ implode(', ', array_keys($log->changes)) }}</span>
                        @elseif($log->action === 'created')
                            <span class="text-emerald-500">{{ __('admin.actlog_newly_created') }}</span>
                        @elseif($log->action === 'deleted')
                            <span class="text-red-400">{{ __('admin.actlog_deleted') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400 font-mono whitespace-nowrap">{{ $log->ip_address }}</td>
                </tr>
                @if($log->action === 'updated' && $log->changes)
                <tr id="changes-{{ $log->id }}" class="hidden bg-indigo-50">
                    <td colspan="8" class="px-5 py-3">
                        <div class="text-xs font-semibold text-indigo-600 mb-2">{{ __('admin.actlog_change_detail') }}</div>
                        <div class="grid grid-cols-1 gap-1">
                            @foreach($log->changes as $field => $change)
                            <div class="flex items-start gap-3 text-xs">
                                <span class="font-mono text-indigo-500 font-semibold min-w-28 flex-shrink-0">{{ $field }}</span>
                                <span class="text-red-500 line-through max-w-xs truncate" title="{{ $change['old'] }}">{{ $change['old'] ?? __('admin.actlog_no_content') }}</span>
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" class="flex-shrink-0 mt-0.5 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                <span class="text-emerald-600 max-w-xs truncate" title="{{ $change['new'] }}">{{ $change['new'] ?? __('admin.actlog_no_content') }}</span>
                            </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-12 text-center text-gray-400 text-sm">{{ __('admin.actlog_no_actlogs') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $logs->withQueryString()->links() }}</div>
</div>
@endsection

@section('scripts')
<script>
async function toggleChanges(id) {
    const row = document.getElementById('changes-' + id);
    if (row) row.classList.toggle('hidden');
}
</script>
@endsection
