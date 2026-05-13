<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>산출물 승인 요청</title>
</head>
<body style="margin:0;padding:0;background:#f0eeff;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0eeff;padding:40px 16px;">
<tr><td align="center">

  <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;border:1px solid #ddd6fe;overflow:hidden;box-shadow:0 4px 24px rgba(124,58,237,.08);">

    {{-- 헤더 --}}
    <tr>
      <td style="background:linear-gradient(135deg,#ede9fe,#ddd6fe,#e0e7ff);padding:32px 36px 28px;text-align:center;">
        <div style="font-size:36px;margin-bottom:10px;">✅</div>
        <h1 style="margin:0 0 6px;font-size:18px;font-weight:700;color:#3730a3;">
          산출물 승인 요청
        </h1>
        <p style="margin:0;font-size:13px;color:#6d28d9;">
          {{ $requesterName }}님이 산출물 검토·승인을 요청했습니다.
        </p>
      </td>
    </tr>

    {{-- 상세 --}}
    <tr>
      <td style="padding:28px 36px 0;">
        <table cellpadding="0" cellspacing="0" style="width:100%;background:#f5f3ff;border-radius:10px;border:1px solid #ede9fe;">
          <tr>
            <td style="padding:14px 18px;border-bottom:1px solid #ede9fe;background:#ede9fe;">
              <span style="font-size:11px;font-weight:700;color:#7c3aed;padding:3px 9px;background:#fff;border-radius:20px;border:1px solid #7c3aed;">승인 요청</span>
              <span style="font-size:12px;font-weight:600;color:#5b21b6;margin-left:10px;">산출물</span>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 18px;">
              <p style="margin:0 0 8px;font-size:15px;font-weight:600;color:#1f2937;">
                {{ $deliverableName }}
              </p>
              <p style="margin:0;font-size:13px;color:#6d28d9;">
                단계: {{ $stepTitle }}
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 요청자 정보 --}}
    <tr>
      <td style="padding:20px 36px 0;">
        <table cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td style="font-size:12px;color:#6b7280;">요청자</td>
            <td style="font-size:13px;font-weight:600;color:#111827;text-align:right;">{{ $requesterName }}</td>
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
              <a href="{{ $approveUrl }}"
                 style="display:inline-block;padding:13px 36px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;font-size:14px;font-weight:700;border-radius:10px;text-decoration:none;letter-spacing:0.3px;">
                승인 검토하기
              </a>
            </td>
          </tr>
          <tr>
            <td style="padding-top:12px;text-align:center;font-size:11px;color:#9ca3af;">
              위 버튼 클릭 후 해당 단계에서 승인 또는 반려할 수 있습니다.
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 푸터 --}}
    <tr>
      <td style="padding:16px 36px;background:#f9fafb;border-top:1px solid #f3f4f6;text-align:center;">
        <p style="margin:0;font-size:11px;color:#9ca3af;">
          SupportWorks · 이 메일은 자동 발송되었습니다.
        </p>
      </td>
    </tr>

  </table>
</td></tr>
</table>

</body>
</html>
