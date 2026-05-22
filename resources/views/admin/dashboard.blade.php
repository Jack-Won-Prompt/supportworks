@extends('layouts.admin')

@section('title', __('admin.dashboard'))

@section('content')
@php
    $totalUsers    = \App\Models\User::count();
    $activeUsers   = \App\Models\User::where('created_at', '>=', now()->subDays(30))->count();
    $openInquiries = \App\Models\Conversation::where('type','inquiry')->where('status','open')->count();
    $todayLogs     = \App\Models\AdminLoginLog::whereDate('created_at', today())->count();
    $recentLogs    = \App\Models\AdminLoginLog::with('admin')->orderByDesc('created_at')->take(8)->get();
    $recentInquiries = \App\Models\Conversation::where('type','inquiry')
        ->with(['participants','messages'=>fn($q)=>$q->latest()->limit(1)])
        ->orderByDesc('updated_at')->take(6)->get();
    $latestApp = \App\Models\AppVersion::where('is_active', true)->first()
              ?? \App\Models\AppVersion::orderByDesc('created_at')->first();
@endphp

{{-- Windows 앱 다운로드 --}}
@if($latestApp && $latestApp->download_url)
<div class="admin-card" style="margin-bottom:20px;background:linear-gradient(135deg,#1e3a5f 0%,#1e6fd9 100%);border:none;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="22" height="22" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <div style="font-size:13px;font-weight:700;color:#fff;">{{ __('admin.windows_app_title') }}</div>
                <div style="font-size:12px;color:rgba(255,255,255,.65);margin-top:2px;">
                    {{ __('admin.latest_version') }} <span style="font-weight:600;color:#93c5fd;">v{{ $latestApp->version }}</span>
                    @if($latestApp->release_notes)
                    &nbsp;·&nbsp; {{ Str::limit($latestApp->release_notes, 60) }}
                    @endif
                </div>
            </div>
        </div>
        <a href="{{ $latestApp->download_url }}"
           style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#fff;color:#1e3a5f;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;transition:opacity .15s;"
           onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            {{ __('admin.download') }}
        </a>
    </div>
</div>
@endif

<div class="admin-stat-grid">
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#6366f1;">{{ $totalUsers }}</div>
        <div class="admin-stat-lbl">{{ __('admin.stat_total_users') }}</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#10b981;">{{ $activeUsers }}</div>
        <div class="admin-stat-lbl">{{ __('admin.stat_new_30d') }}</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#f59e0b;">{{ $openInquiries }}</div>
        <div class="admin-stat-lbl">{{ __('admin.stat_open_inquiries') }}</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#64748b;">{{ $todayLogs }}</div>
        <div class="admin-stat-lbl">{{ __('admin.stat_today_logins') }}</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

    {{-- 최근 문의 --}}
    <div class="admin-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h3 style="font-size:13px;font-weight:700;color:#1e293b;">{{ __('admin.recent_inquiries') }}</h3>
            <a href="{{ route('admin.inquiries.index') }}" class="btn-secondary" style="padding:4px 10px;font-size:11px;">{{ __('admin.view_all') }}</a>
        </div>
        @forelse($recentInquiries as $conv)
        @php
            $user = $conv->participants->first();
            $lastMsg = $conv->messages->first();
        @endphp
        <a href="{{ route('admin.inquiries.show', $conv) }}" style="display:flex;align-items:center;gap:12px;padding:9px 0;border-bottom:1px solid #f1f5f9;text-decoration:none;" class="last:border-0">
            <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;">
                {{ $user ? mb_substr($user->name,0,1) : '?' }}
            </div>
            <div style="flex:1;min-width:0;">
                <p style="font-size:12px;font-weight:600;color:#1e293b;">{{ $user?->name ?? __('admin.unknown') }}</p>
                <p style="font-size:11px;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $lastMsg?->body ? Str::limit(strip_tags($lastMsg->body), 60) : __('admin.no_message') }}</p>
            </div>
            @php
                $badge = match($conv->status) {
                    'open'    => ['badge-red',  __('admin.status_open')],
                    'active'  => ['badge-blue', __('admin.status_active')],
                    default   => ['badge-gray', __('admin.status_closed')]
                };
            @endphp
            <span class="badge {{ $badge[0] }}" style="flex-shrink:0;">{{ $badge[1] }}</span>
        </a>
        @empty
        <p style="font-size:13px;color:#94a3b8;text-align:center;padding:20px 0;">{{ __('admin.no_inquiries') }}</p>
        @endforelse
    </div>

    {{-- 최근 로그인 로그 --}}
    <div class="admin-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h3 style="font-size:13px;font-weight:700;color:#1e293b;">{{ __('admin.recent_login_logs') }}</h3>
            <a href="{{ route('admin.login-logs.index') }}" class="btn-secondary" style="padding:4px 10px;font-size:11px;">{{ __('admin.view_all') }}</a>
        </div>
        @foreach($recentLogs as $log)
        <div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid #f1f5f9;">
            @php
                $lbadge = match($log->result) {
                    'success' => ['badge-green',  __('admin.login_success')],
                    'fail'    => ['badge-red',    __('admin.login_fail')],
                    default   => ['badge-yellow', __('admin.login_locked')]
                };
            @endphp
            <span class="badge {{ $lbadge[0] }}" style="flex-shrink:0;min-width:40px;justify-content:center;">{{ $lbadge[1] }}</span>
            <div style="flex:1;min-width:0;">
                <p style="font-size:12px;font-weight:500;color:#334155;">{{ $log->login_id }}</p>
                <p style="font-size:11px;color:#94a3b8;">{{ $log->ip_address }}</p>
            </div>
            <span style="font-size:11px;color:#94a3b8;flex-shrink:0;">{{ $log->created_at->format('m/d H:i') }}</span>
        </div>
        @endforeach
    </div>

</div>
@endsection
