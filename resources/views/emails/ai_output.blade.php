<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{{ __('emails.ai_title') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0effe;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Helvetica Neue',Arial,sans-serif;-webkit-font-smoothing:antialiased;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0effe;padding:48px 16px;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

        <!-- 로고 헤더 -->
        <tr>
          <td align="center" style="padding-bottom:24px;">
            <table cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="background:linear-gradient(135deg,#7c3aed,#4f46e5);border-radius:12px;width:40px;height:40px;text-align:center;vertical-align:middle;">
                  <span style="font-size:19px;font-weight:900;color:#fff;line-height:40px;display:block;">S</span>
                </td>
                <td style="padding-left:10px;vertical-align:middle;">
                  <span style="font-size:18px;font-weight:800;color:#1e1b4b;letter-spacing:-0.3px;">SupportWorks</span>
                  <span style="font-size:11px;color:#7c3aed;font-weight:600;margin-left:7px;background:#ede9fe;padding:2px 7px;border-radius:20px;">웍스 Agent</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- 메인 카드 -->
        <tr>
          <td style="border-radius:20px;overflow:hidden;background:#ffffff;box-shadow:0 4px 6px rgba(109,40,217,0.06),0 20px 60px rgba(109,40,217,0.10),0 1px 3px rgba(0,0,0,0.05);">

            <!-- 히어로 배너 -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="background:linear-gradient(135deg,#7c3aed 0%,#4f46e5 50%,#2563eb 100%);padding:40px 44px 36px;text-align:center;">

                  <!-- 아이콘 원 -->
                  <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 20px;">
                    <tr>
                      <td style="background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);border-radius:50%;width:68px;height:68px;text-align:center;vertical-align:middle;backdrop-filter:blur(8px);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" style="display:inline-block;vertical-align:middle;">
                          <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                            stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </td>
                    </tr>
                  </table>

                  <p style="font-size:11px;font-weight:700;color:rgba(255,255,255,0.65);letter-spacing:3px;text-transform:uppercase;margin:0 0 10px;">웍스 AGENT OUTPUT</p>
                  <h1 style="font-size:22px;font-weight:800;color:#ffffff;line-height:1.3;letter-spacing:-0.4px;margin:0 0 14px;">{{ $sessionTitle }}</h1>

                  <!-- 메타 정보 칩들 -->
                  <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
                    <tr>
                      <td style="background:rgba(255,255,255,0.18);border-radius:20px;padding:5px 13px;font-size:12px;color:#ffffff;font-weight:500;">
                        {{ $user->name }}
                      </td>
                      <td style="width:8px;"></td>
                      <td style="background:rgba(255,255,255,0.18);border-radius:20px;padding:5px 13px;font-size:12px;color:#ffffff;font-weight:500;">
                        {{ now()->format('Y.m.d H:i') }}
                      </td>
                      @if($message->ai_provider)
                      <td style="width:8px;"></td>
                      <td style="background:rgba(255,255,255,0.25);border-radius:20px;padding:5px 13px;font-size:12px;color:#ffffff;font-weight:700;">
                        {{ $message->ai_provider === 'claude' ? '⚡ Claude' : '✦ GPT-4.1' }}
                      </td>
                      @endif
                    </tr>
                  </table>

                </td>
              </tr>
            </table>

            <!-- 웍스 응답 본문 -->
            @if($message->content)
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding:32px 40px 0;">
                  <!-- 섹션 라벨 -->
                  <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:14px;">
                    <tr>
                      <td style="border-left:3px solid #7c3aed;padding-left:10px;">
                        <span style="font-size:12px;font-weight:700;color:#7c3aed;letter-spacing:0.5px;text-transform:uppercase;">{{ __('emails.ai_response_label') }}</span>
                      </td>
                    </tr>
                  </table>
                  <div style="background:#faf9ff;border:1px solid #ede9fe;border-radius:12px;padding:20px 22px;">
                    <p style="font-size:14px;color:#374151;line-height:1.8;margin:0;white-space:pre-wrap;">{{ $message->content }}</p>
                  </div>
                </td>
              </tr>
            </table>
            @endif

            <!-- HTML 코드 -->
            @if($message->html_output)
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding:24px 40px 0;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <!-- 탭 바 -->
                    <tr>
                      <td style="background:#f8f9fa;padding:10px 16px;border-bottom:1px solid #e5e7eb;">
                        <table cellpadding="0" cellspacing="0" border="0">
                          <tr>
                            <td style="width:10px;height:10px;border-radius:50%;background:#ff5f57;"></td>
                            <td style="width:6px;"></td>
                            <td style="width:10px;height:10px;border-radius:50%;background:#ffbd2e;"></td>
                            <td style="width:6px;"></td>
                            <td style="width:10px;height:10px;border-radius:50%;background:#28c840;"></td>
                            <td style="width:16px;"></td>
                            <td style="background:#7c3aed;border-radius:4px;padding:2px 10px;">
                              <span style="font-size:11px;font-weight:700;color:#ffffff;letter-spacing:0.5px;">HTML</span>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <!-- 코드 -->
                    <tr>
                      <td style="background:#1e1b2e;padding:18px 20px;">
                        <pre style="font-family:'JetBrains Mono','Fira Code','Courier New',monospace;font-size:11.5px;color:#c4b5fd;line-height:1.65;margin:0;white-space:pre-wrap;word-break:break-all;">{{ Str::limit($message->html_output, 1500, "\n\n" . __('emails.ai_code_truncated')) }}</pre>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
            @endif

            <!-- CSS 코드 -->
            @if($message->css_output)
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding:14px 40px 0;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                      <td style="background:#f8f9fa;padding:10px 16px;border-bottom:1px solid #e5e7eb;">
                        <table cellpadding="0" cellspacing="0" border="0">
                          <tr>
                            <td style="width:10px;height:10px;border-radius:50%;background:#ff5f57;"></td>
                            <td style="width:6px;"></td>
                            <td style="width:10px;height:10px;border-radius:50%;background:#ffbd2e;"></td>
                            <td style="width:6px;"></td>
                            <td style="width:10px;height:10px;border-radius:50%;background:#28c840;"></td>
                            <td style="width:16px;"></td>
                            <td style="background:#059669;border-radius:4px;padding:2px 10px;">
                              <span style="font-size:11px;font-weight:700;color:#ffffff;letter-spacing:0.5px;">CSS</span>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <tr>
                      <td style="background:#1e1b2e;padding:18px 20px;">
                        <pre style="font-family:'JetBrains Mono','Fira Code','Courier New',monospace;font-size:11.5px;color:#6ee7b7;line-height:1.65;margin:0;white-space:pre-wrap;word-break:break-all;">{{ Str::limit($message->css_output, 800, "\n\n" . __('emails.ai_truncated')) }}</pre>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
            @endif

            <!-- JS 코드 -->
            @if($message->js_output)
            <table width="100%" cellpadding="0">
              <tr>
                <td style="padding:14px 40px 0;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                      <td style="background:#f8f9fa;padding:10px 16px;border-bottom:1px solid #e5e7eb;">
                        <table cellpadding="0" cellspacing="0" border="0">
                          <tr>
                            <td style="width:10px;height:10px;border-radius:50%;background:#ff5f57;"></td>
                            <td style="width:6px;"></td>
                            <td style="width:10px;height:10px;border-radius:50%;background:#ffbd2e;"></td>
                            <td style="width:6px;"></td>
                            <td style="width:10px;height:10px;border-radius:50%;background:#28c840;"></td>
                            <td style="width:16px;"></td>
                            <td style="background:#d97706;border-radius:4px;padding:2px 10px;">
                              <span style="font-size:11px;font-weight:700;color:#ffffff;letter-spacing:0.5px;">JS</span>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <tr>
                      <td style="background:#1e1b2e;padding:18px 20px;">
                        <pre style="font-family:'JetBrains Mono','Fira Code','Courier New',monospace;font-size:11.5px;color:#fde68a;line-height:1.65;margin:0;white-space:pre-wrap;word-break:break-all;">{{ Str::limit($message->js_output, 800, "\n\n" . __('emails.ai_truncated')) }}</pre>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
            @endif

            <!-- 하단 CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding:32px 40px 36px;text-align:center;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
                    <tr>
                      <td style="height:1px;background:linear-gradient(90deg,transparent,#ddd6fe,transparent);"></td>
                    </tr>
                  </table>
                  <p style="font-size:12px;color:#9ca3af;line-height:1.8;margin:0;">
                    {{ __('emails.ai_footer_note') }}<br>
                    {{ __('emails.ai_footer_note2') }}
                  </p>
                </td>
              </tr>
            </table>

          </td>
        </tr>

        <!-- 푸터 -->
        <tr>
          <td style="padding-top:24px;text-align:center;">
            <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 10px;">
              <tr>
                <td style="background:linear-gradient(135deg,#7c3aed,#4f46e5);border-radius:8px;width:28px;height:28px;text-align:center;vertical-align:middle;">
                  <span style="font-size:13px;font-weight:900;color:#fff;line-height:28px;display:block;">S</span>
                </td>
                <td style="padding-left:7px;vertical-align:middle;">
                  <span style="font-size:14px;font-weight:800;color:#6d28d9;letter-spacing:-0.2px;">SupportWorks</span>
                </td>
              </tr>
            </table>
            <p style="font-size:11px;color:#a78bfa;margin:0;">© {{ date('Y') }} SupportWorks. All rights reserved.</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
