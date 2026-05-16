<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('discussions.public_title', ['title' => $discussion->title]) }}</title>
<style>
    body { margin:0;padding:0;background:#f0eeff;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;min-height:100vh;color:#1f2937; }
    .topbar { background:#1a1730;padding:0 18px;height:50px;display:flex;align-items:center;gap:10px;color:#e5e7eb;flex-shrink:0; }
    .topbar-logo { font-size:14px;font-weight:800;color:#fff;letter-spacing:-.01em; }
    .topbar-logo strong { color:#c4b5fd; }
    .topbar-sep { color:#4b5563;font-size:11px; }
    .topbar-title { font-size:13px;font-weight:600;color:#e5e7eb;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
    .topbar-badge { font-size:11px;font-weight:700;padding:3px 9px;border-radius:5px;background:#3f1515;color:#f9a8a8;flex-shrink:0; }
    .topbar-shared { font-size:11px;color:#a78bfa;display:inline-flex;align-items:center;gap:4px;flex-shrink:0; }
    .wrap { max-width:760px;margin:32px auto;padding:0 16px; }
    .card { background:#fff;border-radius:14px;border:1px solid #ede9fe;box-shadow:0 8px 32px rgba(0,0,0,.08);overflow:hidden; }
    .card-head { padding:20px 24px;background:linear-gradient(135deg,#faf5ff,#ede9fe);border-bottom:1px solid #ddd6fe; }
    .card-head h1 { margin:0;font-size:14px;font-weight:700;color:#5b21b6;letter-spacing:.04em;text-transform:uppercase;display:flex;align-items:center;gap:6px; }
    .card-head h2 { margin:6px 0 0;font-size:18px;font-weight:800;color:#18181b;line-height:1.4;word-break:break-word; }
    .card-meta { margin-top:10px;font-size:12px;color:#6b7280;display:flex;flex-wrap:wrap;gap:8px;align-items:center; }
    .card-meta b { color:#374151; }
    .card-body { padding:22px 24px; }
    .author-row { display:flex;align-items:center;gap:10px;padding:12px 14px;background:#faf5ff;border:1px solid #ede9fe;border-radius:10px;margin-bottom:14px; }
    .avatar { width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#c4b5fd,#9b8afb);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;flex-shrink:0; }
    .author-info p { margin:0;font-size:13px; }
    .author-name { font-weight:700;color:#1f2937; }
    .author-time { color:#9ca3af;font-size:11px; }
    .content { font-size:14px;color:#1f2937;line-height:1.7;word-break:break-word; }
    .content p { margin:0 0 8px; }
    .content h1,.content h2,.content h3,.content h4 { margin:14px 0 6px;font-weight:700;color:#18181b; }
    .content h1 { font-size:17px; } .content h2 { font-size:16px; } .content h3 { font-size:15px; } .content h4 { font-size:14px; }
    .content ul,.content ol { padding-left:24px;margin:6px 0; }
    .content li { margin-bottom:3px; }
    .content a { color:#7c3aed;text-decoration:underline; }
    .content img { max-width:100%;height:auto;border-radius:6px;margin:6px 0; }
    .content code { background:#f3f4f6;color:#5b21b6;padding:2px 6px;border-radius:4px;font-size:13px; }
    .content pre { background:#1f2937;color:#e5e7eb;padding:12px 14px;border-radius:8px;overflow-x:auto;margin:8px 0; }
    .content pre code { background:transparent;color:inherit;padding:0; }
    .content blockquote { border-left:3px solid #c4b5fd;margin:8px 0;padding:4px 14px;color:#6b7280;background:#faf5ff; }
    .ctx { margin-top:18px;padding:14px 16px;background:#fafafa;border:1px solid #f3f4f6;border-radius:10px; }
    .ctx-label { font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.04em;text-transform:uppercase;margin-bottom:6px; }
    .ctx-title { font-size:14px;font-weight:600;color:#374151;margin-bottom:3px; }
    .ctx-meta  { font-size:11px;color:#9ca3af; }
    .footer { text-align:center;padding:24px 0 40px;font-size:11px;color:#9ca3af; }
    .footer a { color:#7c3aed;text-decoration:none;font-weight:600; }
</style>
</head>
<body>

<div class="topbar">
    <span class="topbar-logo"><strong>Support</strong>Works</span>
    <span class="topbar-sep">›</span>
    <span class="topbar-title">{{ $discussion->title }}</span>
    <span class="topbar-shared">
        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        {{ __('discussions.public_shared_label') }}
    </span>
</div>

<div class="wrap">
    <div class="card">
        <div class="card-head">
            <h1>
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                {{ __('discussions.public_shared_label') }}
            </h1>
            <h2>{{ $discussion->title }}</h2>
            <div class="card-meta">
                <span>{{ __('discussions.public_project') }}<b>{{ $discussion->project->name ?? '' }}</b></span>
                <span>·</span>
                <span>{{ __('discussions.public_author') }}<b>{{ $discussion->author->name ?? '' }}</b></span>
                @if($discussion->created_at)
                <span>·</span>
                <span>{{ $discussion->created_at->format('Y-m-d') }}</span>
                @endif
            </div>
        </div>

        <div class="card-body">
            <div class="author-row">
                <div class="avatar">{{ mb_substr($comment->user->name ?? '?', 0, 1) }}</div>
                <div class="author-info" style="flex:1;min-width:0;">
                    <p class="author-name">{{ $comment->user->name ?? __('discussions.public_unknown') }}</p>
                    <p class="author-time">{{ optional($comment->created_at)->format('Y-m-d H:i') }}</p>
                </div>
            </div>

            <div id="cmt-content" class="content" data-md="{{ $comment->content }}"></div>

            @if($discussion->content)
            <div class="ctx">
                <p class="ctx-label">{{ __('discussions.public_context_label') }}</p>
                <div id="ctx-content" class="content" style="font-size:13px;color:#4b5563;" data-md="{{ $discussion->content }}"></div>
            </div>
            @endif
        </div>
    </div>

    <p class="footer">SupportWorks · <a href="{{ url('/') }}">{{ __('discussions.public_login_cta') }}</a></p>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>
<script>
    marked.setOptions({ breaks: true, gfm: true });
    function render(elId) {
        const el = document.getElementById(elId);
        if (!el) return;
        const md = el.dataset.md || '';
        el.innerHTML = DOMPurify.sanitize(marked.parse(md));
    }
    render('cmt-content');
    render('ctx-content');
</script>

</body>
</html>
