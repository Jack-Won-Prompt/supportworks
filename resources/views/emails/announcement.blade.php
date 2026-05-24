<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $announcementTitle }}</title>
</head>
<body style="margin:0;padding:0;background:#f0eeff;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;">
@php
    $typeMap = [
        'info'        => ['label' => '안내',        'color' => '#7c3aed', 'bg' => '#ede9fe'],
        'warning'     => ['label' => '주의',        'color' => '#d97706', 'bg' => '#fef3c7'],
        'maintenance' => ['label' => '점검',        'color' => '#0284c7', 'bg' => '#dbeafe'],
        'update'      => ['label' => '업데이트',    'color' => '#16a34a', 'bg' => '#dcfce7'],
    ];
    $cur = $typeMap[$announcementType] ?? $typeMap['info'];
@endphp
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0eeff;padding:40px 16px;">
<tr><td align="center">
  <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;border:1px solid #ddd6fe;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <tr>
      <td style="background:linear-gradient(135deg,{{ $cur['bg'] }},#ffffff);padding:28px 36px 22px;">
        <span style="display:inline-block;padding:4px 12px;background:#fff;border:1px solid {{ $cur['color'] }};color:{{ $cur['color'] }};border-radius:14px;font-size:11px;font-weight:700;letter-spacing:.04em;">📢 SupportWorks {{ $cur['label'] }} 공지</span>
        @if($recipientName)
        <p style="margin:12px 0 0;font-size:12px;color:#6b7280;">{{ $recipientName }}님께</p>
        @endif
      </td>
    </tr>
    <tr>
      <td style="padding:24px 36px 6px;">
        <h1 style="margin:0;font-size:18px;font-weight:700;color:#18181b;line-height:1.4;">{{ $announcementTitle }}</h1>
      </td>
    </tr>
    <tr>
      <td style="padding:14px 36px 28px;">
        <div style="font-size:14px;color:#27272a;line-height:1.75;word-break:break-word;white-space:pre-wrap;">{!! nl2br(e($announcementBody)) !!}</div>
      </td>
    </tr>
    <tr>
      <td style="padding:14px 36px;background:#f9fafb;border-top:1px solid #f3f4f6;text-align:center;">
        <p style="margin:0;font-size:11px;color:#9ca3af;">SupportWorks · 관리자 공지사항</p>
      </td>
    </tr>
  </table>
</td></tr></table>
</body>
</html>
