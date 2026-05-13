@extends('layouts.admin')

@section('title', __('admin.user_page_logs'))

@section('content')
<div class="admin-stat-grid">
    <div class="admin-stat"><div class="admin-stat-val" style="color:#1e293b;">{{ number_format($stats['total']) }}</div><div class="admin-stat-lbl">{{ __('admin.pglog_total_records') }}</div></div>
    <div class="admin-stat"><div class="admin-stat-val" style="color:#6366f1;">{{ number_format($stats['today']) }}</div><div class="admin-stat-lbl">{{ __('admin.pglog_today_access') }}</div></div>
    <div class="admin-stat"><div class="admin-stat-val" style="color:#16a34a;">{{ $stats['active_users'] }}</div><div class="admin-stat-lbl">{{ __('admin.pglog_today_active_users') }}</div></div>
</div>

<div class="admin-card">
    <form method="GET" action="{{ route('admin.user-page-logs.index') }}" class="filter-bar" style="flex-wrap:wrap;">
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
        <a href="{{ route('admin.user-page-logs.index') }}" style="padding:7px 12px;font-size:13px;color:#94a3b8;text-decoration:none;">{{ __('admin.pglog_reset_filter') }}</a>

        <span style="margin-left:auto;font-size:12px;color:#94a3b8;align-self:center;">{{ __('admin.pglog_total_count', ['count' => number_format($logs->total())]) }}</span>
    </form>

    <form method="POST" action="{{ route('admin.reset.user-page-logs') }}"
          onsubmit="return confirm('{{ __('admin.pglog_reset_all_confirm') }}')"
          style="margin-top:12px;text-align:right;">
        @csrf @method('DELETE')
        <button type="submit" style="padding:7px 14px;font-size:13px;font-weight:500;color:#dc2626;background:#fff;border:1px solid #fca5a5;border-radius:8px;cursor:pointer;transition:all .12s;"
                onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
            {{ __('admin.pglog_reset_all') }}
        </button>
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
@endsection
