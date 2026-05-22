<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>SR {{ $eventLabel }} 알림</title>
</head>
<body style="margin:0;padding:0;background:#f5f3ff;font-family:-apple-system,'Segoe UI','Noto Sans KR',sans-serif;color:#1e1b2e;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f5f3ff;padding:24px 0;">
        <tr><td align="center">
            <table width="600" cellpadding="0" cellspacing="0" border="0" style="background:#fff;border-radius:14px;box-shadow:0 2px 14px rgba(0,0,0,.06);overflow:hidden;">

                <!-- 헤더 -->
                <tr><td style="background:linear-gradient(135deg,#0f86ef,#006cca);padding:20px 24px;color:#fff;">
                    <div style="font-size:11px;font-weight:600;letter-spacing:.06em;opacity:.85;text-transform:uppercase;">SR #{{ $request->id }} · {{ $eventLabel }}</div>
                    <div style="font-size:18px;font-weight:700;margin-top:4px;">{{ $summary ?: '제목 없음' }}</div>
                </td></tr>

                <!-- 본문 -->
                <tr><td style="padding:24px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:13px;line-height:1.7;">
                        <tr>
                            <td width="100" style="color:#6b7280;padding:6px 0;">회사</td>
                            <td style="color:#1e1b2e;font-weight:500;padding:6px 0;">{{ $companyName ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td style="color:#6b7280;padding:6px 0;">상태</td>
                            <td style="color:#1e1b2e;font-weight:500;padding:6px 0;">{{ $statusLabel ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td style="color:#6b7280;padding:6px 0;">우선순위</td>
                            <td style="color:#1e1b2e;font-weight:500;padding:6px 0;">{{ $priorityLabel ?: '-' }}</td>
                        </tr>
                        @if($coloAssignee)
                        <tr>
                            <td style="color:#6b7280;padding:6px 0;">{{ $companyName ?: '콜로' }} 담당자</td>
                            <td style="color:#1e1b2e;font-weight:500;padding:6px 0;">{{ $coloAssignee }}</td>
                        </tr>
                        @endif
                        @if($devAssignee)
                        <tr>
                            <td style="color:#6b7280;padding:6px 0;">링크더랩 담당자</td>
                            <td style="color:#1e1b2e;font-weight:500;padding:6px 0;">{{ $devAssignee }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td style="color:#6b7280;padding:6px 0;">{{ $eventLabel }} 시간</td>
                            <td style="color:#1e1b2e;padding:6px 0;">{{ $createdAt }}</td>
                        </tr>
                    </table>

                    @if($request->content)
                    <div style="margin-top:18px;padding:14px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;font-size:12.5px;color:#374151;line-height:1.6;">
                        {!! \Illuminate\Support\Str::limit(strip_tags($request->content), 400) !!}
                    </div>
                    @endif

                    <div style="margin-top:24px;text-align:center;">
                        <a href="{{ $detailUrl }}" style="display:inline-block;padding:11px 26px;background:#0f86ef;color:#fff;text-decoration:none;border-radius:9px;font-weight:600;font-size:13px;">SR 상세 보기 →</a>
                    </div>
                </td></tr>

                <!-- 푸터 -->
                <tr><td style="padding:14px 24px;background:#f8fafc;border-top:1px solid #e5e7eb;font-size:11px;color:#9ca3af;text-align:center;">
                    SupportWorks · 본 메일은 SR 등록·수정 시 자동 발송됩니다.
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
