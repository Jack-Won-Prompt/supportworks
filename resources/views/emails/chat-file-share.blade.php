<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>{{ $fileName }}</title>
</head>
<body style="margin:0;padding:24px;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;color:#1f2937;background:#f9fafb;">
    <div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:24px;">
        <p style="margin:0 0 10px;font-size:14px;line-height:1.6;">
            <strong>{{ $senderName }}</strong>님이 파일을 공유했습니다.
        </p>
        <p style="margin:0;font-size:13px;color:#6b7280;">
            첨부파일: {{ $fileName }}
        </p>
    </div>
</body>
</html>
