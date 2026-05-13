@extends('layouts.admin')

@section('title', __('admin.user_login_logs'))

@section('content')
<div class="admin-stat-grid">
    <div class="admin-stat"><div class="admin-stat-val" style="color:#1e293b;">{{ $stats['total'] }}</div><div class="admin-stat-lbl">{{ __('admin.loginlog_total_attempts') }}</div></div>
    <div class="admin-stat"><div class="admin-stat-val" style="color:#16a34a;">{{ $stats['success'] }}</div><div class="admin-stat-lbl">{{ __('admin.login_success') }}</div></div>
    <div class="admin-stat"><div class="admin-stat-val" style="color:#ef4444;">{{ $stats['fail'] }}</div><div class="admin-stat-lbl">{{ __('admin.login_fail') }}</div></div>
    <div class="admin-stat"><div class="admin-stat-val" style="color:#6366f1;">{{ $stats['today'] }}</div><div class="admin-stat-lbl">{{ __('admin.loginlog_today') }}</div></div>
</div>

<div class="admin-card">
    <form method="GET" action="{{ route('admin.user-login-logs.index') }}" class="filter-bar">
        <a href="{{ route('admin.user-login-logs.index', ['result'=>'all','search'=>$search]) }}" class="filter-tab {{ $result==='all'?'active':'' }}">{{ __('admin.status_all') }}</a>
        <a href="{{ route('admin.user-login-logs.index', ['result'=>'success','search'=>$search]) }}" class="filter-tab {{ $result==='success'?'active':'' }}">{{ __('admin.login_success') }}</a>
        <a href="{{ route('admin.user-login-logs.index', ['result'=>'fail','search'=>$search]) }}" class="filter-tab {{ $result==='fail'?'active':'' }}">{{ __('admin.login_fail') }}</a>
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
