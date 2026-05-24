<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>요청 #{{ $r->id }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background:#f5f3ff; }
        .embed-wrap { padding:20px 24px; }
        .maint-sticky-bar { position: sticky; top: 0; z-index: 30; }
        .maint-sticky-bar.is-stuck { box-shadow: 0 4px 12px -4px rgba(0,0,0,.08); }
    </style>
</head>
<body>
    {{-- Quill 표준 이미지 모듈 + 이미지 주석 + 커스텀 다이얼로그 — _form 안의 즉시실행 스크립트보다 먼저 정의되도록 body 최상단에 위치 --}}
    @include('partials._quill-image-resize')
    @include('partials._mail-image-annotator')
    @include('partials.custom-dialog')

    <div class="embed-wrap">
        @include('maint-requests._form', ['isEmbed' => true])
    </div>

    @if(session('maint_modal_close'))
    <script>
        if (window.parent && window.parent.maintCloseDetailModal) {
            window.parent.maintHandleModalClose();
        }
    </script>
    @endif
</body>
</html>
