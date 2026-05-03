<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>window.broadcastAuthPath = '{{ request()->getBasePath() }}/broadcasting/auth';</script>
        <title>{{ config('app.name', 'SupportWorks') }} - @yield('title', __('app.nav_home'))</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
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
            :root {
                --t50:#f5f3ff; --t100:#ede9fe; --t200:#ddd6fe; --t300:#c4b5fd;
                --t400:#a78bfa; --t500:#8b5cf6; --t600:#7c3aed; --t700:#6d28d9;
                --tText:#6d5ce7; --tBg:#f5f3ff;
            }
            .sidebar-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 7px 10px;
                border-radius: 9px;
                font-size: 13.5px;
                font-weight: 500;
                color: #5b5677;
                cursor: pointer;
                transition: background 0.13s, color 0.13s;
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
                font-weight: 600;
            }
            .sidebar-item.active svg { color: var(--tText); }
            .sidebar-item svg {
                flex-shrink: 0;
                color: #9e97c0;
                transition: color 0.13s;
            }
            .sidebar-item:hover svg { color: var(--t700); }

            .project-dot { width:8px; height:8px; border-radius:3px; flex-shrink:0; }

            .section-label {
                font-size: 10.5px;
                font-weight: 700;
                color: #b8b0d8;
                letter-spacing: 0.07em;
                text-transform: uppercase;
                padding: 0 10px;
                margin-bottom: 2px;
            }
            .sidebar-divider {
                height: 1px;
                background: linear-gradient(to right, transparent, var(--t200), transparent);
                margin: 8px 0;
            }
            #sidebar-search {
                background: var(--t50);
                border: 1px solid var(--t200);
                border-radius: 9px;
                padding: 7px 10px 7px 34px;
                font-size: 13px;
                color: #3f3f46;
                width: 100%;
                outline: none;
                transition: all 0.15s;
            }
            #sidebar-search:focus {
                background: var(--t100);
                border-color: var(--t300);
            }
            #sidebar-search::placeholder { color: #b8b0d8; }

            .project-item {
                display: flex;
                align-items: center;
                gap: 9px;
                padding: 6px 10px;
                border-radius: 8px;
                font-size: 13px;
                color: #5b5677;
                text-decoration: none;
                transition: background 0.12s, color 0.12s;
                width: 100%;
            }
            .project-item:hover { background: var(--t50); color: var(--t700); }
            .project-item.active { background: var(--t100); color: var(--tText); font-weight:600; }

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
                background: #ddd6fe;
                border-radius: 2px;
                overflow: hidden;
            }
            .sw-dlp-fill {
                height: 100%;
                width: 0%;
                background: linear-gradient(90deg, #7c3aed, #a78bfa);
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
                color: #7c3aed;
                font-weight: 700;
                text-align: right;
                margin-top: 2px;
                line-height: 1;
                white-space: nowrap;
                font-family: inherit;
            }
        </style>
    </head>
    <body class="font-sans antialiased" style="background:#f5f3ff;">

        @if(session('impersonating'))
        <div id="impersonate-bar" style="position:sticky;top:0;z-index:9999;display:flex;align-items:center;gap:10px;background:linear-gradient(90deg,#b45309,#92400e);color:#fff;padding:8px 18px;font-size:12.5px;font-weight:600;box-shadow:0 2px 8px rgba(0,0,0,.25);">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            <span style="opacity:.8;">{{ __('app.admin_mode') }}</span>
            <span style="background:rgba(255,255,255,.2);border-radius:5px;padding:2px 9px;font-size:12px;">
                {{ session('impersonating_name') }}
            </span>
            <span style="opacity:.7;font-size:11.5px;font-weight:400;">{{ session('impersonating_email') }}</span>
            <span style="flex:1;"></span>
            <span style="opacity:.75;font-size:11px;font-weight:400;">{{ __('app.admin_mode_notice') }}</span>
            <button onclick="window.close()" style="display:flex;align-items:center;gap:5px;background:rgba(255,255,255,.18);border:1.5px solid rgba(255,255,255,.35);color:#fff;border-radius:7px;padding:4px 13px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;">
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
            $myConvIds         = $myConvs->pluck('id');
            $myInquiryConvIds  = $myConvs->where('type', 'inquiry')->pluck('id');

            // 처리 중인 문의 수
            $openInquiries = \App\Models\Conversation::where('type', 'inquiry')
                ->whereIn('status', ['open', 'active'])
                ->whereHas('participants', fn($q) => $q->where('user_id', auth()->id()))
                ->count();

            // 현재 활성 프로젝트 ID 감지 (중첩 라우트 포함)
            $currentProjectId = null;
            if ($rp = request()->route('project')) {
                $currentProjectId = $rp instanceof \App\Models\Project ? $rp->id : (int)$rp;
            } elseif ($rs = request()->route('schedule')) {
                $currentProjectId = $rs instanceof \App\Models\Schedule ? $rs->project_id : null;
            } elseif ($rq = request()->route('question')) {
                $currentProjectId = $rq instanceof \App\Models\Question ? $rq->project_id : null;
            } elseif ($rmt = request()->route('maintenance')) {
                $currentProjectId = ($rmt instanceof \App\Models\ProjectMaintenance) ? $rmt->project_id : null;
            }
        @endphp

        <div class="min-h-screen flex">

            {{-- ===== 사이드바 ===== --}}
            <aside id="global-sidebar" style="width:240px;min-width:240px;background:#fff;border-right:1px solid #ede8ff;display:flex;flex-direction:column;height:100vh;position:sticky;top:0;overflow:hidden;box-shadow:2px 0 12px rgba(139,122,240,.06);transition:width .22s ease,min-width .22s ease;">

                {{-- 워크스페이스 헤더 --}}
                <div style="padding:12px 10px 12px;border-bottom:1px solid #f0eeff;flex-shrink:0;display:flex;align-items:center;gap:8px;">
                    <a href="{{ route('dashboard') }}" id="gsb-logo-wrap" style="display:flex;align-items:center;gap:10px;text-decoration:none;overflow:hidden;flex:1;min-width:0;">
                        <img id="sw-logo" src="{{ asset('support_works_logo.png') }}" alt="SupportWorks" style="width:34px;height:34px;border-radius:10px;flex-shrink:0;object-fit:contain;">
                        <div style="overflow:hidden;">
                            <div style="font-size:14px;font-weight:700;color:#1e1b2e;line-height:1.2;letter-spacing:-.01em;white-space:nowrap;">SupportWorks</div>
                            <div style="font-size:11px;color:#b8b0d8;line-height:1.2;white-space:nowrap;">{{ auth()->user()->company ?? __('app.nav_workspace') }}</div>
                        </div>
                    </a>
                    <button onclick="toggleGlobalSidebar()" title="{{ __('app.toggle_sidebar') }}" style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;min-width:30px;border-radius:8px;border:1.5px solid #ddd6fe;background:#f5f3ff;cursor:pointer;color:#7c3aed;padding:0;flex-shrink:0;transition:background .12s;">
                        <svg id="gsb-icon" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="transition:transform .22s;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                </div>

                {{-- 검색 --}}
                <div id="gsb-search-area" style="padding:10px 12px;flex-shrink:0;position:relative;">
                    <div style="position:relative;">
                        <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#a1a1aa;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input id="sidebar-search" type="text" placeholder="{{ __('app.nav_search') }}" autocomplete="off">
                    </div>
                    <div id="sidebar-search-drop" style="display:none;position:absolute;top:100%;left:8px;right:8px;background:#fff;border:1px solid #ede8ff;border-radius:10px;box-shadow:0 8px 24px rgba(109,92,231,.13);z-index:9999;max-height:320px;overflow-y:auto;margin-top:2px;"></div>
                </div>

                {{-- 스크롤 영역 --}}
                <div style="flex:1;overflow-y:auto;padding:4px 10px 10px;">

                    {{-- 메인 네비게이션 --}}
                    <div style="margin-bottom:4px;overflow:hidden;">
                        <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_home') }}</span>
                        </a>
                        <a href="{{ route('projects.index') }}" class="sidebar-item {{ request()->routeIs('projects.index') || request()->routeIs('projects.create') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_my_work') }}</span>
                        </a>
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
                        <a href="{{ route('team.index') }}" class="sidebar-item {{ request()->routeIs('team.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span class="gsb-hide">{{ __('app.nav_team') }}</span>
                        </a>

                    <div class="sidebar-divider"></div>

                    {{-- 내 프로젝트 섹션 --}}
                    <div style="margin-bottom:4px;">
                        <div class="gsb-hide" style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px 4px;">
                            <button onclick="toggleSection('proj-list')" style="display:flex;align-items:center;gap:5px;background:none;border:none;cursor:pointer;padding:0;flex:1;text-align:left;">
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

                    <div class="sidebar-divider"></div>

                    {{-- SR 접수 섹션 --}}
                    @if(auth()->user()->hasFeature('sr'))
                    <div id="sr-menu-items" style="margin-bottom:4px;">
                        <div class="gsb-hide" style="display:flex;align-items:center;padding:6px 10px 4px;">
                            <button onclick="toggleSection('sr-list')" style="display:flex;align-items:center;gap:5px;background:none;border:none;cursor:pointer;padding:0;flex:1;text-align:left;">
                                <span class="section-label" style="pointer-events:none;">{{ __('app.nav_sr') }}</span>
                                <svg id="chevron-sr-list" width="10" height="10" fill="none" stroke="#b8b0d8" viewBox="0 0 24 24" style="flex-shrink:0;transition:transform .2s;pointer-events:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>

                        <div id="sr-list" style="overflow:hidden;max-height:600px;transition:max-height .22s ease;">
                        @forelse($myProjects as $index => $proj)
                        @php $srColor = $projectColors[$index % count($projectColors)]; @endphp
                        <a href="{{ route('projects.maintenances.index', $proj) }}"
                           class="project-item {{ ($currentProjectId == $proj->id && request()->routeIs('projects.maintenances.*', 'maintenances.*')) ? 'active' : '' }}"
                           data-proj-id="{{ $proj->id }}"
                           title="{{ __('app.sr_for_project', ['name' => $proj->name]) }}">
                            <svg width="13" height="13" fill="none" stroke="{{ $srColor }}" viewBox="0 0 24 24" style="flex-shrink:0;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6M9 16h4"/>
                            </svg>
                            <span class="gsb-hide" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj->name }}</span>
                        </a>
                        @empty
                        <div class="gsb-hide" style="padding:8px 10px;font-size:12px;color:#a1a1aa;">{{ __('app.nav_no_projects') }}</div>
                        @endforelse
                        </div>
                    </div>
                    @endif {{-- sr feature --}}

                    <div class="sidebar-divider"></div>

                    <div style="margin-bottom:4px;overflow:hidden;">
                        @if(auth()->user()->hasFeature('meeting_minutes'))
                        <a href="{{ route('meeting-minutes.index') }}" class="sidebar-item {{ request()->routeIs('meeting-minutes.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="gsb-hide">{{ __('app.nav_meeting_minutes') }}</span>
                        </a>
                        @endif {{-- meeting_minutes feature --}}

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
                        @if(auth()->user()->hasFeature('ai'))
                        <a href="{{ route('ai.index') }}" class="sidebar-item {{ request()->routeIs('ai.index') || (request()->routeIs('ai.*') && !request()->routeIs('ai-agent.*')) ? 'active' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>
                            <span class="gsb-hide">AI 채팅</span>
                        </a>
                        <a href="{{ route('ai-agent.dashboard') }}" class="sidebar-item {{ request()->routeIs('ai-agent.*') ? 'active' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                            <span class="gsb-hide">AI 개발 에이전트</span>
                        </a>
                        @endif {{-- ai feature --}}
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

                    {{-- 개인 작업 --}}
                    <div style="margin-bottom:4px;">
                        <div class="gsb-hide" style="padding:6px 10px 4px;">
                            <span class="section-label">{{ __('app.nav_personal') }}</span>
                        </div>

                        @if(auth()->user()->hasFeature('tasks'))
                        <a href="{{ route('tasks.index') }}" class="sidebar-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <span class="gsb-hide">Tasks</span>
                        </a>
                        @endif {{-- tasks feature --}}

                        @if(auth()->user()->hasFeature('action_items'))
                        <a href="{{ route('action-items.index') }}" class="sidebar-item {{ request()->routeIs('action-items.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span class="gsb-hide">{{ __('app.nav_action_items') }}</span>
                        </a>
                        @endif {{-- action_items feature --}}

                        @if(auth()->user()->hasFeature('memos'))
                        <a href="{{ route('memos.index') }}" class="sidebar-item {{ request()->routeIs('memos.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span class="gsb-hide">{{ __('app.nav_memos') }}</span>
                        </a>
                        @endif {{-- memos feature --}}
                    </div>

                    <div class="sidebar-divider"></div>

                    {{-- 관리자 섹션 --}}
                    @if(auth()->user()->isAdmin())
                    <div style="margin-bottom:4px;">
                        <div class="gsb-hide" style="padding:6px 10px 4px;">
                            <span class="section-label">{{ __('app.nav_admin') }}</span>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="sidebar-item {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span class="gsb-hide">{{ __('app.nav_user_mgmt') }}</span>
                        </a>
                    </div>
                    <div class="sidebar-divider"></div>
                    @endif

                </div>

                {{-- 하단 프로필 영역 --}}
                <div style="padding:10px 12px;border-top:1px solid #f0eeff;flex-shrink:0;background:linear-gradient(to bottom,#fff,#faf8ff);">
                    <div id="gsb-profile-area" style="display:flex;align-items:center;gap:10px;">
                        <div id="sw-avatar" style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#ddd6fe,#c4b5fd);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#6d5ce7;flex-shrink:0;box-shadow:0 2px 8px rgba(196,181,253,.4);">
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div id="gsb-profile-text" style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:600;color:#18181b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()->name }}</div>
                            <div style="font-size:11px;color:#a1a1aa;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()->email }}</div>
                        </div>
                        <div id="gsb-profile-actions" style="display:flex;gap:4px;flex-shrink:0;">
                            <a href="{{ route('profile.edit') }}" style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;color:#a1a1aa;text-decoration:none;transition:background 0.12s,color 0.12s;" title="{{ __('app.nav_profile') }}" onmouseover="this.style.background='#f4f4f5';this.style.color='#3f3f46'" onmouseout="this.style.background='transparent';this.style.color='#a1a1aa'">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;color:#a1a1aa;background:transparent;border:none;cursor:pointer;transition:background 0.12s,color 0.12s;" title="{{ __('app.nav_logout') }}" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444'" onmouseout="this.style.background='transparent';this.style.color='#a1a1aa'">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- ===== 메인 영역 ===== --}}
            <div class="flex-1 flex flex-col overflow-hidden">

                {{-- 상단 헤더 --}}
                <header style="background:#fff;border-bottom:1px solid #ede8ff;padding:0 24px;height:52px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;box-shadow:0 1px 8px rgba(139,122,240,.06);">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <h1 style="font-size:15px;font-weight:600;color:#18181b;">@yield('title', __('app.nav_home'))</h1>
                        @yield('header-breadcrumb')
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">

                        {{-- 언어 스위처 --}}
                        <div style="position:relative;">
                            <button id="lang-btn"
                                onclick="(function(){var d=document.getElementById('lang-dropdown');d.style.display=d.style.display==='block'?'none':'block';})()"
                                style="display:flex;align-items:center;gap:5px;padding:0 10px;height:32px;border-radius:8px;border:none;background:transparent;cursor:pointer;color:#a1a1aa;font-size:12px;font-weight:600;transition:background .12s,color .12s;"
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
                            <div id="theme-dropdown" style="display:none;position:absolute;top:40px;right:0;background:#fff;border:1px solid #ede8ff;border-radius:12px;padding:10px 12px;box-shadow:0 8px 28px rgba(0,0,0,.1);z-index:9999;min-width:170px;">
                                <div style="font-size:11px;font-weight:600;color:#a1a1aa;letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px;">{{ __('app.theme_colors') }}</div>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <button class="theme-swatch" data-theme="violet" title="{{ __('app.theme_violet') }}" style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#c4b5fd,#8b5cf6);border:2px solid #fff;cursor:pointer;outline:none;transition:transform .15s,box-shadow .15s;"></button>
                                    <button class="theme-swatch" data-theme="blue"   title="{{ __('app.theme_blue') }}" style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#93c5fd,#3b82f6);border:2px solid #fff;cursor:pointer;outline:none;transition:transform .15s,box-shadow .15s;"></button>
                                    <button class="theme-swatch" data-theme="teal"   title="{{ __('app.theme_teal') }}" style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#5eead4,#14b8a6);border:2px solid #fff;cursor:pointer;outline:none;transition:transform .15s,box-shadow .15s;"></button>
                                    <button class="theme-swatch" data-theme="green"  title="{{ __('app.theme_green') }}" style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#86efac,#22c55e);border:2px solid #fff;cursor:pointer;outline:none;transition:transform .15s,box-shadow .15s;"></button>
                                    <button class="theme-swatch" data-theme="amber"  title="{{ __('app.theme_amber') }}" style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#fcd34d,#f59e0b);border:2px solid #fff;cursor:pointer;outline:none;transition:transform .15s,box-shadow .15s;"></button>
                                </div>
                            </div>
                        </div>
                        @include('partials.collab-widget')
                        @yield('header-actions')
                    </div>
                </header>

                {{-- 알림 메시지 --}}
                @if(session('success') || session('error') || $errors->any())
                <div style="padding:12px 24px 0;">
                    @if(session('success'))
                        <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#15803d;font-size:13px;margin-bottom:8px;">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#dc2626;font-size:13px;margin-bottom:8px;">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ session('error') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div style="padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#dc2626;font-size:13px;margin-bottom:8px;">
                            <ul style="list-style:disc;padding-left:16px;margin:0;space-y:2px;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                @endif

                {{-- 페이지 콘텐츠 --}}
                <main style="flex:1;overflow-y:auto;padding:20px 24px 24px;background:#f5f3ff;">
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
        <div id="toast-container" style="position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;"></div>

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

        // ── 인앱 토스트 ────────────────────────────────────────
        window.showToast = function(senderName, preview, href) {
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
        window.updateSidebarBadge = function(delta, isInquiry) {
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
        function setupGlobalEcho() {
            const msgBase = '{{ url("/messages") }}';
            window.MY_CONV_IDS.forEach(function(cid) {
                window.Echo.private('conversation.' + cid)
                    .listen('.MessageSent', function(data) {
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
        function setupUserChannel() {
            const userCh = window.Echo.private('user.' + window.MY_ID);

            userCh.listen('.LeaveNotification', function(data) {
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
                .listen('.NewAdminMessage', function(data) {
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
                            .listen('.MessageSent', function(msgData) {
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
        (function() {
            const MENU = [
                { label:'{{ __("app.search_label_home") }}',      url:'{{ route("dashboard") }}',        icon:'🏠' },
                { label:'{{ __("app.nav_my_work") }}',            url:'{{ route("projects.index") }}',   icon:'📋' },
                { label:'{{ __("app.search_label_calendar") }}',  url:'{{ route("calendar") }}',         icon:'📅' },
                { label:'{{ __("app.search_label_messages") }}',  url:'{{ route("messages.index") }}',   icon:'💬' },
                { label:'{{ __("app.search_label_team") }}',      url:'{{ route("team.index") }}',       icon:'👥' },
                { label:'Teams',                                   url:'{{ route("teams.index") }}',      icon:'🔗' },
                { label:'AI Agent',                                url:'{{ route("ai.index") }}',         icon:'🤖' },
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
            document.querySelectorAll('#sr-menu-items a[href]').forEach(function(a) {
                var name = (a.querySelector('.gsb-hide') || {}).textContent || '';
                if (name.trim()) MENU.push({ label: 'SR - ' + name.trim(), url: a.href, icon: '🔧' });
            });

            const input = document.getElementById('sidebar-search');
            const drop  = document.getElementById('sidebar-search-drop');
            if (!input || !drop) return;

            function renderDrop(q) {
                if (!q) { drop.style.display = 'none'; return; }
                const matched = MENU.filter(m => m.label.toLowerCase().includes(q));
                if (!matched.length) {
                    drop.innerHTML = '<div style="padding:12px 14px;font-size:12px;color:#a1a1aa;">{{ __("app.search_no_results") }}</div>';
                } else {
                    drop.innerHTML = matched.map(m =>
                        `<a href="${m.url}" style="display:flex;align-items:center;gap:10px;padding:9px 14px;font-size:13px;color:#1e1b2e;text-decoration:none;border-radius:0;transition:background .12s;" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background=''">`+
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
                if (e.key === 'Escape') { this.value = ''; drop.style.display = 'none'; }
            });

            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !drop.contains(e.target)) {
                    drop.style.display = 'none';
                }
            });
        })();

        // ── 컬러 테마 시스템 ──────────────────────────────────
        const THEMES = {
            violet:{ t50:'#f5f3ff',t100:'#ede9fe',t200:'#ddd6fe',t300:'#c4b5fd',t400:'#a78bfa',t500:'#8b5cf6',t600:'#7c3aed',t700:'#6d28d9',tText:'#6d5ce7',tBg:'#f5f3ff' },
            blue:  { t50:'#eff6ff',t100:'#dbeafe',t200:'#bfdbfe',t300:'#93c5fd',t400:'#60a5fa',t500:'#3b82f6',t600:'#2563eb',t700:'#1d4ed8',tText:'#2563eb',tBg:'#eff6ff' },
            teal:  { t50:'#f0fdfa',t100:'#ccfbf1',t200:'#99f6e4',t300:'#5eead4',t400:'#2dd4bf',t500:'#14b8a6',t600:'#0d9488',t700:'#0f766e',tText:'#0d9488',tBg:'#f0fdfa' },
            green: { t50:'#f0fdf4',t100:'#dcfce7',t200:'#bbf7d0',t300:'#86efac',t400:'#4ade80',t500:'#22c55e',t600:'#16a34a',t700:'#15803d',tText:'#16a34a',tBg:'#f0fdf4' },
            amber: { t50:'#fffbeb',t100:'#fef3c7',t200:'#fde68a',t300:'#fcd34d',t400:'#fbbf24',t500:'#f59e0b',t600:'#d97706',t700:'#b45309',tText:'#d97706',tBg:'#fffbeb' },
        };
        window.applyTheme = function(name) {
            const t = THEMES[name]; if (!t) return;
            const r = document.documentElement;
            const vars = {'--t50':t.t50,'--t100':t.t100,'--t200':t.t200,'--t300':t.t300,'--t400':t.t400,'--t500':t.t500,'--t600':t.t600,'--t700':t.t700,'--tText':t.tText,'--tBg':t.tBg};
            for (const [k,v] of Object.entries(vars)) r.style.setProperty(k,v);
            document.body.style.background = t.tBg;
            const main = document.querySelector('main'); if (main) main.style.background = t.tBg;
            const avatar = document.getElementById('sw-avatar'); if (avatar) avatar.style.background = 'linear-gradient(135deg,'+t.t200+','+t.t300+')';
            localStorage.setItem('app-theme', name);
            document.querySelectorAll('.theme-swatch').forEach(function(s) {
                const active = s.dataset.theme === name;
                s.style.boxShadow = active ? '0 0 0 3px #fff,0 0 0 5px '+t.t500 : 'none';
                s.style.transform = active ? 'scale(1.15)' : 'scale(1)';
            });
        };
        document.addEventListener('click', function(e) {
            const sw = e.target.closest('.theme-swatch');
            if (sw) { applyTheme(sw.dataset.theme); return; }
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
        });
        applyTheme(localStorage.getItem('app-theme') || 'violet');

        // ── 글로벌 사이드바 접기/펼치기 ──────────────────────
        window.toggleGlobalSidebar = function() {
            const aside = document.getElementById('global-sidebar');
            if (!aside) return;
            const collapsed = aside.classList.toggle('gsb-collapsed');
            localStorage.setItem('gsb-collapsed', collapsed ? '1' : '0');
        };
        (function() {
            if (localStorage.getItem('gsb-collapsed') === '1') {
                document.getElementById('global-sidebar')?.classList.add('gsb-collapsed');
            }
        })();

        // ── 섹션 접힘/펼침 (내 프로젝트 / SR 접수) ───────────
        window.toggleSection = function(id) {
            var el = document.getElementById(id);
            var ch = document.getElementById('chevron-' + id);
            if (!el) return;
            var isCollapsed = el.style.maxHeight === '0px';
            el.style.maxHeight = isCollapsed ? '600px' : '0px';
            if (ch) ch.style.transform = isCollapsed ? '' : 'rotate(-90deg)';
            localStorage.setItem('sec-' + id, isCollapsed ? '0' : '1');
        };
        (function() {
            ['proj-list', 'sr-list'].forEach(function(id) {
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
                xhr.onprogress = function (e) {
                    if (e.lengthComputable && e.total > 0) {
                        hasLen = true;
                        setPct(el, (e.loaded / e.total) * 97);
                    } else if (!hasLen) {
                        setIndet(el);
                    }
                };

                xhr.onload = function () {
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
                        setTimeout(function () { URL.revokeObjectURL(blobUrl); }, 1500);
                        const pctEl = el._swDlp && el._swDlp.querySelector('.sw-dlp-pct');
                        if (pctEl) pctEl.textContent = '✓';
                        setTimeout(function () { removeBar(el); }, 1200);
                    } else {
                        removeBar(el);
                    }
                };
                xhr.onerror = xhr.onabort = function () { removeBar(el); };
                xhr.send();
            }

            document.addEventListener('click', function (e) {
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
                <form id="form-new-project" onsubmit="submitNewProject(event)" style="padding:20px 24px 24px;display:flex;flex-direction:column;gap:14px;">
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

                    <div style="display:flex;gap:10px;padding-top:4px;">
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
        function openNewProjectModal() {
            const modal = document.getElementById('modal-new-project');
            modal.style.display = 'flex';
            document.getElementById('form-new-project').reset();
            document.getElementById('np-error').style.display = 'none';
            setTimeout(() => modal.querySelector('input[name="name"]').focus(), 50);
        }
        function closeNewProjectModal() {
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
                    const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || '오류가 발생했습니다.');
                    errBox.textContent   = msgs;
                    errBox.style.display = 'block';
                    btn.disabled    = false;
                    btn.textContent = '{{ __("projects.create_project") }}';
                }
            } catch (err) {
                errBox.textContent   = '오류가 발생했습니다. 다시 시도해 주세요.';
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
        if (typeof window.MAINTENANCE_KEY === 'undefined') {
            window.MAINTENANCE_KEY   = '{{ addslashes(request()->route()?->getName() ?? '') }}';
            window.MAINTENANCE_NAME  = window.MAINTENANCE_KEY;
            window.MAINTENANCE_BLADE = '';
        }
        </script>
        {{-- @include('maintenance._panel') --}}
    </body>
</html>
