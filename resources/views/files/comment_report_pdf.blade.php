<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>의견 보고서 — {{ $ctx->sourceName }}</title>
    <style>
        @page { margin: 28mm 18mm 22mm 18mm; }
        body {
            font-family: 'Malgun Gothic', '맑은 고딕', 'NanumGothic', sans-serif;
            font-size: 11pt; color: #1f2937; line-height: 1.55;
        }
        .cover-title  { font-size: 22pt; font-weight: 700; color: #312e81; margin-bottom: 6px; }
        .cover-meta   { font-size: 10pt; color: #6b7280; line-height: 1.7; }
        .meta-row     { margin-bottom: 2px; }
        .meta-key     { display: inline-block; width: 78px; color: #9ca3af; }
        hr.divider    { border: none; border-top: 1px solid #e5e7eb; margin: 14px 0 22px; }

        .page-section { margin-top: 22px; page-break-inside: avoid; }
        .page-h       {
            font-size: 13pt; font-weight: 700; color: #4f46e5;
            border-bottom: 2px solid #4f46e5; padding-bottom: 4px; margin-bottom: 10px;
        }
        .page-h .count{ font-size: 9pt; color: #9ca3af; font-weight: 500; margin-left: 6px; }

        .comment      {
            border: 1px solid #e5e7eb; border-radius: 6px;
            padding: 10px 12px; margin-bottom: 10px; background: #fafafa;
        }
        .comment-head { font-size: 10pt; color: #6b7280; margin-bottom: 5px; }
        .comment-author { font-weight: 700; color: #1f2937; margin-right: 8px; }
        .comment-body { font-size: 11pt; color: #1f2937; white-space: pre-wrap; word-wrap: break-word; }
        .resolved-tag {
            display: inline-block; padding: 1px 6px; border-radius: 3px;
            background: #dcfce7; color: #166534; font-size: 9pt; font-weight: 700;
            margin-left: 6px;
        }

        .replies      { margin-top: 8px; padding-left: 14px; border-left: 3px solid #ddd6fe; }
        .reply        { margin-top: 7px; }
        .reply-head   { font-size: 9pt; color: #7c3aed; margin-bottom: 3px; }
        .reply-author { font-weight: 700; color: #6d28d9; margin-right: 6px; }
        .reply-body   { font-size: 10pt; color: #374151; white-space: pre-wrap; word-wrap: break-word; }

        .empty        { text-align: center; padding: 40px 20px; color: #9ca3af; font-size: 11pt; }
        .footer-note  { margin-top: 30px; font-size: 9pt; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>

    <div class="cover-title">{{ $ctx->sourceName }} — 의견 보고서</div>
    <div class="cover-meta">
        <div class="meta-row"><span class="meta-key">파일</span> {{ $ctx->sourceName }}</div>
        <div class="meta-row"><span class="meta-key">버전</span> v{{ $ctx->version }}{{ $ctx->isCurrentVersion ? ' (현재)' : '' }}</div>
        <div class="meta-row"><span class="meta-key">생성</span> {{ $ctx->generatedAt }}{{ $ctx->generatedByName ? ' / ' . $ctx->generatedByName : '' }}</div>
        <div class="meta-row"><span class="meta-key">의견 수</span> {{ count($ctx->rootComments) }} 건</div>
    </div>
    <hr class="divider">

    @if (count($commentsByPage) === 0)
        <div class="empty">등록된 의견이 없습니다.</div>
    @else
        @foreach ($commentsByPage as $pageNo => $comments)
            <div class="page-section">
                <div class="page-h">
                    @if ($pageNo > 0)
                        페이지 {{ $pageNo }}
                    @else
                        (페이지 미지정)
                    @endif
                    <span class="count">의견 {{ count($comments) }}건</span>
                </div>

                @foreach ($comments as $c)
                    <div class="comment">
                        <div class="comment-head">
                            <span class="comment-author">{{ $c->user?->name ?? $c->guest_name ?? '외부' }}</span>
                            <span>{{ optional($c->created_at)->format('Y-m-d H:i') }}</span>
                            @if ($c->resolved)
                                <span class="resolved-tag">해결됨</span>
                            @endif
                        </div>
                        <div class="comment-body">{{ $c->content }}</div>

                        @if ($c->replies && count($c->replies) > 0)
                            <div class="replies">
                                @foreach ($c->replies as $r)
                                    <div class="reply">
                                        <div class="reply-head">
                                            <span class="reply-author">↳ {{ $r->user?->name ?? $r->guest_name ?? '외부' }}</span>
                                            <span>{{ optional($r->created_at)->format('Y-m-d H:i') }}</span>
                                        </div>
                                        <div class="reply-body">{{ $r->content }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif

    <div class="footer-note">SupportWorks · 파일 의견 보고서</div>

</body>
</html>
