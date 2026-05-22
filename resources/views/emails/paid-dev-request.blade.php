<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>추가 개발(유상) 승인 요청</title>
</head>
<body style="margin:0;padding:0;background:#f5f3ff;font-family:-apple-system,'Segoe UI','Noto Sans KR',sans-serif;color:#1e1b2e;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f5f3ff;padding:24px 0;">
        <tr><td align="center">
            <table width="620" cellpadding="0" cellspacing="0" border="0" style="background:#fff;border-radius:14px;box-shadow:0 2px 14px rgba(0,0,0,.06);overflow:hidden;">

                <tr><td style="background:linear-gradient(135deg,#f59e0b,#d97706);padding:22px 24px;color:#fff;">
                    <div style="font-size:11px;font-weight:600;letter-spacing:.06em;opacity:.9;text-transform:uppercase;">SR #{{ $request->id }} · 추가 개발(유상) 승인 요청</div>
                    <div style="font-size:18px;font-weight:700;margin-top:4px;">{{ $summary ?: '제목 없음' }}</div>
                </td></tr>

                <tr><td style="padding:24px;">
                    <p style="margin:0 0 18px;font-size:13.5px;color:#374151;line-height:1.65;">
                        <strong>{{ $requesterName }}</strong> 님이 SR <strong>#{{ $request->id }}</strong> 에 대해 추가 개발(유상) 승인을 요청했습니다. 아래 내용을 확인 후 검토해 주세요.
                    </p>

                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:13px;line-height:1.7;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;">
                        <tr>
                            <td width="120" style="color:#92400e;padding:10px 14px;font-weight:600;border-bottom:1px solid #fde68a;">회사</td>
                            <td style="color:#1e1b2e;padding:10px 14px;border-bottom:1px solid #fde68a;">{{ $companyName ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td style="color:#92400e;padding:10px 14px;font-weight:600;border-bottom:1px solid #fde68a;">예상 기간</td>
                            <td style="color:#1e1b2e;font-weight:700;padding:10px 14px;border-bottom:1px solid #fde68a;">{{ $days }}</td>
                        </tr>
                        <tr>
                            <td style="color:#92400e;padding:10px 14px;font-weight:600;border-bottom:1px solid #fde68a;">예상 비용</td>
                            <td style="color:#1e1b2e;font-weight:700;padding:10px 14px;border-bottom:1px solid #fde68a;">{{ $cost }}</td>
                        </tr>
                        <tr>
                            <td style="color:#92400e;padding:10px 14px;font-weight:600;vertical-align:top;">개발 설명</td>
                            <td style="color:#1e1b2e;padding:10px 14px;white-space:pre-wrap;line-height:1.65;">{{ $description }}</td>
                        </tr>
                    </table>

                    <div style="margin-top:24px;text-align:center;">
                        <a href="{{ $detailUrl }}" style="display:inline-block;padding:11px 26px;background:#0f86ef;color:#fff;text-decoration:none;border-radius:9px;font-weight:600;font-size:13px;">SR 상세 보기 →</a>
                    </div>

                    <p style="margin:18px 0 0;font-size:11px;color:#9ca3af;text-align:center;">전송: {{ $sentAt }} · SR 상태는 '추가 개발'로 변경되었습니다.</p>
                </td></tr>

                <tr><td style="padding:14px 24px;background:#f8fafc;border-top:1px solid #e5e7eb;font-size:11px;color:#9ca3af;text-align:center;">
                    SupportWorks · 본 메일은 SR 추가 개발 매니저 전송 시 자동 발송됩니다.
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
