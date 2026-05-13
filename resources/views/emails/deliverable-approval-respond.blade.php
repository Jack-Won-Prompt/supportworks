<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>산출물 {{ $isApproved ? '승인 완료' : '반려' }}</title>
</head>
<body style="margin:0;padding:0;background:#f0eeff;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0eeff;padding:40px 16px;">
<tr><td align="center">

  <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;border:1px solid {{ $isApproved ? '#bbf7d0' : '#fecaca' }};overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

    {{-- 헤더 --}}
    <tr>
      <td style="background:{{ $isApproved ? 'linear-gradient(135deg,#d1fae5,#a7f3d0)' : 'linear-gradient(135deg,#fee2e2,#fecaca)' }};padding:32px 36px 28px;text-align:center;">
        <div style="font-size:36px;margin-bottom:10px;">{{ $isApproved ? '✅' : '❌' }}</div>
        <h1 style="margin:0 0 6px;font-size:18px;font-weight:700;color:{{ $isApproved ? '#065f46' : '#991b1b' }};">
          산출물 {{ $isApproved ? '승인 완료' : '반려' }}
        </h1>
        <p style="margin:0;font-size:13px;color:{{ $isApproved ? '#047857' : '#b91c1c' }};">
          {{ $approverName }}님이 산출물을 {{ $isApproved ? '승인' : '반려' }}했습니다.
        </p>
      </td>
    </tr>

    {{-- 산출물 정보 --}}
    <tr>
      <td style="padding:28px 36px 0;">
        <table cellpadding="0" cellspacing="0" style="width:100%;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
          <tr>
            <td style="padding:14px 18px;border-bottom:1px solid #e5e7eb;background:#f3f4f6;">
              <span style="font-size:11px;font-weight:700;color:#374151;padding:3px 9px;background:#fff;border-radius:20px;border:1px solid #d1d5db;">산출물</span>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 18px;">
              <p style="margin:0 0 6px;font-size:15px;font-weight:600;color:#1f2937;">{{ $deliverableName }}</p>
              <p style="margin:0;font-size:13px;color:#6b7280;">단계: {{ $stepTitle }}</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 처리 정보 --}}
    <tr>
      <td style="padding:20px 36px 0;">
        <table cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td style="padding:5px 0;font-size:12px;color:#6b7280;">처리자</td>
            <td style="padding:5px 0;font-size:13px;font-weight:600;color:#111827;text-align:right;">{{ $approverName }}</td>
          </tr>
          <tr>
            <td style="padding:5px 0;font-size:12px;color:#6b7280;">결과</td>
            <td style="padding:5px 0;text-align:right;">
              <span style="font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;
                background:{{ $isApproved ? '#d1fae5' : '#fee2e2' }};
                color:{{ $isApproved ? '#065f46' : '#991b1b' }};">
                {{ $isApproved ? '승인' : '반려' }}
              </span>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 코멘트 (있을 때만) --}}
    @if($note)
    <tr>
      <td style="padding:16px 36px 0;">
        <table cellpadding="0" cellspacing="0" style="width:100%;background:{{ $isApproved ? '#f0fdf4' : '#fff5f5' }};border-radius:8px;border-left:4px solid {{ $isApproved ? '#22c55e' : '#ef4444' }};">
          <tr>
            <td style="padding:14px 16px;">
              <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:{{ $isApproved ? '#15803d' : '#b91c1c' }};letter-spacing:.04em;text-transform:uppercase;">{{ $isApproved ? '승인 코멘트' : '반려 사유' }}</p>
              <p style="margin:0;font-size:13px;color:#374151;line-height:1.6;">{{ $note }}</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    @endif

    {{-- CTA --}}
    <tr>
      <td style="padding:28px 36px;">
        <table cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td align="center">
              <a href="{{ $deliverableUrl }}"
                 style="display:inline-block;padding:13px 36px;background:{{ $isApproved ? 'linear-gradient(135deg,#059669,#047857)' : 'linear-gradient(135deg,#dc2626,#b91c1c)' }};color:#fff;font-size:14px;font-weight:700;border-radius:10px;text-decoration:none;letter-spacing:0.3px;">
                {{ $isApproved ? '산출물 확인하기' : '내용 수정하기' }}
              </a>
            </td>
          </tr>
          @if(!$isApproved)
          <tr>
            <td style="padding-top:10px;text-align:center;font-size:11px;color:#9ca3af;">
              내용을 수정한 후 다시 승인을 요청할 수 있습니다.
            </td>
          </tr>
          @endif
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
