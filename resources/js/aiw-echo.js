// AI Works 전용 Echo 인스턴스 (Reverb).
// 기존 window.Echo(pusher)는 그대로 두고 별도 인스턴스를 쓴다 — 채팅·협업 등 기존 실시간 기능과 분리.
// AI Works 화면에서만 import 한다.
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY;

// REVERB 미설정 환경에서는 인스턴스를 만들지 않는다(서버측 채널 등록 가드와 대칭).
window.EchoAiw = key
    ? new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        // 기본 /broadcasting/auth 는 pusher secret 으로 서명하므로 쓸 수 없다.
        authEndpoint: '/aiw/broadcasting/auth',
    })
    : null;

if (!window.EchoAiw) {
    console.warn('AI Works: VITE_REVERB_APP_KEY 미설정 — 실시간 구독이 비활성화됩니다.');
}
