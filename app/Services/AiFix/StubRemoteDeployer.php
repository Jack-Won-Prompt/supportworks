<?php

namespace App\Services\AiFix;

/**
 * PoC/테스트용 RemoteDeployer. 실제 SSH 호출 없이 미리 지정된 exit code 를 반환.
 *
 * 사용 예:
 *   new StubRemoteDeployer()              // 성공 (exit 0)
 *   new StubRemoteDeployer(exitCode: 4)   // 헬스체크 실패 + 롤백 성공
 *   new StubRemoteDeployer(exitCode: 5)   // 롤백도 실패 (수동 개입)
 *   new StubRemoteDeployer(throwOnDeploy: true)  // SSH 자체 실패 시뮬레이션
 */
final class StubRemoteDeployer implements RemoteDeployer
{
    public function __construct(
        private readonly int $exitCode = 0,
        private readonly string $stdout = '[stub] deploy.sh executed',
        private readonly string $stderr = '',
        private readonly bool $throwOnDeploy = false,
        private readonly ?string $exception = null,
    ) {}

    public function deploy(?string $expectedSha = null): DeployResult
    {
        if ($this->throwOnDeploy) {
            throw new \RuntimeException($this->exception ?? 'stub ssh failure');
        }

        return DeployResult::fromShell(
            exitCode:       $this->exitCode,
            stdout:         $this->stdout,
            stderr:         $this->stderr,
            deployedCommit: $this->exitCode === 0 ? $expectedSha : null,
        );
    }
}
