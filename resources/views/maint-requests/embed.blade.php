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
