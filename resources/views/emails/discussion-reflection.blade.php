<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>{{ $project->name }} - 논의사항 결과</title>
</head>
<body style="margin:0;padding:24px;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;color:#1f2937;background:#f9fafb;">
    <div style="max-width:640px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
        @php
            $isReflected = $decision === 'reflected';
            $statusKr    = $isReflected ? '기획서에 반영됨' : '반영하지 않기로 결정';
            $statusBg    = $isReflected ? '#dcfce7' : '#fee2e2';
            $statusFg    = $isReflected ? '#15803d' : '#b91c1c';
        @endphp

        <div style="background:linear-gradient(135deg,#7c3aed,#a78bfa);padding:18px 24px;color:#fff;">
            <div style="font-size:11px;opacity:.85;letter-spacing:.05em;text-transform:uppercase;">{{ $project->name }} · 논의사항 결과</div>
            <div style="font-size:18px;font-weight:700;margin-top:4px;">{{ $discussion->title }}</div>
        </div>

        <div style="padding:20px 24px;">
            <div style="display:inline-block;font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px;background:{{ $statusBg }};color:{{ $statusFg }};margin-bottom:14px;">
                {{ $statusKr }}
            </div>

            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <tr>
                    <td style="padding:8px 0;color:#6b7280;width:110px;vertical-align:top;">결정자</td>
                    <td style="padding:8px 0;color:#1f2937;font-weight:600;">{{ $decidedBy->name }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#6b7280;vertical-align:top;">결정일</td>
                    <td style="padding:8px 0;color:#1f2937;">{{ now()->format('Y-m-d H:i') }}</td>
                </tr>
                @if($isReflected && $planningDoc)
                <tr>
                    <td style="padding:8px 0;color:#6b7280;vertical-align:top;">반영 기획서</td>
                    <td style="padding:8px 0;color:#1f2937;">{{ $planningDoc->title }}</td>
                </tr>
                @endif
                @if(!$isReflected && !empty($note))
                <tr>
                    <td style="padding:8px 0;color:#6b7280;vertical-align:top;">결정 사유</td>
                    <td style="padding:8px 0;color:#1f2937;white-space:pre-wrap;">{{ $note }}</td>
                </tr>
                @endif
            </table>

            <div style="margin-top:18px;padding:14px 16px;background:#fafafa;border:1px solid #f3f4f6;border-radius:8px;">
                <div style="font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.04em;margin-bottom:6px;">결론</div>
                <div style="font-size:13px;color:#1f2937;line-height:1.65;white-space:pre-wrap;">{!! $discussion->conclusion ? e($discussion->conclusion) : '<span style="color:#9ca3af;">(결론 없음)</span>' !!}</div>
            </div>

            <div style="margin-top:22px;display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ $discussionUrl }}" style="display:inline-block;padding:9px 18px;background:#fff;border:1.5px solid #ddd6fe;color:#5b21b6;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">논의사항 보기 →</a>
                @if($planningUrl)
                    <a href="{{ $planningUrl }}" style="display:inline-block;padding:9px 18px;background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:700;">기획서 보기 →</a>
                @endif
            </div>
        </div>

        <div style="padding:12px 24px;background:#fafafa;border-top:1px solid #f3f4f6;font-size:11px;color:#9ca3af;">
            본 메일은 SupportWorks에서 자동 발송되었습니다.
        </div>
    </div>
</body>
</html>
