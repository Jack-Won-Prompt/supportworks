<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>{{ $doc->title }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #fff; font-family: 'MalgunGothic', 'Malgun Gothic', DejaVu Sans, sans-serif; font-size: 11pt; color: #1f2937; line-height: 1.7; }

        .cover-page { text-align: center; border-bottom: 3px solid #e0e7ff; margin-bottom: 48px; padding: 60px 0 48px; }
        .cover-logo  { font-size: 10px; font-weight: 700; color: #9ca3af; letter-spacing: .1em; text-transform: uppercase; margin-bottom: 28px; }
        .cover-title { font-size: 24pt; font-weight: 800; color: #1e1b4b; margin-bottom: 12px; line-height: 1.3; }
        .cover-sub   { font-size: 12px; color: #4f46e5; font-weight: 600; margin-bottom: 28px; }
        .cover-meta  { font-size: 10px; color: #9ca3af; }
        .cover-meta span { margin: 0 10px; }

        .md-body h1 { font-size: 18pt; font-weight: 800; color: #1e1b4b; margin: 0 0 16px; padding-bottom: 8px; border-bottom: 3px solid #c7d2fe; }
        .md-body h2 { font-size: 14pt; font-weight: 700; color: #4338ca; margin: 28px 0 10px; padding-bottom: 5px; border-bottom: 1px solid #e0e7ff; }
        .md-body h3 { font-size: 12pt; font-weight: 700; color: #0891b2; margin: 20px 0 7px; padding-left: 10px; border-left: 3px solid #67e8f9; }
        .md-body h4 { font-size: 11pt; font-weight: 700; color: #374151; margin: 14px 0 5px; }
        .md-body p  { margin: 0 0 10px; }
        .md-body ul { margin: 0 0 10px 4px; padding-left: 20px; }
        .md-body ol { margin: 0 0 10px 4px; padding-left: 22px; }
        .md-body li { margin-bottom: 4px; }
        .md-body hr { border: none; border-top: 2px solid #e0e7ff; margin: 24px 0; }
        .md-body strong { font-weight: 700; color: #111827; }
        .md-body em { font-family: 'MalgunGothic', sans-serif; font-style: italic; color: #4b5563; }
        .md-body a  { color: #4f46e5; }
        .md-body blockquote { margin: 10px 0; padding: 10px 14px; border-left: 4px solid #6366f1; background: #f5f3ff; color: #4c1d95; }
        .md-body blockquote p { margin: 0; }
        .md-body code { font-family: 'MalgunGothic', 'Courier New', monospace; font-size: 10pt; background: #f1f5f9; padding: 1px 5px; border-radius: 3px; color: #0369a1; }
        .md-body pre  { background: #1e293b; border-radius: 6px; padding: 12px 14px; margin: 10px 0; }
        .md-body pre code { background: none; color: #e2e8f0; padding: 0; }
        .md-body table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 10pt; }
        .md-body table th { background: #eff6ff; padding: 7px 10px; text-align: left; font-weight: 700; color: #1e40af; border: 1px solid #bfdbfe; }
        .md-body table td { padding: 6px 10px; border: 1px solid #e2e8f0; color: #374151; }
        .md-body table tr:nth-child(even) td { background: #f8fafc; }
    </style>
</head>
<body>

<div class="cover-page">
    <div class="cover-logo">SupportWorks</div>
    <div class="cover-title">{{ $doc->title }}</div>
    <div class="cover-sub">프로젝트 기획서</div>
    <div class="cover-meta">
        <span>프로젝트: {{ $doc->project->name }}</span>
        <span>버전: v{{ $doc->version }}</span>
        <span>상태: {{ $doc->status_label }}</span>
        @if($doc->approved_at)
        <span>승인일: {{ $doc->approved_at->format('Y.m.d') }}</span>
        @endif
    </div>
</div>

@if($htmlContent)
<div class="md-body">{!! $htmlContent !!}</div>
@else
<div class="md-body" style="color:#9ca3af;text-align:center;padding:40px;">기획서 내용이 없습니다.</div>
@endif

</body>
</html>
