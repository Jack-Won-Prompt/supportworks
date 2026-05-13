<?php

return [

    /*
    |----------------------------------------------------------------------
    | Anthropic
    |----------------------------------------------------------------------
    */
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model'   => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 240),
    ],

    /*
    |----------------------------------------------------------------------
    | OpenAI
    |----------------------------------------------------------------------
    */
    'openai' => [
        'api_key'  => env('OPENAI_API_KEY'),
        'model'    => env('OPENAI_MODEL', 'gpt-4o'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout'  => (int) env('OPENAI_TIMEOUT', 240),
    ],

    /*
    |----------------------------------------------------------------------
    | Provider routing — Agent Session 단위 Provider 선택용 기본값
    |----------------------------------------------------------------------
    | sessions.default_provider: 새 세션 생성 시 기본 provider (anthropic|openai)
    | mock_when_unconfigured: API key 미설정 시 mock 응답 사용 여부
    */
    'sessions' => [
        'default_provider'       => env('AI_AGENT_DEFAULT_PROVIDER', 'anthropic'),
        'mock_when_unconfigured' => filter_var(env('AI_AGENT_MOCK_WHEN_UNCONFIGURED', true), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |----------------------------------------------------------------------
    | Figma
    |----------------------------------------------------------------------
    */
    'figma' => [
        'access_token'  => env('FIGMA_ACCESS_TOKEN'),
        'client_id'     => env('FIGMA_CLIENT_ID'),
        'client_secret' => env('FIGMA_CLIENT_SECRET'),
        'redirect_uri'  => env('FIGMA_REDIRECT_URI'),
        'base_url'      => env('FIGMA_API_BASE_URL', 'https://api.figma.com/v1'),
        'oauth_base_url'=> env('FIGMA_OAUTH_BASE_URL', 'https://www.figma.com'),
        'scopes'        => env('FIGMA_OAUTH_SCOPES', 'file_read'),
    ],

    /*
    |----------------------------------------------------------------------
    | Storage — Agent Session 산출물/스냅샷/피드백 저장 경로
    |----------------------------------------------------------------------
    */
    'storage' => [
        'disk'          => env('AI_AGENT_STORAGE_DISK', 'local'),
        'output_path'   => env('AI_AGENT_OUTPUT_PATH',   'ai-agent/outputs'),
        'snapshot_path' => env('AI_AGENT_SNAPSHOT_PATH', 'ai-agent/snapshots'),
        'feedback_path' => env('AI_AGENT_FEEDBACK_PATH', 'ai-agent/feedback'),
        'zip_path'      => env('AI_AGENT_ZIP_PATH',      'ai-agent/zips'),
    ],

    /*
    |----------------------------------------------------------------------
    | Output 제약
    |----------------------------------------------------------------------
    | max_files_per_output: 한 Output당 허용 파일 수 (ZIP 생성 등에 사용)
    | allowed_screenshot_mime: 피드백 스크린샷 허용 MIME
    | max_screenshot_size_kb: 스크린샷 최대 크기 (KB)
    */
    'limits' => [
        'max_files_per_output'    => (int) env('AI_AGENT_MAX_FILES_PER_OUTPUT', 200),
        'max_screenshot_size_kb'  => (int) env('AI_AGENT_MAX_SCREENSHOT_SIZE_KB', 10240),
        'allowed_screenshot_mime' => ['image/png', 'image/jpeg', 'image/webp'],
    ],

];
