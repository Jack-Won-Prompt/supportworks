<?php

namespace App\Services\AiFix;

/**
 * 운영 서버에 SSH 로 접속해 deploy.sh 를 실행.
 *
 * 구현체:
 *   - StubRemoteDeployer (테스트/PoC) — 실제 SSH 호출 없음
 *   - (추후) SshRemoteDeployer — phpseclib/phpseclib 로 ED25519 키 인증.
 *     키 경로: storage/app/deploy/id_ed25519 (chmod 600)
 *     운영 사용자: aifix-deployer (sudoers 좁힘)
 *
 * 실행 명령 예:
 *   bash /var/www/supportworks/scripts/deploy.sh
 *
 * 멱등성: deploy.sh 자체가 git 청결 검사 + ff-only pull + healthz 자동 롤백을 포함하므로
 * 동일 commit 으로 두 번 호출돼도 두 번째는 "already at NEW_HEAD — nothing to deploy" (exit 0).
 *
 * 예외는 SSH 연결 자체가 실패한 경우만. 원격 명령이 non-zero 로 끝나도 DeployResult 로 반환.
 */
interface RemoteDeployer
{
    /**
     * @param string|null $expectedSha 머지된 commit SHA. 운영 서버 pull 결과가 이와 다르면 경고/실패 처리.
     *                                  null 이면 검증 생략 (긴급 디플로이 등).
     */
    public function deploy(?string $expectedSha = null): DeployResult;
}
