<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'turn' => [
        'url'        => env('TURN_URL', 'openrelay.metered.ca'),
        'username'   => env('TURN_USERNAME', 'openrelayproject'),
        'credential' => env('TURN_CREDENTIAL', 'openrelayproject'),
    ],

    'libreoffice' => [
        'path' => env('LIBREOFFICE_PATH', 'C:\\Program Files\\LibreOffice\\program\\soffice.exe'),
    ],

    'genspark' => [
        'key'      => env('GENSPARK_API_KEY'),
        'endpoint' => env('GENSPARK_API_URL', 'https://api.genspark.ai/v1/chat/completions'),
        'model'    => env('GENSPARK_MODEL', 'gpt-4o'),
    ],

    'anthropic' => [
        'key'     => env('ANTHROPIC_API_KEY'),
        'model'   => env('ANTHROPIC_MODEL', 'claude-opus-4-7'),
        'timeout' => env('ANTHROPIC_TIMEOUT', 30),
    ],

    'openai' => [
        'key'         => env('OPENAI_API_KEY'),
        'model'       => env('OPENAI_MODEL', 'gpt-4o'),
        // function-calling(tools) 호출 시 사용할 모델 — gpt-5.5는 tools 호출 시 500 server_error 반환하는 버그가 있어 별도 분리
        'tools_model' => env('OPENAI_TOOLS_MODEL', 'gpt-4o'),
        'timeout'     => env('OPENAI_TIMEOUT', 30),
        'base_url'    => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    'llm_router' => [
        'primary'          => env('LLM_PRIMARY_PROVIDER', 'claude'),
        'fallback'         => env('LLM_FALLBACK_PROVIDER', 'openai'),
        'fallback_enabled' => env('LLM_FALLBACK_ENABLED', true),
    ],

    // AiSetting::figmaToken() / manusKey() 가 fallback 으로 사용 — config 통해 env() 캡처.
    'figma' => [
        'token' => env('FIGMA_TOKEN'),
    ],

    'manus' => [
        'key' => env('MANUS_API_KEY'),
    ],

    // 외부 시스템(withworks 등)에서 supportworks 로 보내는 에러의 HMAC 인증.
    // source 키별로 secret 을 분리하여 키 회전·시스템별 차단을 쉽게 함.
    'external_errors' => [
        'sources' => [
            'withworks'   => env('WITHWORKS_HMAC_SECRET'),
            'fulfillment' => env('FULFILLMENT_HMAC_SECRET'),
        ],
    ],

];
