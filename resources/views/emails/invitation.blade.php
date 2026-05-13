<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{{ __('emails.invite_title') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0eeff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Helvetica Neue',Arial,sans-serif;-webkit-font-smoothing:antialiased;">

<!-- 외부 래퍼 -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0eeff;padding:48px 16px;">
  <tr>
    <td align="center">
      <table width="580" cellpadding="0" cellspacing="0" border="0" style="max-width:580px;width:100%;">

        <!-- ══ 로고 영역 ══ -->
        <tr>
          <td align="center" style="padding-bottom:32px;">
            <table cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="background:linear-gradient(135deg,#a78bfa,#7c3aed);border-radius:14px;width:42px;height:42px;text-align:center;vertical-align:middle;">
                  <span style="font-size:20px;font-weight:900;color:#fff;line-height:42px;display:block;">S</span>
                </td>
                <td style="padding-left:11px;vertical-align:middle;">
                  <span style="font-size:19px;font-weight:800;color:#3b2a8a;letter-spacing:-0.3px;">SupportWorks</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ══ 메인 카드 ══ -->
        <tr>
          <td style="border-radius:24px;overflow:hidden;background:#ffffff;border:1px solid #ddd6fe;box-shadow:0 16px 48px rgba(109,92,231,0.12),0 4px 16px rgba(109,92,231,0.06);">

            <!-- 히어로 섹션 -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding:0;position:relative;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="background:linear-gradient(160deg,#ede9fe 0%,#ddd6fe 40%,#e0e7ff 100%);padding:56px 48px 52px;text-align:center;border-bottom:1px solid #e0d9ff;">

                        <!-- 아이콘 링 -->
                        <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 28px;">
                          <tr>
                            <td style="background:#ffffff;border:1.5px solid #c4b5fd;border-radius:50%;width:80px;height:80px;text-align:center;vertical-align:middle;box-shadow:0 8px 24px rgba(124,58,237,0.15);">
                              <!-- 봉투 SVG -->
                              <svg width="34" height="34" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;vertical-align:middle;">
                                <path d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z" stroke="url(#mailGrad)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <defs>
                                  <linearGradient id="mailGrad" x1="3" y1="5" x2="21" y2="19" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#8b5cf6"/>
                                    <stop offset="1" stop-color="#6366f1"/>
                                  </linearGradient>
                                </defs>
                              </svg>
                            </td>
                          </tr>
                        </table>

                        <!-- 라벨 -->
                        <p style="font-size:11px;font-weight:700;color:#7c3aed;letter-spacing:3px;text-transform:uppercase;margin:0 0 14px;">TEAM INVITATION</p>

                        <!-- 메인 헤드라인 -->
                        <h1 style="font-size:30px;font-weight:900;color:#1e1b4b;line-height:1.2;letter-spacing:-0.8px;margin:0 0 16px;">
                          {{ __('emails.invite_headline') }}
                        </h1>
                        <p style="font-size:15px;color:#5b5677;line-height:1.65;margin:0;max-width:360px;display:block;margin-left:auto;margin-right:auto;">
                          <span style="color:#7c3aed;font-weight:600;">{{ $inviterName }}</span> {{ __('emails.invite_subheadline', ['inviter' => '']) }}
                        </p>

                        @if($inviteMessage)
                        <!-- 초대 메시지 -->
                        <table cellpadding="0" cellspacing="0" border="0" style="margin:24px auto 0;max-width:400px;width:100%;">
                          <tr>
                            <td style="background:#ffffff;border:1px solid #c4b5fd;border-radius:12px;padding:18px 22px;text-align:left;box-shadow:0 2px 8px rgba(124,58,237,0.08);">
                              <p style="font-size:11px;font-weight:700;color:#7c3aed;letter-spacing:1.5px;text-transform:uppercase;margin:0 0 10px;">{{ __('emails.invite_message_label') }}</p>
                              <p style="font-size:14px;color:#374151;line-height:1.7;margin:0;font-style:italic;">"{{ $inviteMessage }}"</p>
                              <p style="font-size:12px;color:#9ca3af;margin:10px 0 0;text-align:right;">— {{ $inviterName }}</p>
                            </td>
                          </tr>
                        </table>
                        @endif

                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- 초대 정보 카드 -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding:36px 40px 0;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-radius:14px;overflow:hidden;border:1px solid #e0d9ff;">

                    <!-- 카드 헤더 -->
                    <tr>
                      <td style="background:#f5f3ff;padding:14px 20px;border-bottom:1px solid #e0d9ff;">
                        <p style="font-size:11px;font-weight:700;color:#7c3aed;letter-spacing:1.5px;text-transform:uppercase;margin:0;">{{ __('emails.invite_info_label') }}</p>
                      </td>
                    </tr>

                    <!-- 항목 1 -->
                    <tr>
                      <td style="padding:16px 20px;border-bottom:1px solid #f3f4f6;background:#ffffff;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                          <tr>
                            <td style="width:20px;vertical-align:middle;">
                              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="#8b5cf6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                              </svg>
                            </td>
                            <td style="padding-left:12px;font-size:12px;color:#9ca3af;width:90px;">{{ __('emails.invite_info_inviter') }}</td>
                            <td style="font-size:13px;font-weight:600;color:#1f2937;text-align:right;">{{ $inviterName }}</td>
                          </tr>
                        </table>
                      </td>
                    </tr>

                    <!-- 항목 2 -->
                    <tr>
                      <td style="padding:16px 20px;border-bottom:1px solid #f3f4f6;background:#ffffff;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                          <tr>
                            <td style="width:20px;vertical-align:middle;">
                              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                              </svg>
                            </td>
                            <td style="padding-left:12px;font-size:12px;color:#9ca3af;width:90px;">{{ __('emails.invite_info_email') }}</td>
                            <td style="font-size:13px;font-weight:600;color:#1f2937;text-align:right;">{{ $invitation->email }}</td>
                          </tr>
                        </table>
                      </td>
                    </tr>

                    <!-- 항목 3 -->
                    <tr>
                      <td style="padding:16px 20px;{{ $invitedProjects ? 'border-bottom:1px solid #f3f4f6;' : '' }}background:#ffffff;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                          <tr>
                            <td style="width:20px;vertical-align:middle;">
                              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 3H5C4.46957 3 3.96086 3.21071 3.58579 3.58579C3.21071 3.96086 3 4.46957 3 5V9M9 3H15M9 3V9M15 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V9M15 3V9M21 9V15M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H15M21 15H15M15 21H9M15 21V15M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15M9 21V15M3 15V9M3 9H9M9 9H15M9 9V15M15 9H21M15 9V15M9 15H15" stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                              </svg>
                            </td>
                            <td style="padding-left:12px;font-size:12px;color:#9ca3af;width:90px;">{{ __('emails.invite_info_platform') }}</td>
                            <td style="font-size:13px;font-weight:600;color:#1f2937;text-align:right;">SupportWorks</td>
                          </tr>
                        </table>
                      </td>
                    </tr>

                    @if($invitedProjects)
                    <!-- 초대 프로젝트 -->
                    <tr>
                      <td style="padding:16px 20px;background:#ffffff;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                          <tr>
                            <td style="width:20px;vertical-align:top;padding-top:1px;">
                              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 11H5M19 11C20.1046 11 21 11.8954 21 13V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V13C3 11.8954 3.89543 11 5 11M19 11V9C19 7.89543 18.1046 7 17 7M5 11V9C5 7.89543 5.89543 7 7 7M7 7V5C7 3.89543 7.89543 3 9 3H15C16.1046 3 17 3.89543 17 5V7M7 7H17" stroke="#059669" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                              </svg>
                            </td>
                            <td style="padding-left:12px;font-size:12px;color:#9ca3af;width:90px;vertical-align:top;padding-top:2px;">{{ __('emails.invite_info_projects') }}</td>
                            <td style="text-align:right;">
                              @foreach($invitedProjects as $pName)
                              <span style="display:inline-block;font-size:11px;font-weight:600;color:#059669;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:6px;padding:2px 8px;margin:2px 0 2px 4px;">{{ $pName }}</span>
                              @endforeach
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    @endif

                  </table>
                </td>
              </tr>
            </table>

            <!-- CTA 버튼 -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding:32px 40px 36px;text-align:center;">
                  <a href="{{ $inviteUrl }}"
                    style="display:inline-block;background:linear-gradient(135deg,#7c3aed 0%,#6366f1 100%);color:#ffffff;font-size:15px;font-weight:700;padding:16px 52px;border-radius:12px;letter-spacing:-0.2px;text-decoration:none;box-shadow:0 8px 24px rgba(124,58,237,0.35),0 2px 8px rgba(124,58,237,0.15);">
                    {{ __('emails.invite_cta') }} &nbsp;→
                  </a>
                  <p style="font-size:12px;color:#9ca3af;margin:16px 0 0;">{{ __('emails.invite_cta_hint') }}</p>
                </td>
              </tr>
            </table>

            <!-- 기능 소개 카드 3개 -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding:0 40px 36px;">

                  <!-- 섹션 라벨 -->
                  <p style="font-size:11px;font-weight:700;color:#a1a1aa;letter-spacing:2px;text-transform:uppercase;text-align:center;margin:0 0 20px;">{{ __('emails.invite_features_label') }}</p>

                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr valign="top">

                      <!-- 기능 1 -->
                      <td width="32%" style="background:#f5f3ff;border:1px solid #e0d9ff;border-radius:12px;padding:18px 14px;text-align:center;">
                        <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 10px;">
                          <tr>
                            <td style="background:#ede9fe;border-radius:10px;width:36px;height:36px;text-align:center;vertical-align:middle;">
                              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;vertical-align:middle;">
                                <path d="M9 5H7C6.46957 5 5.96086 5.21071 5.58579 5.58579C5.21071 5.96086 5 6.46957 5 7V19C5 19.5304 5.21071 20.0391 5.58579 20.4142C5.96086 20.7893 6.46957 21 7 21H17C17.5304 21 18.0391 20.7893 18.4142 20.4142C18.7893 20.0391 19 19.5304 19 19V7C19 6.46957 18.7893 5.96086 18.4142 5.58579C18.0391 5.21071 17.5304 5 17 5H15M9 5C9 5.53043 9.21071 6.03914 9.58579 6.41421C9.96086 6.78929 10.4696 7 11 7H13C13.5304 7 14.0391 6.78929 14.4142 6.41421C14.7893 6.03914 15 5.53043 15 5M9 5C9 4.46957 9.21071 3.96086 9.58579 3.58579C9.96086 3.21071 10.4696 3 11 3H13C13.5304 3 14.0391 3.21071 14.4142 3.58579C14.7893 3.96086 15 4.46957 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="#8b5cf6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                              </svg>
                            </td>
                          </tr>
                        </table>
                        <p style="font-size:12px;font-weight:700;color:#3b2a8a;margin:0 0 5px;">{{ __('emails.invite_feature1_name') }}</p>
                        <p style="font-size:11px;color:#7c6ef5;line-height:1.5;margin:0;">{{ __('emails.invite_feature1_desc') }}</p>
                      </td>

                      <td width="2%"></td>

                      <!-- 기능 2 -->
                      <td width="32%" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:18px 14px;text-align:center;">
                        <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 10px;">
                          <tr>
                            <td style="background:#dcfce7;border-radius:10px;width:36px;height:36px;text-align:center;vertical-align:middle;">
                              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;vertical-align:middle;">
                                <path d="M8 12H8.01M12 12H12.01M16 12H16.01M21 12C21 16.4183 16.9706 20 12 20C10.4607 20 9.01172 19.6565 7.74467 19.0511L3 20L4.39499 16.28C3.51156 15.0423 3 13.5743 3 12C3 7.58172 7.02944 4 12 4C16.9706 4 21 7.58172 21 12Z" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                              </svg>
                            </td>
                          </tr>
                        </table>
                        <p style="font-size:12px;font-weight:700;color:#065f46;margin:0 0 5px;">{{ __('emails.invite_feature2_name') }}</p>
                        <p style="font-size:11px;color:#059669;line-height:1.5;margin:0;">{{ __('emails.invite_feature2_desc') }}</p>
                      </td>

                      <td width="2%"></td>

                      <!-- 기능 3 -->
                      <td width="32%" style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:18px 14px;text-align:center;">
                        <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 10px;">
                          <tr>
                            <td style="background:#fef3c7;border-radius:10px;width:36px;height:36px;text-align:center;vertical-align:middle;">
                              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;vertical-align:middle;">
                                <path d="M9.75 17L9 20L8 21H16L15 20L14.25 17M3 13H21M5 17H19C20.1046 17 21 16.1046 21 15V5C21 3.89543 20.1046 3 19 3H5C3.89543 3 3 3.89543 3 5V15C3 16.1046 3.89543 17 5 17Z" stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                              </svg>
                            </td>
                          </tr>
                        </table>
                        <p style="font-size:12px;font-weight:700;color:#78350f;margin:0 0 5px;">{{ __('emails.invite_feature3_name') }}</p>
                        <p style="font-size:11px;color:#d97706;line-height:1.5;margin:0;">{{ __('emails.invite_feature3_desc') }}</p>
                      </td>

                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- 하단 구분선 -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding:0 40px 28px;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="height:1px;background:#e0d9ff;"></td>
                    </tr>
                  </table>
                  <p style="font-size:11px;color:#a1a1aa;line-height:1.8;margin:20px 0 0;text-align:center;">
                    {{ __('emails.invite_footer_note') }}<br>
                    {{ __('emails.invite_footer_ignore') }}
                  </p>
                </td>
              </tr>
            </table>

          </td>
        </tr>

        <!-- ══ 푸터 ══ -->
        <tr>
          <td style="padding-top:32px;text-align:center;">
            <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 14px;">
              <tr>
                <td style="padding:0 8px;">
                  <span style="font-size:13px;font-weight:800;color:#7c3aed;letter-spacing:-0.3px;">⚡ SupportWorks</span>
                </td>
              </tr>
            </table>
            <p style="font-size:11px;color:#a1a1aa;margin:0;">© {{ date('Y') }} SupportWorks. All rights reserved.</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
