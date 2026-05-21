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
 *   - bare_path: bare clone 의 .git 디렉터리 경로 (예: /home/ubuntu/ai-maintenance/supportworks.git)
 *   - base_path: worktree 들이 생성될 부모 디렉터리 (예: /home/ubuntu/ai-maintenance)
 *   - source_env: 복사 원본 .env (예: /home/ubuntu/www/supportworks/.env)
 *
 * 운영 사전 셋업:
 *   mkdir -p /home/ubuntu/ai-maintenance
 *   git clone --bare https://github.com/Jack-Won-Prompt/supportworks.git \
 *       /home/ubuntu/ai-maintenance/supportworks.git
 *
 * 안전 오버라이드: 운영 DB·메일·큐·세션·캐시를 건드리지 않도록 모두 격리 환경으로.
 */
final class ProcessWorktreeManager implements WorktreeManager
{
    private const PROCESS_TIMEOUT = 300; // composer install 가 느릴 수 있음

    public function __construct(
        private readonly string $barePath,
        private readonly string $basePath,
        private readonly string $sourceEnv,
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

        // 2) 격리 .env 작성 (sqlite + mail=log + queue=sync 등으로 운영 의존성 차단)
        $this->writeIsolatedEnv($worktreePath);

        // 3) 빈 sqlite (testing DB)
        $sqlite = $worktreePath . '/database/ai-test.sqlite';
        if (!is_dir(dirname($sqlite))) {
            mkdir(dirname($sqlite), 0775, true);
        }
        if (!is_file($sqlite)) {
            touch($sqlite);
        }

        // 4) composer install (--no-scripts: 일부 ServiceProvider 가 미생성 테이블 eager
        //    조회하는 걸 우회. package:discover 는 migrate 다음에 분리 실행)
        $this->run(['composer', 'install', '--no-interaction', '--no-scripts', '--prefer-dist'], $worktreePath);

        // 5) APP_KEY 보장
        $this->run(['php', 'artisan', 'key:generate', '--force'], $worktreePath, allowFail: true);

        // 6) migrate
        $this->run(['php', 'artisan', 'migrate', '--force', '--no-interaction'], $worktreePath);

        // 7) package:discover (이제 테이블 다 있으니 안전)
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

        $overrides = [
            'DB_CONNECTION'        => 'sqlite',
            'DB_DATABASE'          => $worktreePath . '/database/ai-test.sqlite',
            'APP_ENV'              => 'testing',
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
