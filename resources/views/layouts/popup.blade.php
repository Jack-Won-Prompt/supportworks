<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
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
        :root {
            --t50:#f5f3ff; --t100:#ede9fe; --t200:#ddd6fe; --t300:#c4b5fd;
            --t400:#a78bfa; --t500:#8b5cf6; --t600:#7c3aed; --t700:#6d28d9;
            --tText:#6d5ce7; --tBg:#f5f3ff;
        }
        * { box-sizing: border-box; }
        html, body { margin:0; padding:0; height:100%; background:var(--tBg); font-family:'Figtree',sans-serif; }

        #popup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            height: 52px;
            background: #fff;
            border-bottom: 1px solid #e4e4e7;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        #popup-title {
            font-size: 14px;
            font-weight: 700;
            color: #18181b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #popup-title svg { color: var(--t500); }
        #popup-actions { display: flex; align-items: center; gap: 8px; }
        #popup-main { padding: 20px; height: calc(100vh - 52px); overflow-y: auto; }
    </style>
</head>
<body>
    <div id="popup-header">
        <div id="popup-title">
            @hasSection('popup-title')
                @yield('popup-title')
            @else
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ __('projects.planning') }}
            @endif
        </div>
        <div id="popup-actions" style="display:flex;align-items:center;gap:8px;">
            @yield('header-actions')
            <button onclick="if(window.parent&&window.parent.closeMinutePopup){window.parent.closeMinutePopup(false);}else if(window.parent&&window.parent.dbCloseMinutePopup){window.parent.dbCloseMinutePopup();}else if(window.parent&&window.parent.closeWeeklyReportPopup){window.parent.closeWeeklyReportPopup(false);}else if(window.parent&&window.parent.closeInqView){window.parent.closeInqView();}else if(window.parent&&window.parent.closePlanningModal){window.parent.closePlanningModal();}else{window.close();}"
                style="display:flex;align-items:center;gap:4px;padding:5px 11px;font-size:13px;color:#6b7280;border:1px solid #e4e4e7;border-radius:7px;background:#fff;cursor:pointer;"
                onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>{{ __('common.close') }}
            </button>
        </div>
    </div>
    <div id="popup-main">
        @yield('content')
    </div>
    @yield('scripts')
    @stack('scripts')
    <script>
    (function() {
        const THEMES = {
            violet:{ t50:'#f5f3ff',t100:'#ede9fe',t200:'#ddd6fe',t300:'#c4b5fd',t400:'#a78bfa',t500:'#8b5cf6',t600:'#7c3aed',t700:'#6d28d9',tText:'#6d5ce7',tBg:'#f5f3ff' },
            blue:  { t50:'#eff6ff',t100:'#dbeafe',t200:'#bfdbfe',t300:'#93c5fd',t400:'#60a5fa',t500:'#3b82f6',t600:'#2563eb',t700:'#1d4ed8',tText:'#2563eb',tBg:'#eff6ff' },
            teal:  { t50:'#f0fdfa',t100:'#ccfbf1',t200:'#99f6e4',t300:'#5eead4',t400:'#2dd4bf',t500:'#14b8a6',t600:'#0d9488',t700:'#0f766e',tText:'#0d9488',tBg:'#f0fdfa' },
            green: { t50:'#f0fdf4',t100:'#dcfce7',t200:'#bbf7d0',t300:'#86efac',t400:'#4ade80',t500:'#22c55e',t600:'#16a34a',t700:'#15803d',tText:'#16a34a',tBg:'#f0fdf4' },
            amber: { t50:'#fffbeb',t100:'#fef3c7',t200:'#fde68a',t300:'#fcd34d',t400:'#fbbf24',t500:'#f59e0b',t600:'#d97706',t700:'#b45309',tText:'#d97706',tBg:'#fffbeb' },
        };
        function applyTheme(name) {
            const t = THEMES[name] || THEMES.violet;
            const r = document.documentElement;
            r.style.setProperty('--t50',  t.t50);
            r.style.setProperty('--t100', t.t100);
            r.style.setProperty('--t200', t.t200);
            r.style.setProperty('--t300', t.t300);
            r.style.setProperty('--t400', t.t400);
            r.style.setProperty('--t500', t.t500);
            r.style.setProperty('--t600', t.t600);
            r.style.setProperty('--t700', t.t700);
            r.style.setProperty('--tText', t.tText);
            r.style.setProperty('--tBg',  t.tBg);
        }
        applyTheme(localStorage.getItem('app-theme') || 'violet');
        window.addEventListener('storage', function(e) {
            if (e.key === 'app-theme') applyTheme(e.newValue || 'violet');
        });
    })();
    </script>
</body>
</html>
