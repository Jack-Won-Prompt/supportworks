<?php

namespace App\Services\AiFix;

/**
 * RemoteDeployer 가 운영 서버의 deploy.sh 를 실행한 결과.
 *
 * deploy.sh exit code 매핑:
 *   0 → success=true, rolledBack=false  (정상 배포)
 *   1 → success=false, rolledBack=false (preflight 실패)
 *   2 → success=false, rolledBack=false (dep install 실패)
 *   3 → success=false, rolledBack=false (migration 실패)
 *   4 → success=false, rolledBack=true  (healthz 실패 + 자동 롤백 성공)
 *   5 → success=false, rolledBack=false (healthz 실패 + 롤백도 실패 — 수동 개입)
 */
final class DeployResult
{
    public function __construct(
        public readonly bool   $success,
        public readonly int    $exitCode,
        public readonly string $stdout    = '',
        public readonly string $stderr    = '',
        public readonly bool   $rolledBack = false,
        /** 운영 서버 HEAD 의 commit SHA (deploy.sh 가 출력한 값) */
        public readonly ?string $deployedCommit = null,
    ) {}

    /** deploy.sh exit code 를 받아 매핑된 DeployResult 를 생성 */
    public static function fromShell(int $exitCode, string $stdout = '', string $stderr = '', ?string $deployedCommit = null): self
    {
        return new self(
            success:        $exitCode === 0,
            exitCode:       $exitCode,
            stdout:         $stdout,
            stderr:         $stderr,
            rolledBack:     $exitCode === 4,
            deployedCommit: $deployedCommit,
        );
    }

    /** 결과를 사람이 읽을 수 있는 짧은 문자열로 (FCM body, AiFixJob.error_message 등에 사용) */
    public function summary(): string
    {
        if ($this->success) {
            return 'deployed: ' . ($this->deployedCommit ?? '(unknown commit)');
        }
        return match ($this->exitCode) {
            1 => 'deploy preflight failed (uncommitted changes or missing tools)',
            2 => 'dependency install failed (composer/npm)',
            3 => 'database migration failed',
            4 => 'health check failed — auto-rolled back to previous commit',
            5 => 'health check failed AND rollback also failed — manual intervention required',
            default => "deploy.sh exited with code {$this->exitCode}",
        };
    }

    public function toArray(): array
    {
        return [
            'success'         => $this->success,
            'exit_code'       => $this->exitCode,
            'rolled_back'     => $this->rolledBack,
            'deployed_commit' => $this->deployedCommit,
            'summary'         => $this->summary(),
            'stdout'          => mb_substr($this->stdout, -4000),
            'stderr'          => mb_substr($this->stderr, -2000),
        ];
    }
}
