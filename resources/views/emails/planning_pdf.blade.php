<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>{{ $doc->title }}</title>
<style>

* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'MalgunGothic', sans-serif;
    font-size: 11pt;
    color: #1f2937;
    line-height: 1.6;
}

/* ── 헤더 ── */
.header {
    background-color: #4f46e5;
    padding: 28px 36px;
    margin-bottom: 0;
}
.header-logo {
    font-size: 12pt;
    font-weight: bold;
    color: #c7d2fe;
    margin-bottom: 12px;
}
.header h1 {
    font-size: 18pt;
    font-weight: bold;
    color: #ffffff;
    margin-bottom: 10px;
    line-height: 1.3;
}
.header-meta {
    font-size: 10pt;
    color: #c7d2fe;
}
.header-meta span {
    margin-right: 14px;
}

/* ── 본문 ── */
.content {
    padding: 28px 36px;
}

/* 마크다운 스타일 */
.content h1 { font-size: 16pt; font-weight: bold; color: #1e1b4b; margin: 0 0 14px; padding-bottom: 6px; border-bottom: 2px solid #c7d2fe; }
.content h2 { font-size: 14pt; font-weight: bold; color: #4338ca; margin: 24px 0 10px; padding-bottom: 4px; border-bottom: 1px solid #e0e7ff; }
.content h3 { font-size: 12pt; font-weight: bold; color: #0891b2; margin: 16px 0 6px; padding-left: 8px; border-left: 3px solid #67e8f9; }
.content h4 { font-size: 11pt; font-weight: bold; color: #374151; margin: 12px 0 4px; }
.content p  { margin: 0 0 10px; font-size: 11pt; }
.content ul, .content ol { margin: 0 0 10px 16px; padding-left: 4px; }
.content li { margin-bottom: 4px; font-size: 11pt; }
.content hr { border: none; border-top: 1px solid #e0e7ff; margin: 20px 0; }
.content strong { font-weight: bold; color: #111827; }
.content em { font-family: 'MalgunGothic', sans-serif; font-style: italic; color: #4b5563; }
.content a { color: #4f46e5; }
.content blockquote { border-left: 3px solid #c7d2fe; padding-left: 12px; margin: 10px 0; color: #4b5563; }
.content code { background: #f3f4f6; padding: 1px 4px; border-radius: 3px; font-size: 10pt; font-family: 'MalgunGothic', 'Courier New', monospace; color: #374151; }
.content pre { background: #1e1b4b; padding: 12px 16px; border-radius: 6px; margin: 10px 0; }
.content pre code { background: none; color: #e0e7ff; font-size: 10pt; padding: 0; }
.content table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 10pt; }
.content table th { background: #ede9fe; color: #4338ca; padding: 7px 10px; text-align: left; border: 1px solid #ddd6fe; font-weight: bold; }
.content table td { padding: 6px 10px; border: 1px solid #e5e7eb; }
.content table tr:nth-child(even) td { background: #faf9ff; }

/* ── 푸터 ── */
.footer {
    border-top: 1px solid #e4e4e7;
    padding: 16px 36px;
    font-size: 9pt;
    color: #9ca3af;
}
</style>
</head>
<body>

<div class="header">
    <div class="header-logo">SupportWorks</div>
    <h1>{{ $doc->title }}</h1>
    <div class="header-meta">
        <span>{{ $project->name }}</span>
        <span>v{{ $doc->version }}</span>
        <span>{{ $doc->status_label }}</span>
        <span>{{ now()->format('Y.m.d') }}</span>
    </div>
</div>

<div class="content">
    {!! $htmlContent !!}
</div>

<div class="footer">
    SupportWorks 기획서 &nbsp;|&nbsp;
    @if(!empty($sentBy))
    발송: {{ $sentBy->name }} &nbsp;|&nbsp;
    @endif
    {{ now()->format('Y-m-d H:i') }}
</div>

</body>
</html>
