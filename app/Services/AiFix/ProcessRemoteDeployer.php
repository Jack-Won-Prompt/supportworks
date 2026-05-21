<?php

namespace App\Services\AiFix;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * deploy.sh 를 운영 서버에서 실행하는 RemoteDeployer.
 *
 * 현재 모드: local (Symfony Process 로 bash deploy.sh 직접 호출)
 *   AI Fix worker 가 같은 EC2 의 ubuntu user 로 돌고 deploy.sh 도 같은 위치라
 *   SSH 거치지 않고 self-deploy 가능. self-update 함정([[production-environment]])
 *   은 deploy.sh 자체 작동 — process spawn 이라 영향 없음.
 *
 * 향후 모드: ssh (다른 host 로 배포 시) — phpseclib 또는 ssh CLI 추가 가능.
 *
 * 결과 파싱: deploy.sh stdout 에서 "Deploy complete: PREV -> NEW" 또는
 *   "moved to NEW" 패턴으로 NEW commit SHA 추출.
 *
 * 활성화: AI_FIX_DEPLOYER_DRIVER=process
 */
final class ProcessRemoteDeployer implements RemoteDeployer
{
    public function __construct(
        private readonly string $script,   // 예: /home/ubuntu/www/supportworks/scripts/deploy.sh
        private readonly string $appPath,  // 예: /home/ubuntu/www/supportworks (deploy.sh 의 cwd)
        private readonly int    $timeout = 900,
    ) {}

    public function deploy(?string $expectedSha = null): DeployResult
    {
        if (!is_file($this->script)) {
            return new DeployResult(
                success:  false,
                exitCode: -1,
                stderr:   "deploy script not found: {$this->script}",
            );
        }

        $proc = new Process(['bash', $this->script], $this->appPath);
        $proc->setTimeout($this->timeout);
        $proc->run();

        $stdout   = $proc->getOutput();
        $stderr   = $proc->getErrorOutput();
        $exitCode = (int) $proc->getExitCode();

        // deploy.sh 로그에서 commit SHA 추출 (가능하면), 못 찾으면 expectedSha fallback
        $deployedCommit = $this->extractCommit($stdout) ?? $expectedSha;

        // expectedSha 와 실 commit 이 다르면 warning (배포는 됐지만 다른 commit)
        if ($expectedSha !== null && $deployedCommit !== null && $deployedCommit !== $expectedSha) {
            Log::warning("[ProcessRemoteDeployer] expectedSha={$expectedSha} but deployedCommit={$deployedCommit}");
        }

        return DeployResult::fromShell($exitCode, $stdout, $stderr, $deployedCommit);
    }

    /**
     * deploy.sh stdout 에서 NEW HEAD commit SHA 추출.
     * 패턴 예: " moved to abc1234..." / "Deploy complete: prev -> new"
     */
    private function extractCommit(string $stdout): ?string
    {
        if (preg_match('/Deploy complete:\s*\S+\s*->\s*([a-f0-9]{7,40})/i', $stdout, $m)) {
            return $m[1];
        }
        if (preg_match('/moved to ([a-f0-9]{7,40})/i', $stdout, $m)) {
            return $m[1];
        }
        if (preg_match('/already at ([a-f0-9]{7,40})/i', $stdout, $m)) {
            return $m[1];
        }
        return null;
    }
}
