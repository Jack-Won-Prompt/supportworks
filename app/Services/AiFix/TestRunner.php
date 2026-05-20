<?php

namespace App\Services\AiFix;

use App\Models\AiFixJob;

/**
 * 워크트리에서 테스트를 실행하고 결과를 반환하는 어댑터.
 *
 * 구현체:
 *   - StubTestRunner — 정해진 결과를 반환 (테스트용)
 *   - (추후) PhpUnitTestRunner — Symfony Process 로 vendor/bin/phpunit 호출
 */
interface TestRunner
{
    public function run(AiFixJob $job, string $worktreePath): TestResult;
}