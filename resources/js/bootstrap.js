import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Set CSRF token from meta tag for all Axios requests (more reliable than XSRF-TOKEN cookie)
const _csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (_csrfMeta) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = _csrfMeta.getAttribute('content');
}

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;


window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    // APP_URL 무관하게 현재 접속 중인 서버로 auth 요청
    authorizer: (channel) => ({
        authorize: (socketId, callback) => {
            axios.post(
                window.location.origin + (window.broadcastAuthPath || '/broadcasting/auth'),
                { socket_id: socketId, channel_name: channel.name }
            )
            .then(r  => callback(false, r.data))
            .catch(e => {
                const status = e.response?.status;
                // 403 is expected for deleted/inaccessible channels — degrade gracefully
                if (status !== 403) {
                    console.warn('[Pusher Auth] unexpected status', status, 'for', channel.name);
                }
                callback(true, e);
            });
        },
    }),
});

window.dispatchEvent(new CustomEvent('echoReady'));
