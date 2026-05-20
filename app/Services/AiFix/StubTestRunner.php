<?php

namespace App\Services\AiFix;

use App\Models\AiFixJob;

/**
 * 미리 정해둔 결과를 그대로 반환하는 PoC/테스트용 TestRunner.
 */
final class StubTestRunner implements TestRunner
{
    public function __construct(public readonly TestResult $result) {}

    public function run(AiFixJob $job, string $worktreePath): TestResult
    {
        return $this->result;
    }
}