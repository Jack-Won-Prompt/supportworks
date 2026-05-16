<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $doc->title }} — {{ __('planning.doc_suffix') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { background: #fff; font-family: 'Malgun Gothic', '맑은 고딕', -apple-system, sans-serif; font-size: 12pt; color: #1f2937; }

        /* 화면용 */
        @media screen {
            body { max-width: 860px; margin: 0 auto; padding: 40px 32px 80px; }
            .print-bar {
                position: fixed; bottom: 24px; right: 24px; z-index: 100;
                display: flex; gap: 8px;
            }
            .btn-print {
                display: flex; align-items: center; gap: 6px;
                padding: 10px 20px; font-size: 13px; font-weight: 700;
                color: #fff; background: #4f46e5; border: none; border-radius: 9px;
                cursor: pointer; box-shadow: 0 4px 16px rgba(79,70,229,.35);
            }
            .btn-print:hover { background: #4338ca; }
            .btn-close {
                padding: 10px 16px; font-size: 13px; font-weight: 600;
                color: #6b7280; background: #fff; border: 1px solid #e4e4e7;
                border-radius: 9px; cursor: pointer;
            }
            .btn-close:hover { background: #f4f4f5; }
        }

        /* 인쇄용 */
        @media print {
            body { padding: 0; margin: 0; font-size: 11pt; }
            .print-bar { display: none !important; }
            .cover-page { page-break-after: always; }
            h1, h2 { page-break-after: avoid; }
            pre, table { page-break-inside: avoid; }
        }

        /* 표지 */
        .cover-page {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 60vh; text-align: center; border-bottom: 3px solid #e0e7ff;
            margin-bottom: 48px; padding-bottom: 48px;
        }
        .cover-logo { font-size: 11px; font-weight: 700; color: #9ca3af; letter-spacing: .1em; text-transform: uppercase; margin-bottom: 32px; }
        .cover-title { font-size: 28pt; font-weight: 800; color: #1e1b4b; margin-bottom: 12px; line-height: 1.3; }
        .cover-sub { font-size: 13px; color: #4f46e5; font-weight: 600; margin-bottom: 32px; }
        .cover-meta { display: flex; gap: 24px; font-size: 11px; color: #9ca3af; }
        .cover-meta span { display: flex; align-items: center; gap: 4px; }

        /* 본문 마크다운 */
        .md-body h1 { font-size: 20pt; font-weight: 800; color: #1e1b4b; margin: 0 0 18px; padding-bottom: 10px; border-bottom: 3px solid #c7d2fe; }
        .md-body h2 { font-size: 15pt; font-weight: 700; color: #4338ca; margin: 36px 0 12px; padding-bottom: 6px; border-bottom: 1px solid #e0e7ff; }
        .md-body h3 { font-size: 13pt; font-weight: 700; color: #0891b2; margin: 24px 0 8px; padding-left: 10px; border-left: 3px solid #67e8f9; }
        .md-body h4 { font-size: 12pt; font-weight: 700; color: #374151; margin: 16px 0 6px; }
        .md-body p  { margin: 0 0 10px; line-height: 1.8; }
        .md-body ul { margin: 0 0 10px 4px; padding-left: 20px; }
        .md-body ol { margin: 0 0 10px 4px; padding-left: 22px; }
        .md-body li { margin-bottom: 4px; line-height: 1.7; }
        .md-body hr { border: none; border-top: 2px solid #e0e7ff; margin: 28px 0; }
        .md-body strong { font-weight: 700; color: #111827; }
        .md-body em { font-style: italic; color: #4b5563; }
        .md-body a { color: #4f46e5; }
        .md-body blockquote { margin: 12px 0; padding: 10px 16px; border-left: 4px solid #6366f1; background: #f5f3ff; border-radius: 0 6px 6px 0; color: #4c1d95; }
        .md-body blockquote p { margin: 0; }
        .md-body code { font-family: 'Courier New', monospace; font-size: 10pt; background: #f1f5f9; padding: 1px 5px; border-radius: 3px; color: #0369a1; }
        .md-body pre { background: #1e293b; border-radius: 8px; padding: 14px 16px; overflow-x: auto; margin: 12px 0; }
        .md-body pre code { background: none; color: #e2e8f0; padding: 0; font-size: 10pt; }
        .md-body table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 11pt; }
        .md-body table th { background: #eff6ff; padding: 8px 12px; text-align: left; font-weight: 700; color: #1e40af; border: 1px solid #bfdbfe; }
        .md-body table td { padding: 7px 12px; border: 1px solid #e2e8f0; color: #374151; }
        .md-body table tr:nth-child(even) td { background: #f8fafc; }

        /* 푸터 (화면) */
        @media screen {
            .print-footer { margin-top: 48px; padding-top: 20px; border-top: 1px solid #f4f4f5; font-size: 11px; color: #d1d5db; text-align: center; }
        }
        @media print {
            .print-footer { display: none; }
        }
    </style>
</head>
<body>

    {{-- 표지 --}}
    <div class="cover-page">
        <div class="cover-logo">SupportWorks</div>
        <div class="cover-title">{{ $doc->title }}</div>
        <div class="cover-sub">{{ __('planning.project_planning') }}</div>
        <div class="cover-meta">
            <span>{{ __('planning.meta_project', ['name' => $doc->project->name]) }}</span>
            <span>{{ __('planning.meta_version', ['version' => $doc->version]) }}</span>
            <span>{{ __('planning.meta_status', ['status' => $doc->status_label]) }}</span>
            @if($doc->approved_at)
            <span>{{ __('planning.meta_approved', ['date' => $doc->approved_at->format('Y.m.d')]) }}</span>
            @endif
        </div>
    </div>

    {{-- 본문 --}}
    @if($doc->content)
    <div class="md-body" id="md-body"></div>
    @else
    <div class="md-body" style="color:#9ca3af;text-align:center;padding:40px;">{{ __('planning.empty_content') }}</div>
    @endif

    <div class="print-footer">{{ __('planning.print_footer') }}</div>

    {{-- 인쇄 버튼 (화면에서만 표시) --}}
    <div class="print-bar">
        <button class="btn-close" onclick="window.close()">{{ __('common.close') }}</button>
        <button class="btn-print" onclick="window.print()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            {{ __('planning.btn_print_save') }}
        </button>
    </div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
@if($doc->content)
<script>
document.getElementById('md-body').innerHTML = marked.parse(@json($doc->content));
window.addEventListener('load', () => setTimeout(() => window.print(), 400));
</script>
@endif

</body>
</html>
