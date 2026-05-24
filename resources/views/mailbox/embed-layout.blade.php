<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '메일')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html, body { margin:0; padding:0; height:100%; background:var(--tBg, #fafafa); font-family:'Pretendard','Noto Sans KR',sans-serif; }
        .mb-embed-body { padding:18px 22px; }
    </style>
    @stack('styles')
</head>
<body>
    {{-- Quill 표준 이미지 모듈 — embed-content 안의 즉시실행 스크립트보다 먼저 정의되도록 body 최상단에 위치 --}}
    @include('partials._quill-image-resize')
    {{-- 메일 본문 이미지 주석 --}}
    @include('partials._mail-image-annotator')
    {{-- 커스텀 다이얼로그 (__confirm / __alert / __prompt) --}}
    @include('partials.custom-dialog')

    <div class="mb-embed-body">
        @yield('embed-content')
    </div>
    @stack('scripts')
    <script>
    (function() {
        function apply(name) {
            const valid = ['coral','blue','green','yellow','purple'];
            if (!valid.includes(name)) name = 'blue';
            document.documentElement.setAttribute('data-accent', name);
        }
        let accent = localStorage.getItem('wsAccent');
        if (!accent) {
            const old = localStorage.getItem('app-theme');
            const map = { violet:'purple', blue:'blue', teal:'blue', green:'green', amber:'yellow', gray:'blue', white:'blue' };
            accent = old ? (map[old] || 'blue') : 'blue';
        }
        apply(accent);
    })();
    </script>
</body>
</html>
