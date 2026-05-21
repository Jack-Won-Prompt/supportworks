<?php

namespace App\Services\AiFix;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * 실제 git worktree 생성/제거 + 격리 .env + sqlite + composer install + migrate
 * 까지 자동화하는 WorktreeManager 구현.
 *
 * 메모리 [[ai-maintenance-worktree]] 의 prepare-worktree.ps1 / cleanup-worktree.ps1 와
 * 같은 책임을 OS 독립적으로 PHP/Symfony Process 로 수행.
 *
 * 필수 환경:
 *   - bare_path:     bare clone 의 .git 디렉터리 경로 (예: /home/ubuntu/ai-maintenance/supportworks.git)
 *   - base_path:     worktree 들이 생성될 부모 디렉터리
 *   - source_env:    복사 원본 .env (운영 mysql credentials 포함)
 *   - test_database: 격리 테스트용 mysql database 이름 (예: supportworks_ai_test).
 *                    운영 mysql 서버에 미리 생성돼 있어야 하고 운영 DB user 가 GRANT 받음.
 *
 * 운영 사전 셋업:
 *   mkdir -p /home/ubuntu/ai-maintenance
 *   git clone --bare https://github.com/Jack-Won-Prompt/supportworks.git \
 *       /home/ubuntu/ai-maintenance/supportworks.git
 *
 *   # 격리 테스트 DB (운영 mysql 같은 서버, 다른 database)
 *   mysql -e "CREATE DATABASE supportworks_ai_test
 *             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
 *   mysql -e "GRANT ALL ON supportworks_ai_test.* TO '<운영_db_user>'@'%';"
 *
 * 격리 .env 가 DB_DATABASE 만 운영 → test 로 오버라이드. host/port/user/password 는
 * 운영 .env 값 그대로 사용. mail/queue/cache/session 등은 운영 자원 안 건드리도록 분리.
 *
 * 멀티-worktree 충돌: 단일 test_database 공유. migration 은 멱등이라 첫 worktree 만 시간
 * 많이 들고, 그 다음 worktree 는 nothing to migrate. 테스트 데이터는 phpunit 의 transaction
 * rollback 또는 RefreshDatabase trait 으로 분리.
 */
final class ProcessWorktreeManager implements WorktreeManager
{
    private const PROCESS_TIMEOUT = 300; // composer install 가 느릴 수 있음

    public function __construct(
        private readonly string $barePath,
        private readonly string $basePath,
        private readonly string $sourceEnv,
        private readonly string $testDatabase,
    ) {}

    public function create(int $jobId, string $branch): string
    {
        $worktreePath = $this->worktreePath($jobId);

        // 멱등: 이미 worktree 가 있으면 그 경로 그대로 (재시도 안전)
        if (is_dir($worktreePath) && (is_dir($worktreePath . '/.git') || is_file($worktreePath . '/.git'))) {
            return $worktreePath;
        }

        // 1) git worktree add — bare 에서 새 worktree + 새 브랜치 (master 기준)
        $this->git(['worktree', 'add', $worktreePath, '-b', $branch, 'master']);

        // 2) 격리 .env 작성 (DB_DATABASE 만 test DB 로 오버라이드, 나머지는 운영 .env 그대로)
        $this->writeIsolatedEnv($worktreePath);

        // 3) composer install (--no-scripts: 일부 ServiceProvider 가 미생성 테이블 eager
        //    조회하는 걸 우회. package:discover 는 migrate 다음에 분리 실행)
        $this->run(['composer', 'install', '--no-interaction', '--no-scripts', '--prefer-dist'], $worktreePath);

        // 4) APP_KEY 보장
        $this->run(['php', 'artisan', 'key:generate', '--force'], $worktreePath, allowFail: true);

        // 5) migrate (mysql dialect — 운영과 같은 schema)
        $this->run(['php', 'artisan', 'migrate', '--force', '--no-interaction'], $worktreePath);

        // 6) package:discover (이제 테이블 다 있으니 안전)
        $this->run(['php', 'artisan', 'package:discover'], $worktreePath, allowFail: true);

        return $worktreePath;
    }

    public function remove(int $jobId): void
    {
        $worktreePath = $this->worktreePath($jobId);

        if (file_exists($worktreePath)) {
            $this->git(['worktree', 'remove', '--force', $worktreePath], allowFail: true);
        } else {
            $this->git(['worktree', 'prune'], allowFail: true);
        }

        // 로컬 브랜치 정리 (best-effort — checkout 실패해도 무시)
        $this->git(['branch', '-D', "ai-fix/{$jobId}"], allowFail: true);
    }

    private function worktreePath(int $jobId): string
    {
        return rtrim($this->basePath, '/') . "/fix-{$jobId}";
    }

    private function writeIsolatedEnv(string $worktreePath): void
    {
        $env = is_file($this->sourceEnv) ? file_get_contents($this->sourceEnv) : '';

        // DB 만 격리 — host/port/user/password 는 운영 .env 그대로 사용 (같은 mysql 서버).
        // config/database.php 의 supportworks connection 이 DB_SUPPORTWORKS_DATABASE → DB_DATABASE
        // 순으로 fallback 하므로 둘 다 명시적으로 셋팅 (어떤 connection 이름이든 안전).
        // 나머지는 운영 자원(메일/큐/캐시/세션)을 건드리지 않도록 분리.
        $overrides = [
            'DB_DATABASE'              => $this->testDatabase,
            'DB_SUPPORTWORKS_DATABASE' => $this->testDatabase,
            'APP_ENV'                  => 'testing',
            'APP_DEBUG'            => 'true',
            'APP_URL'              => 'http://localhost',
            'MAIL_MAILER'          => 'log',
            'QUEUE_CONNECTION'     => 'sync',
            'CACHE_STORE'          => 'file',
            'SESSION_DRIVER'       => 'file',
            'BROADCAST_CONNECTION' => 'null',
            'AI_FIX_AUTO_TRIGGER'  => 'false', // 격리 환경에서 또 AiFix 자기 트리거 방지
        ];

        foreach ($overrides as $key => $value) {
            $env = $this->setEnvLine($env, $key, $value);
        }

        file_put_contents($worktreePath . '/.env', $env);
    }

    private function setEnvLine(string $env, string $key, string $value): string
    {
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
        $line = "$key=$value";
        if (preg_match($pattern, $env)) {
            return preg_replace($pattern, $line, $env);
        }
        return rtrim($env) . "\n" . $line . "\n";
    }

    private function git(array $args, bool $allowFail = false): void
    {
        $this->run(array_merge(['git', "--git-dir={$this->barePath}"], $args), cwd: null, allowFail: $allowFail);
    }

    private function run(array $cmd, ?string $cwd = null, bool $allowFail = false): void
    {
        $proc = new Process($cmd, $cwd);
        $proc->setTimeout(self::PROCESS_TIMEOUT);
        $proc->run();
        if (!$proc->isSuccessful() && !$allowFail) {
            throw new RuntimeException(
                "Process failed: " . implode(' ', $cmd) . "\n" . trim($proc->getErrorOutput() ?: $proc->getOutput())
            );
        }
    }
}
