<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $doc->title }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Malgun Gothic', '맑은 고딕', sans-serif; background: #f5f3ff; color: #1f2937; line-height: 1.7; }
.wrap { max-width: 860px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(109,40,217,.10); overflow: hidden; }
.header { background: linear-gradient(135deg,#7c3aed 0%,#4f46e5 50%,#2563eb 100%); padding: 40px 48px 36px; }
.header-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
.logo-box { width: 36px; height: 36px; background: rgba(255,255,255,.2); border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 900; color: #fff; }
.logo-name { font-size: 16px; font-weight: 800; color: #fff; letter-spacing: -0.3px; }
.header h1 { font-size: 22px; font-weight: 800; color: #fff; margin-bottom: 10px; line-height: 1.35; }
.meta { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.meta-chip { background: rgba(255,255,255,.18); border-radius: 20px; padding: 4px 12px; font-size: 12px; color: #fff; font-weight: 500; }
.content { padding: 40px 48px 48px; }
.content h1 { font-size: 22px; font-weight: 800; color: #1e1b4b; margin: 0 0 20px; padding-bottom: 10px; border-bottom: 3px solid #c7d2fe; }
.content h2 { font-size: 17px; font-weight: 700; color: #4338ca; margin: 32px 0 12px; padding-bottom: 6px; border-bottom: 1px solid #e0e7ff; }
.content h3 { font-size: 15px; font-weight: 700; color: #0891b2; margin: 20px 0 8px; padding-left: 10px; border-left: 3px solid #67e8f9; }
.content h4 { font-size: 14px; font-weight: 700; color: #374151; margin: 14px 0 6px; }
.content p { margin: 0 0 12px; font-size: 14px; }
.content ul, .content ol { margin: 0 0 12px 4px; padding-left: 20px; }
.content li { margin-bottom: 5px; font-size: 14px; }
.content hr { border: none; border-top: 2px solid #e0e7ff; margin: 28px 0; }
.content strong { font-weight: 700; color: #111827; }
.content em { font-style: italic; color: #4b5563; }
.content a { color: #4f46e5; }
.content blockquote { border-left: 4px solid #c7d2fe; padding-left: 16px; margin: 12px 0; color: #4b5563; font-style: italic; }
.content code { background: #f3f4f6; padding: 1px 5px; border-radius: 4px; font-size: 13px; font-family: 'Courier New', monospace; color: #374151; }
.content pre { background: #1e1b4b; padding: 16px 20px; border-radius: 10px; overflow-x: auto; margin: 12px 0; }
.content pre code { background: none; color: #e0e7ff; font-size: 13px; padding: 0; }
.content table { width: 100%; border-collapse: collapse; margin: 12px 0; }
.content table th { background: #ede9fe; color: #4338ca; padding: 8px 12px; font-size: 13px; text-align: left; border: 1px solid #ddd6fe; }
.content table td { padding: 8px 12px; font-size: 13px; border: 1px solid #e5e7eb; }
.content table tr:nth-child(even) td { background: #faf9ff; }
.footer { border-top: 1px solid #f0f0f0; padding: 20px 48px; display: flex; align-items: center; justify-content: space-between; }
.footer-info { font-size: 12px; color: #9ca3af; }
.footer-sent { font-size: 11px; color: #a78bfa; }
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="header-logo">
            <div class="logo-box">S</div>
            <span class="logo-name">SupportWorks</span>
        </div>
        <h1>{{ $doc->title }}</h1>
        <div class="meta">
            <span class="meta-chip">{{ $project->name }}</span>
            <span class="meta-chip">v{{ $doc->version }}</span>
            <span class="meta-chip">{{ $doc->status_label }}</span>
            <span class="meta-chip">{{ now()->format('Y.m.d H:i') }}</span>
        </div>
    </div>
    <div class="content">
        {!! $htmlContent !!}
    </div>
    <div class="footer">
        <span class="footer-info">SupportWorks 기획서 · 자동 생성된 문서입니다.</span>
        <span class="footer-sent">발송: {{ $sentBy->name }}</span>
    </div>
</div>
</body>
</html>
