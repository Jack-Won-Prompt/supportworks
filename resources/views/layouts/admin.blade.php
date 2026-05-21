<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.admin')) — SupportWorks Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <script>window.broadcastAuthPath = '{{ request()->getBasePath() }}/broadcasting/auth';</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials._client-error-reporter')
    @stack('styles')
    @php
        $__hYear = (int)date('Y');
        $__holidays = array_merge(
            \App\Helpers\KoreanHolidays::getHolidays($__hYear - 1),
            \App\Helpers\KoreanHolidays::getHolidays($__hYear),
            \App\Helpers\KoreanHolidays::getHolidays($__hYear + 1),
            \App\Helpers\KoreanHolidays::getHolidays($__hYear + 2)
        );
    @endphp
    <script>window.KOREAN_HOLIDAYS = @json($__holidays);</script>
    <style>
        *{box-sizing:border-box;}
        body{margin:0;font-family:'Pretendard','Noto Sans KR',sans-serif;background:#f1f5f9;color:#1e293b;}
        .admin-wrap{display:flex;min-height:100vh;}

        /* ── 사이드바 ── */
        .admin-side{width:220px;min-width:220px;background:#1e293b;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;}
        .admin-logo{padding:20px 18px 16px;border-bottom:1px solid rgba(255,255,255,.07);}
        .admin-logo a{text-decoration:none;display:flex;align-items:center;gap:10px;}
        .admin-logo-icon{width:32px;height:32px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:9px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:#fff;flex-shrink:0;}
        .admin-logo-text{font-size:13px;font-weight:700;color:#fff;line-height:1.2;}
        .admin-logo-sub{font-size:10px;color:rgba(255,255,255,.35);margin-top:1px;}
        .admin-nav{flex:1;overflow-y:auto;padding:10px 10px;}
        .admin-nav-label{font-size:10px;font-weight:600;color:rgba(255,255,255,.25);letter-spacing:.08em;text-transform:uppercase;padding:14px 8px 6px;}
        .admin-nav a{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;color:rgba(255,255,255,.55);transition:all .15s;margin-bottom:1px;}
        .admin-nav a:hover{background:rgba(255,255,255,.07);color:#fff;}
        .admin-nav a.active{background:rgba(99,102,241,.25);color:#a5b4fc;}
        .admin-nav a svg{flex-shrink:0;opacity:.7;}
        .admin-nav a.active svg{opacity:1;}
        .admin-nav a .nav-badge{margin-left:auto;background:#ef4444;color:#fff;font-size:10px;font-weight:700;border-radius:10px;padding:1px 6px;}
        .admin-bottom{padding:12px;border-top:1px solid rgba(255,255,255,.07);}
        .admin-profile{display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:8px;}
        .admin-avatar{width:30px;height:30px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;}
        .admin-profile-name{font-size:12px;font-weight:600;color:#e2e8f0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        .admin-profile-role{font-size:10px;color:rgba(255,255,255,.35);}
        .admin-logout{display:flex;align-items:center;gap:7px;width:100%;padding:7px 10px;background:rgba(239,68,68,.12);border:none;border-radius:7px;color:#fca5a5;font-size:12px;font-weight:500;cursor:pointer;margin-top:6px;transition:background .15s;}
        .admin-logout:hover{background:rgba(239,68,68,.22);}

        /* ── 메인 ── */
        .admin-main{flex:1;display:flex;flex-direction:column;min-width:0;}
        .admin-topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0 24px;height:52px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;}
        .admin-topbar-title{font-size:14px;font-weight:700;color:#1e293b;}
        .admin-topbar-right{display:flex;align-items:center;gap:12px;}
        .admin-content{padding:24px;flex:1;}

        /* ── 공통 UI ── */
        .admin-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;}
        .admin-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px;}
        .admin-stat{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 18px;}
        .admin-stat-val{font-size:24px;font-weight:800;line-height:1;}
        .admin-stat-lbl{font-size:11px;color:#64748b;margin-top:4px;}
        .btn-primary{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:#6366f1;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;transition:background .15s;}
        .btn-primary:hover{background:#4f46e5;}
        .btn-secondary{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:#f8fafc;color:#475569;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;transition:all .15s;}
        .btn-secondary:hover{background:#f1f5f9;}
        .btn-danger{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:#fee2e2;color:#dc2626;border:none;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;transition:background .15s;}
        .btn-danger:hover{background:#fecaca;}
        .admin-table{width:100%;border-collapse:collapse;}
        .admin-table th{background:#f8fafc;padding:10px 14px;text-align:left;font-size:11px;font-weight:600;color:#64748b;letter-spacing:.04em;text-transform:uppercase;border-bottom:1px solid #e2e8f0;}
        .admin-table td{padding:12px 14px;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
        .admin-table tr:last-child td{border-bottom:none;}
        .admin-table tr:hover td{background:#f8fafc;}
        .badge{display:inline-flex;align-items:center;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;}
        .badge-green{background:#dcfce7;color:#16a34a;}
        .badge-yellow{background:#fef9c3;color:#a16207;}
        .badge-red{background:#fee2e2;color:#dc2626;}
        .badge-gray{background:#f1f5f9;color:#64748b;}
        .badge-blue{background:#dbeafe;color:#1d4ed8;}
        .badge-purple{background:#ede9fe;color:#6d28d9;}
        .filter-bar{display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap;}
        .filter-bar input[type=text]{padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#334155;outline:none;min-width:200px;}
        .filter-bar input[type=text]:focus{border-color:#6366f1;}
        .filter-tab{padding:6px 14px;border-radius:20px;font-size:12px;font-weight:500;text-decoration:none;border:1px solid #e2e8f0;color:#64748b;background:#fff;cursor:pointer;transition:all .15s;}
        .filter-tab:hover,.filter-tab.active{background:#6366f1;color:#fff;border-color:#6366f1;}
        .alert-success{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;font-size:13px;color:#16a34a;margin-bottom:14px;}
        .alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:13px;color:#dc2626;margin-bottom:14px;}

        /* 관리자 메뉴 검색 */
        .admin-search-wrap{padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.06);flex-shrink:0;position:relative;}
        #admin-sidebar-search{width:100%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:7px 10px 7px 32px;font-size:12.5px;color:#e2e8f0;outline:none;transition:all .15s;box-sizing:border-box;}
        #admin-sidebar-search::placeholder{color:rgba(255,255,255,.3);}
        #admin-sidebar-search:focus{background:rgba(255,255,255,.12);border-color:rgba(99,102,241,.5);}
        #admin-search-drop{display:none;position:absolute;top:calc(100% - 4px);left:10px;right:10px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.18);z-index:9999;max-height:320px;overflow-y:auto;}
        #admin-search-drop a{display:flex;align-items:center;gap:10px;padding:9px 14px;font-size:13px;color:#1e293b;text-decoration:none;transition:background .12s;}
        #admin-search-drop a:hover{background:#f1f5f9;}
        #admin-search-drop a:first-child{border-radius:10px 10px 0 0;}
        #admin-search-drop a:last-child{border-radius:0 0 10px 10px;}

        /* 신규 문의 토스트 알림 */
        #inquiry-toast-container{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;}
        .inquiry-toast{pointer-events:auto;background:#1e293b;color:#fff;border-radius:12px;padding:14px 18px;box-shadow:0 8px 32px rgba(0,0,0,.25);display:flex;align-items:flex-start;gap:12px;min-width:280px;max-width:340px;animation:toastIn .3s ease;}
        .inquiry-toast-icon{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .inquiry-toast-body{flex:1;min-width:0;}
        .inquiry-toast-title{font-size:12px;font-weight:700;color:#a5b4fc;margin-bottom:3px;}
        .inquiry-toast-name{font-size:13px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .inquiry-toast-msg{font-size:11px;color:#94a3b8;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .inquiry-toast-close{background:none;border:none;color:#64748b;font-size:16px;cursor:pointer;line-height:1;padding:0;flex-shrink:0;}
        @keyframes toastIn{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
    </style>
</head>
<body>
<div class="admin-wrap">

    {{-- 사이드바 --}}
    <aside class="admin-side">
        <div class="admin-logo">
            <a href="{{ route('admin.dashboard') }}">
                <div class="admin-logo-icon">A</div>
                <div>
                    <div class="admin-logo-text">SupportWorks</div>
                    <div class="admin-logo-sub">Admin Panel</div>
                </div>
            </a>
        </div>

        {{-- 메뉴 검색 --}}
        <div class="admin-search-wrap">
            <div style="position:relative;">
                <svg style="position:absolute;left:9px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:rgba(255,255,255,.35);pointer-events:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input id="admin-sidebar-search" type="text" placeholder="{{ __('admin.menu_search') }}" autocomplete="off">
            </div>
            <div id="admin-search-drop"></div>
        </div>

        <nav class="admin-nav">
            <div class="admin-nav-label">{{ __('admin.section_main') }}</div>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                {{ __('admin.dashboard') }}
            </a>

            <div class="admin-nav-label">{{ __('admin.section_manage') }}</div>
            <a href="{{ route('admin.projects.index') }}" class="{{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                프로젝트 현황
            </a>
            <a href="{{ route('admin.announcements.index') }}" class="{{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                공지사항
            </a>
            <a href="{{ route('admin.ai-usage.index') }}" class="{{ request()->routeIs('admin.ai-usage.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                웍스 사용량 통계
            </a>
            <a href="{{ route('admin.company-groups.index') }}" class="{{ request()->routeIs('admin.company-groups.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                {{ __('admin.company_manage') }}
            </a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ __('admin.user_manage') }}
            </a>
            <a href="{{ route('admin.ai-prompts.index') }}" class="{{ request()->routeIs('admin.ai-prompts.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                {{ __('admin.ai_prompts') }}
            </a>
            <a href="{{ route('admin.inquiries.index') }}" class="{{ request()->routeIs('admin.inquiries.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                {{ __('admin.inquiries') }}
                @php
                    $admin = auth('admin')->user();
                    $openCnt = \App\Models\Conversation::where('type','inquiry')->where('status','open')
                        ->when(!$admin->isSuperAdmin(), function($q) use ($admin) {
                            $ids = $admin->companyGroups()->pluck('company_groups.id');
                            $q->whereIn('company_group_id', $ids);
                        })->count();
                @endphp
                <span class="nav-badge" id="inquiry-open-badge" style="{{ $openCnt > 0 ? '' : 'display:none;' }}">{{ $openCnt }}</span>
            </a>

            {{-- TODO: 유지보수 요청 관리자 메뉴 (maint_*) — 화면 작성 시 재추가 --}}

            <div class="admin-nav-label">{{ __('admin.section_account') }}</div>
            <a href="{{ route('admin.admins.index') }}" class="{{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                {{ __('admin.admin_accounts') }}
            </a>
            <a href="{{ route('admin.management.index') }}" class="{{ request()->routeIs('admin.management.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ __('admin.admin_manage') }}
            </a>

            <div class="admin-nav-label">{{ __('admin.section_system') }}</div>
            <a href="{{ route('admin.logs.index') }}" class="{{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                {{ __('admin.logs') }}
            </a>
            @php $unresolvedErrors = \App\Models\SystemErrorLog::unresolved()->count(); @endphp
            <a href="{{ route('admin.system-errors.index') }}" class="{{ request()->routeIs('admin.system-errors.*') ? 'active' : '' }}" style="position:relative">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                {{ __('admin.system_errors') }}
                @if($unresolvedErrors > 0)
                <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:9999px;min-width:18px;text-align:center">
                    {{ $unresolvedErrors > 99 ? '99+' : $unresolvedErrors }}
                </span>
                @endif
            </a>
            @if(auth('admin')->user()->isSuperAdmin())
            @php $maintenanceOn = \App\Models\SystemSetting::isMaintenanceMode(); @endphp
            <a href="{{ route('admin.system-maintenance.index') }}" class="{{ request()->routeIs('admin.system-maintenance.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                {{ __('admin.system_maintenance') }}
                @if($maintenanceOn)
                <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:9999px;">ON</span>
                @endif
            </a>
            <a href="{{ route('admin.app-versions.index') }}" class="{{ request()->routeIs('admin.app-versions.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                {{ __('admin.app_versions') }}
            </a>
            <a href="{{ route('admin.ai-settings.index') }}" class="{{ request()->routeIs('admin.ai-settings.*') ? 'active' : '' }}">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                {{ __('admin.ai_settings') }}
            </a>
            @endif
        </nav>

        <div class="admin-bottom">
            @php $admin = auth('admin')->user(); @endphp
            <div class="admin-profile">
                <div class="admin-avatar">{{ mb_substr($admin->name, 0, 1) }}</div>
                <div>
                    <div class="admin-profile-name">{{ $admin->name }}</div>
                    <div class="admin-profile-role">{{ $admin->role }}</div>
                </div>
            </div>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="admin-logout">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    {{ __('admin.logout') }}
                </button>
            </form>
        </div>
    </aside>

    {{-- 메인 콘텐츠 --}}
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar-title">@yield('title', __('admin.dashboard'))</div>
            <div class="admin-topbar-right">
                @yield('header-actions')
                <span style="font-size:12px;color:#94a3b8;">{{ now()->format('Y.m.d H:i') }}</span>
            </div>
        </header>

        <main class="admin-content">
            @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
            <div class="alert-error">{{ $errors->first() }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

{{-- 신규 문의 토스트 컨테이너 --}}
<div id="inquiry-toast-container"></div>

@yield('scripts')
@stack('scripts')

<script>
const ADMIN_STR = {
    noResults:  '{{ __("admin.search_no_results") }}',
    newInquiry: '{{ __("admin.new_inquiry_toast") }}',
    guest:      '{{ __("admin.guest") }}',
    newMessage: '{{ __("admin.new_message") }}',
    user:       '{{ __("admin.user") }}',
};
// ── 관리자 메뉴 검색 ─────────────────────────────────────────
(function() {
    const MENU = [
        { label:'{{ __("admin.dashboard") }}',        url:'{{ route("admin.dashboard") }}',          icon:'🏠' },
        { label:'{{ __("admin.company_manage") }}',   url:'{{ route("admin.company-groups.index") }}',icon:'🏢' },
        { label:'{{ __("admin.user_manage") }}',      url:'{{ route("admin.users.index") }}',         icon:'👥' },
        { label:'{{ __("admin.ai_prompts") }}',       url:'{{ route("admin.ai-prompts.index") }}',    icon:'🤖' },
        { label:'{{ __("admin.inquiries") }}',        url:'{{ route("admin.inquiries.index") }}',     icon:'💬' },
        { label:'{{ __("admin.admin_accounts") }}',   url:'{{ route("admin.admins.index") }}',        icon:'🛡️' },
        { label:'{{ __("admin.admin_manage") }}',     url:'{{ route("admin.management.index") }}',    icon:'👥' },
        { label:'{{ __("admin.logs") }}',             url:'{{ route("admin.logs.index") }}',          icon:'📋' },
        { label:'{{ __("admin.system_errors") }}',    url:'{{ route("admin.system-errors.index") }}', icon:'⚠️' },
        @if(auth('admin')->user()->isSuperAdmin())
        { label:'{{ __("admin.app_versions") }}',     url:'{{ route("admin.app-versions.index") }}',  icon:'📦' },
        { label:'{{ __("admin.ai_settings") }}',      url:'{{ route("admin.ai-settings.index") }}',   icon:'⚙️' },
        @endif
    ];

    const input = document.getElementById('admin-sidebar-search');
    const drop  = document.getElementById('admin-search-drop');
    if (!input || !drop) return;

    let activeIdx = -1;

    function renderDrop(q) {
        activeIdx = -1;
        if (!q) { drop.style.display = 'none'; return; }
        const matched = MENU.filter(m => m.label.toLowerCase().includes(q));
        if (!matched.length) {
            drop.innerHTML = `<div style="padding:12px 14px;font-size:12px;color:#94a3b8;">${ADMIN_STR.noResults}</div>`;
        } else {
            drop.innerHTML = matched.map((m, i) =>
                `<a href="${m.url}" data-idx="${i}">`+
                `<span style="font-size:14px;width:20px;text-align:center;">${m.icon}</span>`+
                `<span>${m.label}</span></a>`
            ).join('');
        }
        drop.style.display = 'block';
    }

    input.addEventListener('input', function() {
        renderDrop(this.value.trim().toLowerCase());
    });

    input.addEventListener('keydown', function(e) {
        const items = drop.querySelectorAll('a');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = Math.min(activeIdx + 1, items.length - 1);
            items.forEach((a, i) => a.style.background = i === activeIdx ? '#f1f5f9' : '');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = Math.max(activeIdx - 1, 0);
            items.forEach((a, i) => a.style.background = i === activeIdx ? '#f1f5f9' : '');
        } else if (e.key === 'Enter' && activeIdx >= 0 && items[activeIdx]) {
            e.preventDefault();
            window.location.href = items[activeIdx].href;
        } else if (e.key === 'Escape') {
            this.value = ''; drop.style.display = 'none'; activeIdx = -1;
        }
    });

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !drop.contains(e.target)) {
            drop.style.display = 'none';
        }
    });
})();

// ── 관리자 신규 문의 실시간 알림 ──────────────────────────────
(function() {
    const ADMIN_ID   = {{ auth('admin')->user()->id }};
    const INQUIRY_URL = '{{ route('admin.inquiries.index') }}';

    function setupAdminEcho() {
        window.Echo.private('admin.' + ADMIN_ID)
        .listen('.NewInquiry', function(data) {
                showInquiryToast({
                    title: ADMIN_STR.newInquiry,
                    name:  data.customer_name || ADMIN_STR.guest,
                    msg:   data.subject || data.message || '',
                    url:   INQUIRY_URL,
                });
                incrementInquiryBadge();
            })
            .listen('.MessageSent', function(data) {
                window.dispatchEvent(new CustomEvent('adminMessageReceived', { detail: data }));
                // 현재 열려 있는 대화창이면 토스트 생략
                if (window.OPEN_CONV_ID && window.OPEN_CONV_ID === data.room_id) return;
                // 관리자가 보낸 메시지는 토스트/뱃지 생략
                if (data.is_admin) return;
                showInquiryToast({
                    title: ADMIN_STR.newMessage,
                    name:  data.sender_name || ADMIN_STR.user,
                    msg:   data.body || '',
                    url:   INQUIRY_URL + '/' + data.room_id,
                });
                incrementInquiryBadge();
            });
    }

    if (window.Echo) { setupAdminEcho(); }
    else { window.addEventListener('echoReady', setupAdminEcho, { once: true }); }

    function showInquiryToast({ title, name, msg, url }) {
        const container = document.getElementById('inquiry-toast-container');
        const toast = document.createElement('div');
        toast.className = 'inquiry-toast';
        toast.innerHTML = `
            <div class="inquiry-toast-icon">
                <svg width="16" height="16" fill="none" stroke="#fff" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
            </div>
            <div class="inquiry-toast-body" style="cursor:pointer;">
                <div class="inquiry-toast-title">${escHtml(title)}</div>
                <div class="inquiry-toast-name">${escHtml(name)}</div>
                <div class="inquiry-toast-msg">${escHtml(msg)}</div>
            </div>
            <button class="inquiry-toast-close" onclick="this.closest('.inquiry-toast').remove()">×</button>
        `;

        toast.querySelector('.inquiry-toast-body').addEventListener('click', function() {
            window.location.href = url;
        });

        container.appendChild(toast);
        setTimeout(() => toast.remove(), 6000);
    }

    function incrementInquiryBadge() {
        const badge = document.getElementById('inquiry-open-badge');
        if (!badge) return;
        const current = parseInt(badge.textContent || '0', 10);
        badge.textContent = current + 1;
        badge.style.display = '';
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
})();
</script>
@include('partials.custom-dialog')
</body>
</html>
