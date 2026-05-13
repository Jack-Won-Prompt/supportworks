@extends('layouts.admin')

@section('title', __('admin.logs'))

@section('header-actions')
@if($tab === 'activity' && auth('admin')->user()?->isSuperAdmin())
<form method="POST" action="{{ route('admin.reset.activity-logs') }}"
      onsubmit="return confirm('{{ __('admin.actlog_reset_all_confirm') }}')">
    @csrf @method('DELETE')
    <button type="submit" style="font-size:12px;padding:6px 14px;background:#dc2626;color:#fff;border:1px solid #dc2626;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"
            onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        {{ __('admin.actlog_reset_all') }}
    </button>
</form>
@elseif($tab === 'login' && auth('admin')->user()?->isSuperAdmin())
<form method="POST" action="{{ route('admin.reset.login-logs') }}"
      onsubmit="return confirm('{{ __('admin.loginlog_reset_all_confirm') }}')">
    @csrf @method('DELETE')
    <button type="submit" style="font-size:12px;padding:6px 14px;background:#dc2626;color:#fff;border:1px solid #dc2626;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"
            onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        {{ __('admin.loginlog_reset_all') }}
    </button>
</form>
@elseif($tab === 'user-login' && auth('admin')->user()?->isSuperAdmin())
<form method="POST" action="{{ route('admin.reset.user-login-logs') }}"
      onsubmit="return confirm('{{ __('admin.uloginlog_reset_all_confirm') }}')">
    @csrf @method('DELETE')
    <button type="submit" style="font-size:12px;padding:6px 14px;background:#dc2626;color:#fff;border:1px solid #dc2626;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"
            onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        {{ __('admin.uloginlog_reset_all') }}
    </button>
</form>
@elseif($tab === 'page')
<form method="POST" action="{{ route('admin.reset.user-page-logs') }}"
      onsubmit="return confirm('{{ __('admin.pglog_reset_all_confirm') }}')">
    @csrf @method('DELETE')
    <button type="submit" style="font-size:12px;padding:6px 14px;background:#fff;color:#dc2626;border:1px solid #fca5a5;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"
            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        {{ __('admin.pglog_reset_all') }}
    </button>
</form>
@endif
@endsection

