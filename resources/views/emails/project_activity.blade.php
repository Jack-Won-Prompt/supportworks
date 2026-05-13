<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $eventLabel }}</title>
</head>
<body style="margin:0;padding:0;background:#f0eeff;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0eeff;padding:40px 16px;">
<tr><td align="center">

  {{-- 카드 --}}
  <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;border:1px solid #ddd6fe;overflow:hidden;box-shadow:0 4px 24px rgba(124,58,237,.08);">

    {{-- 헤더 --}}
    <tr>
      <td style="background:linear-gradient(135deg,#ede9fe,#ddd6fe,#e0e7ff);padding:32px 36px 28px;text-align:center;">
        <div style="font-size:36px;margin-bottom:10px;">{{ $eventIcon }}</div>
        <h1 style="margin:0 0 6px;font-size:18px;font-weight:700;color:#3730a3;">
          {{ $eventLabel }}
        </h1>
        <p style="margin:0;font-size:13px;color:#6d28d9;">
          {{ __('emails.activity_project_label', ['name' => $project->name]) }}
        </p>
      </td>
    </tr>

    {{-- 배지 + 항목명 --}}
    <tr>
      <td style="padding:28px 36px 0;">
        <table cellpadding="0" cellspacing="0" style="width:100%;background:#f5f3ff;border-radius:10px;border:1px solid #ede9fe;overflow:hidden;">
          <tr>
            <td style="padding:14px 18px;border-bottom:1px solid #ede9fe;background:#ede9fe;">
              <span style="font-size:11px;font-weight:700;color:{{ $actionColor }};padding:3px 9px;background:#fff;border-radius:20px;border:1px solid {{ $actionColor }};">
                {{ $actionBadge }}
              </span>
              <span style="font-size:12px;font-weight:600;color:#5b21b6;margin-left:10px;">
                @if(str_starts_with($eventType, 'schedule')) {{ __('emails.activity_type_schedule') }}
                @elseif(str_starts_with($eventType, 'file')) {{ __('emails.activity_type_file') }}
                @elseif(str_starts_with($eventType, 'question')) {{ __('emails.activity_type_question') }}
                @elseif(str_starts_with($eventType, 'answer')) {{ __('emails.activity_type_answer') }}
                @else {{ __('emails.activity_type_default') }}
                @endif
              </span>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 18px;">
              <p style="margin:0;font-size:15px;font-weight:600;color:#1f2937;word-break:break-word;">
                {{ $entityTitle }}
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 검토 요청 내용 --}}
    @if(!empty($reviewMessage))
    <tr>
      <td style="padding:16px 36px 0;">
        <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:10px;padding:16px 18px;">
          <p style="margin:0 0 8px;font-size:11px;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.04em;">{{ __('emails.activity_review_label') }}</p>
          <p style="margin:0;font-size:14px;color:#374151;white-space:pre-line;word-break:break-word;">{{ $reviewMessage }}</p>
        </div>
      </td>
    </tr>
    @endif

    {{-- 작성자 정보 --}}
    <tr>
      <td style="padding:20px 36px 0;">
        <table cellpadding="0" cellspacing="0">
          <tr>
            <td style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#8b5cf6,#6d28d9);text-align:center;vertical-align:middle;flex-shrink:0;">
              <span style="font-size:14px;font-weight:700;color:#fff;">{{ mb_substr($actor->name, 0, 1) }}</span>
            </td>
            <td style="padding-left:12px;vertical-align:middle;">
              <p style="margin:0;font-size:13px;font-weight:700;color:#1f2937;">{{ $actor->name }}</p>
              <p style="margin:2px 0 0;font-size:12px;color:#9ca3af;">{{ $actor->email }}</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 바로가기 버튼 --}}
    @if($hasLink)
    <tr>
      <td style="padding:24px 36px;">
        <table cellpadding="0" cellspacing="0">
          <tr>
            <td style="background:{{ $actionColor }};border-radius:9px;">
              <a href="{{ $url }}"
                 style="display:inline-block;padding:11px 28px;font-size:14px;font-weight:700;color:#fff;text-decoration:none;">
                {{ __('emails.activity_goto') }}
              </a>
            </td>
          </tr>
        </table>
        <p style="margin:10px 0 0;font-size:11px;color:#9ca3af;">
          {{ __('emails.activity_url_hint') }}<br>
          <a href="{{ $url }}" style="color:#8b5cf6;word-break:break-all;">{{ $url }}</a>
        </p>
      </td>
    </tr>
    @else
    <tr>
      <td style="padding:24px 36px 28px;">
        <p style="margin:0;font-size:13px;color:#6b7280;">
          {{ __('emails.activity_deleted_hint') }}
        </p>
      </td>
    </tr>
    @endif

    {{-- 푸터 --}}
    <tr>
      <td style="padding:16px 36px 24px;border-top:1px solid #f3f4f6;text-align:center;">
        <p style="margin:0;font-size:11px;color:#a1a1aa;">
          {{ __('emails.activity_footer_note', ['app_name' => config('app.name')]) }}<br>
          {{ __('emails.activity_footer_note2') }}
        </p>
      </td>
    </tr>

  </table>
  {{-- /카드 --}}

</td></tr>
</table>

</body>
</html>
