<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/png">
    <title>{{ __('planning.share_page_title', ['title' => $doc->title]) }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        .topbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            height: 54px; background: rgba(20,17,35,.97); backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(196,181,253,.12);
            display: flex; align-items: center; gap: 12px; padding: 0 18px;
        }
        .topbar-logo  { font-size: 13px; font-weight: 800; color: #a78bfa; letter-spacing: -.3px; flex-shrink: 0; padding: 4px 10px; background: rgba(167,139,250,.12); border: 1px solid rgba(167,139,250,.2); border-radius: 7px; }
        .topbar-sep   { color: rgba(196,181,253,.25); flex-shrink: 0; }
        .topbar-title { flex: 1; overflow: hidden; font-size: 13px; font-weight: 600; color: #e5e7eb; white-space: nowrap; text-overflow: ellipsis; }
        .topbar-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; color: #a78bfa; padding: 4px 10px; border: 1px solid rgba(167,139,250,.25); border-radius: 20px; flex-shrink: 0; }

        .main { padding: 74px 24px 48px; max-width: 860px; margin: 0 auto; }

        .doc-meta {
            margin-bottom: 24px; padding: 16px 20px;
            background: #fff; border: 1px solid #e4e4e7; border-radius: 12px;
            display: flex; align-items: center; gap: 12px;
        }
        .doc-meta-title { font-size: 18px; font-weight: 800; color: #1e1b4b; flex: 1; }
        .doc-meta-project { font-size: 12px; color: #9ca3af; }
        .status-badge { padding: 4px 10px; font-size: 12px; font-weight: 700; border-radius: 20px; flex-shrink: 0; }

        /* Markdown */
        .md-render { background: #fff; border: 1px solid #e4e4e7; border-radius: 12px; padding: 28px 32px; font-size: 14px; line-height: 1.8; color: #1f2937; }
        .md-render h1 { font-size: 22px; font-weight: 800; color: #1e1b4b; margin: 0 0 20px; padding-bottom: 10px; border-bottom: 3px solid #c7d2fe; }
        .md-render h2 { font-size: 17px; font-weight: 700; color: #4338ca; margin: 32px 0 12px; padding-bottom: 6px; border-bottom: 1px solid #e0e7ff; }
        .md-render h3 { font-size: 15px; font-weight: 700; color: #0891b2; margin: 20px 0 8px; padding-left: 10px; border-left: 3px solid #67e8f9; }
        .md-render h4 { font-size: 14px; font-weight: 700; color: #374151; margin: 14px 0 6px; }
        .md-render p  { margin: 0 0 12px; }
        .md-render ul { margin: 0 0 12px 4px; padding-left: 20px; }
        .md-render ol { margin: 0 0 12px 4px; padding-left: 22px; }
        .md-render li { margin-bottom: 5px; }
        .md-render li > ul, .md-render li > ol { margin-top: 4px; margin-bottom: 4px; }
        .md-render hr { border: none; border-top: 2px solid #e0e7ff; margin: 28px 0; }
        .md-render strong { font-weight: 700; color: #111827; }
        .md-render em { font-style: italic; color: #4b5563; }
        .md-render a { color: #4f46e5; text-decoration: underline; word-break: break-all; }
        .md-render blockquote { margin: 14px 0; padding: 12px 16px; border-left: 4px solid #6366f1; background: #f5f3ff; border-radius: 0 8px 8px 0; color: #4c1d95; }
        .md-render blockquote p { margin: 0; }
        .md-render code { font-family: 'Courier New', monospace; font-size: 12px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #0369a1; border: 1px solid #e2e8f0; }
        .md-render pre { background: #1e293b; border-radius: 10px; padding: 16px 18px; overflow-x: auto; margin: 14px 0; }
        .md-render pre code { background: none; color: #e2e8f0; padding: 0; font-size: 12.5px; border: none; }
        .md-render table { width: 100%; border-collapse: collapse; margin: 14px 0; font-size: 13px; border-radius: 8px; overflow: hidden; box-shadow: 0 0 0 1px #e2e8f0; }
        .md-render table th { background: #eff6ff; padding: 9px 14px; text-align: left; font-weight: 700; color: #1e40af; border-bottom: 2px solid #bfdbfe; border-right: 1px solid #dbeafe; }
        .md-render table th:last-child { border-right: none; }
        .md-render table td { padding: 8px 14px; border-bottom: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; color: #374151; }
        .md-render table td:last-child { border-right: none; }
        .md-render table tr:last-child td { border-bottom: none; }
        .md-render table tr:nth-child(even) td { background: #f8fafc; }

        .empty-msg { text-align: center; padding: 48px; color: #9ca3af; font-size: 14px; }

        .footer { margin-top: 24px; text-align: center; font-size: 11px; color: #d1d5db; padding-bottom: 32px; }
    </style>
</head>
<body>

<div class="topbar">
    <span class="topbar-logo">SupportWorks</span>
    <span class="topbar-sep">›</span>
    <span class="topbar-title">{{ $doc->title }}</span>
    <span class="topbar-badge">
        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        {{ __('planning.share_link_badge') }}
    </span>
</div>

<div class="main">
    {{-- 문서 메타 --}}
    <div class="doc-meta">
        <div style="flex:1;min-width:0;">
            <div class="doc-meta-title">{{ $doc->title }}</div>
            @if($doc->description)
            <div class="doc-meta-project" style="margin-top:4px;">{{ $doc->description }}</div>
            @endif
            <div class="doc-meta-project" style="margin-top:4px;">
                {{ $doc->project->name }} · v{{ $doc->version }}
                @if($doc->approved_at)
                · {{ __('planning.meta_approved_short', ['date' => $doc->approved_at->format('Y.m.d')]) }}
                @endif
            </div>
        </div>
        @php
            $statusColors = ['draft'=>['#f3f4f6','#6b7280'],'ai_processed'=>['#eff6ff','#3b82f6'],'pending_review'=>['#fef3c7','#d97706'],'approved'=>['#d1fae5','#059669'],'rejected'=>['#fee2e2','#dc2626']];
            [$sbg,$stc] = $statusColors[$doc->status] ?? ['#f3f4f6','#6b7280'];
        @endphp
        <span class="status-badge" style="background:{{ $sbg }};color:{{ $stc }};">{{ $doc->status_label }}</span>
        <button id="pdf-dl-btn" onclick="downloadPlanningPdf()"
           style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;font-size:12px;font-weight:600;color:#dc2626;border:1.5px solid #fca5a5;border-radius:8px;background:#fff;cursor:pointer;flex-shrink:0;font-family:inherit;"
           onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            {{ __('planning.pdf_download') }}
        </button>
    </div>

    {{-- 기획서 본문 --}}
    @if($doc->content)
    <div class="md-render" id="md-render"></div>
    @else
    <div class="md-render">
        <p class="empty-msg">{{ __('planning.empty_content') }}</p>
    </div>
    @endif

    <div class="footer">{{ __('planning.share_footer') }}</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
@if($doc->content)
<script>
const RAW = @json($doc->content);
document.getElementById('md-render').innerHTML = marked.parse(RAW);
</script>
@endif

<script>
async function downloadPlanningPdf() {
    const btn = document.getElementById('pdf-dl-btn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.style.opacity = '0.65';
    btn.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation:spin .8s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> {{ __('planning.pdf_generating') }}';
    try {
        const resp = await fetch('{{ route("planning.public-print", $token) }}');
        if (!resp.ok) throw new Error(@json(__('planning.server_error')).replace(':status', resp.status));
        const blob = await resp.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = '{{ addslashes($doc->title) }}_v{{ $doc->version }}.pdf';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } catch (err) {
        alert(@json(__('planning.pdf_download_failed')).replace(':message', err.message));
    } finally {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.innerHTML = origHtml;
    }
}
</script>
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</body>
</html>
