import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // AI Works 화면에서만 로드한다. 기존 번들을 무겁게 하지 않는다.
                'resources/js/aiw-echo.js',
                'resources/js/aiw-markdown.js',
            ],
            refresh: true,
        }),
    ],
});
