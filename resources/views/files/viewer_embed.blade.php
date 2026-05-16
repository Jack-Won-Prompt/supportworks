<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $file->original_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; padding: 0; background: #1a1730; overflow: hidden; }
    </style>
</head>
<body>

{{-- 버전 비교 모달의 iframe 안에서 로드되는 단독 뷰어. 미리보기 모달 전체를 재사용한다. --}}
@include('partials.file-preview-modal')

<script>
document.addEventListener('DOMContentLoaded', function () {
    var params  = new URLSearchParams(window.location.search);
    var version = params.get('version');
    var base    = @json(route('projects.files.preview-data', [$project, $file]));
    var url     = version ? base + '?version=' + encodeURIComponent(version) : base;
    openPreview({{ $file->id }}, {{ $project->id }}, url);
});
</script>

</body>
</html>
