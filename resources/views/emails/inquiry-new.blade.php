<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>신규 문의 등록</title>
</head>
<body style="margin:0;padding:0;background:#f0eeff;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0eeff;padding:40px 16px;">
<tr><td align="center">

  <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;border:1px solid #ddd6fe;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

    {{-- 헤더 --}}
    <tr>
      <td style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);padding:32px 36px 28px;text-align:center;">
        <div style="font-size:36px;margin-bottom:10px;">📨</div>
        <h1 style="margin:0 0 6px;font-size:18px;font-weight:700;color:#4c1d95;">신규 문의 등록</h1>
        <p style="margin:0;font-size:13px;color:#6d28d9;">{{ $inquirerName }}님이 새 문의를 등록했습니다.</p>
      </td>
    </tr>

    {{-- 문의자 정보 --}}
    <tr>
      <td style="padding:28px 36px 0;">
        <table cellpadding="0" cellspacing="0" style="width:100%;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
          <tr>
            <td style="padding:14px 18px;border-bottom:1px solid #e5e7eb;background:#f3f4f6;">
              <span style="font-size:11px;font-weight:700;color:#374151;padding:3px 9px;background:#fff;border-radius:20px;border:1px solid #d1d5db;">문의자</span>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 18px;">
              <p style="margin:0 0 6px;font-size:15px;font-weight:600;color:#1f2937;">{{ $inquirerName }}</p>
              @if($inquirerEmail)
              <p style="margin:0 0 3px;font-size:12px;color:#6b7280;">{{ $inquirerEmail }}</p>
              @endif
              @if($companyName)
              <p style="margin:0;font-size:12px;color:#9ca3af;">{{ $companyName }}</p>
              @endif
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 문의 제목 + 본문 미리보기 --}}
    <tr>
      <td style="padding:18px 36px 0;">
        <table cellpadding="0" cellspacing="0" style="width:100%;background:#fff;border-radius:10px;border:1px solid #e5e7eb;">
          <tr>
            <td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;">
              <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6d28d9;letter-spacing:.04em;text-transform:uppercase;">제목</p>
              <p style="margin:0;font-size:15px;font-weight:600;color:#1f2937;">{{ $inquirySubject }}</p>
            </td>
          </tr>
          <tr>
            <td style="padding:14px 18px;">
              <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.04em;text-transform:uppercase;">내용 미리보기</p>
              <p style="margin:0;font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap;">{{ $bodyPreview }}</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 메타 --}}
    <tr>
      <td style="padding:18px 36px 0;">
        <table cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td style="padding:5px 0;font-size:12px;color:#6b7280;">등록 시각</td>
            <td style="padding:5px 0;font-size:13px;font-weight:600;color:#111827;text-align:right;">{{ $createdAt }}</td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- CTA --}}
    <tr>
      <td style="padding:28px 36px;">
        <table cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td align="center">
              <a href="{{ $inquiryUrl }}"
                 style="display:inline-block;padding:13px 36px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;font-size:14px;font-weight:700;border-radius:10px;text-decoration:none;letter-spacing:0.3px;">
                관리자 페이지에서 답변하기
              </a>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 푸터 --}}
    <tr>
      <td style="padding:16px 36px;background:#f9fafb;border-top:1px solid #f3f4f6;text-align:center;">
        <p style="margin:0;font-size:11px;color:#9ca3af;">SupportWorks · 이 메일은 자동 발송되었습니다.</p>
      </td>
    </tr>

  </table>
</td></tr>
</table>

</body>
</html>
