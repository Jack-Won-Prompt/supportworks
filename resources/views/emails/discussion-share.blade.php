<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>논의 공유</title>
</head>
<body style="margin:0;padding:0;background:#f0eeff;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0eeff;padding:40px 16px;">
<tr><td align="center">
  <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;border:1px solid #ddd6fe;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <tr>
      <td style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);padding:32px 36px 28px;text-align:center;">
        <div style="font-size:36px;margin-bottom:10px;">💬</div>
        <h1 style="margin:0 0 6px;font-size:18px;font-weight:700;color:#4c1d95;">논의에 공유되었습니다</h1>
        <p style="margin:0;font-size:13px;color:#6d28d9;">{{ $authorName }}님이 의견을 나누고 싶어합니다.</p>
      </td>
    </tr>
    <tr><td style="padding:28px 36px 0;">
      <table cellpadding="0" cellspacing="0" style="width:100%;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
        <tr><td style="padding:14px 18px;border-bottom:1px solid #e5e7eb;background:#f3f4f6;">
          <span style="font-size:11px;font-weight:700;color:#374151;padding:3px 9px;background:#fff;border-radius:20px;border:1px solid #d1d5db;">프로젝트</span>
        </td></tr>
        <tr><td style="padding:14px 18px;">
          <p style="margin:0;font-size:14px;font-weight:600;color:#1f2937;">{{ $projectName }}</p>
        </td></tr>
      </table>
    </td></tr>
    <tr><td style="padding:18px 36px 0;">
      <table cellpadding="0" cellspacing="0" style="width:100%;background:#fff;border-radius:10px;border:1px solid #e5e7eb;">
        <tr><td style="padding:14px 18px;border-bottom:1px solid #f3f4f6;">
          <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6d28d9;letter-spacing:.04em;text-transform:uppercase;">논의 제목</p>
          <p style="margin:0;font-size:15px;font-weight:600;color:#1f2937;">{{ $discussionTitle }}</p>
        </td></tr>
        @if($contentPreview)
        <tr><td style="padding:14px 18px;">
          <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.04em;text-transform:uppercase;">내용 미리보기</p>
          <p style="margin:0;font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap;">{{ $contentPreview }}</p>
        </td></tr>
        @endif
      </table>
    </td></tr>
    <tr><td style="padding:28px 36px;">
      <table cellpadding="0" cellspacing="0" style="width:100%;"><tr><td align="center">
        <a href="{{ $url }}" style="display:inline-block;padding:13px 36px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;font-size:14px;font-weight:700;border-radius:10px;text-decoration:none;letter-spacing:0.3px;">
          논의 보기 / 의견 남기기
        </a>
      </td></tr></table>
    </td></tr>
    <tr><td style="padding:16px 36px;background:#f9fafb;border-top:1px solid #f3f4f6;text-align:center;">
      <p style="margin:0;font-size:11px;color:#9ca3af;">SupportWorks · 이 메일은 자동 발송되었습니다.</p>
    </td></tr>
  </table>
</td></tr></table>
</body>
</html>
