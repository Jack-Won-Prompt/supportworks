@extends('layouts.admin')

@section('title', __('admin.login_logs'))

@section('header-actions')
@if(auth('admin')->user()?->isSuperAdmin())
<form method="POST" action="{{ route('admin.reset.login-logs') }}"
      onsubmit="return confirm('{{ __('admin.loginlog_reset_all_confirm') }}')">
    @csrf @method('DELETE')
    <button type="submit" class="btn-danger" style="font-size:12px;padding:6px 14px;background:#dc2626;color:#fff;border:1px solid #dc2626;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;"
            onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        {{ __('admin.loginlog_reset_all') }}
    </button>
</form>
@endif
@endsection

@section('content')
<div class="admin-stat-grid">
    <div class="admin-stat"><div class="admin-stat-val" style="color:#1e293b;">{{ $stats['total'] }}</div><div class="admin-stat-lbl">{{ __('admin.loginlog_total_attempts') }}</div></div>
    <div class="admin-stat"><div class="admin-stat-val" style="color:#16a34a;">{{ $stats['success'] }}</div><div class="admin-stat-lbl">{{ __('admin.login_success') }}</div></div>
    <div class="admin-stat"><div class="admin-stat-val" style="color:#ef4444;">{{ $stats['fail'] }}</div><div class="admin-stat-lbl">{{ __('admin.login_fail') }}</div></div>
    <div class="admin-stat"><div class="admin-stat-val" style="color:#d97706;">{{ $stats['locked'] }}</div><div class="admin-stat-lbl">{{ __('admin.login_locked') }}</div></div>
    <div class="admin-stat"><div class="admin-stat-val" style="color:#6366f1;">{{ $stats['today'] }}</div><div class="admin-stat-lbl">{{ __('admin.loginlog_today') }}</div></div>
</div>

<div class="admin-card">
    <form method="GET" action="{{ route('admin.login-logs.index') }}" class="filter-bar">
        <a href="{{ route('admin.login-logs.index', ['result'=>'all','search'=>$search]) }}" class="filter-tab {{ $result==='all'?'active':'' }}">{{ __('admin.status_all') }}</a>
        <a href="{{ route('admin.login-logs.index', ['result'=>'success','search'=>$search]) }}" class="filter-tab {{ $result==='success'?'active':'' }}">{{ __('admin.login_success') }}</a>
        <a href="{{ route('admin.login-logs.index', ['result'=>'fail','search'=>$search]) }}" class="filter-tab {{ $result==='fail'?'active':'' }}">{{ __('admin.login_fail') }}</a>
        <a href="{{ route('admin.login-logs.index', ['result'=>'locked','search'=>$search]) }}" class="filter-tab {{ $result==='locked'?'active':'' }}">{{ __('admin.login_locked') }}</a>
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
                <td>
                    <span style="font-size:12px;color:#64748b;font-family:monospace;">{{ $log->ip_address ?? '—' }}</span>
                </td>
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
@endsection
