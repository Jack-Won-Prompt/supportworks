<!DOCTYPE html>
<html lang="ko" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{{ $mailSubject }}</title>
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
                  <span style="font-size:11px;color:#7c3aed;font-weight:600;margin-left:7px;background:#ede9fe;padding:2px 7px;border-radius:20px;">기획서</span>
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
                @php
                  $bannerBg = match($type) {
                    'cleanup'    => 'linear-gradient(135deg,#7c3aed 0%,#4f46e5 50%,#2563eb 100%)',
                    'suggestion' => 'linear-gradient(135deg,#0891b2 0%,#0e7490 50%,#155e75 100%)',
                    'history'    => 'linear-gradient(135deg,#374151 0%,#1f2937 50%,#111827 100%)',
                    'document'   => 'linear-gradient(135deg,#7c3aed 0%,#4f46e5 50%,#2563eb 100%)',
                    default      => 'linear-gradient(135deg,#7c3aed 0%,#4f46e5 50%,#2563eb 100%)',
                  };
                  $typeLabel = match($type) {
                    'cleanup'    => '웍스 기획서 정제 결과',
                    'suggestion' => '웍스 기능 추천',
                    'history'    => '변경 이력',
                    'document'   => '기획서 공유',
                    default      => '기획서 내용',
                  };
                @endphp
                <td style="background:{{ $bannerBg }};padding:40px 44px 36px;text-align:center;">

                  <!-- 아이콘 원 -->
                  <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 20px;">
                    <tr>
                      <td style="background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);border-radius:50%;width:68px;height:68px;text-align:center;vertical-align:middle;">
                        @if($type === 'cleanup')
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" style="display:inline-block;vertical-align:middle;">
                          <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                            stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        @elseif($type === 'suggestion')
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" style="display:inline-block;vertical-align:middle;">
                          <path d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"
                            stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        @else
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" style="display:inline-block;vertical-align:middle;">
                          <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        @endif
                      </td>
                    </tr>
                  </table>

                  <p style="font-size:11px;font-weight:700;color:rgba(255,255,255,0.65);letter-spacing:3px;text-transform:uppercase;margin:0 0 10px;">{{ strtoupper($typeLabel) }}</p>
                  <h1 style="font-size:20px;font-weight:800;color:#ffffff;line-height:1.35;letter-spacing:-0.4px;margin:0 0 14px;">{{ $mailSubject }}</h1>

                  <!-- 메타 정보 칩들 -->
                  <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
                    <tr>
                      <td style="background:rgba(255,255,255,0.18);border-radius:20px;padding:5px 13px;font-size:12px;color:#ffffff;font-weight:500;">
                        {{ $projectName }}
                      </td>
                      <td style="width:8px;"></td>
                      <td style="background:rgba(255,255,255,0.18);border-radius:20px;padding:5px 13px;font-size:12px;color:#ffffff;font-weight:500;">
                        {{ $docTitle }}
                      </td>
                      <td style="width:8px;"></td>
                      <td style="background:rgba(255,255,255,0.18);border-radius:20px;padding:5px 13px;font-size:12px;color:#ffffff;font-weight:500;">
                        {{ now()->format('Y.m.d H:i') }}
                      </td>
                    </tr>
                  </table>

                </td>
              </tr>
            </table>

            <!-- 내용 본문 -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding:32px 40px 0;">
                  @if($type === 'document')
                  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:18px 22px;margin-bottom:16px;text-align:center;">
                    <p style="font-size:14px;font-weight:700;color:#065f46;margin:0 0 6px;">📎 첨부파일을 확인해 주세요</p>
                    <p style="font-size:13px;color:#047857;margin:0;">기획서 전문이 HTML 파일로 첨부되어 있습니다. 브라우저로 열어보세요.</p>
                  </div>
                  @if($content)
                  <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:14px;">
                    <tr>
                      <td style="border-left:3px solid #7c3aed;padding-left:10px;">
                        <span style="font-size:12px;font-weight:700;color:#7c3aed;letter-spacing:0.5px;text-transform:uppercase;">추가 메시지</span>
                      </td>
                    </tr>
                  </table>
                  <div style="background:#faf9ff;border:1px solid #ede9fe;border-radius:12px;padding:20px 22px;">
                    <pre style="font-size:13px;color:#374151;line-height:1.8;margin:0;white-space:pre-wrap;word-break:break-word;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Helvetica Neue',Arial,sans-serif;">{{ $content }}</pre>
                  </div>
                  @endif
                  @else
                  <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:14px;">
                    <tr>
                      <td style="border-left:3px solid #7c3aed;padding-left:10px;">
                        <span style="font-size:12px;font-weight:700;color:#7c3aed;letter-spacing:0.5px;text-transform:uppercase;">내용</span>
                      </td>
                    </tr>
                  </table>
                  <div style="background:#faf9ff;border:1px solid #ede9fe;border-radius:12px;padding:20px 22px;">
                    <pre style="font-size:13px;color:#374151;line-height:1.8;margin:0;white-space:pre-wrap;word-break:break-word;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Helvetica Neue',Arial,sans-serif;">{{ $content }}</pre>
                  </div>
                  @endif
                </td>
              </tr>
            </table>

            <!-- 하단 -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding:32px 40px 36px;text-align:center;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
                    <tr>
                      <td style="height:1px;background:linear-gradient(90deg,transparent,#ddd6fe,transparent);"></td>
                    </tr>
                  </table>
                  <p style="font-size:12px;color:#9ca3af;line-height:1.8;margin:0;">
                    SupportWorks 기획서에서 직접 발송된 메일입니다.<br>
                    이 메일은 자동으로 생성되었습니다.
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
