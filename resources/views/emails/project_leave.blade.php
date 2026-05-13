<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>
@if($type === 'approved') {{ __('emails.leave_title_approved') }}
@elseif($type === 'rejected') {{ __('emails.leave_title_rejected') }}
@else {{ __('emails.leave_title_request') }}
@endif
</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Apple SD Gothic Neo','Malgun Gothic',sans-serif;">

@php
$isApproved  = $type === 'approved';
$isRejected  = $type === 'rejected';
$isRequest   = $type === 'approval_request';

$accentColor = $isApproved ? '#10b981' : ($isRejected ? '#ef4444' : '#6366f1');
$accentLight = $isApproved ? '#ecfdf5' : ($isRejected ? '#fef2f2' : '#eef2ff');
$accentMid   = $isApproved ? '#d1fae5' : ($isRejected ? '#fee2e2' : '#e0e7ff');
$accentText  = $isApproved ? '#065f46' : ($isRejected ? '#991b1b' : '#3730a3');
$badgeBg     = $isApproved ? '#dcfce7' : ($isRejected ? '#fee2e2' : '#e0e7ff');
$badgeText   = $isApproved ? '#15803d' : ($isRejected ? '#b91c1c' : '#4338ca');
$badgeLabel  = $isApproved ? __('emails.leave_badge_approved') : ($isRejected ? __('emails.leave_badge_rejected') : __('emails.leave_badge_request'));
$iconChar    = $isApproved ? '✓' : ($isRejected ? '✕' : '◎');
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:48px 16px;">
<tr><td align="center">

  {{-- 카드 --}}
  <table width="580" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.06),0 0 0 1px rgba(0,0,0,.04);">

    {{-- 상단 컬러 스트라이프 --}}
    <tr>
      <td style="background:{{ $accentColor }};height:4px;font-size:0;line-height:0;">&nbsp;</td>
    </tr>

    {{-- 헤더 --}}
    <tr>
      <td style="padding:36px 44px 28px;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="vertical-align:top;">
              <div style="display:inline-block;width:44px;height:44px;border-radius:12px;background:{{ $accentLight }};border:1.5px solid {{ $accentMid }};text-align:center;line-height:44px;font-size:20px;font-weight:700;color:{{ $accentColor }};">{{ $iconChar }}</div>
            </td>
            <td style="padding-left:16px;vertical-align:middle;">
              <p style="margin:0 0 4px;font-size:18px;font-weight:700;color:#111827;letter-spacing:-.3px;">
                @if($isRequest) {{ __('emails.leave_header_request') }}
                @elseif($isApproved) {{ __('emails.leave_header_approved') }}
                @else {{ __('emails.leave_header_rejected') }}
                @endif
              </p>
              <p style="margin:0;font-size:13px;color:#6b7280;">
                {{ $project->name }}
                &nbsp;·&nbsp;
                <span style="display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $badgeBg }};color:{{ $badgeText }};">{{ $badgeLabel }}</span>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 구분선 --}}
    <tr><td style="padding:0 44px;"><div style="height:1px;background:#f3f4f6;"></div></td></tr>

    {{-- 처리자 / 신청자 정보 --}}
    <tr>
      <td style="padding:24px 44px 0;">
        <p style="margin:0 0 12px;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;">
          @if($isRequest) {{ __('emails.leave_actor_request') }}
          @elseif($isApproved) {{ __('emails.leave_actor_approved') }}
          @else {{ __('emails.leave_actor_rejected') }}
          @endif
        </p>
        <table cellpadding="0" cellspacing="0">
          <tr>
            <td>
              <div style="width:38px;height:38px;border-radius:50%;background:{{ $accentLight }};border:1.5px solid {{ $accentMid }};text-align:center;line-height:38px;font-size:15px;font-weight:700;color:{{ $accentColor }};">{{ mb_substr($actor->name, 0, 1) }}</div>
            </td>
            <td style="padding-left:12px;">
              <p style="margin:0;font-size:14px;font-weight:600;color:#111827;">{{ $actor->name }}</p>
              <p style="margin:2px 0 0;font-size:12px;color:#9ca3af;">{{ $actor->email }}</p>
            </td>
          </tr>
        </table>
        @if($isRequest && $leave->user_id !== $actor->id)
        <div style="margin-top:14px;padding:10px 14px;background:#f9fafb;border-radius:8px;border:1px solid #f3f4f6;">
          <span style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.07em;">{{ __('emails.leave_target_label') }}&nbsp;&nbsp;</span>
          <span style="font-size:13px;font-weight:600;color:#374151;">{{ $leave->user->name }}</span>
        </div>
        @endif
      </td>
    </tr>

    {{-- 휴무 상세 카드 --}}
    <tr>
      <td style="padding:20px 44px 0;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:12px;border:1px solid #f3f4f6;overflow:hidden;">
          <tr>
            <td style="padding:13px 20px;border-bottom:1px solid #f3f4f6;">
              <p style="margin:0;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;">{{ __('emails.leave_info_title') }}</p>
            </td>
          </tr>
          {{-- 유형 --}}
          <tr>
            <td style="padding:14px 20px;border-bottom:1px solid #f3f4f6;">
              <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td style="width:56px;font-size:12px;color:#9ca3af;vertical-align:middle;">{{ __('emails.leave_info_type') }}</td>
                <td style="vertical-align:middle;">
                  <span style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $leave->leave_type_bg }};color:{{ $leave->leave_type_color }};border:1px solid {{ $leave->leave_type_color }}20;">{{ $leave->leave_type_label }}</span>
                </td>
              </tr></table>
            </td>
          </tr>
          {{-- 기간 --}}
          <tr>
            <td style="padding:14px 20px;{{ $leave->reason ? 'border-bottom:1px solid #f3f4f6;' : '' }}">
              <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td style="width:56px;font-size:12px;color:#9ca3af;vertical-align:top;padding-top:2px;">{{ __('emails.leave_info_period') }}</td>
                <td style="vertical-align:top;">
                  <span style="font-size:14px;font-weight:600;color:#111827;">
                    @if($leave->start_date->eq($leave->end_date))
                      {{ $leave->start_date->format('Y년 m월 d일') }}
                    @else
                      {{ $leave->start_date->format('Y년 m월 d일') }} &ndash; {{ $leave->end_date->format('Y년 m월 d일') }}
                    @endif
                  </span>
                  <span style="margin-left:8px;font-size:12px;color:#9ca3af;">
                    {{ in_array($leave->leave_type, ['half_day_am','half_day_pm']) ? __('emails.leave_days_half') : __('emails.leave_days_count', ['count' => $leave->days_count]) }}
                  </span>
                </td>
              </tr></table>
            </td>
          </tr>
          @if($leave->reason)
          {{-- 사유 --}}
          <tr>
            <td style="padding:14px 20px;">
              <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td style="width:56px;font-size:12px;color:#9ca3af;vertical-align:top;padding-top:2px;">{{ __('emails.leave_info_reason') }}</td>
                <td style="font-size:13px;color:#374151;line-height:1.6;word-break:break-word;">{{ $leave->reason }}</td>
              </tr></table>
            </td>
          </tr>
          @endif
        </table>
      </td>
    </tr>

    {{-- 상태 메시지 배너 --}}
    <tr>
      <td style="padding:16px 44px 0;">
        <div style="padding:14px 18px;background:{{ $accentLight }};border-left:3px solid {{ $accentColor }};border-radius:0 8px 8px 0;">
          <p style="margin:0;font-size:13px;color:{{ $accentText }};line-height:1.6;">
            @if($isRequest)
              {{ __('emails.leave_status_request') }}
            @elseif($isApproved)
              {!! __('emails.leave_status_approved', ['actor' => '<strong>' . e($actor->name) . '</strong>']) !!}
            @else
              {!! __('emails.leave_status_rejected', ['actor' => '<strong>' . e($actor->name) . '</strong>']) !!}
            @endif
          </p>
        </div>
      </td>
    </tr>

    {{-- CTA 버튼 --}}
    <tr>
      <td style="padding:28px 44px 36px;text-align:center;">
        <a href="{{ $leaveUrl }}"
           style="display:inline-block;padding:13px 36px;background:{{ $accentColor }};color:#ffffff;font-size:14px;font-weight:600;border-radius:10px;text-decoration:none;letter-spacing:.01em;box-shadow:0 2px 8px {{ $accentColor }}44;">
          @if($isRequest) {{ __('emails.leave_cta_request') }}
          @else {{ __('emails.leave_cta_default') }}
          @endif
        </a>
      </td>
    </tr>

    {{-- 구분선 --}}
    <tr><td><div style="height:1px;background:#f3f4f6;"></div></td></tr>

    {{-- 푸터 --}}
    <tr>
      <td style="padding:20px 44px;text-align:center;">
        <p style="margin:0;font-size:11px;color:#d1d5db;line-height:1.7;">
          {{ __('emails.leave_footer_note', ['project' => $project->name]) }}<br>
          {{ __('emails.leave_footer_platform') }}
        </p>
      </td>
    </tr>

  </table>

  {{-- 하단 여백 텍스트 --}}
  <p style="margin:24px 0 0;font-size:11px;color:#9ca3af;text-align:center;">
    © SupportWorks. All rights reserved.
  </p>

</td></tr>
</table>
</body>
</html>