@section('content')
<div class="pt-4">

    {{-- ── 탭 네비게이션 ── --}}
    <div style="display:flex;gap:0;border-bottom:2px solid #e5e7eb;margin-bottom:24px;overflow-x:auto;">
        @php
        $tabs = [
            ['activity',   __('admin.activity_logs'),   '📋'],
            ['login',      __('admin.login_logs'),      '🔑'],
            ['user-login', __('admin.user_login_logs'), '👤'],
            ['page',       __('admin.user_page_logs'),  '👁'],
        ];
        @endphp
        @foreach($tabs as [$key, $label, $icon])
        <a href="{{ route('admin.logs.index', ['tab' => $key]) }}"
           style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;
                  {{ $tab === $key ? 'color:#6366f1;border-bottom-color:#6366f1;' : 'color:#94a3b8;' }}"
           onmouseover="{{ $tab !== $key ? 'this.style.color=\'#374151\'' : '' }}"
           onmouseout="{{ $tab !== $key ? 'this.style.color=\'#94a3b8\'' : '' }}">
            <span>{{ $icon }}</span>
            <span>{{ $label }}</span>
        </a>
        @endforeach
    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- 탭 1: 사용자 활동 로그                                   --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    @if($tab === 'activity')

    <form method="GET" action="{{ route('admin.logs.index') }}" class="flex gap-3 mb-5 flex-wrap items-end">
        <input type="hidden" name="tab" value="activity">
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
        <a href="{{ route('admin.logs.index', ['tab' => 'activity']) }}" class="px-4 py-2 text-gray-400 text-sm hover:text-gray-600">{{ __('admin.actlog_reset_filter') }}</a>
    </form>

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

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- 탭 2: 로그인 로그 (관리자)                               --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    @elseif($tab === 'login')

    <div class="admin-stat-grid">
        <div class="admin-stat"><div class="admin-stat-val" style="color:#1e293b;">{{ $stats['total'] }}</div><div class="admin-stat-lbl">{{ __('admin.loginlog_total_attempts') }}</div></div>
        <div class="admin-stat"><div class="admin-stat-val" style="color:#16a34a;">{{ $stats['success'] }}</div><div class="admin-stat-lbl">{{ __('admin.login_success') }}</div></div>
        <div class="admin-stat"><div class="admin-stat-val" style="color:#ef4444;">{{ $stats['fail'] }}</div><div class="admin-stat-lbl">{{ __('admin.login_fail') }}</div></div>
        <div class="admin-stat"><div class="admin-stat-val" style="color:#d97706;">{{ $stats['locked'] }}</div><div class="admin-stat-lbl">{{ __('admin.login_locked') }}</div></div>
        <div class="admin-stat"><div class="admin-stat-val" style="color:#6366f1;">{{ $stats['today'] }}</div><div class="admin-stat-lbl">{{ __('admin.loginlog_today') }}</div></div>
    </div>

    <div class="admin-card">
        <form method="GET" action="{{ route('admin.logs.index') }}" class="filter-bar">
            <input type="hidden" name="tab" value="login">
            <a href="{{ route('admin.logs.index', ['tab'=>'login','result'=>'all','search'=>$search]) }}" class="filter-tab {{ $result==='all'?'active':'' }}">{{ __('admin.status_all') }}</a>
            <a href="{{ route('admin.logs.index', ['tab'=>'login','result'=>'success','search'=>$search]) }}" class="filter-tab {{ $result==='success'?'active':'' }}">{{ __('admin.login_success') }}</a>
            <a href="{{ route('admin.logs.index', ['tab'=>'login','result'=>'fail','search'=>$search]) }}" class="filter-tab {{ $result==='fail'?'active':'' }}">{{ __('admin.login_fail') }}</a>
            <a href="{{ route('admin.logs.index', ['tab'=>'login','result'=>'locked','search'=>$search]) }}" class="filter-tab {{ $result==='locked'?'active':'' }}">{{ __('admin.login_locked') }}</a>
            <input type="hidden" name="result" value="{{ $result }}">
            <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('admin.loginlog_search_ph') }}">
            <button type="submit" class="btn-primary" style="padding:7px 14px;">{{ __('admin.search_btn') }}</button>
        </form>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('admin.col_result') }}</th>
                    <th>{{ __('admin.loginlog_col_login_id') }}</th>
                    <th>{{ __('admin.loginlog_col_admin_name') }}</th>
                    <th>{{ __('admin.loginlog_col_ip_address') }}</th>
                    <th>{{ __('admin.loginlog_col_datetime') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $badgeClass = match($log->result) { 'success'=>'badge-green', 'fail'=>'badge-red', default=>'badge-yellow' };
                    $badgeText  = match($log->result) { 'success'=>__('admin.login_success'), 'fail'=>__('admin.login_fail'), default=>__('admin.login_locked') };
                @endphp
                <tr>
                    <td style="color:#94a3b8;font-size:12px;">{{ $log->id }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $badgeText }}</span></td>
                    <td style="font-size:13px;font-weight:500;color:#1e293b;">{{ $log->login_id }}</td>
                    <td style="font-size:12px;color:#64748b;">{{ $log->admin?->name ?? '—' }}</td>
                    <td><span style="font-size:12px;color:#64748b;font-family:monospace;">{{ $log->ip_address ?? '—' }}</span></td>
                    <td style="font-size:12px;color:#94a3b8;">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:32px;color:#94a3b8;">{{ __('admin.no_logs') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($logs->hasPages())
        <div style="margin-top:16px;">{{ $logs->links() }}</div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- 탭 3: 사용자 로그인 로그                                 --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    @elseif($tab === 'user-login')

    <div class="admin-stat-grid">
        <div class="admin-stat"><div class="admin-stat-val" style="color:#1e293b;">{{ $stats['total'] }}</div><div class="admin-stat-lbl">{{ __('admin.loginlog_total_attempts') }}</div></div>
        <div class="admin-stat"><div class="admin-stat-val" style="color:#16a34a;">{{ $stats['success'] }}</div><div class="admin-stat-lbl">{{ __('admin.login_success') }}</div></div>
        <div class="admin-stat"><div class="admin-stat-val" style="color:#ef4444;">{{ $stats['fail'] }}</div><div class="admin-stat-lbl">{{ __('admin.login_fail') }}</div></div>
        <div class="admin-stat"><div class="admin-stat-val" style="color:#6366f1;">{{ $stats['today'] }}</div><div class="admin-stat-lbl">{{ __('admin.loginlog_today') }}</div></div>
    </div>

    <div class="admin-card">
        <form method="GET" action="{{ route('admin.logs.index') }}" class="filter-bar">
            <input type="hidden" name="tab" value="user-login">
            <a href="{{ route('admin.logs.index', ['tab'=>'user-login','result'=>'all','search'=>$search]) }}" class="filter-tab {{ $result==='all'?'active':'' }}">{{ __('admin.status_all') }}</a>
            <a href="{{ route('admin.logs.index', ['tab'=>'user-login','result'=>'success','search'=>$search]) }}" class="filter-tab {{ $result==='success'?'active':'' }}">{{ __('admin.login_success') }}</a>
            <a href="{{ route('admin.logs.index', ['tab'=>'user-login','result'=>'fail','search'=>$search]) }}" class="filter-tab {{ $result==='fail'?'active':'' }}">{{ __('admin.login_fail') }}</a>
            <input type="hidden" name="result" value="{{ $result }}">
            <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('admin.loginlog_search_email_ip') }}">
            <button type="submit" class="btn-primary" style="padding:7px 14px;">{{ __('admin.search_btn') }}</button>
        </form>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('admin.col_result') }}</th>
                    <th>{{ __('admin.loginlog_col_email') }}</th>
                    <th>{{ __('admin.loginlog_col_username') }}</th>
                    <th>{{ __('admin.loginlog_col_ip_address') }}</th>
                    <th>{{ __('admin.loginlog_col_datetime') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $badgeClass = $log->result === 'success' ? 'badge-green' : 'badge-red';
                    $badgeText  = $log->result === 'success' ? __('admin.login_success') : __('admin.login_fail');
                @endphp
                <tr>
                    <td style="color:#94a3b8;font-size:12px;">{{ $log->id }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $badgeText }}</span></td>
                    <td style="font-size:13px;font-weight:500;color:#1e293b;">{{ $log->email }}</td>
                    <td style="font-size:12px;color:#64748b;">{{ $log->user?->name ?? '—' }}</td>
                    <td><span style="font-size:12px;color:#64748b;font-family:monospace;">{{ $log->ip_address ?? '—' }}</span></td>
                    <td style="font-size:12px;color:#94a3b8;">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:32px;color:#94a3b8;">{{ __('admin.no_logs') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($logs->hasPages())
        <div style="margin-top:16px;">{{ $logs->links() }}</div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- 탭 4: 사용자 화면 접근 로그                              --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    @elseif($tab === 'page')

    <div class="admin-stat-grid">
        <div class="admin-stat"><div class="admin-stat-val" style="color:#1e293b;">{{ number_format($stats['total']) }}</div><div class="admin-stat-lbl">{{ __('admin.pglog_total_records') }}</div></div>
        <div class="admin-stat"><div class="admin-stat-val" style="color:#6366f1;">{{ number_format($stats['today']) }}</div><div class="admin-stat-lbl">{{ __('admin.pglog_today_access') }}</div></div>
        <div class="admin-stat"><div class="admin-stat-val" style="color:#16a34a;">{{ $stats['active_users'] }}</div><div class="admin-stat-lbl">{{ __('admin.pglog_today_active_users') }}</div></div>
    </div>

    <div class="admin-card">
        <form method="GET" action="{{ route('admin.logs.index') }}" class="filter-bar" style="flex-wrap:wrap;">
            <input type="hidden" name="tab" value="page">
            <select name="user_id" onchange="this.form.submit()" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#374151;background:#fff;cursor:pointer;">
                <option value="">{{ __('admin.actlog_all_users') }}</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>

            <select name="screen" onchange="this.form.submit()" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#374151;background:#fff;cursor:pointer;">
                <option value="">{{ __('admin.pglog_all_screens') }}</option>
                @foreach($screens as $s)
                <option value="{{ $s }}" {{ $screen === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ $dateFrom }}"
                   style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#374151;">
            <span style="color:#94a3b8;font-size:13px;align-self:center;">~</span>
            <input type="date" name="date_to" value="{{ $dateTo }}"
                   style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#374151;">

            <button type="submit" class="btn-primary" style="padding:7px 14px;">{{ __('admin.search_btn') }}</button>
            <a href="{{ route('admin.logs.index', ['tab' => 'page']) }}" style="padding:7px 12px;font-size:13px;color:#94a3b8;text-decoration:none;">{{ __('admin.pglog_reset_filter') }}</a>
            <span style="margin-left:auto;font-size:12px;color:#94a3b8;align-self:center;">{{ __('admin.pglog_total_count', ['count' => number_format($logs->total())]) }}</span>
        </form>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>{{ __('admin.pglog_col_datetime') }}</th>
                    <th>{{ __('admin.col_user') }}</th>
                    <th>{{ __('admin.pglog_col_screen') }}</th>
                    <th>{{ __('admin.pglog_col_url') }}</th>
                    <th>{{ __('admin.pglog_col_ip_address') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="font-size:12px;color:#94a3b8;white-space:nowrap;">
                        {{ $log->created_at->format('Y-m-d') }}
                        <span style="color:#cbd5e1;">{{ $log->created_at->format('H:i:s') }}</span>
                    </td>
                    <td>
                        @if($log->user)
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:24px;height:24px;border-radius:50%;background:#e0e7ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                                {{ mb_substr($log->user->name, 0, 1) }}
                            </div>
                            <span style="font-size:13px;font-weight:500;color:#1e293b;">{{ $log->user->name }}</span>
                        </div>
                        @else
                        <span style="font-size:12px;color:#94a3b8;">{{ __('admin.pglog_deleted_user') }}</span>
                        @endif
                    </td>
                    <td>
                        <span style="display:inline-block;padding:2px 10px;background:#f1f5f9;border-radius:9999px;font-size:12px;font-weight:500;color:#475569;">
                            {{ $log->screen_name ?? '—' }}
                        </span>
                    </td>
                    <td style="font-size:12px;color:#64748b;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $log->url }}">
                        {{ $log->url }}
                    </td>
                    <td style="font-size:12px;color:#94a3b8;font-family:monospace;white-space:nowrap;">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:40px;color:#94a3b8;">{{ __('admin.pglog_no_access_logs') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($logs->hasPages())
        <div style="margin-top:16px;">{{ $logs->links() }}</div>
        @endif
    </div>

    @endif

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
