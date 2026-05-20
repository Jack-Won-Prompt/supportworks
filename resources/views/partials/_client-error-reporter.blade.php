{{-- 브라우저 JS 에러 자동 수집 → POST /client-errors → SystemErrorLog + 관리자 FCM 알림.
     모든 layout(app/admin/guest/popup)에 @include 권장. 비로그인 페이지에서도 동작 가능. --}}
<script>
(function () {
    var ENDPOINT = '/client-errors';
    var sent = {};
    var MAX_PER_SESSION = 30;
    var sentCount = 0;

    function send(payload) {
        // session dedup: 같은 (name+source+line) 은 한 번만
        var key = (payload.name || '') + '|' + (payload.source || '') + '|' + (payload.line || '');
        if (sent[key]) return;
        sent[key] = 1;
        if (sentCount++ >= MAX_PER_SESSION) return;

        try {
            var body = JSON.stringify(payload);
            // sendBeacon 우선 (페이지 떠나는 중에도 동작). 실패 시 fetch fallback.
            if (navigator.sendBeacon) {
                var blob = new Blob([body], { type: 'application/json' });
                if (navigator.sendBeacon(ENDPOINT, blob)) return;
            }
            fetch(ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: body,
                credentials: 'same-origin',
                keepalive: true,
            }).catch(function () { /* 에러 보고 실패는 무시 */ });
        } catch (_) { /* JSON.stringify 실패 등 무시 */ }
    }

    // 1) 일반 JS 에러
    window.addEventListener('error', function (e) {
        // 리소스 로딩 에러(이미지 404 등) 는 e.error 없음 — 노이즈라 제외
        if (!e.error && !e.message) return;
        send({
            name:    (e.error && e.error.name) || 'Error',
            message: e.message || String(e.error),
            source:  e.filename || '',
            line:    e.lineno || 0,
            column:  e.colno || 0,
            stack:   (e.error && e.error.stack) ? String(e.error.stack).slice(0, 4000) : '',
            url:     location.href,
        });
    });

    // 2) Promise rejection (await 실패, fetch.catch 누락 등)
    window.addEventListener('unhandledrejection', function (e) {
        var reason = e.reason;
        var msg = (reason && reason.message) ? reason.message : String(reason);
        send({
            name:    (reason && reason.name) || 'UnhandledRejection',
            message: msg,
            source:  '',
            line:    0,
            stack:   (reason && reason.stack) ? String(reason.stack).slice(0, 4000) : '',
            url:     location.href,
        });
    });
})();
</script>