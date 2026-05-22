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
    // Phase 1 — data-accent 기반 액센트 적용 (--t* 변수는 app.css 가 디자인 토큰으로 브릿지)
    (function() {
        function apply(name) {
            const valid = ['coral','blue','green','yellow','purple'];
            if (!valid.includes(name)) name = 'blue';
            document.documentElement.setAttribute('data-accent', name);
        }
        // 새 키 → 옛 키 마이그레이션 지원
        let accent = localStorage.getItem('wsAccent');
        if (!accent) {
            const old = localStorage.getItem('app-theme');
            const map = { violet:'purple', blue:'blue', teal:'blue', green:'green', amber:'yellow', gray:'blue', white:'blue' };
            accent = old ? (map[old] || 'blue') : 'blue';
        }
        apply(accent);
        window.addEventListener('storage', function(e) {
            if (e.key === 'wsAccent') apply(e.newValue || 'blue');
        });
    })();
    </script>
</body>
</html>
