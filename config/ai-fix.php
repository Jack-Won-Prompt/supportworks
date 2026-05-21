<?php

/*
 * AI Fix 자동화 정책
 *
 * 시스템 에러를 AI가 자동 수정할지, 사람이 검토해야 할지를 결정하는 규칙 집합.
 * EscalationEvaluator 가 이 설정을 읽어 FixContext 를 평가하고
 * 'auto' / 'escalate' / 'block' 중 하나의 결정을 내린다.
 *
 * 결정 순서:
 *   1) 변경 파일이 always_block 패턴에 매칭 → 'block' (사람 수동 처리)
 *   2) red 신호가 하나라도 → 'escalate' (관리자 승인 필요)
 *   3) yellow 신호 누적 >= yellow_threshold → 'escalate'
 *   4) 모든 변경 파일이 auto_eligible 패턴에 매칭 + red 0 + yellow < threshold → 'auto'
 *   5) 그 외 → 'escalate' (안전 기본값)
 *
 * 경로 패턴은 fnmatch() 구문 (* / ? / ** / [chars]).
 */

return [

    /*
    |--------------------------------------------------------------------------
    | 자동 트리거 (auto_trigger)
    |--------------------------------------------------------------------------
    | true 면 SystemErrorLog::record() 가 critical 레벨 에러를 기록한 직후
    | AnalyzeSystemErrorJob 을 dispatch 해 AI 분석을 시작한다.
    |
    | 기본값 false — PoC/검증 단계에선 artisan ai-fix:analyze 로 수동 트리거 권장.
    | 운영에서 켜기 전에 큐 워커 / 비용 정책 / 에스컬레이션 정책이 안정화돼야 함.
    */
    'auto_trigger' => env('AI_FIX_AUTO_TRIGGER', false),

    /*
    |--------------------------------------------------------------------------
    | Worktree (격리 작업 트리)
    |--------------------------------------------------------------------------
    | driver=stub: 가짜 경로만 반환, 파일시스템 영향 0. PoC/테스트 default.
    | driver=process: ProcessWorktreeManager 가 실제 git worktree + sqlite +
    |   composer install + migrate 까지 자동 셋업. bare_path/base_path 필수.
    |
    | 운영 사전 셋업 (driver=process 활성화 전):
    |   mkdir -p /home/ubuntu/ai-maintenance
    |   git clone --bare https://github.com/Jack-Won-Prompt/supportworks.git \
    |       /home/ubuntu/ai-maintenance/supportworks.git
    */
    'worktree' => [
        'driver'        => env('AI_FIX_WORKTREE_DRIVER', 'stub'),
        'bare_path'     => env('AI_FIX_WORKTREE_BARE_PATH'),
        'base_path'     => env('AI_FIX_WORKTREE_BASE_PATH'),
        'source_env'    => env('AI_FIX_WORKTREE_SOURCE_ENV', base_path('.env')),
        // 격리 테스트 DB (운영 mysql 서버에 별도 database). dialect 일치라 모든 migration
        // 정상. 운영 사전 셋업: CREATE DATABASE + GRANT ALL TO 운영 DB user.
        'test_database' => env('AI_FIX_WORKTREE_TEST_DB', 'supportworks_ai_test'),
    ],

    /*
    |--------------------------------------------------------------------------
    | TestRunner (테스트 실행기)
    |--------------------------------------------------------------------------
    | driver=stub: 미리 정해둔 TestResult 반환 (PoC).
    | driver=phpunit: PhpUnitTestRunner 가 worktree 안 vendor/bin/phpunit 실행 후
    |   stdout 파싱해 TestResult 채움. AI_FIX_TEST_RUNNER_TIMEOUT 로 timeout 조절.
    */
    'test_runner' => [
        'driver'  => env('AI_FIX_TEST_RUNNER_DRIVER', 'stub'),
        'timeout' => (int) env('AI_FIX_TEST_RUNNER_TIMEOUT', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | AiAnalyzer (에러 분석기)
    |--------------------------------------------------------------------------
    | driver=stub: 휴리스틱 기반 분류 (PoC).
    | driver=openai: OpenAI Chat Completions 호출 (response_format=json_object).
    |   API key 는 AiSetting::current()->openaiKey() 에서. binding 단계에서 자동 주입.
    | model: gpt-4o-mini default (비용·성능 균형). gpt-4o 또는 더 작은 모델로 변경 가능.
    */
    'analyzer' => [
        'driver'         => env('AI_FIX_ANALYZER_DRIVER',         'stub'),
        // primary 모델이 5xx / timeout / invalid JSON 등 어떤 이유로든 실패하면
        // fallback_model 로 한 번 재시도. 둘 다 실패해야 fallback() AnalysisResult 반환.
        // default: gpt-4.1 (2025-04 최신, 사용자 키 가용 확인) → gpt-4o (안정·범용).
        // primary 가 5xx/limit/timeout 등으로 실패하면 자동 fallback. env 로 override 가능.
        'model'          => env('AI_FIX_ANALYZER_MODEL',          'gpt-4.1'),
        'fallback_model' => env('AI_FIX_ANALYZER_FALLBACK_MODEL', 'gpt-4o'),
        'timeout'        => (int) env('AI_FIX_ANALYZER_TIMEOUT',  60),
    ],

    /*
    |--------------------------------------------------------------------------
    | AiCodeApplier (코드 수정 적용기)
    |--------------------------------------------------------------------------
    | driver=stub: 실제 코드 수정 없이 항상 true (PoC).
    | driver=openai: OpenAiCodeApplier 가 각 changed_files 에 대해 AI 호출 → 새
    |   파일 전체 내용 받음 → php -l syntax 통과 시만 write. fallback chain 동일.
    | timeout 120s (파일 큰 경우 대비). API key 는 AiSetting 에서.
    */
    'applier' => [
        'driver'         => env('AI_FIX_APPLIER_DRIVER',          'stub'),
        'model'          => env('AI_FIX_APPLIER_MODEL',           'gpt-4.1'),
        'fallback_model' => env('AI_FIX_APPLIER_FALLBACK_MODEL',  'gpt-4o'),
        'timeout'        => (int) env('AI_FIX_APPLIER_TIMEOUT',   120),
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHubMerger (PR 생성 + auto merge)
    |--------------------------------------------------------------------------
    | driver=stub: 가짜 PR/SHA 반환 (PoC).
    | driver=github: GuzzleGitHubMerger 가 worktree 에서 git push → GitHub PR
    |   생성 → squash merge. token 은 AI_FIX_MERGER_GITHUB_TOKEN (PAT 또는 GitHub
    |   App). owner/repo default 는 운영 repo.
    */
    'merger' => [
        'driver'       => env('AI_FIX_MERGER_DRIVER',       'stub'),
        'token'        => env('AI_FIX_MERGER_GITHUB_TOKEN'),
        'owner'        => env('AI_FIX_MERGER_OWNER',        'Jack-Won-Prompt'),
        'repo'         => env('AI_FIX_MERGER_REPO',         'supportworks'),
        'merge_method' => env('AI_FIX_MERGER_METHOD',       'squash'),
        'timeout'      => (int) env('AI_FIX_MERGER_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | 자동 수정 화이트리스트 (auto_eligible)
    |--------------------------------------------------------------------------
    | 변경 파일 *전체*가 이 패턴에 매칭되고 red/yellow 신호가 임계값 미만이면
    | 관리자 승인 없이 PR 생성 + 자동 머지 가능 (운영 정책에 따라).
    */
    'auto_eligible' => [
        'app/Http/Requests/**/*Request.php',
        'resources/lang/**',
        'resources/views/**/*.blade.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | 절대 차단 (always_block)
    |--------------------------------------------------------------------------
    | 어떤 변경이든 이 경로에 영향을 주면 'block'. 자동 수정 불가, 사람 수동.
    */
    'always_block' => [
        'app/Services/Payment/**',
        'app/Services/Billing/**',
        'app/Http/Middleware/Auth*.php',
        'app/Http/Middleware/Admin*.php',
        'database/migrations/**',
        'config/database.php',
        'config/auth.php',
        'config/services.php',
        '.env*',
        'storage/app/firebase/**',
    ],

    /*
    |--------------------------------------------------------------------------
    | 보안/민감 키워드 (security_keywords)
    |--------------------------------------------------------------------------
    | 변경 파일 경로 또는 진단된 카테고리 문자열에 이 키워드가 있으면
    | 자동으로 red 신호 'security_keyword_match' 추가.
    */
    'security_keywords' => [
        'password',
        'token',
        'api_key',
        'apikey',
        'secret',
        'permission',
        'authorize',
        'encrypt',
        'decrypt',
        'session',
    ],

    /*
    |--------------------------------------------------------------------------
    | 신호 임계값 (signals)
    |--------------------------------------------------------------------------
    */
    'signals' => [

        // 변경 파일 수 (red): 영향 범위가 너무 크면 즉시 사람.
        'many_files_changed_threshold' => 5,

        // AI 분류 신뢰도 (yellow): 0.0~1.0, 이 값 미만이면 신호 발동.
        'classification_confidence_min' => 0.5,

        // 같은 에러 반복 (yellow): 동일 fingerprint 가 최근 1시간 내 N회 이상.
        'same_error_repeat_threshold' => 3,
        'same_error_window_minutes'   => 60,

        // 외부 API 의존 키워드 (yellow) — 스택트레이스/메시지에 매칭되면 발동.
        'external_api_keywords' => [
            'curl_exec',
            'GuzzleHttp',
            'Connection refused',
            'cURL error',
            'timed out',
            'TLS handshake',
            'SMTP',
        ],

        // 환경 의존 (yellow) — 재현 불가 가능성 시그널.
        'env_specific_keywords' => [
            'browser',
            'user agent',
            'platform',
            'locale',
            'timezone',
        ],

        // 비즈니스 로직 영역 (yellow) — 의도 변경 위험. 패턴 매칭.
        // EscalationEvaluator 가 changedFiles 중 하나라도 매칭되면 'business_logic_modified' 발동.
        'business_logic_paths' => [
            'app/Services/**',
            'app/Domain/**',
            'app/Models/**/Concerns/**',
        ],

        // 운영 DB 실제 데이터 확인 필요 (yellow) — 키워드 매칭.
        // 메시지가 "데이터 불일치 / 누락 레코드" 를 시사하면 AI 가 운영 DB 를 못 보므로 사람 필요.
        'prod_data_keywords' => [
            'row not found',
            'no query results',
            'duplicate entry',
            'data inconsistency',
            'integrity constraint',
            'foreign key constraint',
            'missing record',
            'data corruption',
        ],

        // 여러 시스템 간 연관 (yellow) — 도메인 키워드를 그룹화. errorBlob 에
        // 서로 다른 도메인 키워드가 2개 이상 동시에 매칭되면 'cross_system_concern' 발동.
        'system_domains' => [
            'auth'     => ['login', 'logout', 'authentication', 'auth guard'],
            'payment'  => ['payment', 'billing', 'invoice', 'stripe', 'iamport'],
            'queue'    => ['queue', 'job dispatched', 'failed_jobs'],
            'fcm'      => ['firebase', 'fcm', 'push notification'],
            'file'     => ['file upload', 'storage::disk', 'multipart'],
            'realtime' => ['websocket', 'broadcast', 'pusher'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 결정 임계값 (decision)
    |--------------------------------------------------------------------------
    */
    'decision' => [
        // red 신호 1개라도 → escalate
        'red_max'    => 0,

        // yellow 신호가 이 개수 이상이면 escalate
        'yellow_max' => 1,
    ],

];