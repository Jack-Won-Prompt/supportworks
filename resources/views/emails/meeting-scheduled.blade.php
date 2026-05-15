<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $isUpdate ? '회의 일정 변경' : '회의 일정 안내' }}</title>
</head>
<body style="margin:0;padding:0;background:#f0eeff;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0eeff;padding:40px 16px;">
<tr><td align="center">

  <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;border:1px solid #ddd6fe;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

    {{-- 헤더 --}}
    <tr>
      <td style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);padding:32px 36px 28px;text-align:center;">
        <div style="font-size:36px;margin-bottom:10px;">📅</div>
        <h1 style="margin:0 0 6px;font-size:18px;font-weight:700;color:#4c1d95;">
          {{ $isUpdate ? '회의 일정이 변경되었습니다' : '회의 일정이 등록되었습니다' }}
        </h1>
        <p style="margin:0;font-size:13px;color:#6d28d9;">{{ $recipientName }}님, 아래 회의에 참석 예정입니다.</p>
      </td>
    </tr>

    {{-- 회의 정보 --}}
    <tr>
      <td style="padding:28px 36px 0;">
        <table cellpadding="0" cellspacing="0" style="width:100%;background:#fff;border-radius:10px;border:1px solid #e5e7eb;">
          <tr>
            <td style="padding:16px 18px;border-bottom:1px solid #f3f4f6;">
              <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6d28d9;letter-spacing:.04em;text-transform:uppercase;">회의명</p>
              <p style="margin:0;font-size:15px;font-weight:600;color:#1f2937;">{{ $title }}</p>
            </td>
          </tr>
          <tr>
            <td style="padding:12px 18px;border-bottom:1px solid #f3f4f6;">
              <table cellpadding="0" cellspacing="0" style="width:100%;">
                <tr>
                  <td style="padding:4px 0;font-size:12px;color:#6b7280;width:70px;">일시</td>
                  <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;">{{ $dateLabel }}</td>
                </tr>
                @if($location)
                <tr>
                  <td style="padding:4px 0;font-size:12px;color:#6b7280;">장소</td>
                  <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;">{{ $location }}</td>
                </tr>
                @endif
                <tr>
                  <td style="padding:4px 0;font-size:12px;color:#6b7280;">주최자</td>
                  <td style="padding:4px 0;font-size:13px;color:#374151;">{{ $organizerName }}</td>
                </tr>
                @if($attendeeNames)
                <tr>
                  <td style="padding:4px 0;font-size:12px;color:#6b7280;vertical-align:top;">참석자</td>
                  <td style="padding:4px 0;font-size:13px;color:#374151;line-height:1.6;">{{ $attendeeNames }}</td>
                </tr>
                @endif
              </table>
            </td>
          </tr>
          @if($agenda)
          <tr>
            <td style="padding:14px 18px;">
              <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.04em;text-transform:uppercase;">안건 (Agenda)</p>
              <p style="margin:0;font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap;">{{ $agenda }}</p>
            </td>
          </tr>
          @endif
        </table>
      </td>
    </tr>

    {{-- 캘린더 등록 안내 --}}
    <tr>
      <td style="padding:18px 36px 0;">
        <table cellpadding="0" cellspacing="0" style="width:100%;background:#f5f3ff;border-radius:10px;border:1px solid #ddd6fe;">
          <tr>
            <td style="padding:14px 18px;font-size:12.5px;color:#5b21b6;line-height:1.6;">
              📎 첨부된 <strong>meeting.ics</strong> 파일을 열면 Outlook · Google 캘린더 등에서
              이 회의 일정을 클릭 한 번으로 등록할 수 있습니다.
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 푸터 --}}
    <tr>
      <td style="padding:24px 36px 16px;background:#f9fafb;border-top:1px solid #f3f4f6;text-align:center;">
        <p style="margin:0;font-size:11px;color:#9ca3af;">SupportWorks · 이 메일은 자동 발송되었습니다.</p>
      </td>
    </tr>

  </table>
</td></tr>
</table>

</body>
</html>
