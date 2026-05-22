<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" data-accent="blue">
    <head>
        {{-- Phase 1 — FOUC 방지: 사용자 지정 hex 우선, 없으면 프리셋 액센트 복원 --}}
        <script>(function(){try{var h=localStorage.getItem('wsAccentHex');if(h&&/^#[0-9a-f]{6}$/i.test(h)){document.documentElement.setAttribute('data-accent','custom');document.documentElement.style.setProperty('--color-theme-active',h);return;}var a=localStorage.getItem('wsAccent');if(a&&/^[a-z]+$/.test(a))document.documentElement.setAttribute('data-accent',a);}catch(e){}})();</script>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>window.broadcastAuthPath = '{{ request()->getBasePath() }}/broadcasting/auth';</script>
        <title>{{ config('app.name', 'SupportWorks') }} - @yield('title', __('app.nav_home'))</title>
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
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
            /* Phase 1 — --t* 변수는 resources/css/app.css 에서 디자인 시스템 토큰으로 매핑됨.
               <html data-accent="..."> 값에 따라 자동으로 톤 변경. 기본값은 tokens.css 의 blue. */
            .sidebar-item {
                display: flex;
                align-items: center;
                gap: var(--space-3, 12px);
                padding: var(--space-2, 8px) var(--space-3, 12px);
                border-radius: var(--radius-md, 8px);
                font-size: var(--font-size-body-13, 13px);
                font-weight: var(--font-weight-medium, 500);
                color: var(--color-text-secondary);
                cursor: pointer;
                transition: background-color var(--transition-fast, 150ms ease), color var(--transition-fast, 150ms ease);
                text-decoration: none;
                width: 100%;
            }
            .sidebar-item:hover {
                background: var(--t50);
                color: var(--t700);
            }
            .sidebar-item.active {
                background: linear-gradient(135deg, var(--t100), var(--t200));
                color: var(--tText);
                font-weight: var(--font-weight-bold, 700);
            }
            .sidebar-item.active svg { color: var(--tText); }
            .sidebar-item svg {
                flex-shrink: 0;
                color: var(--color-text-tertiary);
                transition: color var(--transition-fast, 150ms ease);
            }
            .sidebar-item:hover svg { color: var(--t700); }

            .project-dot { width:8px; height:8px; border-radius:3px; flex-shrink:0; }

            .section-label {
                font-size: 10.5px;
                font-weight: var(--font-weight-bold, 700);
                color: var(--color-text-tertiary);
                letter-spacing: 0.07em;
                text-transform: uppercase;
                padding: 0 var(--space-3, 12px);
                margin-bottom: var(--space-0_5, 2px);
            }
            .sidebar-divider {
                height: 1px;
                background: linear-gradient(to right, transparent, var(--t200), transparent);
                margin: var(--space-2, 8px) 0;
            }
            #sidebar-search {
                background: var(--t50);
                border: 1px solid var(--t200);
                border-radius: var(--radius-md, 8px);
                padding: var(--space-2, 8px) var(--space-3, 12px) var(--space-2, 8px) 34px;
                font-size: var(--font-size-body-13, 13px);
                color: var(--color-text-primary);
                width: 100%;
                outline: none;
                transition: background-color var(--transition-fast, 150ms ease), border-color var(--transition-fast, 150ms ease);
            }
            #sidebar-search:focus {
                background: var(--t100);
                border-color: var(--t300);
            }
            #sidebar-search::placeholder { color: var(--color-text-placeholder); }

            .project-item {
                display: flex;
                align-items: center;
                gap: 9px;
                padding: var(--space-2, 8px) var(--space-3, 12px);
                border-radius: var(--radius-md, 8px);
                font-size: var(--font-size-body-13, 13px);
                color: var(--color-text-secondary);
                text-decoration: none;
                transition: background-color var(--transition-fast, 150ms ease), color var(--transition-fast, 150ms ease);
                width: 100%;
            }
            .project-item:hover { background: var(--t50); color: var(--t700); }
            .project-item.active { background: var(--t100); color: var(--tText); font-weight: var(--font-weight-bold, 700); }

            /* ── 사이드바 접힘 ── */
            #global-sidebar { transition: width .22s ease, min-width .22s ease; }
            #global-sidebar.gsb-collapsed { width: 52px !important; min-width: 52px !important; }
            #global-sidebar.gsb-collapsed #gsb-logo-wrap { display: none !important; }
            #global-sidebar.gsb-collapsed #gsb-search-area { display: none !important; }
            #global-sidebar.gsb-collapsed .gsb-hide { display: none !important; }
            #global-sidebar.gsb-collapsed .sidebar-item,
            #global-sidebar.gsb-collapsed .project-item { justify-content: center; padding: 8px 0; gap: 0; }
            #global-sidebar.gsb-collapsed .sidebar-item > svg,
            #global-sidebar.gsb-collapsed .project-item > svg { flex-shrink: 0; }
            #global-sidebar.gsb-collapsed #gsb-profile-text { display: none !important; }
            #global-sidebar.gsb-collapsed #gsb-profile-actions { display: none !important; }
            #global-sidebar.gsb-collapsed #gsb-profile-area { justify-content: center; }
            #global-sidebar #gsb-icon { transition: transform .22s; }
            #global-sidebar.gsb-collapsed #gsb-icon { transform: rotate(180deg); }
            #global-sidebar.gsb-collapsed .section-label { display: none !important; }

            /* ── 다운로드 프로그래스 바 ── */
            .sw-dlp {
                position: fixed;
                z-index: 999999;
                pointer-events: none;
                min-width: 60px;
            }
            .sw-dlp-track {
                height: 4px;
                background: var(--t100);
                border-radius: 2px;
                overflow: hidden;
            }
            .sw-dlp-fill {
                height: 100%;
                width: 0%;
                background: linear-gradient(90deg, var(--t600), var(--t400));
                border-radius: 2px;
                transition: width .25s ease;
            }
            .sw-dlp-fill.sw-dlp-indet {
                width: 38%;
                animation: sw-dlp-slide 1.1s infinite ease-in-out;
                transition: none;
            }
            @keyframes sw-dlp-slide {
                0%   { transform: translateX(-110%); }
                100% { transform: translateX(370%); }
            }
            .sw-dlp-pct {
                display: block;
                font-size: 10px;
                color: var(--t600);
                font-weight: var(--font-weight-bold, 700);
                text-align: right;
                margin-top: 2px;
                line-height: 1;
                white-space: nowrap;
                font-family: inherit;
            }
        </style>
    </head>
    <body class="font-sans antialiased" style="background:var(--tBg);">

        @if(session('impersonating') && !request()->boolean('embed'))
        <div id="impersonate-bar" style="position:sticky;top:0;z-index:9999;display:flex;align-items:center;gap:12px;background:linear-gradient(90deg,#b45309,#92400e);color:#fff;padding:8px 18px;font-size:12.5px;font-weight:600;box-shadow:0 2px 8px rgba(0,0,0,.25);">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            <span style="opacity:.8;">{{ __('app.admin_mode') }}</span>
            <span style="background:rgba(255,255,255,.2);border-radius:5px;padding:2px 9px;font-size:12px;">
                {{ session('impersonating_name') }}
            </span>
            <span style="opacity:.7;font-size:11.5px;font-weight:400;">{{ session('impersonating_email') }}</span>
            <span style="flex:1;"></span>
            <span style="opacity:.75;font-size:11px;font-weight:400;">{{ __('app.admin_mode_notice') }}</span>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" style="display:flex;align-items:center;gap:4px;background:rgba(255,255,255,.12);border:1.5px solid rgba(255,255,255,.3);color:#fff;border-radius:7px;padding:4px 13px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;margin-right:6px;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    {{ __('app.nav_logout') }}
                </button>
            </form>
            <button onclick="window.close()" style="display:flex;align-items:center;gap:4px;background:rgba(255,255,255,.18);border:1.5px solid rgba(255,255,255,.35);color:#fff;border-radius:7px;padding:4px 13px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ __('app.admin_mode_close') }}
            </button>
        </div>
        @endif

        @php
            $projectColors = ['#a394f9','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6'];
            $myProjects = auth()->user()->isAdmin()
                ? \App\Models\Project::orderBy('name')->get()
                : auth()->user()->projects()->orderBy('name')->get();

            // 안읽은 메시지 수 & 구독할 대화 ID 목록
            $myConvs = \App\Models\Conversation::whereHas('participants', fn($q) => $q->where('user_id', auth()->id()))
                ->with(['participants' => fn($q) => $q->where('user_id', auth()->id()), 'messages'])
                ->get();
            $unreadMessages  = $myConvs->whereNull('type')->sum(fn($c) => $c->unreadCount(auth()->id()));
            $unreadInquiries = $myConvs->where('type', 'inquiry')->sum(fn($c) => $c->unreadCount(auth()->id()));

            // Works Builder 읽지 않은 알림 수
            $unreadWbNotifications = \Illuminate\Support\Facades\Schema::hasTable('wb_notifications')
                ? \App\Models\WorksBuilder\Notification::where('recipient_id', auth()->id())->unread()->count()
                : 0;
            $myConvIds         = $myConvs->pluck('id');
            $myInquiryConvIds  = $myConvs->where('type', 'inquiry')->pluck('id');

            // 처리 중인 문의 수
            $openInquiries = \App\Models\Conversation::where('type', 'inquiry')
                ->whereIn('status', ['open', 'active'])
                ->whereHas('participants', fn($q) => $q->where('user_id', auth()->id()))
                ->count();

            // 고정된 메모 (전역 floating)
            $pinnedMemos = \App\Models\Memo::where('user_id', auth()->id())
                ->where('is_pinned', true)
                ->orderByDesc('updated_at')
                ->get();

            // 공유 받아 고정한 메모
            $pinnedSharedMemos = \App\Models\MemoShare::where('shared_to', auth()->id())
                ->where('is_pinned', true)
                ->with(['memo', 'sharedByUser'])
                ->latest()
                ->get();

            // 현재 활성 프로젝트 ID 감지 (중첩 라우트 포함)
            $currentProjectId = null;
            if ($rp = request()->route('project')) {
                $currentProjectId = $rp instanceof \App\Models\Project ? $rp->id : (int)$rp;
            } elseif ($rs = request()->route('schedule')) {
                $currentProjectId = $rs instanceof \App\Models\Schedule ? $rs->project_id : null;
            } elseif ($rq = request()->route('question')) {
                $currentProjectId = $rq instanceof \App\Models\Question ? $rq->project_id : null;
            }
        @endphp

        @php $embedMode = request()->boolean('embed'); @endphp
        <div class="min-h-screen flex">

            {{-- ===== 사이드바 (embed=1 일 때 숨김) ===== --}}
            @unless($embedMode)
            <aside id="global-sidebar" style="width:var(--layout-sidebar-width-open,240px);min-width:var(--layout-sidebar-width-open,240px);background:var(--color-bg-base);border-right:1px solid var(--color-border-default);display:flex;flex-direction:column;height:100vh;position:sticky;top:0;box-shadow:var(--shadow-sm);transition:width .22s ease,min-width .22s ease;">

                {{-- 워크스페이스 헤더 --}}
                <div style="padding:var(--space-3,12px) var(--space-3,12px);border-bottom:1px solid var(--color-border-default);flex-shrink:0;display:flex;align-items:center;gap:var(--space-2,8px);">
                    <a href="{{ route('dashboard') }}" id="gsb-logo-wrap" style="display:flex;align-items:center;gap:var(--space-3,12px);text-decoration:none;overflow:hidden;flex:1;min-width:0;">
                        <img id="sw-logo" src="{{ asset('support_works_logo.png') }}" alt="SupportWorks" style="width:34px;height:34px;border-radius:var(--radius-md,8px);flex-shrink:0;object-fit:contain;">
                        <div style="overflow:hidden;">
                            <div style="font-size:14px;font-weight:var(--font-weight-bold,700);color:var(--color-text-primary);line-height:1.2;letter-spacing:-.01em;white-space:nowrap;">SupportWorks</div>
                            <div style="font-size:11px;color:var(--color-text-tertiary);line-height:1.2;white-space:nowrap;">{{ auth()->user()->company ?? __('app.nav_workspace') }}</div>
                        </div>
                    </a>
                    <button onclick="toggleGlobalSidebar()" title="{{ __('app.toggle_sidebar') }}" style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;min-width:30px;border-radius:var(--radius-md,8px);border:1.5px solid var(--t200);background:var(--t50);cursor:pointer;color:var(--t600);padding:0;flex-shrink:0;transition:background-color var(--transition-fast,150ms ease);">
                        <svg id="gsb-icon" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="transition:transform .22s;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                </div>

                {{-- 검색 --}}
                <div id="gsb-search-area" style="padding:var(--space-3,12px);flex-shrink:0;position:relative;">
                    <div style="position:relative;">
                        <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--color-text-tertiary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input id="sidebar-search" type="text" placeholder="{{ __('app.nav_search') }}" autocomplete="off">
                    </div>
                    <div id="sidebar-search-drop" style="display:none;position:absolute;top:100%;left:8px;right:8px;background:var(--color-bg-base);border:1px solid var(--color-border-default);border-radius:var(--radius-lg,12px);box-shadow:var(--shadow-lg);z-index:9999;max-height:320px;overflow-y:auto;margin-top:2px;"></div>
                </div>

                {{-- 스크롤 영역 --}}
                <div style="flex:1;overflow-y:auto;overflow-x:hidden;padding:4px 10px 10px;min-height:0;">

                    {{-- 메인 네비게이션 --}}
                    <div style="margin-bottom:4px;overflow:hidden;">
                        @if(auth()->user()->hasFeature('dashboard'))
                        <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_home') }}</span>
                        </a>
                        @endif {{-- dashboard feature --}}
                        @if(auth()->user()->hasFeature('tasks') || auth()->user()->hasFeature('action_items'))
                        @php
                            $myWorkOverdue = 0;
                            try {
                                $myWorkOverdue = \App\Models\Task::where('user_id', auth()->id())
                                    ->whereIn('status', ['todo','in_progress'])
                                    ->where('due_date', '<', today())
                                    ->count()
                                    + \App\Models\ActionItem::where(fn($q) => $q->where('user_id', auth()->id())->orWhere('assigned_to', auth()->id()))
                                    ->where('is_completed', false)
                                    ->whereNotNull('due_date')
                                    ->where('due_date', '<', today())
                                    ->count();
                            } catch (\Throwable $e) {}
                        @endphp
                        <a href="{{ route('my-work.index') }}" class="sidebar-item {{ request()->routeIs('my-work.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <span class="gsb-hide">{{ __('app.nav_my_work') }}</span>
                            @if($myWorkOverdue > 0)
                            <span class="gsb-hide" style="margin-left:auto;min-width:18px;height:18px;padding:0 5px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;border-radius:9px;display:flex;align-items:center;justify-content:center;">{{ $myWorkOverdue > 99 ? '99+' : $myWorkOverdue }}</span>
                            @endif
                        </a>
                        @endif {{-- tasks/action_items feature --}}
                        {{-- 메모 사이드바 메뉴 비표시 --}}
                        @if(auth()->user()->hasFeature('calendar'))
                        <a href="{{ route('calendar') }}" class="sidebar-item {{ request()->routeIs('calendar') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_calendar') }}</span>
                        </a>
                        @endif
                        @if(auth()->user()->hasFeature('messages'))
                        <a href="{{ route('messages.index') }}" class="sidebar-item {{ request()->routeIs('messages.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_messages') }}</span>
                            <span id="sidebar-msg-badge" class="gsb-hide" style="margin-left:auto;background:#ef4444;color:#fff;font-size:10px;font-weight:700;border-radius:10px;padding:1px 6px;flex-shrink:0;display:{{ $unreadMessages > 0 ? 'inline-block' : 'none' }};">{{ $unreadMessages ?: '' }}</span>
                        </a>
                        @endif
                        @if(auth()->user()->hasFeature('team'))
                        <a href="{{ route('team.index') }}" class="sidebar-item {{ request()->routeIs('team.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_team') }}</span>
                        </a>
                        @endif {{-- team feature --}}
                        @if(auth()->user()->hasFeature('meeting_minutes'))
                        <a href="{{ route('meeting-minutes.index') }}" class="sidebar-item {{ request()->routeIs('meeting-minutes.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="gsb-hide">{{ __('app.nav_meeting_minutes') }}</span>
                        </a>
                        @endif {{-- meeting_minutes feature --}}
                        @if(auth()->user()->hasFeature('weekly_reports'))
                        <a href="{{ route('my-weekly.index') }}" class="sidebar-item {{ request()->routeIs('my-weekly.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="gsb-hide">{{ __('app.nav_weekly') }}</span>
                        </a>
                        @endif {{-- weekly_reports feature --}}
                        <a href="{{ route('plan-do-acts.index') }}" class="sidebar-item {{ request()->routeIs('plan-do-acts.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span class="gsb-hide">{{ __('plan-do-acts.nav') }}</span>
                        </a>
                        @if(auth()->user()->hasCompany())
                        <a href="{{ route('shared-folder.index') }}" class="sidebar-item {{ request()->routeIs('shared-folder.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-7l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <span class="gsb-hide">{{ __('shared-folder.nav') }}</span>
                        </a>
                        @endif {{-- shared folder --}}
                        @if(auth()->user()->hasFeature('teams'))
                        <a href="{{ route('teams.index') }}" class="sidebar-item {{ request()->routeIs('teams.*') ? 'active' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M14 5.5C14 6.88 12.88 8 11.5 8S9 6.88 9 5.5 10.12 3 11.5 3 14 4.12 14 5.5z" fill="{{ request()->routeIs('teams.*') ? '#6d5ce7' : '#9e97c0' }}"/>
                                <path d="M19 7.5C19 8.33 18.33 9 17.5 9S16 8.33 16 7.5 16.67 6 17.5 6 19 6.67 19 7.5z" fill="{{ request()->routeIs('teams.*') ? '#9b8afb' : '#c4b5fd' }}"/>
                                <path d="M16 10h3a1 1 0 011 1v4a3 3 0 01-3 3h-.5A5.5 5.5 0 0111 13.5V10h5z" fill="{{ request()->routeIs('teams.*') ? '#7c6ef5' : '#b8b0d8' }}"/>
                                <path d="M5 10h9v3.5A4.5 4.5 0 019.5 18h-1A3.5 3.5 0 015 14.5V10z" fill="{{ request()->routeIs('teams.*') ? '#6d5ce7' : '#9e97c0' }}"/>
                            </svg>
                            <span class="gsb-hide">Teams</span>
                            @php $teamsVerified = \App\Models\TeamsSetting::current()->is_verified; @endphp
                            @if($teamsVerified)
                            <span class="gsb-hide" style="margin-left:auto;width:6px;height:6px;border-radius:50%;background:#16a34a;flex-shrink:0;display:block;"></span>
                            @endif
                        </a>
                        @endif {{-- teams feature --}}

                    <div class="sidebar-divider"></div>

                    {{-- 내 프로젝트 섹션 --}}
                    @if(auth()->user()->hasFeature('my_projects'))
                    <div style="margin-bottom:4px;">
                        <div class="gsb-hide" style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px 4px;">
                            <button onclick="toggleSection('proj-list')" style="display:flex;align-items:center;gap:4px;background:none;border:none;cursor:pointer;padding:0;flex:1;text-align:left;">
                                <span class="section-label" style="pointer-events:none;">{{ __('app.nav_my_projects') }}</span>
                                <svg id="chevron-proj-list" width="10" height="10" fill="none" stroke="#b8b0d8" viewBox="0 0 24 24" style="flex-shrink:0;transition:transform .2s;pointer-events:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <button onclick="openNewProjectModal()" style="display:flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:4px;color:#a1a1aa;background:transparent;border:none;cursor:pointer;transition:background 0.12s,color 0.12s;" title="{{ __('app.nav_new_project') }}" onmouseover="this.style.background='#f4f4f5';this.style.color='#3f3f46'" onmouseout="this.style.background='transparent';this.style.color='#a1a1aa'">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>

                        <div id="proj-list" style="overflow:hidden;max-height:600px;transition:max-height .22s ease;">
                        @forelse($myProjects as $index => $proj)
                            @php $color = $projectColors[$index % count($projectColors)]; @endphp
                            <a href="{{ route('projects.show', $proj) }}" class="project-item {{ $currentProjectId == $proj->id && request()->routeIs('projects.show') ? 'active' : '' }}" data-proj-id="{{ $proj->id }}">
                                <span class="project-dot" style="background:{{ $color }};"></span>
                                <span class="gsb-hide" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj->name }}</span>
                                @if($proj->status === 'active')
                                    <span class="gsb-hide" style="font-size:10px;padding:1px 5px;border-radius:3px;background:#dcfce7;color:#16a34a;flex-shrink:0;">{{ __('app.status_active') }}</span>
                                @elseif($proj->status === 'on_hold')
                                    <span class="gsb-hide" style="font-size:10px;padding:1px 5px;border-radius:3px;background:#fef9c3;color:#ca8a04;flex-shrink:0;">{{ __('app.status_on_hold') }}</span>
                                @endif
                            </a>
                        @empty
                            <div class="gsb-hide" style="padding:8px 10px;font-size:12px;color:#a1a1aa;">{{ __('app.nav_no_projects') }}</div>
                        @endforelse

                        @if($myProjects->count() > 0)
                        <a class="gsb-hide" href="{{ route('projects.index') }}" style="display:flex;align-items:center;gap:8px;padding:5px 10px;font-size:12px;color:#a1a1aa;text-decoration:none;border-radius:6px;transition:color 0.12s;" onmouseover="this.style.color='#3f3f46'" onmouseout="this.style.color='#a1a1aa'">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            {{ __('app.nav_view_all') }}
                        </a>
                        @endif
                        </div>
                    </div>
                    @endif {{-- my_projects feature --}}

                    <div class="sidebar-divider"></div>

                    {{-- SR 관리 --}}
                    @php
                        $__u = auth()->user();
                        $__srAll = $__u && ($__u->isAdmin() || (bool) ($__u->is_sr_agent ?? false));
                        $__myCgId = $__u?->company_group_id;
                        $__srCompanies = \DB::table('company_groups as cg')
                            ->join('maint_requests as mr', 'mr.company_group_id', '=', 'cg.id')
                            ->select('cg.id', 'cg.name', \DB::raw('COUNT(*) as sr_count'))
                            ->groupBy('cg.id', 'cg.name')
                            ->orderBy('cg.name')
                            ->get();
                        if (!$__srAll) {
                            $__srCompanies = $__myCgId
                                ? $__srCompanies->where('id', $__myCgId)->values()
                                : collect();
                        }
                        $__currentCgId = (int) request('company_group_id');
                    @endphp
                    <a href="{{ route('maint-requests.index') }}"
                       class="project-item {{ request()->routeIs('maint-requests.*') && !$__currentCgId ? 'active' : '' }}"
                       style="margin-top:4px;">
                        <svg width="13" height="13" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" style="flex-shrink:0;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6M9 16h4"/>
                        </svg>
                        <span class="gsb-hide" style="flex:1;">SR 관리</span>
                    </a>
                    @if($__srCompanies->count() > 0)
                        <div class="gsb-hide" style="margin: 2px 0 4px 16px;">
                            @foreach($__srCompanies as $__cg)
                                <a href="{{ route('maint-requests.index', ['company_group_id' => $__cg->id, 'bucket' => 'all']) }}"
                                   class="project-item {{ $__currentCgId === (int)$__cg->id ? 'active' : '' }}"
                                   style="padding: 4px 10px; font-size: 12px; gap:8px;">
                                    <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;opacity:.6;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $__cg->name }}</span>
                                    <span style="opacity:.5;font-size:11px;">{{ $__cg->sr_count }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="sidebar-divider"></div>

                    {{-- Works Builder 섹션 — 관리자 전용 --}}
                    @if(auth()->user()->isAdmin() && auth()->user()->hasFeature('works_builder'))
                    <div style="margin-bottom:4px;">
                        <div class="gsb-hide" style="padding:6px 10px 4px;">
                            <span class="section-label">{{ __('app.nav_wb_section') }}</span>
                        </div>
                        <a href="{{ route('wb.tasks.create') }}" class="sidebar-item {{ request()->routeIs('wb.tasks.create') || request()->routeIs('wb.tasks.options.*') || request()->routeIs('wb.tasks.prompts.*') || request()->routeIs('wb.tasks.result-confirm.*') || request()->routeIs('wb.tasks.review.*') || request()->routeIs('wb.tasks.ng-input.*') ? 'active' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_wb_new') }}</span>
                        </a>
                        <a href="{{ route('wb.tasks.index') }}" class="sidebar-item {{ request()->routeIs('wb.tasks.index') || request()->routeIs('wb.tasks.show') ? 'active' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_wb_in_progress') }}</span>
                        </a>
                        <a href="{{ route('wb.tasks.completed') }}" class="sidebar-item {{ request()->routeIs('wb.tasks.completed') ? 'active' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_wb_completed') }}</span>
                        </a>
                        <a href="{{ route('wb.checklists.entry') }}" class="sidebar-item {{ request()->routeIs('wb.checklists.*') ? 'active' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_wb_checklists') }}</span>
                        </a>
                        <a href="{{ route('wb.ai-call-logs.index') }}" class="sidebar-item {{ request()->routeIs('wb.ai-call-logs.*') ? 'active' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            <span class="gsb-hide">웍스 호출 이력</span>
                        </a>
                        <a href="{{ route('wb.notifications.index') }}" class="sidebar-item {{ request()->routeIs('wb.notifications.*') ? 'active' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_wb_notifications') }}</span>
                            <span id="sidebar-wb-badge" class="gsb-hide" style="margin-left:auto;background:#ef4444;color:#fff;font-size:10px;font-weight:700;border-radius:10px;padding:1px 6px;flex-shrink:0;display:{{ $unreadWbNotifications > 0 ? 'inline-block' : 'none' }};">{{ $unreadWbNotifications ?: '' }}</span>
                        </a>
                    </div>
                    @endif {{-- works_builder feature --}}

                    <div class="sidebar-divider"></div>

                    {{-- 웍스 도구 섹션 --}}
                    <div style="margin-bottom:4px;">
                        <div class="gsb-hide" style="padding:6px 10px 4px;">
                            <span class="section-label">{{ __('app.nav_works_tools') }}</span>
                        </div>
                        {{-- 웍스 채팅 메뉴 숨김 처리
                        @if(auth()->user()->hasFeature('ai_chat'))
                        <a href="{{ route('ai.index') }}" class="sidebar-item {{ request()->routeIs('ai.index') || (request()->routeIs('ai.*') && !request()->routeIs('ai-agent.*')) ? 'active' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_works_chat') }}</span>
                        </a>
                        @endif
                        --}}
                        @if(auth()->user()->hasFeature('prompt_agent'))
                        <a href="{{ route('works-prompt.index') }}" class="sidebar-item {{ request()->routeIs('works-prompt.*') ? 'active' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_prompt_agent') }}</span>
                        </a>
                        @endif {{-- prompt_agent feature --}}
                        {{-- 웍스 개발 Agent 메뉴 숨김 처리
                        @if(auth()->user()->hasFeature('ai_agent'))
                        <a href="{{ route('ai-agent.dashboard', ['force_home' => 1]) }}" class="sidebar-item {{ request()->routeIs('ai-agent.*') ? 'active' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_works_dev_agent') }}</span>
                        </a>
                        @endif
                        --}}
                    </div>

                    <div class="sidebar-divider"></div>

                    {{-- 커뮤니티 섹션 --}}
                    <div style="margin-bottom:4px;">
                        <div class="gsb-hide" style="padding:6px 10px 4px;">
                            <span class="section-label">{{ __('app.nav_community') }}</span>
                        </div>
                        @if(auth()->user()->hasFeature('community'))
                        <a href="{{ route('community.index') }}" class="sidebar-item {{ request()->routeIs('community.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_community') }}</span>
                        </a>
                        @endif {{-- community feature --}}
                        @if(auth()->user()->hasFeature('inquiry'))
                        <a href="{{ route('inquiry.index') }}" class="sidebar-item {{ request()->routeIs('inquiry.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_inquiry') }}</span>
                            <span id="sidebar-inquiry-badge" class="gsb-hide" style="margin-left:auto;background:#ef4444;color:#fff;font-size:10px;font-weight:700;border-radius:10px;padding:1px 6px;flex-shrink:0;display:{{ $unreadInquiries > 0 ? 'inline-block' : 'none' }};">{{ $unreadInquiries ?: '' }}</span>
                        </a>
                        @endif {{-- inquiry feature --}}
                    </div>

                    <div class="sidebar-divider"></div>

                    {{-- 관리자 섹션 --}}
                    @if(auth()->user()->isAdmin())
                    @php $adminUnresolvedErrors = \App\Models\SystemErrorLog::unresolved()->count(); @endphp
                    <div style="margin-bottom:4px;">
                        <div class="gsb-hide" style="padding:6px 10px 4px;">
                            <span class="section-label">{{ __('app.nav_admin') }}</span>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span class="gsb-hide">{{ __('app.nav_user_mgmt') }}</span>
                        </a>
                        <a href="{{ route('admin.company-groups.index') }}" class="sidebar-item {{ request()->routeIs('admin.company-groups.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span class="gsb-hide">{{ __('app.nav_company_mgmt') }}</span>
                        </a>
                        <a href="{{ route('admin.system-errors.index') }}" class="sidebar-item {{ request()->routeIs('admin.system-errors.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span class="gsb-hide">{{ __('app.nav_system_errors') }}</span>
                            @if($adminUnresolvedErrors > 0)
                            <span class="gsb-hide" style="margin-left:auto;background:#ef4444;color:#fff;font-size:10px;font-weight:700;border-radius:10px;padding:1px 6px;flex-shrink:0;">{{ $adminUnresolvedErrors > 99 ? '99+' : $adminUnresolvedErrors }}</span>
                            @endif
                        </a>
                    </div>
                    <div class="sidebar-divider"></div>
                    @endif

                </div>
                {{-- /스크롤 영역 --}}
                </div>

                {{-- 하단 프로필 영역 --}}
                <div style="padding:var(--space-2,8px) var(--space-3,12px);border-top:1px solid var(--color-border-default);flex-shrink:0;background:var(--color-bg-base);">
                    <div id="gsb-profile-area" style="display:flex;align-items:center;gap:var(--space-3,12px);">
                        <div id="sw-avatar" style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--t300),var(--t500));display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:var(--font-weight-bold,700);color:var(--color-text-inverse);flex-shrink:0;box-shadow:var(--shadow-sm);">
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div id="gsb-profile-text" style="flex:1;min-width:0;">
                            <div style="font-size:var(--font-size-body-13,13px);font-weight:var(--font-weight-bold,700);color:var(--color-text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()->name }}</div>
                            <div style="font-size:11px;color:var(--color-text-tertiary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()->email }}</div>
                        </div>
                        <div id="gsb-profile-actions" style="display:flex;gap:var(--space-1,4px);flex-shrink:0;">
                            <a href="{{ route('profile.edit') }}" style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:var(--radius-md,8px);color:var(--color-text-tertiary);text-decoration:none;transition:background-color var(--transition-fast,150ms ease),color var(--transition-fast,150ms ease);" title="{{ __('app.nav_profile') }}" onmouseover="this.style.background='var(--color-bg-hover)';this.style.color='var(--color-text-primary)'" onmouseout="this.style.background='transparent';this.style.color='var(--color-text-tertiary)'">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:var(--radius-md,8px);color:var(--color-text-tertiary);background:transparent;border:none;cursor:pointer;transition:background-color var(--transition-fast,150ms ease),color var(--transition-fast,150ms ease);" title="{{ __('app.nav_logout') }}" onmouseover="this.style.background='var(--color-bg-danger-subtle)';this.style.color='var(--color-text-danger)'" onmouseout="this.style.background='transparent';this.style.color='var(--color-text-tertiary)'">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>
            @endunless

            {{-- ===== 메인 영역 ===== --}}
            <div class="flex-1 flex flex-col overflow-hidden">

                {{-- 상단 헤더 (embed=1 일 때 숨김) --}}
                @unless($embedMode)
                <header style="background:var(--color-bg-base);border-bottom:1px solid var(--color-border-default);padding:0 var(--space-6,24px);height:var(--layout-header-height,52px);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;box-shadow:var(--shadow-sm);">
                    <div style="display:flex;align-items:center;gap:var(--space-2,8px);">
                        <h1 style="font-size:15px;font-weight:var(--font-weight-bold,700);color:var(--color-text-primary);">@yield('title', __('app.nav_home'))</h1>
                        @yield('header-breadcrumb')
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">

                        {{-- 휴대폰 미등록 안내 (등록되어 있으면 숨김) --}}
                        @if(empty(auth()->user()->phone))
                        <div id="phone-notice-wrap" style="position:relative;">
                            <a id="phone-notice-btn" href="{{ route('profile.edit') }}#phone"
                               onclick="event.preventDefault(); togglePhoneNotice();"
                               title="{{ __('app.phone_register_prompt') }}"
                               style="position:relative;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:#fff7ed;color:#ea580c;text-decoration:none;transition:background .12s;"
                               onmouseover="this.style.background='#ffedd5'" onmouseout="this.style.background='#fff7ed'">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span style="position:absolute;top:-2px;right:-2px;width:9px;height:9px;border-radius:50%;background:#ef4444;border:2px solid #fff;"></span>
                            </a>
                            <div id="phone-notice-popover" style="display:none;position:absolute;top:42px;right:0;background:#fff;border:1px solid #fde68a;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.12);z-index:9999;width:280px;overflow:hidden;">
                                <div style="padding:12px 14px;background:linear-gradient(135deg,#fff7ed,#fef3c7);border-bottom:1px solid #fde68a;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#9a3412;">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        {{ __('app.phone_not_registered') }}
                                    </div>
                                    <button onclick="dismissPhoneNotice(event)" title="{{ __('common.close') }}"
                                        style="background:none;border:none;cursor:pointer;color:#9a3412;font-size:18px;line-height:1;padding:2px 4px;border-radius:5px;transition:background .12s;"
                                        onmouseover="this.style.background='rgba(154,52,18,.12)'" onmouseout="this.style.background='none'">&times;</button>
                                </div>
                                <div style="padding:12px 14px;font-size:12px;color:#57534e;line-height:1.55;">
                                    {!! __('app.phone_notice_desc') !!}
                                </div>
                                <div style="padding:0 14px 12px;">
                                    <a href="{{ route('profile.edit') }}#phone"
                                       style="display:flex;align-items:center;justify-content:center;gap:8px;padding:8px 12px;background:linear-gradient(135deg,#ea580c,#c2410c);color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:opacity .12s;"
                                       onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                                        {{ __('app.phone_register_now') }}
                                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <script>
                            (function() {
                                if (sessionStorage.getItem('phone-notice-dismissed') !== '1') {
                                    var p = document.getElementById('phone-notice-popover');
                                    if (p) p.style.display = 'block';
                                }
                            })();
                            function togglePhoneNotice() {
                                var p = document.getElementById('phone-notice-popover');
                                if (!p) return;
                                p.style.display = (p.style.display === 'none') ? 'block' : 'none';
                            }
                            function dismissPhoneNotice(e) {
                                e.stopPropagation();
                                var p = document.getElementById('phone-notice-popover');
                                if (p) p.style.display = 'none';
                                try { sessionStorage.setItem('phone-notice-dismissed', '1'); } catch (_) {}
                            }
                        </script>
                        @endif

                        {{-- 공지사항 아이콘 --}}
                        @php $__announcements = \App\Models\Announcement::active()->latest()->get(); @endphp
                        <div style="position:relative;" id="ann-icon-wrap">
                            <button id="ann-icon-btn" onclick="toggleAnnDropdown()" title="{{ __('app.announcements') }}"
                                style="position:relative;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:none;background:transparent;cursor:pointer;color:#a1a1aa;transition:background .12s,color .12s;"
                                onmouseover="this.style.background='var(--t50)';this.style.color='var(--tText)'"
                                onmouseout="this.style.background='transparent';this.style.color='#a1a1aa'">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                </svg>
                                <span id="ann-badge" style="display:{{ $__announcements->count()>0?'flex':'none' }};position:absolute;top:-3px;right:-3px;background:#ef4444;color:#fff;font-size:9px;font-weight:700;min-width:16px;height:16px;border-radius:8px;padding:0 3px;align-items:center;justify-content:center;border:2px solid #fff;line-height:1;">{{ $__announcements->count() }}</span>
                            </button>
                            <div id="ann-dropdown" style="display:none;position:absolute;top:42px;right:0;background:#fff;border:1px solid #ede8ff;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.12);z-index:9999;width:340px;max-height:440px;flex-direction:column;overflow:hidden;">
                                <div style="padding:12px 16px 10px;border-bottom:1px solid #f4f4f5;font-size:13px;font-weight:700;color:#18181b;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                                    <span style="display:flex;align-items:center;gap:8px;">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                        {{ __('app.announcements') }}
                                    </span>
                                    @if($__announcements->isNotEmpty())
                                    <button onclick="dismissAllAnn()" style="font-size:11px;color:#94a3b8;background:none;border:none;cursor:pointer;padding:3px 7px;border-radius:6px;transition:background .1s;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background=''">{{ __('app.dismiss_all') }}</button>
                                    @endif
                                </div>
                                <div style="overflow-y:auto;flex:1;">
                                @if($__announcements->isEmpty())
                                <div style="padding:28px 16px;text-align:center;color:#94a3b8;font-size:13px;">{{ __('app.no_announcements') }}</div>
                                @else
                                @foreach($__announcements as $__ann2)
                                @php
                                    $__ac=['info'=>['#eff6ff','#bfdbfe','#1d4ed8'],'warning'=>['#fffbeb','#fde68a','#b45309'],'maintenance'=>['#fef2f2','#fecaca','#dc2626'],'update'=>['#f5f3ff','#ddd6fe','#6d28d9']];
                                    [$__ab,$__abo,$__at]=$__ac[$__ann2->type]??['#f8fafc','#e2e8f0','#334155'];
                                    $__al2=['info'=>__('app.ann_type_info'),'warning'=>__('app.ann_type_warning'),'maintenance'=>__('app.ann_type_maintenance'),'update'=>__('app.ann_type_update')][$__ann2->type]??$__ann2->type;
                                @endphp
                                <div data-ann-drop="{{ $__ann2->id }}" style="padding:12px 16px;border-bottom:1px solid #f4f4f5;display:flex;align-items:flex-start;gap:12px;transition:background .1s;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                                    <div style="flex:1;min-width:0;">
                                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                            <span style="flex-shrink:0;background:{{ $__abo }};color:{{ $__at }};font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;">{{ $__al2 }}</span>
                                            @if($__ann2->ends_at)<span style="font-size:11px;color:#94a3b8;">~ {{ $__ann2->ends_at->format('Y.m.d') }}</span>@endif
                                        </div>
                                        <div style="font-size:13px;font-weight:600;color:#18181b;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $__ann2->title }}</div>
                                        <div style="font-size:12px;color:#71717a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $__ann2->body }}</div>
                                    </div>
                                    <button onclick="dismissAnn({{ $__ann2->id }})" title="{{ __('common.close') }}" style="flex-shrink:0;background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:17px;line-height:1;padding:1px 2px;margin-top:-1px;">&times;</button>
                                </div>
                                @endforeach
                                @endif
                                </div>
                            </div>
                        </div>

                        {{-- 이메일 보내기 (팝오버) --}}
                        <div style="position:relative;" id="mail-compose-wrap">
                            <button id="mail-compose-btn" type="button" onclick="mailComposeToggle()" title="{{ __('app.mail_send') }}"
                                style="position:relative;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:none;background:transparent;cursor:pointer;color:#a1a1aa;transition:background .12s,color .12s;"
                                onmouseover="this.style.background='var(--t50)';this.style.color='var(--tText)'"
                                onmouseout="this.style.background='transparent';this.style.color='#a1a1aa'">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </button>
                            <div id="mail-compose-pop" style="display:none;position:absolute;top:42px;right:0;background:#fff;border:1px solid var(--t200,#ddd6fe);border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,.14);z-index:9999;width:440px;flex-direction:column;overflow:visible;">
                                <div style="padding:13px 16px;border-bottom:1px solid #f4f4f5;font-size:13px;font-weight:700;color:#18181b;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                                    <span style="display:flex;align-items:center;gap:8px;">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        {{ __('app.mail_send') }}
                                    </span>
                                    <button type="button" onclick="mailComposeClose()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:18px;line-height:1;padding:2px 4px;">&times;</button>
                                </div>
                                <form id="mail-compose-form" onsubmit="return mailComposeSend(event)" style="padding:14px 16px;display:flex;flex-direction:column;gap:12px;overflow:visible;">
                                    <div>
                                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;letter-spacing:.03em;">{{ __('app.mail_from') }}</label>
                                        <div style="padding:8px 10px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;">
                                            {{ auth()->user()?->name }} <span style="color:#9ca3af;font-size:12px;">&lt;{{ auth()->user()?->email }}&gt;</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;letter-spacing:.03em;">{{ __('app.mail_to') }}</label>
                                        <div style="position:relative;margin-bottom:6px;">
                                            <input id="mail-compose-search" type="text" autocomplete="off"
                                                placeholder="{{ __('app.mail_search_member') }}"
                                                oninput="mailComposeFilter()" onfocus="mailComposeFilter()"
                                                style="width:100%;padding:7px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:12.5px;background:#fff;color:#374151;box-sizing:border-box;">
                                            <div id="mail-compose-dropdown" style="display:none;position:absolute;top:calc(100% + 3px);left:0;right:0;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.13);max-height:210px;overflow-y:auto;z-index:10010;"></div>
                                        </div>
                                        <input id="mail-compose-direct" type="text" placeholder="{{ __('app.mail_direct_placeholder') }}"
                                            onkeydown="mailComposeOnDirectKey(event)"
                                            style="width:100%;padding:7px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:12.5px;background:#fff;color:#374151;box-sizing:border-box;">
                                        <div id="mail-compose-chips" style="display:flex;flex-wrap:wrap;gap:4px;margin-top:6px;min-height:4px;"></div>
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;letter-spacing:.03em;">{{ __('common.title') }}</label>
                                        <input id="mail-compose-subject" type="text" maxlength="200" required
                                            style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;background:#fff;color:#374151;box-sizing:border-box;">
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;letter-spacing:.03em;">{{ __('common.content') }}</label>
                                        <textarea id="mail-compose-body" rows="6" maxlength="50000" required
                                            style="width:100%;padding:9px 11px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;background:#fff;color:#374151;box-sizing:border-box;resize:vertical;line-height:1.6;font-family:inherit;"></textarea>
                                    </div>
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:4px;">
                                        <span id="mail-compose-status" style="font-size:11.5px;color:#94a3b8;"></span>
                                        <div style="display:flex;gap:8px;">
                                            <button type="button" onclick="mailComposeClose()" style="padding:7px 14px;background:#fff;border:1px solid var(--t200,#e5e7eb);color:var(--tText,#6b7280);border-radius:8px;font-size:12.5px;font-weight:600;cursor:pointer;transition:background .12s;" onmouseover="this.style.background='var(--t50)'" onmouseout="this.style.background='#fff'">{{ __('common.cancel') }}</button>
                                            <button type="submit" id="mail-compose-send" style="padding:7px 18px;background:linear-gradient(135deg,var(--t500,#8b5cf6),var(--t700,#6d28d9));color:#fff;border:none;border-radius:8px;font-size:12.5px;font-weight:700;cursor:pointer;box-shadow:0 2px 6px rgba(124,58,237,.3);transition:filter .12s,transform .08s;" onmouseover="this.style.filter='brightness(1.08)'" onmouseout="this.style.filter=''">{{ __('app.mail_dispatch') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <script>
                            (function() {
                                let _mcRecipients = [];   // 추가된 수신자: {label, value}
                                let _mcAllUsers   = [];   // 수신 가능 사용자 전체

                                window.mailComposeToggle = function() {
                                    const pop = document.getElementById('mail-compose-pop');
                                    if (!pop) return;
                                    const show = pop.style.display === 'none' || !pop.style.display;
                                    pop.style.display = show ? 'flex' : 'none';
                                    if (show) mailComposeLoadRecipients();   // 매 오픈마다 재조회 (캐시 X)
                                };
                                window.mailComposeClose = function() {
                                    const pop = document.getElementById('mail-compose-pop');
                                    if (pop) pop.style.display = 'none';
                                };
                                document.addEventListener('click', function(e) {
                                    const wrap = document.getElementById('mail-compose-wrap');
                                    if (!wrap) return;
                                    if (!wrap.contains(e.target)) { mailComposeClose(); return; }
                                    const search = document.getElementById('mail-compose-search');
                                    const dd = document.getElementById('mail-compose-dropdown');
                                    if (dd && search && !search.contains(e.target) && !dd.contains(e.target)) {
                                        dd.style.display = 'none';
                                    }
                                });

                                async function mailComposeLoadRecipients() {
                                    try {
                                        const r = await fetch('{{ route('email-compose.recipients') }}?_=' + Date.now(), {
                                            headers: { 'Accept': 'application/json' },
                                            cache: 'no-store',
                                        });
                                        const d = await r.json();
                                        _mcAllUsers = d.users || [];
                                    } catch (e) {
                                        _mcAllUsers = [];
                                    }
                                    mailComposeSyncDropdown();
                                }

                                function mcEsc(s) {
                                    return String(s ?? '').replace(/[<>&"]/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[c]));
                                }

                                window.mailComposeFilter = function() {
                                    const inp = document.getElementById('mail-compose-search');
                                    const dd  = document.getElementById('mail-compose-dropdown');
                                    if (!inp || !dd) return;
                                    const q = (inp.value || '').trim().toLowerCase();
                                    const matched = _mcAllUsers.filter(u => {
                                        if (!q) return true;
                                        return (`${u.name||''} ${u.email||''} ${u.company||''}`).toLowerCase().includes(q);
                                    });
                                    if (!_mcAllUsers.length) {
                                        dd.innerHTML = `<div style="padding:9px 11px;font-size:12px;color:#9ca3af;">${mcEsc(@json(__('app.mail_no_members')))}</div>`;
                                    } else if (!matched.length) {
                                        dd.innerHTML = `<div style="padding:9px 11px;font-size:12px;color:#9ca3af;">${mcEsc(@json(__('app.mail_no_search_result')))}</div>`;
                                    } else {
                                        const noName = @json(__('app.no_name'));
                                        dd.innerHTML = matched.map(u => {
                                            const val = `${u.name||''}|${u.email||''}|${u.phone||''}`;
                                            const label = `${u.name||''} <${u.email||''}>`.trim();
                                            const checked = _mcRecipients.some(r => r.value === val) ? 'checked' : '';
                                            const co = (u.company && u.company !== '-') ? ` · ${mcEsc(u.company)}` : '';
                                            return `<label style="display:flex;align-items:center;gap:8px;padding:6px 10px;cursor:pointer;font-size:12px;color:#374151;"
                                                onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background=''">
                                                <input type="checkbox" ${checked} data-val="${mcEsc(val)}" data-label="${mcEsc(label)}"
                                                       onchange="mailComposeToggleUser(this)" style="accent-color:#7c3aed;flex-shrink:0;">
                                                <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                                    <b>${mcEsc(u.name) || noName}</b>${co} <span style="color:#9ca3af;">&lt;${mcEsc(u.email)}&gt;</span>
                                                </span>
                                            </label>`;
                                        }).join('');
                                    }
                                    dd.style.display = 'block';
                                };

                                window.mailComposeToggleUser = function(cb) {
                                    if (cb.checked) mailComposeAddChip(cb.dataset.label, cb.dataset.val);
                                    else mailComposeRemoveChipByValue(cb.dataset.val);
                                };

                                function mailComposeSyncDropdown() {
                                    const dd = document.getElementById('mail-compose-dropdown');
                                    if (dd && dd.style.display === 'block') mailComposeFilter();
                                }

                                function mailComposeRemoveChipByValue(value) {
                                    const i = _mcRecipients.findIndex(r => r.value === value);
                                    if (i >= 0) { _mcRecipients.splice(i, 1); mailComposeRenderChips(); }
                                }

                                window.mailComposeOnDirectKey = function(e) {
                                    if (e.key === 'Enter' || e.key === ',') {
                                        e.preventDefault();
                                        const inp = e.target;
                                        const v = (inp.value || '').trim().replace(/,$/, '').trim();
                                        if (v) {
                                            mailComposeAddChip(v, v);
                                            inp.value = '';
                                        }
                                    }
                                };

                                function mailComposeAddChip(label, value) {
                                    if (!value) return;
                                    if (_mcRecipients.some(r => r.value === value)) return;
                                    _mcRecipients.push({ label, value });
                                    mailComposeRenderChips();
                                }
                                function mailComposeRemoveChip(idx) {
                                    _mcRecipients.splice(idx, 1);
                                    mailComposeRenderChips();
                                }
                                window.mailComposeRemoveChip = mailComposeRemoveChip;

                                function mailComposeRenderChips() {
                                    const box = document.getElementById('mail-compose-chips');
                                    if (!box) return;
                                    box.innerHTML = _mcRecipients.map((r, i) =>
                                        `<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 4px 3px 9px;background:var(--t100,#ede9fe);color:var(--t700,#4c1d95);border:1px solid var(--t200,#ddd6fe);border-radius:999px;font-size:11.5px;font-weight:600;">${
                                            r.label.replace(/[<>&]/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[c]))
                                        }<button type="button" onclick="mailComposeRemoveChip(${i})" style="background:none;border:none;cursor:pointer;color:var(--t700,#7c3aed);font-size:14px;line-height:1;padding:0 2px;">&times;</button></span>`
                                    ).join('');
                                    mailComposeSyncDropdown();
                                }

                                window.mailComposeSend = async function(ev) {
                                    ev.preventDefault();
                                    const subject = document.getElementById('mail-compose-subject').value.trim();
                                    const body    = document.getElementById('mail-compose-body').value;
                                    const direct  = document.getElementById('mail-compose-direct').value.trim();
                                    const status  = document.getElementById('mail-compose-status');
                                    const btn     = document.getElementById('mail-compose-send');
                                    if (direct) mailComposeAddChip(direct, direct);

                                    if (!_mcRecipients.length) {
                                        status.style.color = '#dc2626';
                                        status.textContent = @json(__('app.mail_need_recipient'));
                                        return false;
                                    }
                                    if (!subject || !body.trim()) {
                                        status.style.color = '#dc2626';
                                        status.textContent = @json(__('app.mail_need_subject_body'));
                                        return false;
                                    }

                                    btn.disabled = true;
                                    const orig = btn.textContent;
                                    btn.textContent = @json(__('app.mail_sending'));
                                    status.style.color = '#6b7280';
                                    status.textContent = '';

                                    try {
                                        const r = await fetch('{{ route('email-compose.send') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            },
                                            body: JSON.stringify({
                                                subject,
                                                body,
                                                recipients: _mcRecipients.map(r => r.value),
                                            }),
                                        });
                                        const d = await r.json();
                                        if (!d.ok) throw new Error(d.message || @json(__('app.mail_send_failed')));

                                        status.style.color = '#15803d';
                                        status.textContent = d.message || @json(__('app.mail_send_done'));
                                        document.getElementById('mail-compose-subject').value = '';
                                        document.getElementById('mail-compose-body').value = '';
                                        document.getElementById('mail-compose-direct').value = '';
                                        _mcRecipients = [];
                                        mailComposeRenderChips();
                                        setTimeout(() => { mailComposeClose(); status.textContent = ''; }, 1200);
                                    } catch (e) {
                                        status.style.color = '#dc2626';
                                        status.textContent = @json(__('app.mail_send_failed')) + ': ' + e.message;
                                    }
                                    btn.disabled = false;
                                    btn.textContent = orig;
                                    return false;
                                };
                            })();
                        </script>

                        {{-- 언어 스위처 --}}
                        <div style="position:relative;">
                            <button id="lang-btn"
                                onclick="(function(){var d=document.getElementById('lang-dropdown');d.style.display=d.style.display==='block'?'none':'block';})()"
                                style="display:flex;align-items:center;gap:4px;padding:0 10px;height:32px;border-radius:8px;border:none;background:transparent;cursor:pointer;color:#a1a1aa;font-size:12px;font-weight:600;transition:background .12s,color .12s;"
                                onmouseover="this.style.background='var(--t50)';this.style.color='var(--tText)'"
                                onmouseout="this.style.background='transparent';this.style.color='#a1a1aa'">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                                </svg>
                                {{ strtoupper(app()->getLocale()) }}
                            </button>
                            <div id="lang-dropdown" style="display:none;position:absolute;top:40px;right:0;background:#fff;border:1px solid #ede8ff;border-radius:12px;padding:6px;box-shadow:0 8px 28px rgba(0,0,0,.1);z-index:9999;min-width:120px;">
                                <form method="POST" action="{{ route('locale.switch') }}">
                                    @csrf
                                    <input type="hidden" name="locale" value="ko">
                                    <button type="submit" style="display:flex;align-items:center;gap:8px;width:100%;padding:7px 10px;border:none;background:{{ app()->getLocale()==='ko' ? 'var(--t50)' : 'transparent' }};border-radius:8px;cursor:pointer;font-size:13px;font-weight:{{ app()->getLocale()==='ko' ? '700' : '500' }};color:{{ app()->getLocale()==='ko' ? 'var(--tText)' : '#374151' }};"
                                        onmouseover="this.style.background='var(--t50)'" onmouseout="this.style.background='{{ app()->getLocale()==='ko' ? 'var(--t50)' : 'transparent' }}'">
                                        🇰🇷 {{ __('app.lang_ko') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('locale.switch') }}">
                                    @csrf
                                    <input type="hidden" name="locale" value="en">
                                    <button type="submit" style="display:flex;align-items:center;gap:8px;width:100%;padding:7px 10px;border:none;background:{{ app()->getLocale()==='en' ? 'var(--t50)' : 'transparent' }};border-radius:8px;cursor:pointer;font-size:13px;font-weight:{{ app()->getLocale()==='en' ? '700' : '500' }};color:{{ app()->getLocale()==='en' ? 'var(--tText)' : '#374151' }};"
                                        onmouseover="this.style.background='var(--t50)'" onmouseout="this.style.background='{{ app()->getLocale()==='en' ? 'var(--t50)' : 'transparent' }}'">
                                        🇺🇸 {{ __('app.lang_en') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div style="position:relative;">
                            <button id="theme-btn" title="{{ __('app.theme_change') }}" onclick="(function(){var d=document.getElementById('theme-dropdown');d.style.display=d.style.display==='block'?'none':'block';})()" style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:none;background:transparent;cursor:pointer;color:#a1a1aa;transition:background .12s,color .12s;" onmouseover="this.style.background='var(--t50)';this.style.color='var(--tText)'" onmouseout="this.style.background='transparent';this.style.color='#a1a1aa'">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                            </button>
                            <div id="theme-dropdown" style="display:none;position:absolute;top:40px;right:0;background:#fff;border:1px solid #ede8ff;border-radius:12px;padding:10px 12px;box-shadow:0 8px 28px rgba(0,0,0,.1);z-index:9999;min-width:236px;">
                                <div style="font-size:11px;font-weight:600;color:#a1a1aa;letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px;">{{ __('app.theme_colors') }}</div>
                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:nowrap;">
                                    {{-- Phase 1: 디자인 시스템 액센트 5종 --}}
                                    <button class="theme-swatch" data-theme="blue"   title="{{ __('app.theme_blue') }}"   style="width:26px;height:26px;border-radius:50%;background:#0f86ef;border:2px solid #fff;cursor:pointer;outline:none;transition:transform .15s,box-shadow .15s;"></button>
                                    <button class="theme-swatch" data-theme="coral"  title="{{ __('app.theme_coral') ?? 'Coral' }}"  style="width:26px;height:26px;border-radius:50%;background:#f25a3d;border:2px solid #fff;cursor:pointer;outline:none;transition:transform .15s,box-shadow .15s;"></button>
                                    <button class="theme-swatch" data-theme="green"  title="{{ __('app.theme_green') }}"  style="width:26px;height:26px;border-radius:50%;background:#2cc66c;border:2px solid #fff;cursor:pointer;outline:none;transition:transform .15s,box-shadow .15s;"></button>
                                    <button class="theme-swatch" data-theme="yellow" title="{{ __('app.theme_yellow') ?? 'Yellow' }}" style="width:26px;height:26px;border-radius:50%;background:#fbaf2b;border:2px solid #fff;cursor:pointer;outline:none;transition:transform .15s,box-shadow .15s;"></button>
                                    <button class="theme-swatch" data-theme="purple" title="{{ __('app.theme_violet') ?? 'Purple' }}" style="width:26px;height:26px;border-radius:50%;background:#950fef;border:2px solid #fff;cursor:pointer;outline:none;transition:transform .15s,box-shadow .15s;"></button>
                                    {{-- 사용자 지정 색상 (HTML5 native color picker) --}}
                                    <label id="theme-custom-wrap" title="{{ __('app.theme_custom') ?? '사용자 지정' }}"
                                           style="position:relative;width:26px;height:26px;border-radius:50%;border:2px solid #fff;cursor:pointer;background:conic-gradient(from 0deg,#ef4444,#f59e0b,#22c55e,#06b6d4,#3b82f6,#8b5cf6,#ec4899,#ef4444);box-shadow:0 0 0 1px #e5e7eb;display:inline-block;transition:transform .15s,box-shadow .15s;overflow:hidden;">
                                        <input type="color" id="theme-custom-input" value="#0f86ef"
                                               style="position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;border:none;padding:0;"
                                               oninput="setAccentHex(this.value)" onchange="setAccentHex(this.value)">
                                    </label>
                                </div>
                                <div id="theme-custom-hex" style="margin-top:8px;font-size:11px;color:#6b7280;display:none;"></div>
                            </div>
                        </div>
                        {{-- 메모 버튼 --}}
                        @if(auth()->user()->hasFeature('memos'))
                        <button id="memo-btn" onclick="memoPopupToggle()" title="{{ __('app.nav_memos') }}"
                            style="display:flex;align-items:center;gap:4px;height:32px;padding:0 10px;border-radius:8px;border:none;background:transparent;cursor:pointer;color:#a1a1aa;font-size:12px;font-weight:600;transition:background .12s,color .12s;"
                            onmouseover="this.style.background='var(--t50)';this.style.color='var(--tText)'"
                            onmouseout="this.style.background='transparent';this.style.color='#a1a1aa'">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>{{ __('app.nav_memos') }}</span>
                        </button>
                        @endif {{-- memos feature --}}

                        {{-- 프롬프트 변환 버튼 --}}
                        <button id="quick-prompt-btn" onclick="qpPopupToggle()" title="{{ __('app.prompt_convert') }}"
                            style="display:flex;align-items:center;gap:4px;height:32px;padding:0 10px;border-radius:8px;border:none;background:transparent;cursor:pointer;color:#a1a1aa;font-size:12px;font-weight:600;transition:background .12s,color .12s;"
                            onmouseover="this.style.background='var(--t50)';this.style.color='var(--tText)'"
                            onmouseout="this.style.background='transparent';this.style.color='#a1a1aa'">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>{{ __('app.prompt_label') }}</span>
                        </button>

                        @include('partials.collab-widget')
                        @yield('header-actions')
                    </div>
                </header>
                @endunless

                {{-- 공지사항 배너 ($__announcements는 헤더 아이콘 섹션에서 이미 로드됨) — embed=1 일 때 숨김 --}}
                @unless($embedMode)
                @foreach($__announcements as $__ann)
                @php
                    $__annColors = ['info'=>['#eff6ff','#bfdbfe','#1d4ed8'],'warning'=>['#fffbeb','#fde68a','#b45309'],'maintenance'=>['#fef2f2','#fecaca','#dc2626'],'update'=>['#f5f3ff','#ddd6fe','#6d28d9']];
                    [$__bg,$__border,$__text] = $__annColors[$__ann->type] ?? ['#f8fafc','#e2e8f0','#334155'];
                    $__annLabel = ['info'=>__('app.ann_type_info'),'warning'=>__('app.ann_type_warning'),'maintenance'=>__('app.ann_type_maintenance'),'update'=>__('app.ann_type_update')][$__ann->type] ?? $__ann->type;
                @endphp
                <div style="background:{{ $__bg }};border-bottom:1px solid {{ $__border }};padding:9px 24px;display:flex;align-items:center;gap:12px;" data-announcement-id="{{ $__ann->id }}">
                    <span style="flex-shrink:0;background:{{ $__border }};color:{{ $__text }};font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;">{{ $__annLabel }}</span>
                    <span style="font-size:13px;font-weight:500;color:{{ $__text }};flex:1;">{{ $__ann->title }}</span>
                    <span style="font-size:12px;color:{{ $__text }};opacity:.7;flex-shrink:0;">{{ $__ann->body }}</span>
                    <button onclick="dismissAnn({{ $__ann->id }})" style="flex-shrink:0;background:none;border:none;cursor:pointer;color:{{ $__text }};opacity:.5;font-size:16px;line-height:1;padding:0 2px;">&times;</button>
                </div>
                @endforeach
                @endunless

                {{-- 알림 메시지: 화면 영역 배너가 아닌 전역 토스트로 표시 (window.appToast) --}}
                @php
                    $__flashSuccess = session('success');
                    $__flashError   = session('error');
                    $__flashStatus  = session('status');
                    $__flashWarning = session('warning');
                    $__flashErrors  = $errors->any() ? $errors->all() : [];
                @endphp
                @if($__flashSuccess || $__flashError || $__flashStatus || $__flashWarning || count($__flashErrors))
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    @if($__flashSuccess) window.appToast && window.appToast('success', @json($__flashSuccess)); @endif
                    @if($__flashStatus)  window.appToast && window.appToast('success', @json($__flashStatus));  @endif
                    @if($__flashWarning) window.appToast && window.appToast('warning', @json($__flashWarning)); @endif
                    @if($__flashError)   window.appToast && window.appToast('error',   @json($__flashError));   @endif
                    @foreach($__flashErrors as $__e)
                        window.appToast && window.appToast('error', @json($__e), 6000);
                    @endforeach
                });
                </script>
                @endif

                {{-- 페이지 콘텐츠 (embed=1 일 때 패딩 축소) --}}
                <main style="flex:1;overflow-y:auto;padding:{{ $embedMode ? '12px 14px' : '20px 24px 24px' }};background:#f5f3ff;">
                    @hasSection('breadcrumb')
                    <nav style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;font-size:12px;color:#9ca3af;margin-bottom:14px;">
                        @yield('breadcrumb')
                    </nav>
                    @endif
                    @yield('content')
                </main>
            </div>
        </div>

        {{-- 인앱 토스트 컨테이너 --}}
        <div id="toast-container" style="position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:12px;pointer-events:none;"></div>

        {{-- ===== 메모 팝업 ===== --}}
        <div id="memo-popup" style="display:none;position:fixed;top:60px;right:20px;z-index:9995;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:360px;max-height:calc(100vh - 80px);flex-direction:column;overflow:hidden;border:1px solid #ede8ff;">
            {{-- 팝업 헤더 --}}
            <div style="padding:14px 16px 12px;border-bottom:1px solid #f0eeff;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#fff;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <svg width="16" height="16" fill="none" stroke="var(--tText)" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span style="font-size:14px;font-weight:700;color:#18181b;">{{ __('app.nav_memos') }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button onclick="memoShowAddForm()" id="memo-add-btn"
                        style="display:flex;align-items:center;gap:4px;height:28px;padding:0 10px;background:var(--t600);color:#fff;border:none;border-radius:7px;cursor:pointer;font-size:12px;font-weight:600;transition:background .12s;"
                        onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('app.memo_add') }}
                    </button>
                    <button onclick="memoPopupClose()"
                        style="width:28px;height:28px;border:none;background:none;cursor:pointer;color:#a1a1aa;display:flex;align-items:center;justify-content:center;border-radius:6px;"
                        onmouseover="this.style.background='#f3f0ff'" onmouseout="this.style.background='none'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- 메모 추가 폼 --}}
            <div id="memo-add-form" style="display:none;padding:12px 14px;border-bottom:1px solid #f0eeff;background:#fafaff;flex-shrink:0;">
                <input id="memo-input-title" type="text" placeholder="{{ __('app.memo_title_placeholder') }}"
                    style="width:100%;padding:8px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12.5px;outline:none;background:#fff;color:#18181b;margin-bottom:8px;box-sizing:border-box;"
                    onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
                <textarea id="memo-input-content" placeholder="{{ __('app.memo_content_placeholder') }}" rows="4"
                    style="width:100%;padding:8px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12.5px;outline:none;background:#fff;color:#18181b;resize:vertical;margin-bottom:8px;box-sizing:border-box;line-height:1.55;"
                    onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;gap:8px;align-items:center;" id="memo-color-picker">
                        <button class="memo-color-dot" data-color="yellow" onclick="memoSelectColor(this)"
                            style="width:20px;height:20px;border-radius:50%;background:#fde047;border:2.5px solid transparent;cursor:pointer;transition:transform .12s,box-shadow .12s;" title="{{ __('app.color_yellow') }}"></button>
                        <button class="memo-color-dot" data-color="green" onclick="memoSelectColor(this)"
                            style="width:20px;height:20px;border-radius:50%;background:#86efac;border:2.5px solid transparent;cursor:pointer;transition:transform .12s,box-shadow .12s;" title="{{ __('app.color_green') }}"></button>
                        <button class="memo-color-dot" data-color="blue" onclick="memoSelectColor(this)"
                            style="width:20px;height:20px;border-radius:50%;background:#93c5fd;border:2.5px solid transparent;cursor:pointer;transition:transform .12s,box-shadow .12s;" title="{{ __('app.color_blue') }}"></button>
                        <button class="memo-color-dot" data-color="pink" onclick="memoSelectColor(this)"
                            style="width:20px;height:20px;border-radius:50%;background:#f9a8d4;border:2.5px solid transparent;cursor:pointer;transition:transform .12s,box-shadow .12s;" title="{{ __('app.color_pink') }}"></button>
                        <button class="memo-color-dot" data-color="purple" onclick="memoSelectColor(this)"
                            style="width:20px;height:20px;border-radius:50%;background:#c4b5fd;border:2.5px solid transparent;cursor:pointer;transition:transform .12s,box-shadow .12s;" title="{{ __('app.color_purple') }}"></button>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button onclick="memoHideAddForm()"
                            style="height:28px;padding:0 12px;border:1.5px solid #e5e7eb;background:#fff;border-radius:7px;font-size:12px;color:#6b7280;cursor:pointer;"
                            onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">{{ __('common.cancel') }}</button>
                        <button onclick="memoSave()"
                            style="height:28px;padding:0 12px;background:var(--t600);color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;"
                            onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">{{ __('common.save') }}</button>
                    </div>
                </div>
            </div>

            {{-- 메모 목록 --}}
            <div id="memo-list" style="overflow-y:auto;flex:1;padding:12px 14px;"></div>
        </div>

        {{-- ===== 프롬프트 변환 팝업 ===== --}}
        <div id="qp-popup" style="display:none;position:fixed;top:60px;right:20px;z-index:9995;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:420px;max-height:calc(100vh - 80px);flex-direction:column;overflow:hidden;border:1px solid #ede8ff;">
            {{-- 헤더 --}}
            <div style="padding:14px 16px 12px;border-bottom:1px solid #f0eeff;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#fff;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <svg width="16" height="16" fill="none" stroke="var(--tText)" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span style="font-size:14px;font-weight:700;color:#18181b;">{{ __('app.prompt_convert') }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:4px;">
                    <button onclick="qpSuffixManageOpen()" id="qp-suffix-manage-btn" type="button" title="{{ __('app.suffix_manage') }}"
                        style="width:28px;height:28px;border:none;background:none;cursor:pointer;color:#a1a1aa;display:flex;align-items:center;justify-content:center;border-radius:6px;transition:all .12s;"
                        onmouseover="this.style.background='var(--t50)';this.style.color='var(--tText)'" onmouseout="this.style.background='none';this.style.color='#a1a1aa'">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                    <button onclick="qpPopupClose()"
                        style="width:28px;height:28px;border:none;background:none;cursor:pointer;color:#a1a1aa;display:flex;align-items:center;justify-content:center;border-radius:6px;"
                        onmouseover="this.style.background='#f3f0ff'" onmouseout="this.style.background='none'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- 입력 영역 --}}
            <div style="padding:12px 14px;border-bottom:1px solid #f0eeff;background:#fafaff;flex-shrink:0;">
                <textarea id="qp-input" placeholder="{{ __('app.qp_input_placeholder') }}" rows="5"
                    style="width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;color:#18181b;resize:vertical;margin-bottom:10px;box-sizing:border-box;line-height:1.55;font-family:inherit;"
                    onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'"></textarea>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span id="qp-status" style="font-size:11.5px;color:#94a3b8;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                    <button id="qp-submit-btn" onclick="qpSubmit()"
                        style="height:30px;padding:0 14px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:12.5px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:4px;transition:background .12s;"
                        onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        {{ __('app.prompt_convert') }}
                    </button>
                </div>
            </div>

            {{-- 결과/이력 목록 --}}
            <div id="qp-list" style="overflow-y:auto;flex:1;padding:12px 14px;"></div>
        </div>

        {{-- ===== 추가 문구 관리 팝오버 ===== --}}
        <div id="qp-suffix-manage-popup" style="display:none;position:fixed;top:60px;right:450px;z-index:9996;background:#fff;border-radius:14px;box-shadow:0 18px 50px rgba(0,0,0,.18);width:340px;max-height:calc(100vh - 80px);flex-direction:column;overflow:hidden;border:1px solid #ede8ff;">
            <div style="padding:13px 14px 11px;border-bottom:1px solid #f0eeff;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                <span style="font-size:13px;font-weight:700;color:#18181b;display:flex;align-items:center;gap:8px;">
                    <svg width="13" height="13" fill="none" stroke="var(--tText)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ __('app.suffix_manage') }}
                </span>
                <div style="display:flex;align-items:center;gap:4px;">
                    <button onclick="qpSuffixManageNew()" id="qp-suffix-new-btn" type="button"
                        style="display:flex;align-items:center;gap:4px;height:26px;padding:0 10px;background:var(--t600);color:#fff;border:none;border-radius:7px;font-size:11.5px;font-weight:600;cursor:pointer;transition:background .12s;"
                        onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        {{ __('app.suffix_new') }}
                    </button>
                    <button onclick="qpSuffixManageClose()" type="button"
                        style="width:26px;height:26px;border:none;background:none;cursor:pointer;color:#a1a1aa;display:flex;align-items:center;justify-content:center;border-radius:5px;"
                        onmouseover="this.style.background='#f3f0ff'" onmouseout="this.style.background='none'">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- 폼 (토글) --}}
            <div id="qp-suffix-form" style="display:none;padding:11px 12px;background:#fafaff;border-bottom:1px solid #f0eeff;flex-shrink:0;">
                <input id="qp-suffix-form-id" type="hidden" value="">
                <input id="qp-suffix-form-label" type="text" maxlength="100" placeholder="{{ __('app.suffix_label_placeholder') }}"
                    style="width:100%;padding:7px 10px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:12px;outline:none;background:#fff;color:#18181b;margin-bottom:7px;box-sizing:border-box;"
                    onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
                <textarea id="qp-suffix-form-body" rows="4" maxlength="2000" placeholder="{{ __('app.suffix_body_placeholder') }}"
                    style="width:100%;padding:7px 10px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:12px;outline:none;background:#fff;color:#18181b;resize:vertical;box-sizing:border-box;line-height:1.55;font-family:inherit;margin-bottom:8px;"
                    onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
                <div style="display:flex;justify-content:flex-end;gap:4px;">
                    <button onclick="qpSuffixHideForm()" type="button"
                        style="height:26px;padding:0 11px;border:1.5px solid #e5e7eb;background:#fff;border-radius:6px;font-size:11.5px;color:#6b7280;cursor:pointer;">{{ __('common.cancel') }}</button>
                    <button onclick="qpSuffixSave()" id="qp-suffix-save-btn" type="button"
                        style="height:26px;padding:0 12px;background:var(--t600);color:#fff;border:none;border-radius:6px;font-size:11.5px;font-weight:600;cursor:pointer;">{{ __('common.save') }}</button>
                </div>
            </div>

            {{-- 관리 리스트 --}}
            <div id="qp-suffix-manage-list" style="overflow-y:auto;flex:1;padding:8px 10px;"></div>
        </div>

        {{-- ===== 메모 드래그 드롭 힌트 ===== --}}
        <div id="memo-drop-hint" style="display:none;position:fixed;inset:0;z-index:9980;pointer-events:none;align-items:center;justify-content:center;">
            <div style="background:rgba(124,58,237,.13);border:2.5px dashed var(--t400);border-radius:16px;padding:18px 32px;display:flex;align-items:center;gap:12px;backdrop-filter:blur(2px);">
                <svg width="22" height="22" fill="none" stroke="var(--t600)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span style="font-size:14px;font-weight:600;color:var(--t700);">{{ __('app.memo_drop_hint') }}</span>
            </div>
        </div>

        {{-- ===== 메모 공유 모달 ===== --}}
        <div id="memo-share-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:16px;width:380px;max-height:540px;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;" onclick="event.stopPropagation()">
                <div style="padding:16px 18px 12px;border-bottom:1px solid #f0eeff;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <svg width="16" height="16" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        <span style="font-size:15px;font-weight:700;color:#18181b;">{{ __('app.memo_share') }}</span>
                    </div>
                    <button onclick="memoShareModalClose()" style="width:28px;height:28px;border:none;background:none;cursor:pointer;color:#a1a1aa;display:flex;align-items:center;justify-content:center;border-radius:6px;" onmouseover="this.style.background='#f3f0ff'" onmouseout="this.style.background='none'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div style="padding:10px 16px;border-bottom:1px solid #f0eeff;flex-shrink:0;">
                    <input id="memo-share-search" type="text" placeholder="{{ __('app.member_search_placeholder') }}"
                        style="width:100%;padding:8px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fafaff;color:#18181b;box-sizing:border-box;"
                        onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'"
                        oninput="memoShareFilterMembers(this.value)">
                </div>
                <div id="memo-share-member-list" style="overflow-y:auto;flex:1;padding:6px 0;min-height:120px;max-height:280px;"></div>
                <div style="padding:12px 16px;border-top:1px solid #f0eeff;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                    <span id="memo-share-count" style="font-size:12px;color:#7c3aed;font-weight:600;"></span>
                    <div style="display:flex;gap:8px;">
                        <button onclick="memoShareModalClose()" style="height:32px;padding:0 14px;border:1.5px solid #e5e7eb;background:#fff;border-radius:8px;font-size:13px;color:#6b7280;cursor:pointer;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">{{ __('common.cancel') }}</button>
                        <button id="memo-share-confirm-btn" onclick="memoShareConfirm()" style="height:32px;padding:0 16px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;" onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">{{ __('app.memo_share_confirm') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== 고정 메모 컨테이너 (모든 화면에서 표시) ===== --}}
        @php
            $pmColors = [
                'yellow' => ['bg'=>'#fef9c3','border'=>'#fde047','header'=>'#fef08a'],
                'green'  => ['bg'=>'#dcfce7','border'=>'#86efac','header'=>'#bbf7d0'],
                'blue'   => ['bg'=>'#dbeafe','border'=>'#93c5fd','header'=>'#bfdbfe'],
                'pink'   => ['bg'=>'#fce7f3','border'=>'#f9a8d4','header'=>'#fbcfe8'],
                'purple' => ['bg'=>'#ede9fe','border'=>'#c4b5fd','header'=>'#ddd6fe'],
            ];
        @endphp
        <div id="pinned-memos-wrap">
            @foreach($pinnedMemos as $pm)
            @php $pc = $pmColors[$pm->color] ?? $pmColors['yellow']; @endphp
            <div class="pinned-memo-note" data-id="{{ $pm->id }}"
                style="position:fixed;right:24px;bottom:{{ 80 + $loop->index * 230 }}px;z-index:9988;background:{{ $pc['bg'] }};border:1.5px solid {{ $pc['border'] }};border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.12);width:230px;display:flex;flex-direction:column;overflow:hidden;">
                <div class="pinned-memo-header" style="padding:6px 8px;background:{{ $pc['header'] }};border-bottom:1px solid {{ $pc['border'] }};border-radius:10px 10px 0 0;display:flex;align-items:center;gap:4px;cursor:grab;user-select:none;flex-shrink:0;">
                    <span style="font-size:11.5px;font-weight:600;color:#374151;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;">{{ $pm->title ?: __('app.nav_memos') }}</span>
                    <div style="display:flex;gap:4px;flex-shrink:0;" onclick="event.stopPropagation()">
                        <button onclick="memoTogglePin({{ $pm->id }})" title="{{ __('app.memo_unpin') }}"
                            style="width:22px;height:22px;border:none;background:rgba(0,0,0,.06);cursor:pointer;color:#6b7280;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:all .12s;"
                            onmouseover="this.style.background='#fde047';this.style.color='#92400e'" onmouseout="this.style.background='rgba(0,0,0,.06)';this.style.color='#6b7280'">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                        </button>

                        <button onclick="memoDelete({{ $pm->id }})" title="{{ __('common.delete') }}"
                            style="width:22px;height:22px;border:none;background:rgba(0,0,0,.06);cursor:pointer;color:#6b7280;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:all .12s;"
                            onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444'" onmouseout="this.style.background='rgba(0,0,0,.06)';this.style.color='#6b7280'">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                <div class="pinned-memo-body" style="flex:1;padding:8px 10px;font-size:12px;color:#374151;line-height:1.55;overflow-y:auto;white-space:pre-wrap;word-break:break-word;min-height:60px;cursor:text;" title="{{ __('app.memo_edit_hint') }}">{{ $pm->content }}</div>
                <div class="pinned-memo-resize" title="{{ __('app.memo_resize') }}" style="position:absolute;right:0;bottom:0;width:16px;height:16px;cursor:se-resize;display:flex;align-items:center;justify-content:center;opacity:0.45;">
                    <svg width="9" height="9" viewBox="0 0 9 9" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round">
                        <line x1="8" y1="1" x2="1" y2="8"/><line x1="8" y1="5" x2="5" y2="8"/>
                    </svg>
                </div>
            </div>
            @endforeach
            @php $totalPinned = $pinnedMemos->count(); @endphp
            @foreach($pinnedSharedMemos as $ps)
            @php
                $psm = $ps->memo;
                $pc2 = $pmColors[$psm->color] ?? $pmColors['yellow'];
                $psIdx = $totalPinned + $loop->index;
            @endphp
            <div class="pinned-memo-note" data-id="{{ $psm->id }}" data-share-id="{{ $ps->id }}"
                style="position:fixed;right:24px;bottom:{{ 80 + $psIdx * 230 }}px;z-index:9988;background:{{ $pc2['bg'] }};border:1.5px solid {{ $pc2['border'] }};border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.12);width:230px;display:flex;flex-direction:column;overflow:hidden;">
                <div class="pinned-memo-header" style="padding:6px 8px;background:{{ $pc2['header'] }};border-bottom:1px solid {{ $pc2['border'] }};border-radius:10px 10px 0 0;display:flex;align-items:center;gap:4px;cursor:grab;user-select:none;flex-shrink:0;">
                    <span style="font-size:11.5px;font-weight:600;color:#374151;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;">{{ $psm->title ?: __('app.nav_memos') }}</span>
                    <div style="display:flex;gap:4px;flex-shrink:0;" onclick="event.stopPropagation()">
                        <button onclick="memoToggleSharedPin({{ $ps->id }})" title="{{ __('app.memo_unpin') }}"
                            style="width:22px;height:22px;border:none;background:rgba(0,0,0,.06);cursor:pointer;color:#6b7280;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:all .12s;"
                            onmouseover="this.style.background='#fde047';this.style.color='#92400e'" onmouseout="this.style.background='rgba(0,0,0,.06)';this.style.color='#6b7280'">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                        </button>
                    </div>
                </div>
                <div style="padding:3px 10px 0;font-size:10px;color:#7c3aed;font-weight:600;background:{{ $pc2['header'] }};border-bottom:1px solid {{ $pc2['border'] }};flex-shrink:0;">
                    {{ __('app.shared_by', ['name' => $ps->sharedByUser->name ?? '']) }}
                </div>
                <div class="pinned-memo-body" style="flex:1;padding:8px 10px;font-size:12px;color:#374151;line-height:1.55;overflow-y:auto;white-space:pre-wrap;word-break:break-word;min-height:60px;cursor:default;">{{ $psm->content }}</div>
                <div class="pinned-memo-resize" title="{{ __('app.memo_resize') }}" style="position:absolute;right:0;bottom:0;width:16px;height:16px;cursor:se-resize;display:flex;align-items:center;justify-content:center;opacity:0.45;">
                    <svg width="9" height="9" viewBox="0 0 9 9" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round">
                        <line x1="8" y1="1" x2="1" y2="8"/><line x1="8" y1="5" x2="5" y2="8"/>
                    </svg>
                </div>
            </div>
            @endforeach
        </div>

        <script>
        // ── 전역 변수 ─────────────────────────────────────────
        window.MY_ID              = {{ auth()->id() }};
        window.APP_STR = {
            adminLabel:       '{{ __("app.admin_label") }}',
            leaveRequested:   '{{ __("app.leave_requested_suffix") }}',
            leaveApproved:    '{{ __("app.leave_approved_suffix") }}',
            leaveRejected:    '{{ __("app.leave_rejected_suffix") }}',
        };
        window.MY_NAME            = @json(auth()->user()->name);
        window.MY_CONV_IDS        = @json($myConvIds);
        window.MY_INQUIRY_CONV_IDS = new Set(@json($myInquiryConvIds));
        window.OPEN_CONV_ID = null; // 메시지 페이지에서 덮어씀

        // ── 전역 액션 결과 토스트 (success/error/warning/info) ──
        // 사용: window.appToast('success', '저장되었습니다');  window.appToast('error', '실패했습니다', 6000);
        window.appToast = function(type, message, duration) {
            if (!message) return;
            duration = duration || (type === 'error' ? 5500 : 4000);
            let host = document.getElementById('app-toast-container');
            if (!host) {
                host = document.createElement('div');
                host.id = 'app-toast-container';
                host.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:8px;pointer-events:none;max-width:480px;';
                document.body.appendChild(host);
            }
            const palette = {
                success: {bg:'#ecfdf5', border:'#a7f3d0', text:'#047857', icon:'M5 13l4 4L19 7'},
                error:   {bg:'#fef2f2', border:'#fecaca', text:'#b91c1c', icon:'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'},
                warning: {bg:'#fffbeb', border:'#fde68a', text:'#b45309', icon:'M12 9v2m0 4h.01M4.93 19.07a10 10 0 1114.14 0H4.93z'},
                info:    {bg:'#eff6ff', border:'#bfdbfe', text:'#1d4ed8', icon:'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'},
            }[type] || {bg:'#f3f4f6', border:'#e5e7eb', text:'#374151', icon:'M13 16h-1v-4h-1m1-4h.01'};
            const t = document.createElement('div');
            t.style.cssText =
                'background:'+palette.bg+';border:1px solid '+palette.border+';color:'+palette.text+
                ';padding:10px 14px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.08);'+
                'font-size:13px;min-width:280px;max-width:480px;display:flex;align-items:flex-start;gap:8px;'+
                'pointer-events:auto;transform:translateX(20px);opacity:0;transition:transform .2s, opacity .2s;';
            const msgEscaped = String(message).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            t.innerHTML =
                '<svg style="width:18px;height:18px;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">'+
                  '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="'+palette.icon+'"/>'+
                '</svg>'+
                '<div style="flex:1;line-height:1.5;">'+msgEscaped+'</div>'+
                '<button type="button" style="background:none;border:0;color:inherit;cursor:pointer;font-size:18px;line-height:1;padding:0 0 0 4px;opacity:.5;">&times;</button>';
            const dismiss = function () {
                t.style.opacity = '0';
                t.style.transform = 'translateX(20px)';
                setTimeout(function () { t.remove(); }, 220);
            };
            t.querySelector('button').onclick = dismiss;
            host.appendChild(t);
            requestAnimationFrame(function () { t.style.opacity='1'; t.style.transform='translateX(0)'; });
            setTimeout(dismiss, duration);
        };

        // ── 인앱 토스트 (메시지/문의 알림용 — 별도 스타일 유지) ─
        window.showToast = async function(senderName, preview, href) {
            const t = document.createElement('div');
            t.style.cssText = 'display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #ede8ff;border-radius:14px;padding:12px 16px;box-shadow:0 8px 28px rgba(139,122,240,.18);pointer-events:auto;cursor:pointer;max-width:320px;opacity:0;transform:translateY(12px);transition:all .25s;';
            t.innerHTML = `
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#c4b5fd,#9b8afb);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;">${senderName.charAt(0)}</div>
                <div style="min-width:0;">
                    <div style="font-size:13px;font-weight:700;color:#1e1b2e;">${senderName}</div>
                    <div style="font-size:12px;color:#9e97c0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:210px;">${preview}</div>
                </div>
                <svg width="14" height="14" fill="none" stroke="#c4b5fd" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>`;
            if (href) t.onclick = () => location.href = href;
            document.getElementById('toast-container').appendChild(t);
            requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; });
            setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(12px)'; setTimeout(() => t.remove(), 260); }, 4000);
        };

        // ── 사이드바 뱃지 ─────────────────────────────────────
        window.updateSidebarBadge = async function(delta, isInquiry) {
            const id = isInquiry ? 'sidebar-inquiry-badge' : 'sidebar-msg-badge';
            const el = document.getElementById(id);
            if (!el) return;
            const next = Math.max(0, (parseInt(el.textContent) || 0) + delta);
            el.textContent = next || '';
            el.style.display = next > 0 ? 'inline-block' : 'none';
        };

        // ── 알림 권한 요청 ────────────────────────────────────
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        // ── 전역 Pusher 구독 ──────────────────────────────────
        async function setupGlobalEcho() {
            const msgBase = '{{ url("/messages") }}';
            window.MY_CONV_IDS.forEach(async function(cid) {
                window.Echo.private('conversation.' + cid)
                    .listen('.MessageSent', async function(data) {
                        if (data.sender_id === window.MY_ID) return;

                        const isOpenConv = (window.OPEN_CONV_ID && cid === window.OPEN_CONV_ID);

                        // 현재 열린 대화가 아닌 경우 → 사이드바 뱃지 + 토스트
                        if (!isOpenConv) {
                            const isInquiry = window.MY_INQUIRY_CONV_IDS.has(cid);
                            window.updateSidebarBadge(+1, isInquiry);
                            const preview = data.body || (data.file_name ? '📎 ' + data.file_name : '');
                            const href = isInquiry ? '{{ url("/inquiry") }}' : msgBase + '/' + cid;
                            if (typeof window.showToast === 'function') {
                                window.showToast(data.sender_name, preview, href);
                            }
                            if ('Notification' in window && Notification.permission === 'granted' && document.visibilityState !== 'visible') {
                                try { new Notification(data.sender_name, { body: preview, tag: 'sw-' + cid }); } catch(e) {}
                            }
                        }

                        // 메시지 페이지에 커스텀 이벤트 전달 (채팅 렌더링 & 대화목록 업데이트)
                        window.dispatchEvent(new CustomEvent('newChatMessage', { detail: { cid: cid, data: data } }));
                    });
            });
        }

        if (window.Echo) { setupGlobalEcho(); }
        else { window.addEventListener('echoReady', setupGlobalEcho, { once: true }); }

        // ── 관리자 발송 메시지 수신 (개인 채널) ─────────────────
        async function setupUserChannel() {
            const userCh = window.Echo.private('user.' + window.MY_ID);

            userCh.listen('.LeaveNotification', async function(data) {
                var previewMap = {
                    leave_requested: data.leave_label + ' (' + data.date_range + ') ' + window.APP_STR.leaveRequested,
                    leave_approved:  data.leave_label + ' (' + data.date_range + ') ' + window.APP_STR.leaveApproved,
                    leave_rejected:  data.leave_label + ' (' + data.date_range + ') ' + window.APP_STR.leaveRejected,
                };
                var preview = previewMap[data.type] || data.leave_label;
                if (typeof window.showToast === 'function') {
                    window.showToast(data.actor_name, preview, data.url);
                }
            });

            userCh
                .listen('.NewAdminMessage', async function(data) {
                    const cid        = data.conv_id;
                    const adminName  = data.admin_name || window.APP_STR.adminLabel;
                    const preview    = data.body || '';
                    const inquiryUrl = '{{ url("/inquiry") }}/' + cid;

                    // 이미 구독 중인 대화: 토스트만 띄우고 종료
                    if (!window.MY_CONV_IDS.includes(cid)) {
                        // 새 conversation 채널 동적 구독
                        window.MY_CONV_IDS.push(cid);
                        window.MY_INQUIRY_CONV_IDS.add(cid);

                        window.Echo.private('conversation.' + cid)
                            .listen('.MessageSent', async function(msgData) {
                                if (msgData.sender_id === window.MY_ID) return;
                                const isOpenConv = (window.OPEN_CONV_ID && cid === window.OPEN_CONV_ID);
                                if (!isOpenConv) {
                                    window.updateSidebarBadge(+1, true);
                                    const body = msgData.body
                                        ? msgData.body.replace(/^\[관리자\s[^\]]*\]\s*/, '')
                                        : (msgData.file_name ? '📎 ' + msgData.file_name : '');
                                    if (typeof window.showToast === 'function') {
                                        window.showToast(window.APP_STR.adminLabel, body, inquiryUrl);
                                    }
                                }
                                window.dispatchEvent(new CustomEvent('newChatMessage', { detail: { cid: cid, data: msgData } }));
                            });
                    }

                    // 첫 메시지 토스트 & 뱃지
                    window.updateSidebarBadge(+1, true);
                    if (typeof window.showToast === 'function') {
                        window.showToast(adminName, preview, inquiryUrl);
                    }
                    if ('Notification' in window && Notification.permission === 'granted' && document.visibilityState !== 'visible') {
                        try { new Notification(adminName, { body: preview, tag: 'admin-msg-' + cid }); } catch(e) {}
                    }
                });
        }

        if (window.Echo) { setupUserChannel(); }
        else { window.addEventListener('echoReady', setupUserChannel, { once: true }); }

        // ── 사이드바 검색 드롭다운
        (async function() {
            const MENU = [
                { label:'{{ __("app.search_label_home") }}',      url:'{{ route("dashboard") }}',        icon:'🏠' },
                { label:'{{ __("app.nav_my_work") }}',            url:'{{ route("projects.index") }}',   icon:'📋' },
                { label:'{{ __("app.search_label_calendar") }}',  url:'{{ route("calendar") }}',         icon:'📅' },
                { label:'{{ __("app.search_label_messages") }}',  url:'{{ route("messages.index") }}',   icon:'💬' },
                { label:'{{ __("app.search_label_team") }}',      url:'{{ route("team.index") }}',       icon:'👥' },
                { label:'Teams',                                   url:'{{ route("teams.index") }}',      icon:'🔗' },
                { label:'{{ __("app.search_label_works_agent") }}',                                url:'{{ route("ai.index") }}',         icon:'🤖' },
                { label:'{{ __("app.search_label_community") }}', url:'{{ route("community.index") }}',  icon:'🌐' },
                { label:'{{ __("app.search_label_inquiry") }}',   url:'{{ route("inquiry.index") }}',    icon:'❓' },
                { label:'{{ __("app.search_label_minutes") }}',   url:'{{ route("meeting-minutes.index") }}', icon:'📋' },
                { label:'Tasks',                                   url:'{{ route("tasks.index") }}',      icon:'✅' },
                { label:'{{ __("app.nav_action_items") }}',       url:'{{ route("action-items.index") }}', icon:'⚡' },
                { label:'{{ __("app.search_label_memos") }}',     url:'{{ route("memos.index") }}',      icon:'📝' },
                { label:'{{ __("app.search_label_profile") }}',   url:'{{ route("profile.edit") }}',     icon:'👤' },
                @if(auth()->user()->isAdmin())
                { label:'{{ __("app.search_label_admin") }}',     url:'{{ route("admin.users.index") }}', icon:'⚙️' },
                @endif
                @foreach($myProjects as $proj)
                { label:'{{ addslashes($proj->name) }}', url:'{{ route("projects.show", $proj) }}', icon:'📁' },
                @endforeach
            ];
            /* SR 접수 메뉴 항목 — 사이드바 DOM에서 읽어 추가 (Blade 표현식 불필요) */
            document.querySelectorAll('#sr-menu-items a[href]').forEach(async function(a) {
                var name = (a.querySelector('.gsb-hide') || {}).textContent || '';
                if (name.trim()) MENU.push({ label: 'SR - ' + name.trim(), url: a.href, icon: '🔧' });
            });

            const input = document.getElementById('sidebar-search');
            const drop  = document.getElementById('sidebar-search-drop');
            if (!input || !drop) return;

            async function renderDrop(q) {
                if (!q) { drop.style.display = 'none'; return; }
                const matched = MENU.filter(m => m.label.toLowerCase().includes(q));
                if (!matched.length) {
                    drop.innerHTML = '<div style="padding:12px 14px;font-size:12px;color:#a1a1aa;">{{ __("app.search_no_results") }}</div>';
                } else {
                    drop.innerHTML = matched.map(m =>
                        `<a href="${m.url}" style="display:flex;align-items:center;gap:12px;padding:9px 14px;font-size:13px;color:#1e1b2e;text-decoration:none;border-radius:0;transition:background .12s;" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background=''">`+
                        `<span style="font-size:14px;width:20px;text-align:center;">${m.icon}</span>`+
                        `<span>${m.label}</span></a>`
                    ).join('');
                }
                drop.style.display = 'block';
            }

            input.addEventListener('input', async function() {
                renderDrop(this.value.trim().toLowerCase());
            });

            input.addEventListener('keydown', async function(e) {
                if (e.key === 'Escape') { this.value = ''; drop.style.display = 'none'; }
            });

            document.addEventListener('click', async function(e) {
                if (!input.contains(e.target) && !drop.contains(e.target)) {
                    drop.style.display = 'none';
                }
            });
        })();

        // ── 컬러 테마 (Phase 1) — 디자인 시스템 액센트 기반 ───────────
        // resources/assets/css/tokens.css 의 data-accent="coral|blue|green|yellow|purple" 활용
        // resources/css/app.css 가 --t* 변수를 --color-theme-active 로 브릿지함.
        function syncSwatchUI(presetName, isCustom) {
            document.querySelectorAll('.theme-swatch').forEach(function(s) {
                const active = !isCustom && s.dataset.theme === presetName;
                s.style.boxShadow = active ? '0 0 0 3px #fff, 0 0 0 5px var(--color-theme-active)' : 'none';
                s.style.transform = active ? 'scale(1.15)' : 'scale(1)';
            });
            const customWrap = document.getElementById('theme-custom-wrap');
            if (customWrap) {
                customWrap.style.boxShadow = isCustom ? '0 0 0 3px #fff, 0 0 0 5px var(--color-theme-active)' : '0 0 0 1px #e5e7eb';
                customWrap.style.transform  = isCustom ? 'scale(1.15)' : 'scale(1)';
            }
            const hexLabel = document.getElementById('theme-custom-hex');
            if (hexLabel) {
                if (isCustom) {
                    const hex = (localStorage.getItem('wsAccentHex') || '').toUpperCase();
                    if (hex) {
                        const labelText = @json(__('app.theme_custom') ?? '사용자 지정') + ': ';
                        hexLabel.innerHTML = '';
                        hexLabel.appendChild(document.createTextNode(labelText));
                        const code = document.createElement('code');
                        code.style.cssText = "font-family: ui-monospace, 'SF Mono', Consolas, monospace; font-size: inherit; color: inherit;";
                        code.textContent = hex;
                        hexLabel.appendChild(code);
                        hexLabel.style.display = 'block';
                    } else {
                        hexLabel.style.display = 'none';
                    }
                } else {
                    hexLabel.style.display = 'none';
                }
            }
        }
        window.setAccent = function(name) {
            const valid = ['coral','blue','green','yellow','purple'];
            if (!valid.includes(name)) name = 'blue';
            // 사용자 지정 색 해제
            document.documentElement.style.removeProperty('--color-theme-active');
            document.documentElement.setAttribute('data-accent', name);
            try {
                localStorage.setItem('wsAccent', name);
                localStorage.removeItem('wsAccentHex');
            } catch {}
            syncSwatchUI(name, false);
            const dd = document.getElementById('theme-dropdown'); if (dd) dd.style.display = 'none';
        };
        window.setAccentHex = function(hex) {
            if (!/^#[0-9a-fA-F]{6}$/.test(hex)) return;
            document.documentElement.setAttribute('data-accent', 'custom');
            document.documentElement.style.setProperty('--color-theme-active', hex);
            try {
                localStorage.setItem('wsAccentHex', hex);
                localStorage.removeItem('wsAccent');
            } catch {}
            const input = document.getElementById('theme-custom-input');
            if (input) input.value = hex;
            syncSwatchUI(null, true);
        };
        // 마이그레이션: 옛 'app-theme' (violet/blue/teal/...) → 새 'wsAccent' (coral/blue/green/yellow/purple)
        (function migrateOldTheme() {
            try {
                if (localStorage.getItem('wsAccent')) return;
                const old = localStorage.getItem('app-theme');
                if (!old) return;
                const map = { violet:'purple', blue:'blue', teal:'blue', green:'green', amber:'yellow', gray:'blue', white:'blue' };
                const next = map[old] || 'blue';
                localStorage.setItem('wsAccent', next);
                localStorage.removeItem('app-theme');
            } catch {}
        })();
        // 기존 theme-swatch / accent-picker 버튼이 setAccent 를 호출하도록 위임
        document.addEventListener('click', function(e) {
            const sw = e.target.closest('.theme-swatch');
            if (sw) { setAccent(sw.dataset.theme); return; }
            const ap = e.target.closest('#accent-picker-pop button[data-accent]');
            if (ap) { setAccent(ap.dataset.accent); return; }
            const dd = document.getElementById('theme-dropdown');
            const btn = document.getElementById('theme-btn');
            if (dd && btn && !btn.contains(e.target) && !dd.contains(e.target)) {
                dd.style.display = 'none';
            }
            const ld = document.getElementById('lang-dropdown');
            const lb = document.getElementById('lang-btn');
            if (ld && lb && !lb.contains(e.target) && !ld.contains(e.target)) {
                ld.style.display = 'none';
            }
            const pop = document.getElementById('accent-picker-pop');
            const apb = document.getElementById('accent-picker-btn');
            if (pop && pop.style.display === 'block' && !apb?.contains(e.target) && !pop.contains(e.target)) {
                pop.style.display = 'none';
            }
        });
        window.toggleAccentPicker = function(ev) {
            ev?.stopPropagation();
            const pop = document.getElementById('accent-picker-pop');
            if (!pop) return;
            pop.style.display = pop.style.display === 'block' ? 'none' : 'block';
        };
        // 페이지 로드 시 액센트 적용 — 사용자 지정 hex 우선, 없으면 프리셋
        (function initAccent() {
            const customHex = localStorage.getItem('wsAccentHex');
            if (customHex && /^#[0-9a-fA-F]{6}$/.test(customHex)) {
                setAccentHex(customHex);
            } else {
                setAccent(localStorage.getItem('wsAccent') || 'blue');
            }
        })();

        // ── 글로벌 사이드바 접기/펼치기 ──────────────────────
        window.toggleGlobalSidebar = async function() {
            const aside = document.getElementById('global-sidebar');
            if (!aside) return;
            const collapsed = aside.classList.toggle('gsb-collapsed');
            localStorage.setItem('gsb-collapsed', collapsed ? '1' : '0');
        };
        (async function() {
            if (localStorage.getItem('gsb-collapsed') === '1') {
                document.getElementById('global-sidebar')?.classList.add('gsb-collapsed');
            }
        })();

        // ── 섹션 접힘/펼침 (내 프로젝트 / SR 접수) ───────────
        window.toggleSection = async function(id) {
            var el = document.getElementById(id);
            var ch = document.getElementById('chevron-' + id);
            if (!el) return;
            var isCollapsed = el.style.maxHeight === '0px';
            el.style.maxHeight = isCollapsed ? '600px' : '0px';
            if (ch) ch.style.transform = isCollapsed ? '' : 'rotate(-90deg)';
            localStorage.setItem('sec-' + id, isCollapsed ? '0' : '1');
        };
        (async function() {
            ['proj-list', 'sr-list'].forEach(async function(id) {
                if (localStorage.getItem('sec-' + id) === '1') {
                    var el = document.getElementById(id);
                    if (el) el.style.maxHeight = '0px';
                    var ch = document.getElementById('chevron-' + id);
                    if (ch) ch.style.transform = 'rotate(-90deg)';
                }
            });
        })();

        // ── 전역 다운로드 프로그래스 바 ──────────────────────────
        (function () {
            function isSameOrigin(url) {
                try { return new URL(url, location.href).origin === location.origin; } catch (e) { return false; }
            }
            function isDownloadAnchor(a) {
                if (!a || a.tagName !== 'A') return false;
                const href = a.getAttribute('href') || '';
                if (!href || /^(javascript:|#|data:|blob:|mailto:)/i.test(href)) return false;
                if (!isSameOrigin(href)) return false;
                return a.hasAttribute('download') || /\/download(\/|\?|$)/i.test(href);
            }

            function createBar(el) {
                removeBar(el);
                const r = el.getBoundingClientRect();
                const d = document.createElement('div');
                d.className = 'sw-dlp';
                d.style.top   = (r.bottom + 3) + 'px';
                d.style.left  = r.left + 'px';
                d.style.width = Math.max(r.width, 72) + 'px';
                d.innerHTML   = '<div class="sw-dlp-track"><div class="sw-dlp-fill"></div></div>'
                              + '<span class="sw-dlp-pct">0%</span>';
                document.body.appendChild(d);
                el._swDlp = d;
                return d;
            }

            function removeBar(el) {
                if (el._swDlp) { el._swDlp.remove(); el._swDlp = null; }
                if (el._swDlXhr) { try { el._swDlXhr.abort(); } catch (e) {} el._swDlXhr = null; }
            }

            function setPct(el, pct) {
                if (!el._swDlp) return;
                const fill = el._swDlp.querySelector('.sw-dlp-fill');
                const pctEl = el._swDlp.querySelector('.sw-dlp-pct');
                if (fill)  { fill.classList.remove('sw-dlp-indet'); fill.style.width = pct + '%'; }
                if (pctEl) pctEl.textContent = Math.round(pct) + '%';
            }

            function setIndet(el) {
                if (!el._swDlp) return;
                const fill = el._swDlp.querySelector('.sw-dlp-fill');
                if (fill) fill.classList.add('sw-dlp-indet');
                const pctEl = el._swDlp.querySelector('.sw-dlp-pct');
                if (pctEl) pctEl.textContent = '···';
            }

            function doDownload(el, url, fname) {
                createBar(el);
                const xhr = new XMLHttpRequest();
                el._swDlXhr = xhr;
                xhr.open('GET', url, true);
                xhr.responseType = 'blob';

                let hasLen = false;
                xhr.onprogress = async function (e) {
                    if (e.lengthComputable && e.total > 0) {
                        hasLen = true;
                        setPct(el, (e.loaded / e.total) * 97);
                    } else if (!hasLen) {
                        setIndet(el);
                    }
                };

                xhr.onload = async function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        setPct(el, 100);
                        let filename = fname;
                        if (!filename) {
                            const cd = xhr.getResponseHeader('Content-Disposition') || '';
                            const m  = cd.match(/filename\*?=(?:UTF-8''|")?([^"';\r\n]+)/i);
                            if (m && m[1]) {
                                try { filename = decodeURIComponent(m[1].trim().replace(/^"|"$/g,'')); }
                                catch (e2) { filename = m[1].trim(); }
                            }
                        }
                        if (!filename) filename = url.split('/').pop().split('?')[0] || 'download';
                        const blobUrl = URL.createObjectURL(xhr.response);
                        const a = document.createElement('a');
                        a.href = blobUrl; a.download = filename;
                        document.body.appendChild(a); a.click(); document.body.removeChild(a);
                        setTimeout(async function () { URL.revokeObjectURL(blobUrl); }, 1500);
                        const pctEl = el._swDlp && el._swDlp.querySelector('.sw-dlp-pct');
                        if (pctEl) pctEl.textContent = '✓';
                        setTimeout(async function () { removeBar(el); }, 1200);
                    } else {
                        removeBar(el);
                    }
                };
                xhr.onerror = xhr.onabort = async function () { removeBar(el); };
                xhr.send();
            }

            document.addEventListener('click', async function (e) {
                const a = e.target.closest('a');
                if (!isDownloadAnchor(a)) return;
                const href = a.getAttribute('href');
                if (!href) return;
                e.preventDefault();
                doDownload(a, href, a.getAttribute('download') || '');
            }, false);
        })();
        </script>

        @yield('modals')

        {{-- 새 프로젝트 모달 (전역) --}}
        <div id="modal-new-project"
             onclick="if(event.target===this)closeNewProjectModal()"
             style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:520px;max-height:90vh;overflow-y:auto;margin:16px;">
                <div style="padding:20px 24px 16px;border-bottom:1px solid #f3f0ff;display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:15px;font-weight:700;color:#18181b;">{{ __('projects.new_project') }}</span>
                    <button onclick="closeNewProjectModal()" style="width:28px;height:28px;border:none;background:none;cursor:pointer;color:#a1a1aa;display:flex;align-items:center;justify-content:center;border-radius:6px;"
                        onmouseover="this.style.background='#f3f0ff'" onmouseout="this.style.background='none'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-new-project" onsubmit="submitNewProject(event)" style="padding:20px 24px 24px;display:flex;flex-direction:column;gap:12px;">
                    @csrf
                    <div id="np-error" style="display:none;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:13px;color:#dc2626;"></div>

                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('projects.project_name') }} <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="name" required
                               style="width:100%;padding:9px 13px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;color:#18181b;"
                               onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'"
                               placeholder="{{ __('projects.project_name_placeholder') }}">
                    </div>

                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('common.description') }}</label>
                        <textarea name="description" rows="3"
                                  style="width:100%;padding:9px 13px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;color:#18181b;resize:vertical;"
                                  onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'"
                                  placeholder="{{ __('projects.description_placeholder') }}"></textarea>
                    </div>

                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('projects.status') }} <span style="color:#ef4444;">*</span></label>
                        <select name="status"
                                style="width:100%;padding:9px 13px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;color:#18181b;"
                                onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
                            <option value="active">{{ __('projects.status_active') }}</option>
                            <option value="on_hold">{{ __('projects.status_on_hold') }}</option>
                            <option value="completed">{{ __('projects.status_completed') }}</option>
                            <option value="cancelled">{{ __('projects.status_cancelled') }}</option>
                        </select>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('projects.start_date') }}</label>
                            <input type="date" name="start_date"
                                   style="width:100%;padding:9px 13px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;color:#18181b;"
                                   onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('projects.end_date') }}</label>
                            <input type="date" name="end_date"
                                   style="width:100%;padding:9px 13px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;color:#18181b;"
                                   onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
                        </div>
                    </div>

                    <div style="border-top:1px solid #f3f0ff;padding-top:14px;">
                        <div style="font-size:12px;font-weight:600;color:#374151;margin-bottom:10px;">{{ __('projects.client_info') }}</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div>
                                <label style="display:block;font-size:11px;color:#6b7280;margin-bottom:5px;">{{ __('projects.client_name') }}</label>
                                <input type="text" name="client_name"
                                       style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;outline:none;background:#fff;color:#18181b;"
                                       onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'"
                                       placeholder="{{ __('projects.client_name_placeholder') }}">
                            </div>
                            <div>
                                <label style="display:block;font-size:11px;color:#6b7280;margin-bottom:5px;">{{ __('projects.client_email') }}</label>
                                <input type="email" name="client_email"
                                       style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;outline:none;background:#fff;color:#18181b;"
                                       onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'"
                                       placeholder="client@example.com">
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;gap:12px;padding-top:4px;">
                        <button type="submit" id="np-submit-btn"
                                style="flex:1;padding:10px;background:var(--t600);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;"
                                onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">
                            {{ __('projects.create_project') }}
                        </button>
                        <button type="button" onclick="closeNewProjectModal()"
                                style="padding:10px 20px;border:1.5px solid #e5e7eb;background:#fff;border-radius:9px;font-size:13px;color:#6b7280;cursor:pointer;"
                                onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">
                            {{ __('common.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        async function openNewProjectModal() {
            const modal = document.getElementById('modal-new-project');
            modal.style.display = 'flex';
            document.getElementById('form-new-project').reset();
            document.getElementById('np-error').style.display = 'none';
            setTimeout(() => modal.querySelector('input[name="name"]').focus(), 50);
        }
        async function closeNewProjectModal() {
            document.getElementById('modal-new-project').style.display = 'none';
        }
        async function submitNewProject(e) {
            e.preventDefault();
            const form   = document.getElementById('form-new-project');
            const btn    = document.getElementById('np-submit-btn');
            const errBox = document.getElementById('np-error');
            errBox.style.display = 'none';
            const fd = new FormData(form);
            btn.disabled    = true;
            btn.textContent = '...';
            try {
                const res  = await fetch('{{ route("projects.store") }}', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await res.json();
                if (data.ok && data.redirect) {
                    location.href = data.redirect;
                } else {
                    const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || @json(__('common.error')));
                    errBox.textContent   = msgs;
                    errBox.style.display = 'block';
                    btn.disabled    = false;
                    btn.textContent = '{{ __("projects.create_project") }}';
                }
            } catch (err) {
                errBox.textContent   = @json(__('app.error_retry'));
                errBox.style.display = 'block';
                btn.disabled    = false;
                btn.textContent = '{{ __("projects.create_project") }}';
            }
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNewProjectModal(); });
        </script>

        @yield('scripts')
        @stack('scripts')
        <script>
        (async function() {
            var ANN_KEY = 'sw_ann_dismissed';

            function getDismissed() {
                try { return JSON.parse(localStorage.getItem(ANN_KEY) || '[]'); } catch(e) { return []; }
            }

            function saveDismissed(list) {
                localStorage.setItem(ANN_KEY, JSON.stringify(list));
            }

            window.dismissAnn = async function(id) {
                id = parseInt(id);
                var list = getDismissed();
                if (list.indexOf(id) === -1) { list.push(id); saveDismissed(list); }
                // 배너 숨김
                document.querySelectorAll('[data-announcement-id="'+id+'"]').forEach(async function(el){ el.style.display='none'; });
                // 드롭다운 아이템 흐리게
                var di = document.querySelector('[data-ann-drop="'+id+'"]');
                if (di) { di.style.opacity='0.35'; di.style.pointerEvents='none'; }
                updateBadge();
            };

            window.dismissAllAnn = async function() {
                var list = getDismissed();
                document.querySelectorAll('[data-ann-drop]').forEach(async function(el){
                    var id = parseInt(el.dataset.annDrop);
                    if (list.indexOf(id)===-1) list.push(id);
                    el.style.opacity='0.35'; el.style.pointerEvents='none';
                });
                document.querySelectorAll('[data-announcement-id]').forEach(async function(el){ el.style.display='none'; });
                saveDismissed(list);
                updateBadge();
            };

            window.toggleAnnDropdown = async function() {
                var drop = document.getElementById('ann-dropdown');
                var isOpen = drop.style.display === 'flex';
                // 다른 드롭다운 닫기
                ['lang-dropdown','theme-dropdown'].forEach(async function(id){
                    var el = document.getElementById(id); if(el) el.style.display='none';
                });
                drop.style.display = isOpen ? 'none' : 'flex';
            };

            async function updateBadge() {
                var dismissed = getDismissed();
                var items = document.querySelectorAll('[data-ann-drop]');
                var unread = 0;
                items.forEach(async function(el){ if (dismissed.indexOf(parseInt(el.dataset.annDrop))===-1) unread++; });
                var badge = document.getElementById('ann-badge');
                if (badge) { badge.textContent = unread; badge.style.display = unread > 0 ? 'flex' : 'none'; }
            }

            document.addEventListener('DOMContentLoaded', async function() {
                var dismissed = getDismissed();
                // 이미 닫은 배너 즉시 숨김
                dismissed.forEach(async function(id) {
                    document.querySelectorAll('[data-announcement-id="'+id+'"]').forEach(async function(el){ el.style.display='none'; });
                    var di = document.querySelector('[data-ann-drop="'+id+'"]');
                    if (di) { di.style.opacity='0.35'; di.style.pointerEvents='none'; }
                });
                updateBadge();

                // 외부 클릭 시 드롭다운 닫기
                document.addEventListener('click', async function(e) {
                    var wrap = document.getElementById('ann-icon-wrap');
                    if (wrap && !wrap.contains(e.target)) {
                        var drop = document.getElementById('ann-dropdown');
                        if (drop) drop.style.display = 'none';
                    }
                });
            });
        })();
        </script>
        <script>
        if (typeof window.MAINTENANCE_KEY === 'undefined') {
            window.MAINTENANCE_KEY   = '{{ addslashes(request()->route()?->getName() ?? '') }}';
            window.MAINTENANCE_NAME  = window.MAINTENANCE_KEY;
            window.MAINTENANCE_BLADE = '';
        }
        </script>

        {{-- ===== 메모 시스템 JS ===== --}}
        <script>
        (async function() {
            var CSRF     = document.querySelector('meta[name="csrf-token"]').content;
            var MEMO_URL = '{{ url("/memos") }}';
            var MC = {
                yellow: { bg:'#fef9c3', border:'#fde047', header:'#fef08a' },
                green:  { bg:'#dcfce7', border:'#86efac', header:'#bbf7d0' },
                blue:   { bg:'#dbeafe', border:'#93c5fd', header:'#bfdbfe' },
                pink:   { bg:'#fce7f3', border:'#f9a8d4', header:'#fbcfe8' },
                purple: { bg:'#ede9fe', border:'#c4b5fd', header:'#ddd6fe' },
            };
            var MT = {
                loading:        @json(__('app.loading')),
                no_memos:       @json(__('app.memo_empty')),
                no_memos_hint:  @json(__('app.memo_empty_hint')),
                error:          @json(__('common.error')),
                shared_memos:   @json(__('app.memo_shared_received')),
                shared_members: @json(__('app.memo_shared_members')),
                memo:           @json(__('app.nav_memos')),
                unpin:          @json(__('app.memo_unpin')),
                pin:            @json(__('app.memo_pin')),
                share:          @json(__('common.share')),
                del:            @json(__('common.delete')),
                edit_hint:      @json(__('app.memo_edit_hint')),
                resize:         @json(__('app.memo_resize')),
                confirm_del:    @json(__('app.memo_confirm_delete')),
                confirm_del_shared: @json(__('app.memo_confirm_remove_shared')),
                share_loading:  @json(__('app.loading')),
                no_share_members: @json(__('app.memo_no_share_members')),
                shared_now:     @json(__('app.memo_shared_now')),
                count_selected: @json(__('app.memo_count_selected')),
                sharing:        @json(__('app.memo_sharing')),
                share_confirm:  @json(__('app.memo_share_confirm')),
                saving:         @json(__('app.saving_status')),
                saved:          @json(__('app.saved_status')),
                save_failed:    @json(__('app.save_failed_status')),
                shared_by:      @json(__('app.shared_suffix')),
            };

            function esc(s) {
                return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            }

            // ── 팝업 토글 ──────────────────────────────────────
            window.memoPopupToggle = async function() {
                var p = document.getElementById('memo-popup');
                if (!p) return;
                if (p.style.display === 'none' || p.style.display === '') {
                    p.style.display = 'flex';
                    memoLoadList();
                } else {
                    memoPopupClose();
                }
            };

            window.memoPopupClose = async function() {
                var p = document.getElementById('memo-popup');
                if (p) p.style.display = 'none';
                memoHideAddForm();
            };

            // ── 추가 폼 ────────────────────────────────────────
            window.memoShowAddForm = async function() {
                var f = document.getElementById('memo-add-form');
                if (!f) return;
                f.style.display = 'block';
                var dots = document.querySelectorAll('.memo-color-dot');
                var hasSelected = false;
                dots.forEach(async function(d) { if (d.classList.contains('memo-selected')) hasSelected = true; });
                if (!hasSelected && dots.length) memoSelectColor(dots[0]);
                setTimeout(async function() { document.getElementById('memo-input-content').focus(); }, 50);
            };

            window.memoHideAddForm = async function() {
                var f = document.getElementById('memo-add-form');
                if (!f) return;
                f.style.display = 'none';
                document.getElementById('memo-input-title').value = '';
                document.getElementById('memo-input-content').value = '';
                document.getElementById('memo-input-content').style.borderColor = '#e5e7eb';
                document.querySelectorAll('.memo-color-dot').forEach(async function(d) {
                    d.classList.remove('memo-selected');
                    d.style.transform = 'scale(1)';
                    d.style.boxShadow = 'none';
                    d.style.borderColor = 'transparent';
                });
            };

            window.memoSelectColor = async function(btn) {
                document.querySelectorAll('.memo-color-dot').forEach(async function(d) {
                    d.classList.remove('memo-selected');
                    d.style.transform = 'scale(1)';
                    d.style.boxShadow = 'none';
                    d.style.borderColor = 'transparent';
                });
                btn.classList.add('memo-selected');
                btn.style.transform = 'scale(1.25)';
                btn.style.boxShadow = '0 0 0 2px #fff, 0 0 0 4px #7c3aed';
                btn.style.borderColor = '#fff';
            };

            // ── 목록 로드 ──────────────────────────────────────
            async function memoLoadList() {
                var list = document.getElementById('memo-list');
                if (!list) return;
                list.innerHTML = '<div style="text-align:center;padding:28px;color:#a1a1aa;font-size:13px;">' + esc(MT.loading) + '</div>';
                fetch(MEMO_URL, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                }).then(async function(r) { return r.json(); }).then(async function(data) {
                    var mine   = data.mine   || [];
                    var shared = data.shared || [];
                    var all    = mine.concat(shared);
                    window._memoCache = {};
                    all.forEach(async function(m) { window._memoCache[m.id] = m; });
                    if (!all.length) {
                        list.innerHTML = '<div style="text-align:center;padding:34px 20px;color:#a1a1aa;font-size:13px;line-height:1.7;">' + esc(MT.no_memos) + '<br><span style="font-size:12px;">' + esc(MT.no_memos_hint) + '</span></div>';
                        return;
                    }
                    var html = '';
                    if (mine.length) html += mine.map(function(m) { return renderMemoCard(m); }).join('');
                    if (shared.length) {
                        html += '<div style="margin:8px 0 4px;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;">' + esc(MT.shared_memos) + '</div>';
                        html += shared.map(function(m) { return renderMemoCard(m); }).join('');
                    }
                    list.innerHTML = html;
                }).catch(async function() {
                    list.innerHTML = '<div style="text-align:center;padding:28px;color:#ef4444;font-size:13px;">' + esc(MT.error) + '</div>';
                });
            }

            function renderMemoCard(m) {
                var c = MC[m.color] || MC.yellow;
                var titleHtml = m.title ? '<div style="font-weight:600;font-size:13px;color:#18181b;margin-bottom:5px;">' + esc(m.title) + '</div>' : '';
                var preview = m.content.length > 130 ? esc(m.content.slice(0, 130)) + '…' : esc(m.content);

                // 공유 받은 메모 뱃지
                var receivedBadge = '';
                if (m.is_received) {
                    receivedBadge = '<div style="display:flex;align-items:center;gap:4px;margin-bottom:7px;">'
                        + '<span style="display:inline-flex;align-items:center;gap:4px;background:#ede9fe;color:#7c3aed;border-radius:20px;padding:2px 8px;font-size:11px;font-weight:600;">'
                        + '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>'
                        + esc(m.shared_by_name) + ' · ' + esc(m.shared_at)
                        + '</span>'
                        + '</div>';
                }

                // 공유된 멤버 아바타 (내 메모)
                var sharedAvatars = '';
                if (!m.is_received && m.shared_with && m.shared_with.length) {
                    sharedAvatars = '<div style="display:flex;align-items:center;gap:4px;margin-right:4px;" title="' + esc(MT.shared_members) + '">';
                    m.shared_with.slice(0, 3).forEach(async function(u) {
                        var letter = (u.name || '?').charAt(0).toUpperCase();
                        sharedAvatars += '<span style="width:20px;height:20px;border-radius:50%;background:var(--t500);color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;border:1.5px solid #fff;">' + esc(letter) + '</span>';
                    });
                    if (m.shared_with.length > 3) sharedAvatars += '<span style="font-size:10px;color:#7c3aed;font-weight:600;">+' + (m.shared_with.length - 3) + '</span>';
                    sharedAvatars += '</div>';
                }

                var pinFill  = m.is_pinned ? 'currentColor' : 'none';
                var pinBg    = m.is_pinned ? '#fde047' : 'transparent';
                var pinCol   = m.is_pinned ? '#92400e' : '#9ca3af';
                var pinTitle = m.is_pinned ? MT.unpin : MT.pin;

                // 공유 버튼 (내 메모에만)
                var shareBtnHtml = '';
                if (!m.is_received) {
                    var shareActive = m.shared_with && m.shared_with.length > 0;
                    var shareBg     = shareActive ? 'rgba(124,58,237,.12)' : 'transparent';
                    var shareCol    = shareActive ? '#7c3aed' : '#9ca3af';
                    shareBtnHtml = '<button onclick="memoShareOpen(' + m.id + ')" title="' + esc(MT.share) + '" '
                        + 'style="width:26px;height:26px;border-radius:6px;border:none;background:' + shareBg + ';cursor:pointer;display:flex;align-items:center;justify-content:center;color:' + shareCol + ';transition:background .12s,color .12s;" '
                        + 'onmouseover="this.style.background=\'rgba(124,58,237,.15)\';this.style.color=\'#7c3aed\'" '
                        + 'onmouseout="this.style.background=\'' + shareBg + '\';this.style.color=\'' + shareCol + '\'">'
                        + '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>'
                        + '</button>';
                }

                var sharedWithJson = (!m.is_received && m.shared_with) ? encodeURIComponent(JSON.stringify(m.shared_with)) : '';
                return '<div class="memo-card" data-id="' + m.id + '" data-shared-with="' + sharedWithJson + '" draggable="true" ondragstart="memoDragStart(event,' + m.id + ')" style="cursor:grab;background:' + c.bg + ';border:1.5px solid ' + c.border + ';border-radius:10px;padding:12px;margin-bottom:8px;">'
                    + receivedBadge
                    + titleHtml
                    + '<div style="font-size:12.5px;color:#374151;line-height:1.55;white-space:pre-wrap;word-break:break-word;">' + preview + '</div>'
                    + '<div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;">'
                    +   '<span style="font-size:11px;color:#9ca3af;">' + esc(m.updated_at) + '</span>'
                    +   '<div style="display:flex;align-items:center;gap:4px;">'
                    +   sharedAvatars
                    +   shareBtnHtml
                    +   (m.is_received
                        ? '<button onclick="memoToggleSharedPin(' + m.share_id + ',' + m.id + ')" title="' + pinTitle + '" '
                        +   'style="width:26px;height:26px;border-radius:6px;border:none;background:' + pinBg + ';cursor:pointer;display:flex;align-items:center;justify-content:center;color:' + pinCol + ';transition:background .12s;" '
                        +   'onmouseover="this.style.background=\'#fde047\';this.style.color=\'#92400e\'" '
                        +   'onmouseout="this.style.background=\'' + pinBg + '\';this.style.color=\'' + pinCol + '\'">'
                        +   '<svg width="13" height="13" viewBox="0 0 24 24" fill="' + pinFill + '" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>'
                        +   '</button>'
                        : '<button onclick="memoTogglePin(' + m.id + ')" title="' + pinTitle + '" '
                        +   'style="width:26px;height:26px;border-radius:6px;border:none;background:' + pinBg + ';cursor:pointer;display:flex;align-items:center;justify-content:center;color:' + pinCol + ';transition:background .12s;" '
                        +   'onmouseover="this.style.background=\'#fde047\';this.style.color=\'#92400e\'" '
                        +   'onmouseout="this.style.background=\'' + pinBg + '\';this.style.color=\'' + pinCol + '\'">'
                        +   '<svg width="13" height="13" viewBox="0 0 24 24" fill="' + pinFill + '" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>'
                        +   '</button>'
                    )
                    +     '<button onclick="memoDelete(' + m.id + ')" title="' + esc(MT.del) + '" '
                    +       'style="width:26px;height:26px;border-radius:6px;border:none;background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#9ca3af;transition:background .12s,color .12s;" '
                    +       'onmouseover="this.style.background=\'#fee2e2\';this.style.color=\'#ef4444\'" '
                    +       'onmouseout="this.style.background=\'transparent\';this.style.color=\'#9ca3af\'">'
                    +       '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                    +     '</button>'
                    +   '</div>'
                    + '</div>'
                    + '</div>';
            }

            // ── 메모 저장 ──────────────────────────────────────
            window.memoSave = async function() {
                var title   = document.getElementById('memo-input-title').value.trim();
                var content = document.getElementById('memo-input-content').value.trim();
                var sel     = document.querySelector('.memo-color-dot.memo-selected');
                var color   = sel ? sel.dataset.color : 'yellow';
                if (!content) {
                    document.getElementById('memo-input-content').style.borderColor = '#ef4444';
                    document.getElementById('memo-input-content').focus();
                    return;
                }
                fetch(MEMO_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify({ title: title, content: content, color: color })
                }).then(async function(r) {
                    if (r.ok) { memoHideAddForm(); memoLoadList(); }
                });
            };

            // ── 고정 토글 ──────────────────────────────────────
            window.memoTogglePin = async function(id) {
                fetch(MEMO_URL + '/' + id + '/pin', {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                }).then(async function(r) { return r.json(); }).then(async function(memo) {
                    memoLoadList();
                    memoUpdatePinnedNote(memo);
                });
            };

            // ── 메모 드래그 앤 드롭 ────────────────────────────
            var _memoDragId = null;

            window.memoDragStart = async function(e, id) {
                _memoDragId = id;
                e.dataTransfer.effectAllowed = 'copy';
                e.dataTransfer.setData('text/plain', String(id));
                var card = e.currentTarget;
                setTimeout(async function() { if (card) card.style.opacity = '0.45'; }, 0);
            };

            document.addEventListener('dragend', async function(e) {
                if (e.target.classList && e.target.classList.contains('memo-card')) {
                    e.target.style.opacity = '';
                    e.target.style.cursor = 'grab';
                }
                _memoDragId = null;
                var hint = document.getElementById('memo-drop-hint');
                if (hint) hint.style.display = 'none';
            });

            document.addEventListener('dragover', async function(e) {
                if (!_memoDragId) return;
                var popup = document.getElementById('memo-popup');
                if (popup && popup.contains(e.target)) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                var hint = document.getElementById('memo-drop-hint');
                if (hint) hint.style.display = 'flex';
            });

            document.addEventListener('dragleave', async function(e) {
                if (e.clientX <= 0 || e.clientY <= 0 || e.clientX >= window.innerWidth || e.clientY >= window.innerHeight) {
                    var hint = document.getElementById('memo-drop-hint');
                    if (hint) hint.style.display = 'none';
                }
            });

            document.addEventListener('drop', async function(e) {
                var hint = document.getElementById('memo-drop-hint');
                if (hint) hint.style.display = 'none';
                var popup = document.getElementById('memo-popup');
                if (popup && popup.contains(e.target)) return;
                if (!_memoDragId) return;
                e.preventDefault();

                var id = _memoDragId;
                _memoDragId = null;
                var memo = window._memoCache && window._memoCache[id];
                if (!memo) return;

                var dropX = e.clientX;
                var dropY = e.clientY;

                if (!memo.is_pinned) {
                    fetch(MEMO_URL + '/' + id + '/pin', {
                        method: 'PATCH',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                    }).then(async function(r) { return r.json(); }).then(async function(pinned) {
                        memoPinAtPosition(pinned, dropX, dropY);
                        memoLoadList();
                    });
                } else {
                    memoPinAtPosition(memo, dropX, dropY);
                }
            });

            async function memoPinAtPosition(memo, x, y) {
                var existing = document.querySelector('.pinned-memo-note[data-id="' + memo.id + '"]');
                var lx = Math.max(10, Math.min(x - 115, window.innerWidth  - 250));
                var ty = Math.max(10, Math.min(y - 20,  window.innerHeight - 150));
                if (existing) {
                    existing.style.left   = lx + 'px';
                    existing.style.top    = ty + 'px';
                    existing.style.right  = 'auto';
                    existing.style.bottom = 'auto';
                    return;
                }
                var c = MC[memo.color] || MC.yellow;
                var div = document.createElement('div');
                div.className  = 'pinned-memo-note';
                div.dataset.id = memo.id;
                div.style.cssText = 'position:fixed;left:' + lx + 'px;top:' + ty + 'px;z-index:9988;background:' + c.bg + ';border:1.5px solid ' + c.border + ';border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.12);width:230px;display:flex;flex-direction:column;overflow:hidden;';
                div.innerHTML =
                    '<div class="pinned-memo-header" style="padding:6px 8px;background:' + c.header + ';border-bottom:1px solid ' + c.border + ';border-radius:10px 10px 0 0;display:flex;align-items:center;gap:4px;cursor:grab;user-select:none;flex-shrink:0;">'
                    + '<span style="font-size:11.5px;font-weight:600;color:#374151;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;">' + esc(memo.title || MT.memo) + '</span>'
                    + '<div style="display:flex;gap:4px;flex-shrink:0;" onclick="event.stopPropagation()">'
                    +   '<button onclick="memoTogglePin(' + memo.id + ')" title="' + esc(MT.unpin) + '" style="width:22px;height:22px;border:none;background:rgba(0,0,0,.06);cursor:pointer;color:#6b7280;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:all .12s;" onmouseover="this.style.background=\'#fde047\';this.style.color=\'#92400e\'" onmouseout="this.style.background=\'rgba(0,0,0,.06)\';this.style.color=\'#6b7280\'">'
                    +     '<svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>'
                    +   '</button>'
                    +   '<button onclick="memoDelete(' + memo.id + ')" title="' + esc(MT.del) + '" style="width:22px;height:22px;border:none;background:rgba(0,0,0,.06);cursor:pointer;color:#6b7280;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:all .12s;" onmouseover="this.style.background=\'#fee2e2\';this.style.color=\'#ef4444\'" onmouseout="this.style.background=\'rgba(0,0,0,.06)\';this.style.color=\'#6b7280\'">'
                    +     '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                    +   '</button>'
                    + '</div>'
                    + '</div>'
                    + '<div class="pinned-memo-body" style="flex:1;padding:8px 10px;font-size:12px;color:#374151;line-height:1.55;overflow-y:auto;white-space:pre-wrap;word-break:break-word;min-height:60px;cursor:text;" title="' + esc(MT.edit_hint) + '">' + esc(memo.content) + '</div>'
                    + '<div class="pinned-memo-resize" title="' + esc(MT.resize) + '" style="position:absolute;right:0;bottom:0;width:16px;height:16px;cursor:se-resize;display:flex;align-items:center;justify-content:center;opacity:0.45;">'
                    +   '<svg width="9" height="9" viewBox="0 0 9 9" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="1" x2="1" y2="8"/><line x1="8" y1="5" x2="5" y2="8"/></svg>'
                    + '</div>';
                document.body.appendChild(div);
                makeDraggable(div);
                makeResizable(div);
                setupNoteAutoSave(div);
            }

            // ── 공유 받은 메모 고정 토글 ────────────────────────
            window.memoToggleSharedPin = async function(shareId, memoId) {
                fetch('{{ url("/memo-shares") }}/' + shareId + '/pin', {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                }).then(async function(r) { return r.json(); }).then(async function(memo) {
                    memoLoadList();
                    memoUpdatePinnedSharedNote(memo);
                });
            };

            // ── 삭제 ───────────────────────────────────────────
            window.memoDelete = async function(id) {
                if (!await __confirm(MT.confirm_del)) return;
                fetch(MEMO_URL + '/' + id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                }).then(async function(r) {
                    if (r.ok) {
                        memoLoadList();
                        var note = document.querySelector('.pinned-memo-note[data-id="' + id + '"]');
                        if (note) note.remove();
                    }
                });
            };

            // ── 메모 공유 ──────────────────────────────────────
            var _shareTargetId  = null;
            var _shareAllMembers = [];
            var _shareSelected  = {};   // { userId: true/false }
            var _shareAlreadyShared = {}; // { userId: true } — 기존 공유 멤버

            window.memoShareOpen = async function(id) {
                _shareTargetId = id;
                _shareSelected = {};
                _shareAlreadyShared = {};
                document.getElementById('memo-share-search').value = '';
                document.getElementById('memo-share-member-list').innerHTML =
                    '<div style="text-align:center;padding:24px;color:#a1a1aa;font-size:13px;">' + esc(MT.loading) + '</div>';
                document.getElementById('memo-share-modal').style.display = 'flex';

                // 현재 카드의 기존 공유 멤버 파악
                var card = document.querySelector('.memo-card[data-id="' + id + '"]');
                // 서버에서 최신 데이터 fetch
                fetch(MEMO_URL + '/members', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
                    .then(async function(r) { return r.json(); })
                    .then(async function(members) {
                        _shareAllMembers = members;
                        // 기존 공유 상태는 카드 data에서 읽는다 — memoLoadList 후 DOM에 저장
                        var existingEl = document.querySelector('.memo-card[data-id="' + id + '"]');
                        if (existingEl && existingEl.dataset.sharedWith) {
                            try {
                                JSON.parse(decodeURIComponent(existingEl.dataset.sharedWith)).forEach(async function(u) {
                                    _shareAlreadyShared[u.user_id] = true;
                                    _shareSelected[u.user_id]      = true;
                                });
                            } catch(e) {}
                        }
                        memoShareRenderMembers(members);
                    });
            };

            async function memoShareRenderMembers(members) {
                var list = document.getElementById('memo-share-member-list');
                if (!members.length) {
                    list.innerHTML = '<div style="text-align:center;padding:24px;color:#a1a1aa;font-size:13px;">' + esc(MT.no_share_members) + '</div>';
                    return;
                }
                list.innerHTML = members.map(async function(u) {
                    var checked  = _shareSelected[u.id] ? true : false;
                    var isOld    = _shareAlreadyShared[u.id] ? true : false;
                    var letter   = (u.name || '?').charAt(0).toUpperCase();
                    var checkStyle = checked
                        ? 'background:var(--t600);border-color:var(--t600);'
                        : 'background:#fff;border-color:#d1d5db;';
                    return '<div class="memo-share-member-row" data-uid="' + u.id + '" onclick="memoShareToggle(' + u.id + ',this)" '
                        + 'style="display:flex;align-items:center;gap:12px;padding:10px 16px;cursor:pointer;transition:background .1s;" '
                        + 'onmouseover="this.style.background=\'#fafaff\'" onmouseout="this.style.background=\'#fff\'">'
                        + '<div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--t300),var(--t500));display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;">' + esc(letter) + '</div>'
                        + '<div style="flex:1;min-width:0;">'
                        +   '<div style="font-size:13px;font-weight:600;color:#18181b;">' + esc(u.name) + '</div>'
                        +   '<div style="font-size:11.5px;color:#9ca3af;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + esc(u.email) + '</div>'
                        + '</div>'
                        + (isOld ? '<span style="font-size:10.5px;color:#7c3aed;font-weight:600;background:#ede9fe;border-radius:20px;padding:2px 8px;margin-right:6px;">' + esc(MT.shared_now) + '</span>' : '')
                        + '<div class="memo-share-checkbox" style="width:20px;height:20px;border-radius:5px;border:2px solid #d1d5db;display:flex;align-items:center;justify-content:center;transition:all .12s;flex-shrink:0;' + checkStyle + '">'
                        + (checked ? '<svg width="11" height="11" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' : '')
                        + '</div>'
                        + '</div>';
                }).join('');
                memoShareUpdateCount();
            }

            window.memoShareToggle = async function(uid, row) {
                _shareSelected[uid] = !_shareSelected[uid];
                var cb = row.querySelector('.memo-share-checkbox');
                if (_shareSelected[uid]) {
                    cb.style.background = 'var(--t600)';
                    cb.style.borderColor = 'var(--t600)';
                    cb.innerHTML = '<svg width="11" height="11" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
                } else {
                    cb.style.background = '#fff';
                    cb.style.borderColor = '#d1d5db';
                    cb.innerHTML = '';
                }
                memoShareUpdateCount();
            };

            async function memoShareUpdateCount() {
                var cnt = Object.values(_shareSelected).filter(Boolean).length;
                var el = document.getElementById('memo-share-count');
                el.textContent = cnt > 0 ? MT.count_selected.replace(':count', cnt) : '';
            }

            window.memoShareFilterMembers = async function(q) {
                var lower = q.trim().toLowerCase();
                var filtered = lower
                    ? _shareAllMembers.filter(async function(u) {
                        return u.name.toLowerCase().includes(lower) || u.email.toLowerCase().includes(lower);
                      })
                    : _shareAllMembers;
                memoShareRenderMembers(filtered);
            };

            window.memoShareModalClose = async function() {
                document.getElementById('memo-share-modal').style.display = 'none';
                _shareTargetId = null;
            };

            window.memoShareConfirm = async function() {
                var newIds = Object.keys(_shareSelected).filter(async function(uid) {
                    return _shareSelected[uid] && !_shareAlreadyShared[uid];
                }).map(Number);
                var removeIds = Object.keys(_shareAlreadyShared).filter(async function(uid) {
                    return !_shareSelected[uid];
                }).map(Number);

                if (!newIds.length && !removeIds.length) {
                    memoShareModalClose();
                    return;
                }

                var btn = document.getElementById('memo-share-confirm-btn');
                btn.disabled = true;
                btn.textContent = MT.sharing;

                var tasks = [];
                if (newIds.length) {
                    tasks.push(fetch(MEMO_URL + '/' + _shareTargetId + '/share', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ user_ids: newIds })
                    }).then(async function(r) { return r.json(); }));
                }
                removeIds.forEach(async function(uid) {
                    tasks.push(fetch(MEMO_URL + '/' + _shareTargetId + '/share', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ user_id: uid })
                    }).then(async function(r) { return r.json(); }));
                });

                Promise.all(tasks).then(async function() {
                    btn.disabled = false;
                    btn.textContent = MT.share_confirm;
                    memoShareModalClose();
                    memoLoadList();
                });
            };

            // 공유 받은 메모 삭제 (share 레코드만 제거)
            window.memoDeleteShared = async function(shareId) {
                if (!await __confirm(MT.confirm_del_shared)) return;
                // share_id 기반 삭제는 별도 엔드포인트 없이 간단히 목록 갱신
                memoLoadList();
            };

            // ── 고정 메모 floating 업데이트 ────────────────────
            async function memoUpdatePinnedNote(memo) {
                var existing = document.querySelector('.pinned-memo-note[data-id="' + memo.id + '"]');
                if (memo.is_pinned) {
                    if (existing) return;
                    var c = MC[memo.color] || MC.yellow;
                    var div = document.createElement('div');
                    div.className = 'pinned-memo-note';
                    div.dataset.id = memo.id;
                    // 기본 위치: 현재 고정 노트 수 기반으로 우하단 스택
                    var noteCount = document.querySelectorAll('.pinned-memo-note').length;
                    div.style.cssText = 'position:fixed;right:24px;bottom:' + (80 + noteCount * 230) + 'px;z-index:9988;background:' + c.bg + ';border:1.5px solid ' + c.border + ';border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.12);width:230px;display:flex;flex-direction:column;overflow:hidden;';
                    div.innerHTML =
                        '<div class="pinned-memo-header" style="padding:6px 8px;background:' + c.header + ';border-bottom:1px solid ' + c.border + ';border-radius:10px 10px 0 0;display:flex;align-items:center;gap:4px;cursor:grab;user-select:none;flex-shrink:0;">'
                        + '<span style="font-size:11.5px;font-weight:600;color:#374151;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;">' + esc(memo.title || MT.memo) + '</span>'
                        + '<div style="display:flex;gap:4px;flex-shrink:0;" onclick="event.stopPropagation()">'
                        +   '<button onclick="memoTogglePin(' + memo.id + ')" title="' + esc(MT.unpin) + '" style="width:22px;height:22px;border:none;background:rgba(0,0,0,.06);cursor:pointer;color:#6b7280;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:all .12s;" onmouseover="this.style.background=\'#fde047\';this.style.color=\'#92400e\'" onmouseout="this.style.background=\'rgba(0,0,0,.06)\';this.style.color=\'#6b7280\'">'
                        +     '<svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>'
                        +   '</button>'

                        +   '<button onclick="memoDelete(' + memo.id + ')" title="' + esc(MT.del) + '" style="width:22px;height:22px;border:none;background:rgba(0,0,0,.06);cursor:pointer;color:#6b7280;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:all .12s;" onmouseover="this.style.background=\'#fee2e2\';this.style.color=\'#ef4444\'" onmouseout="this.style.background=\'rgba(0,0,0,.06)\';this.style.color=\'#6b7280\'">'
                        +     '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                        +   '</button>'
                        + '</div>'
                        + '</div>'
                        + '<div class="pinned-memo-body" style="flex:1;padding:8px 10px;font-size:12px;color:#374151;line-height:1.55;overflow-y:auto;white-space:pre-wrap;word-break:break-word;min-height:60px;cursor:text;" title="' + esc(MT.edit_hint) + '">' + esc(memo.content) + '</div>'
                        + '<div class="pinned-memo-resize" title="' + esc(MT.resize) + '" style="position:absolute;right:0;bottom:0;width:16px;height:16px;cursor:se-resize;display:flex;align-items:center;justify-content:center;opacity:0.45;">'
                        +   '<svg width="9" height="9" viewBox="0 0 9 9" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="1" x2="1" y2="8"/><line x1="8" y1="5" x2="5" y2="8"/></svg>'
                        + '</div>';
                    document.body.appendChild(div);
                    makeDraggable(div);
                    makeResizable(div);
                    setupNoteAutoSave(div);
                } else {
                    if (existing) existing.remove();
                    localStorage.removeItem('memo-pos-' + memo.id);
                }
            }

            // ── 공유 받은 메모 floating 업데이트 ──────────────
            async function memoUpdatePinnedSharedNote(memo) {
                // data-share-id 로 조회
                var existing = document.querySelector('.pinned-memo-note[data-share-id="' + memo.share_id + '"]');
                if (memo.is_pinned) {
                    if (existing) return;
                    var c = MC[memo.color] || MC.yellow;
                    var div = document.createElement('div');
                    div.className = 'pinned-memo-note';
                    div.dataset.id      = memo.id;
                    div.dataset.shareId = memo.share_id;
                    var noteCount = document.querySelectorAll('.pinned-memo-note').length;
                    div.style.cssText = 'position:fixed;right:24px;bottom:' + (80 + noteCount * 230) + 'px;z-index:9988;background:' + c.bg + ';border:1.5px solid ' + c.border + ';border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.12);width:230px;display:flex;flex-direction:column;overflow:hidden;';
                    div.innerHTML =
                        '<div class="pinned-memo-header" style="padding:6px 8px;background:' + c.header + ';border-bottom:1px solid ' + c.border + ';border-radius:10px 10px 0 0;display:flex;align-items:center;gap:4px;cursor:grab;user-select:none;flex-shrink:0;">'
                        + '<span style="font-size:11.5px;font-weight:600;color:#374151;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;">' + esc(memo.title || MT.memo) + '</span>'
                        + '<div style="display:flex;gap:4px;flex-shrink:0;" onclick="event.stopPropagation()">'
                        +   '<button onclick="memoToggleSharedPin(' + memo.share_id + ',' + memo.id + ')" title="' + esc(MT.unpin) + '" style="width:22px;height:22px;border:none;background:rgba(0,0,0,.06);cursor:pointer;color:#6b7280;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:all .12s;" onmouseover="this.style.background=\'#fde047\';this.style.color=\'#92400e\'" onmouseout="this.style.background=\'rgba(0,0,0,.06)\';this.style.color=\'#6b7280\'">'
                        +     '<svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>'
                        +   '</button>'
                        + '</div>'
                        + '</div>'
                        + '<div style="padding:3px 10px 0;font-size:10px;color:#7c3aed;font-weight:600;background:' + c.header + ';border-bottom:1px solid ' + c.border + ';flex-shrink:0;">' + esc(memo.shared_by_name || '') + ' ' + esc(MT.shared_by) + '</div>'
                        + '<div class="pinned-memo-body" style="flex:1;padding:8px 10px;font-size:12px;color:#374151;line-height:1.55;overflow-y:auto;white-space:pre-wrap;word-break:break-word;min-height:60px;cursor:default;">' + esc(memo.content) + '</div>'
                        + '<div class="pinned-memo-resize" title="' + esc(MT.resize) + '" style="position:absolute;right:0;bottom:0;width:16px;height:16px;cursor:se-resize;display:flex;align-items:center;justify-content:center;opacity:0.45;">'
                        +   '<svg width="9" height="9" viewBox="0 0 9 9" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="1" x2="1" y2="8"/><line x1="8" y1="5" x2="5" y2="8"/></svg>'
                        + '</div>';
                    document.body.appendChild(div);
                    makeDraggable(div);
                    makeResizable(div);
                } else {
                    if (existing) existing.remove();
                    localStorage.removeItem('memo-pos-' + memo.id);
                }
            }

            // ── 드래그 시스템 ──────────────────────────────────
            var _drag   = { el: null, ox: 0, oy: 0 };
            var _resize = { el: null, startX: 0, startY: 0, startW: 0, startH: 0 };

            document.addEventListener('mousemove', async function(e) {
                if (_drag.el) {
                    var el = _drag.el;
                    var newLeft = Math.max(0, Math.min(window.innerWidth  - el.offsetWidth,  e.clientX - _drag.ox));
                    var newTop  = Math.max(0, Math.min(window.innerHeight - el.offsetHeight, e.clientY - _drag.oy));
                    el.style.left = newLeft + 'px';
                    el.style.top  = newTop  + 'px';
                }
                if (_resize.el) {
                    var el = _resize.el;
                    var newW = Math.max(180, Math.min(window.innerWidth  - 20, _resize.startW + (e.clientX - _resize.startX)));
                    var newH = Math.max(100, Math.min(window.innerHeight - 20, _resize.startH + (e.clientY - _resize.startY)));
                    el.style.width  = newW + 'px';
                    el.style.height = newH + 'px';
                }
            });

            document.addEventListener('mouseup', async function() {
                if (_drag.el) {
                    var el = _drag.el;
                    var h = el.querySelector('.pinned-memo-header');
                    if (h) h.style.cursor = 'grab';
                    document.body.style.userSelect = '';
                    el.style.zIndex = '9988';
                    localStorage.setItem('memo-pos-' + el.dataset.id, JSON.stringify({
                        left: parseInt(el.style.left), top: parseInt(el.style.top)
                    }));
                    _drag.el = null;
                }
                if (_resize.el) {
                    var el = _resize.el;
                    document.body.style.userSelect = '';
                    localStorage.setItem('memo-size-' + el.dataset.id, JSON.stringify({
                        width: el.offsetWidth, height: el.offsetHeight
                    }));
                    _resize.el = null;
                }
            });

            // 터치 드래그 / 리사이즈 지원
            document.addEventListener('touchmove', async function(e) {
                var t = e.touches[0];
                if (_drag.el) {
                    var el = _drag.el;
                    var newLeft = Math.max(0, Math.min(window.innerWidth  - el.offsetWidth,  t.clientX - _drag.ox));
                    var newTop  = Math.max(0, Math.min(window.innerHeight - el.offsetHeight, t.clientY - _drag.oy));
                    el.style.left = newLeft + 'px';
                    el.style.top  = newTop  + 'px';
                    e.preventDefault();
                }
                if (_resize.el) {
                    var el = _resize.el;
                    var newW = Math.max(180, Math.min(window.innerWidth  - 20, _resize.startW + (t.clientX - _resize.startX)));
                    var newH = Math.max(100, Math.min(window.innerHeight - 20, _resize.startH + (t.clientY - _resize.startY)));
                    el.style.width  = newW + 'px';
                    el.style.height = newH + 'px';
                    e.preventDefault();
                }
            }, { passive: false });

            document.addEventListener('touchend', async function() {
                if (_drag.el) {
                    var el = _drag.el;
                    localStorage.setItem('memo-pos-' + el.dataset.id, JSON.stringify({
                        left: parseInt(el.style.left), top: parseInt(el.style.top)
                    }));
                    _drag.el = null;
                }
                if (_resize.el) {
                    var el = _resize.el;
                    localStorage.setItem('memo-size-' + el.dataset.id, JSON.stringify({
                        width: el.offsetWidth, height: el.offsetHeight
                    }));
                    _resize.el = null;
                }
            });

            async function makeDraggable(el) {
                // 저장된 위치 복원
                var saved = null;
                try { saved = JSON.parse(localStorage.getItem('memo-pos-' + el.dataset.id)); } catch(e2) {}
                if (saved && typeof saved.left === 'number' && typeof saved.top === 'number') {
                    el.style.left   = saved.left + 'px';
                    el.style.top    = saved.top  + 'px';
                    el.style.right  = 'auto';
                    el.style.bottom = 'auto';
                }
                var header = el.querySelector('.pinned-memo-header');
                if (!header) return;
                header.addEventListener('mousedown', async function(e) {
                    if (e.target.closest('button')) return;
                    var rect = el.getBoundingClientRect();
                    _drag.ox = e.clientX - rect.left;
                    _drag.oy = e.clientY - rect.top;
                    el.style.left   = rect.left + 'px';
                    el.style.top    = rect.top  + 'px';
                    el.style.right  = 'auto';
                    el.style.bottom = 'auto';
                    el.style.zIndex = '9996';
                    header.style.cursor = 'grabbing';
                    document.body.style.userSelect = 'none';
                    _drag.el = el;
                    e.preventDefault();
                });
                header.addEventListener('touchstart', async function(e) {
                    if (e.target.closest('button')) return;
                    var t = e.touches[0];
                    var rect = el.getBoundingClientRect();
                    _drag.ox = t.clientX - rect.left;
                    _drag.oy = t.clientY - rect.top;
                    el.style.left   = rect.left + 'px';
                    el.style.top    = rect.top  + 'px';
                    el.style.right  = 'auto';
                    el.style.bottom = 'auto';
                    el.style.zIndex = '9996';
                    _drag.el = el;
                }, { passive: true });
            }

            async function makeResizable(el) {
                // 저장된 크기 복원
                var saved = null;
                try { saved = JSON.parse(localStorage.getItem('memo-size-' + el.dataset.id)); } catch(e2) {}
                if (saved && typeof saved.width === 'number' && saved.width >= 180) {
                    el.style.width  = saved.width  + 'px';
                    el.style.height = saved.height + 'px';
                }
                var handle = el.querySelector('.pinned-memo-resize');
                if (!handle) return;
                handle.addEventListener('mousedown', async function(e) {
                    _resize.el     = el;
                    _resize.startX = e.clientX;
                    _resize.startY = e.clientY;
                    _resize.startW = el.offsetWidth;
                    _resize.startH = el.offsetHeight;
                    document.body.style.userSelect = 'none';
                    e.stopPropagation();
                    e.preventDefault();
                });
                handle.addEventListener('touchstart', async function(e) {
                    var t = e.touches[0];
                    _resize.el     = el;
                    _resize.startX = t.clientX;
                    _resize.startY = t.clientY;
                    _resize.startW = el.offsetWidth;
                    _resize.startH = el.offsetHeight;
                    e.stopPropagation();
                }, { passive: true });
            }

            // ── 고정 메모 자동 저장 (contenteditable + debounce) ─
            async function setupNoteAutoSave(el) {
                var body = el.querySelector('.pinned-memo-body');
                if (!body || body._autoSaveInit) return;
                body._autoSaveInit = true;

                // 본문을 바로 편집 가능하게
                body.contentEditable = 'true';
                body.style.outline   = 'none';

                // 헤더에 저장 상태 표시 요소 삽입
                var header = el.querySelector('.pinned-memo-header');
                var status = document.createElement('span');
                status.style.cssText = 'font-size:10px;color:#9ca3af;flex-shrink:0;opacity:0;transition:opacity .3s;white-space:nowrap;';
                var btns = header.querySelector('div');
                header.insertBefore(status, btns);

                var saveTimer = null;
                var lastContent = body.innerText.trim();

                async function showStatus(msg, autoHide) {
                    status.textContent = msg;
                    status.style.opacity = '1';
                    if (autoHide) setTimeout(async function() { status.style.opacity = '0'; }, autoHide);
                }

                async function doSave() {
                    var content = body.innerText.trim();
                    if (!content || content === lastContent) {
                        status.style.opacity = '0';
                        return;
                    }
                    showStatus(MT.saving, 0);
                    fetch(MEMO_URL + '/' + el.dataset.id, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF
                        },
                        body: JSON.stringify({ content: content })
                    }).then(async function(r) { return r.json(); }).then(async function(memo) {
                        lastContent = memo.content;
                        showStatus('✓ ' + MT.saved, 1800);
                        var popup = document.getElementById('memo-popup');
                        if (popup && popup.style.display === 'flex') memoLoadList();
                    }).catch(async function() {
                        showStatus(MT.save_failed, 2500);
                    });
                }

                body.addEventListener('input', async function() {
                    clearTimeout(saveTimer);
                    showStatus('...', 0);
                    saveTimer = setTimeout(doSave, 700);
                });

                // 붙여넣기: 서식 제거 후 순수 텍스트만 삽입
                body.addEventListener('paste', async function(e) {
                    e.preventDefault();
                    var text = (e.clipboardData || window.clipboardData).getData('text/plain');
                    document.execCommand('insertText', false, text);
                });

                // body 클릭 시 드래그 이벤트로 전파되지 않도록 차단
                body.addEventListener('mousedown', async function(e) { e.stopPropagation(); });
            }

            // 페이지 로드 시 기존 고정 메모에 드래그 + 리사이즈 + 자동저장 적용
            document.querySelectorAll('.pinned-memo-note').forEach(async function(el) {
                makeDraggable(el);
                makeResizable(el);
                setupNoteAutoSave(el);
            });

            // ── 팝업 / 공유 모달 외부 클릭 시 닫기 ─────────────
            document.addEventListener('click', async function(e) {
                var popup = document.getElementById('memo-popup');
                var btn   = document.getElementById('memo-btn');
                if (popup && btn && popup.style.display === 'flex') {
                    if (!popup.contains(e.target) && !btn.contains(e.target)) {
                        memoPopupClose();
                    }
                }
                var modal = document.getElementById('memo-share-modal');
                if (modal && modal.style.display === 'flex') {
                    if (e.target === modal) memoShareModalClose();
                }
            });
        })();
        </script>

        {{-- ===== 프롬프트 변환 JS ===== --}}
        <script>
        (function() {
            var QP_URL  = '{{ url("/quick-prompts") }}';
            var SFX_URL = '{{ url("/prompt-suffixes") }}';
            var CSRF    = '{{ csrf_token() }}';

            var QT = {
                converting:       @json(__('app.qp_converting')),
                works_organizing: @json(__('app.qp_works_organizing')),
                convert_failed:   @json(__('app.qp_convert_failed')),
                works_done:       @json(__('app.qp_works_done')),
                convert_done:     @json(__('app.qp_convert_done')),
                convert_error:    @json(__('app.qp_convert_error')),
                no_suffixes:      @json(__('app.qp_no_suffixes')),
                no_suffixes_hint: @json(__('app.qp_no_suffixes_hint')),
                edit:             @json(__('common.edit')),
                del:              @json(__('common.delete')),
                saving:           @json(__('app.saving_status')),
                save_failed:      @json(__('app.qp_save_failed')),
                save_error:       @json(__('app.qp_save_error')),
                confirm_del_suffix: @json(__('app.qp_confirm_del_suffix')),
                no_prompts:       @json(__('app.qp_no_prompts')),
                no_prompts_hint:  @json(__('app.qp_no_prompts_hint')),
                list_failed:      @json(__('app.qp_list_failed')),
                works_badge:      @json(__('app.qp_works_badge')),
                fallback_title:   @json(__('app.qp_fallback_title')),
                add_prefix:       @json(__('app.qp_add_prefix')),
                view_original:    @json(__('app.qp_view_original')),
                toggle_failed:    @json(__('app.qp_toggle_failed')),
                toggle_error:     @json(__('app.qp_toggle_error')),
                copied:           @json(__('app.qp_copied')),
                copy_failed:      @json(__('app.qp_copy_failed')),
                confirm_del_prompt: @json(__('app.qp_confirm_del_prompt')),
            };

            // 마지막으로 받은 suffix 목록 캐시 (id → 객체) — 결과 카드 칩 렌더링에 사용
            var qpSuffixCache = {};

            function qpEsc(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            }

            function qpToast(msg) {
                if (typeof window.showToast === 'function') {
                    try { window.showToast(msg); return; } catch (e) {}
                }
                var s = document.getElementById('qp-status');
                if (s) {
                    s.style.color = '#15803d';
                    s.textContent = msg;
                    setTimeout(function() { if (s.textContent === msg) s.textContent = ''; }, 2000);
                }
            }

            window.qpPopupToggle = function() {
                var p = document.getElementById('qp-popup');
                if (!p) return;
                if (p.style.display === 'flex') { qpPopupClose(); return; }
                p.style.display = 'flex';
                qpLoadSuffixes();
                qpLoadList();
                setTimeout(function() {
                    var ta = document.getElementById('qp-input');
                    if (ta) ta.focus();
                }, 50);
            };

            window.qpPopupClose = function() {
                var p = document.getElementById('qp-popup');
                if (p) p.style.display = 'none';
                if (typeof window.qpSuffixManageClose === 'function') qpSuffixManageClose();
            };

            window.qpSubmit = async function() {
                var ta     = document.getElementById('qp-input');
                var btn    = document.getElementById('qp-submit-btn');
                var status = document.getElementById('qp-status');
                var text   = (ta.value || '').trim();
                if (!text) {
                    ta.style.borderColor = '#ef4444';
                    ta.focus();
                    return;
                }
                ta.style.borderColor = '#e5e7eb';

                btn.disabled = true;
                var origHTML = btn.innerHTML;
                btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="9" stroke-opacity=".3"/><path d="M12 3a9 9 0 019 9" stroke-linecap="round"/></svg> ' + qpEsc(QT.converting);
                status.style.color = '#6b7280';
                status.textContent = QT.works_organizing;

                try {
                    var r = await fetch(QP_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF
                        },
                        body: JSON.stringify({ original_input: text })
                    });
                    var d = await r.json();
                    if (!r.ok || !d.ok) throw new Error(d.message || QT.convert_failed);

                    ta.value = '';
                    status.style.color = '#15803d';
                    status.textContent = '✓ ' + QT.works_done;
                    qpLoadList();

                    setTimeout(function() {
                        if (status.textContent.indexOf(QT.convert_done) >= 0) status.textContent = '';
                    }, 2500);
                } catch (e) {
                    status.style.color = '#dc2626';
                    status.textContent = e.message || QT.convert_error;
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = origHTML;
                }
            };

            // ── 추가 문구 라이브러리 (관리 팝오버 + 결과 카드 칩) ─────────
            async function qpLoadSuffixes() {
                try {
                    var r = await fetch(SFX_URL, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
                    var arr = await r.json();
                    if (!Array.isArray(arr)) arr = [];
                    qpSuffixCache = {};
                    arr.forEach(function(s) { qpSuffixCache[s.id] = s; });
                    qpRenderManageList();
                } catch (e) { /* 캐시는 빈 채로 유지 */ }
            }
            window.qpLoadSuffixes = qpLoadSuffixes;

            function qpSortedSuffixes() {
                var arr = Object.keys(qpSuffixCache).map(function(k) { return qpSuffixCache[k]; });
                arr.sort(function(a, b) {
                    if (a.sort_order !== b.sort_order) return a.sort_order - b.sort_order;
                    return a.id - b.id;
                });
                return arr;
            }

            // ── 관리 팝오버 ────────────────────────────────────
            window.qpSuffixManageOpen = function() {
                var p = document.getElementById('qp-suffix-manage-popup');
                if (!p) return;
                p.style.display = 'flex';
                qpSuffixHideForm();
                qpRenderManageList();
            };
            window.qpSuffixManageClose = function() {
                var p = document.getElementById('qp-suffix-manage-popup');
                if (p) p.style.display = 'none';
                qpSuffixHideForm();
            };
            window.qpSuffixManageNew = function() {
                document.getElementById('qp-suffix-form-id').value    = '';
                document.getElementById('qp-suffix-form-label').value = '';
                document.getElementById('qp-suffix-form-body').value  = '';
                document.getElementById('qp-suffix-form').style.display = 'block';
                setTimeout(function() { document.getElementById('qp-suffix-form-label').focus(); }, 30);
            };

            function qpRenderManageList() {
                var box = document.getElementById('qp-suffix-manage-list');
                if (!box) return;
                var arr = qpSortedSuffixes();
                if (arr.length === 0) {
                    box.innerHTML = '<div style="text-align:center;padding:28px 14px;color:#a1a1aa;font-size:12px;line-height:1.7;">' + qpEsc(QT.no_suffixes) + '<br><span style="font-size:11.5px;">' + qpEsc(QT.no_suffixes_hint) + '</span></div>';
                    return;
                }
                box.innerHTML = arr.map(qpRenderManageRow).join('');
            }

            function qpRenderManageRow(s) {
                var preview = (s.body || '').replace(/\s+/g, ' ').trim();
                if (preview.length > 90) preview = preview.slice(0, 90) + '…';
                return ''
                    + '<div data-id="' + s.id + '" style="padding:9px 10px;border:1px solid #f0eeff;border-radius:8px;background:#fff;margin-bottom:6px;">'
                    +   '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:4px;">'
                    +     '<div style="font-size:12.5px;font-weight:700;color:#18181b;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + qpEsc(s.label) + '</div>'
                    +     '<div style="display:flex;gap:4px;flex-shrink:0;">'
                    +       '<button type="button" onclick="qpSuffixEdit(' + s.id + ')" title="' + qpEsc(QT.edit) + '" '
                    +         'style="display:flex;align-items:center;justify-content:center;width:24px;height:24px;background:#fff;color:#9ca3af;border:1.5px solid #e5e7eb;border-radius:6px;cursor:pointer;transition:all .12s;" '
                    +         'onmouseover="this.style.background=\'var(--t50)\';this.style.color=\'var(--t700)\';this.style.borderColor=\'var(--t300)\'" '
                    +         'onmouseout="this.style.background=\'#fff\';this.style.color=\'#9ca3af\';this.style.borderColor=\'#e5e7eb\'">'
                    +         '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    +       '</button>'
                    +       '<button type="button" onclick="qpSuffixDelete(' + s.id + ')" title="' + qpEsc(QT.del) + '" '
                    +         'style="display:flex;align-items:center;justify-content:center;width:24px;height:24px;background:#fff;color:#9ca3af;border:1.5px solid #e5e7eb;border-radius:6px;cursor:pointer;transition:all .12s;" '
                    +         'onmouseover="this.style.background=\'#fee2e2\';this.style.color=\'#ef4444\';this.style.borderColor=\'#fecaca\'" '
                    +         'onmouseout="this.style.background=\'#fff\';this.style.color=\'#9ca3af\';this.style.borderColor=\'#e5e7eb\'">'
                    +         '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V3a1 1 0 011-1h4a1 1 0 011 1v4"/></svg>'
                    +       '</button>'
                    +     '</div>'
                    +   '</div>'
                    +   '<div style="font-size:11.5px;color:#6b7280;line-height:1.5;white-space:pre-wrap;word-break:break-word;">' + qpEsc(preview) + '</div>'
                    + '</div>';
            }

            window.qpSuffixHideForm = function() {
                var f = document.getElementById('qp-suffix-form');
                if (f) f.style.display = 'none';
            };

            window.qpSuffixEdit = function(id) {
                var s = qpSuffixCache[id];
                if (!s) return;
                document.getElementById('qp-suffix-form-id').value    = s.id;
                document.getElementById('qp-suffix-form-label').value = s.label || '';
                document.getElementById('qp-suffix-form-body').value  = s.body || '';
                document.getElementById('qp-suffix-form').style.display = 'block';
                setTimeout(function() { document.getElementById('qp-suffix-form-label').focus(); }, 30);
            };

            window.qpSuffixSave = async function() {
                var id    = document.getElementById('qp-suffix-form-id').value;
                var label = document.getElementById('qp-suffix-form-label').value.trim();
                var body  = document.getElementById('qp-suffix-form-body').value.trim();
                if (!label || !body) {
                    if (!label) document.getElementById('qp-suffix-form-label').focus();
                    else        document.getElementById('qp-suffix-form-body').focus();
                    return;
                }

                var btn  = document.getElementById('qp-suffix-save-btn');
                btn.disabled = true;
                var orig = btn.textContent;
                btn.textContent = QT.saving;

                try {
                    var url    = id ? (SFX_URL + '/' + id) : SFX_URL;
                    var method = id ? 'PATCH' : 'POST';
                    var r = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF
                        },
                        body: JSON.stringify({ label: label, body: body })
                    });
                    var d = await r.json();
                    if (!r.ok || !d.ok) throw new Error(d.message || QT.save_failed);

                    qpSuffixHideForm();
                    await qpLoadSuffixes();
                    // 신규/수정된 suffix 가 결과 카드 칩에도 반영되도록 목록 새로고침
                    if (typeof qpLoadList === 'function') qpLoadList();
                } catch (e) {
                    qpToast(e.message || QT.save_error);
                } finally {
                    btn.disabled = false;
                    btn.textContent = orig;
                }
            };

            window.qpSuffixDelete = async function(id) {
                var ok = (typeof window.__confirm === 'function')
                    ? await window.__confirm(QT.confirm_del_suffix)
                    : confirm(QT.confirm_del_suffix);
                if (!ok) return;
                try {
                    var r = await fetch(SFX_URL + '/' + id, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                    });
                    if (r.ok) {
                        await qpLoadSuffixes();
                        // 삭제된 suffix 가 적용돼 있던 카드들도 서버측에서 자동 정리됨
                        if (typeof qpLoadList === 'function') qpLoadList();
                    }
                } catch (e) {}
            };

            async function qpLoadList() {
                var list = document.getElementById('qp-list');
                if (!list) return;
                try {
                    var r = await fetch(QP_URL, {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                    });
                    var arr = await r.json();
                    if (!Array.isArray(arr) || arr.length === 0) {
                        list.innerHTML = '<div style="text-align:center;padding:34px 20px;color:#a1a1aa;font-size:13px;line-height:1.7;">' + qpEsc(QT.no_prompts) + '<br><span style="font-size:12px;">' + qpEsc(QT.no_prompts_hint) + '</span></div>';
                        return;
                    }
                    list.innerHTML = arr.map(qpRenderItem).join('');
                } catch (e) {
                    list.innerHTML = '<div style="text-align:center;padding:24px;color:#dc2626;font-size:12px;">' + qpEsc(QT.list_failed) + '</div>';
                }
            }
            window.qpLoadList = qpLoadList;

            function qpRenderItem(it) {
                var providerBadge = it.provider_used
                    ? '<span style="display:inline-block;padding:1px 6px;background:#ede9fe;color:#6d28d9;font-size:10px;font-weight:700;border-radius:4px;letter-spacing:.02em;">' + qpEsc(QT.works_badge) + '</span>'
                    : '';
                var fbBadge = it.fallback_reason
                    ? '<span title="' + qpEsc(QT.fallback_title) + '" style="display:inline-block;padding:1px 6px;background:#fef3c7;color:#92400e;font-size:10px;font-weight:700;border-radius:4px;margin-left:4px;">FB</span>'
                    : '';

                // 결과 카드별 추가 문구 칩 (클릭으로 즉시 토글)
                var allSuffixes = qpSortedSuffixes();
                var appliedSet  = new Set((it.applied_suffix_ids || []).map(Number));
                var chipsHtml   = '';
                if (allSuffixes.length > 0) {
                    chipsHtml = '<div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:7px;align-items:center;">'
                        + '<span style="font-size:10.5px;color:#9ca3af;font-weight:600;margin-right:2px;">' + qpEsc(QT.add_prefix) + '</span>'
                        + allSuffixes.map(function(s) {
                            var on   = appliedSet.has(s.id);
                            var bg   = on ? 'var(--t600, #7c3aed)' : '#fff';
                            var bd   = on ? 'var(--t600, #7c3aed)' : '#ddd6fe';
                            var fg   = on ? '#fff' : 'var(--t700, #6d28d9)';
                            var icon = on
                                ? '<svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
                                : '<svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>';
                            return '<button type="button" onclick="event.stopPropagation();qpToggleCardSuffix(' + it.id + ',' + s.id + ', this)" '
                                + 'data-card-id="' + it.id + '" data-suffix-id="' + s.id + '" '
                                + 'title="' + qpEsc(s.body) + '" '
                                + 'style="display:inline-flex;align-items:center;gap:4px;height:22px;padding:0 8px;background:' + bg + ';color:' + fg + ';border:1.5px solid ' + bd + ';border-radius:999px;font-size:11px;font-weight:600;cursor:pointer;transition:all .12s;max-width:200px;">'
                                +    icon
                                +    '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + qpEsc(s.label) + '</span>'
                                + '</button>';
                        }).join('')
                        + '</div>';
                }

                return ''
                    + '<div class="qp-item" data-id="' + it.id + '" style="background:#fff;border:1.5px solid #ede8ff;border-radius:10px;padding:11px 12px;margin-bottom:10px;">'
                    +   '<div style="display:flex;align-items:center;gap:8px;margin-bottom:7px;font-size:11px;color:#9ca3af;">'
                    +     providerBadge + fbBadge
                    +     '<span style="flex:1;text-align:right;">' + qpEsc(it.created_at || '') + '</span>'
                    +   '</div>'
                    +   '<details style="margin-bottom:7px;">'
                    +     '<summary style="font-size:11px;color:#6b7280;cursor:pointer;outline:none;list-style:none;display:flex;align-items:center;gap:4px;user-select:none;">'
                    +       '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>'
                    +       qpEsc(QT.view_original)
                    +     '</summary>'
                    +     '<div style="margin-top:6px;padding:8px 10px;background:#f9fafb;border:1px solid #f0eeff;border-radius:7px;font-size:12px;color:#4b5563;line-height:1.55;white-space:pre-wrap;word-break:break-word;max-height:140px;overflow-y:auto;">' + qpEsc(it.original_input) + '</div>'
                    +   '</details>'
                    +   '<div id="qp-refined-' + it.id + '" style="padding:9px 10px;background:#faf5ff;border:1px solid #e9d5ff;border-radius:7px;font-size:12.5px;color:#1f2937;line-height:1.6;white-space:pre-wrap;word-break:break-word;max-height:260px;overflow-y:auto;">' + qpEsc(it.refined_prompt || '') + '</div>'
                    +   chipsHtml
                    +   '<div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">'
                    +     '<button onclick="qpCopy(' + it.id + ', this)" '
                    +       'style="display:flex;align-items:center;gap:4px;height:26px;padding:0 10px;background:var(--t600);color:#fff;border:none;border-radius:6px;font-size:11.5px;font-weight:600;cursor:pointer;transition:background .12s;" '
                    +       'onmouseover="this.style.background=\'var(--t700)\'" onmouseout="this.style.background=\'var(--t600)\'">'
                    +       '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>'
                    +       'Copy'
                    +     '</button>'
                    +     '<button onclick="qpDelete(' + it.id + ')" title="' + qpEsc(QT.del) + '" '
                    +       'style="display:flex;align-items:center;justify-content:center;width:26px;height:26px;background:#fff;color:#9ca3af;border:1.5px solid #e5e7eb;border-radius:6px;cursor:pointer;transition:all .12s;" '
                    +       'onmouseover="this.style.background=\'#fee2e2\';this.style.color=\'#ef4444\';this.style.borderColor=\'#fecaca\'" '
                    +       'onmouseout="this.style.background=\'#fff\';this.style.color=\'#9ca3af\';this.style.borderColor=\'#e5e7eb\'">'
                    +       '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V3a1 1 0 011-1h4a1 1 0 011 1v4"/></svg>'
                    +     '</button>'
                    +   '</div>'
                    + '</div>';
            }

            // 결과 카드의 추가 문구 토글 (서버 멱등 처리)
            window.qpToggleCardSuffix = async function(promptId, suffixId, btn) {
                if (!btn || btn.disabled) return;
                btn.disabled = true;
                var origBg = btn.style.background;
                btn.style.opacity = '0.6';
                try {
                    var r = await fetch(QP_URL + '/' + promptId + '/toggle-suffix', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF
                        },
                        body: JSON.stringify({ suffix_id: suffixId })
                    });
                    var d = await r.json();
                    if (!r.ok || !d.ok) throw new Error(d.message || QT.toggle_failed);

                    // 해당 카드만 in-place 교체
                    var card = document.querySelector('.qp-item[data-id="' + promptId + '"]');
                    if (card) {
                        var temp = document.createElement('div');
                        temp.innerHTML = qpRenderItem(d.item);
                        card.replaceWith(temp.firstChild);
                    } else {
                        qpLoadList();
                    }
                } catch (e) {
                    btn.style.opacity = '';
                    btn.disabled = false;
                    qpToast(e.message || QT.toggle_error);
                }
            };

            window.qpCopy = async function(id, btn) {
                var box = document.getElementById('qp-refined-' + id);
                if (!box) return;
                var text = box.innerText;
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(text);
                    } else {
                        var ta = document.createElement('textarea');
                        ta.value = text;
                        ta.style.cssText = 'position:fixed;top:-9999px;left:0;';
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                    }
                    var orig = btn.innerHTML;
                    btn.innerHTML = '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> ' + qpEsc(QT.copied);
                    btn.style.background = '#16a34a';
                    setTimeout(function() {
                        btn.innerHTML = orig;
                        btn.style.background = 'var(--t600)';
                    }, 1500);
                } catch (e) {
                    qpToast(QT.copy_failed);
                }
            };

            window.qpDelete = async function(id) {
                var ok = (typeof window.__confirm === 'function')
                    ? await window.__confirm(QT.confirm_del_prompt)
                    : confirm(QT.confirm_del_prompt);
                if (!ok) return;
                try {
                    var r = await fetch(QP_URL + '/' + id, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                    });
                    if (r.ok) qpLoadList();
                } catch (e) {}
            };

            // Ctrl/Cmd+Enter 로 빠르게 변환
            document.addEventListener('keydown', function(e) {
                var pop = document.getElementById('qp-popup');
                if (!pop || pop.style.display !== 'flex') return;
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    qpSubmit();
                }
            });

            // 외부 클릭 시 닫기 (관리 팝오버는 그 자체로 한 번 더 클릭이 일어나야 닫힘)
            document.addEventListener('click', function(e) {
                var popup   = document.getElementById('qp-popup');
                var btn     = document.getElementById('quick-prompt-btn');
                var managePop = document.getElementById('qp-suffix-manage-popup');

                // 관리 팝오버가 열려있을 때: 관리 팝오버 외부 클릭 시 관리만 닫기 (메인은 유지)
                if (managePop && managePop.style.display === 'flex') {
                    if (!managePop.contains(e.target)) {
                        // 단, 메인 팝업 내부의 “관리” 버튼 클릭은 통과
                        var mgmtBtn = document.getElementById('qp-suffix-manage-btn');
                        if (!mgmtBtn || !mgmtBtn.contains(e.target)) {
                            qpSuffixManageClose();
                        }
                    }
                    return; // 관리 팝오버가 열려있는 동안엔 메인 닫기 로직 실행 안 함
                }

                // 메인 팝업 외부 클릭 시 메인 닫기
                if (popup && btn && popup.style.display === 'flex') {
                    if (!popup.contains(e.target) && !btn.contains(e.target)) {
                        qpPopupClose();
                    }
                }
            });
        })();
        </script>
        <style>@keyframes spin { from{transform:rotate(0deg);} to{transform:rotate(360deg);} }</style>

    @include('partials.custom-dialog')
    </body>
</html>
