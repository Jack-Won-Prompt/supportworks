<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>문의 상태 변경</title>
</head>
<body style="margin:0;padding:0;background:#f0eeff;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;">

@php
    $headerBg   = $isClosed ? 'linear-gradient(135deg,#d1fae5,#a7f3d0)' : 'linear-gradient(135deg,#dbeafe,#bfdbfe)';
    $headerText = $isClosed ? '#065f46' : '#1e40af';
    $headerSub  = $isClosed ? '#047857' : '#1d4ed8';
    $headerEmo  = $isClosed ? '✅' : '🔄';
    $borderC    = $isClosed ? '#bbf7d0' : '#bfdbfe';
    $pillBg     = $isClosed ? '#d1fae5' : '#dbeafe';
    $pillFg     = $isClosed ? '#065f46' : '#1e40af';
    $ctaBg      = $isClosed ? 'linear-gradient(135deg,#059669,#047857)' : 'linear-gradient(135deg,#2563eb,#1d4ed8)';
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0eeff;padding:40px 16px;">
<tr><td align="center">

  <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;border:1px solid {{ $borderC }};overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

    {{-- 헤더 --}}
    <tr>
      <td style="background:{{ $headerBg }};padding:32px 36px 28px;text-align:center;">
        <div style="font-size:36px;margin-bottom:10px;">{{ $headerEmo }}</div>
        <h1 style="margin:0 0 6px;font-size:18px;font-weight:700;color:{{ $headerText }};">
          문의 상태가 {{ $statusLabel }}(으)로 변경되었습니다
        </h1>
        <p style="margin:0;font-size:13px;color:{{ $headerSub }};">{{ $adminName }}님이 처리했습니다.</p>
      </td>
    </tr>

    {{-- 문의 정보 --}}
    <tr>
      <td style="padding:28px 36px 0;">
        <table cellpadding="0" cellspacing="0" style="width:100%;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
          <tr>
            <td style="padding:14px 18px;border-bottom:1px solid #e5e7eb;background:#f3f4f6;">
              <span style="font-size:11px;font-weight:700;color:#374151;padding:3px 9px;background:#fff;border-radius:20px;border:1px solid #d1d5db;">문의</span>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 18px;">
              <p style="margin:0 0 6px;font-size:15px;font-weight:600;color:#1f2937;">{{ $inquirySubject }}</p>
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
            <td style="padding:5px 0;font-size:13px;font-weight:600;color:#111827;text-align:right;">{{ $adminName }}</td>
          </tr>
          <tr>
            <td style="padding:5px 0;font-size:12px;color:#6b7280;">변경 시각</td>
            <td style="padding:5px 0;font-size:13px;font-weight:600;color:#111827;text-align:right;">{{ $changedAt }}</td>
          </tr>
          <tr>
            <td style="padding:5px 0;font-size:12px;color:#6b7280;">상태</td>
            <td style="padding:5px 0;text-align:right;">
              <span style="font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;background:{{ $pillBg }};color:{{ $pillFg }};">
                {{ $statusLabel }}
              </span>
            </td>
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
                 style="display:inline-block;padding:13px 36px;background:{{ $ctaBg }};color:#fff;font-size:14px;font-weight:700;border-radius:10px;text-decoration:none;letter-spacing:0.3px;">
                {{ $isClosed ? '문의 내역 확인하기' : '답변 확인하기' }}
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
