<?php

namespace App\Services\AiFix;

use App\Models\AiFixJob;

/**
 * 실제 코드 수정 없이 항상 성공을 반환하는 PoC 적용기.
 *
 * 파이프라인 통합 테스트에서 worktree·tests·notifier 흐름을 검증할 때 사용.
 * 실제 운영에선 ClaudeAiCodeApplier 로 교체.
 */
final class StubCodeApplier implements AiCodeApplier
{
    public function __construct(public readonly bool $shouldSucceed = true) {}

    public function apply(AiFixJob $job, string $worktreePath): bool
    {
        return $this->shouldSucceed;
    }
}