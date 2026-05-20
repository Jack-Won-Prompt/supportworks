<?php

namespace App\Services\AiFix;

/**
 * 파일시스템·git 을 건드리지 않는 PoC/테스트용 WorktreeManager.
 * create() 는 가짜 경로 문자열만 반환하고, remove() 는 no-op.
 */
final class StubWorktreeManager implements WorktreeManager
{
    public function __construct(private readonly string $basePath = '/tmp/ai-maintenance') {}

    public function create(int $jobId, string $branch): string
    {
        // 실제 mkdir 하지 않고 약속된 경로 문자열만 돌려준다.
        return rtrim($this->basePath, '/') . "/fix-{$jobId}";
    }

    public function remove(int $jobId): void
    {
        // no-op
    }
}