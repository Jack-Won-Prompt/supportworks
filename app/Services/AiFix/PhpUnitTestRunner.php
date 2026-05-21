<?php

namespace App\Services\AiFix;

use App\Models\AiFixJob;
use Symfony\Component\Process\Process;

/**
 * 실제 phpunit 을 worktree 안에서 실행하는 TestRunner.
 *
 * Symfony Process 로 worktree 의 vendor/bin/phpunit 호출. stdout 마지막 summary 줄을
 * 정규표현식으로 파싱해 testsRun/assertions/failures/errors 추출. exit code 0 → passed.
 *
 * 활성화: AI_FIX_TEST_RUNNER_DRIVER=phpunit
 */
final class PhpUnitTestRunner implements TestRunner
{
    public function __construct(
        private readonly int $timeout = 600,
    ) {}

    public function run(AiFixJob $job, string $worktreePath): TestResult
    {
        $phpunit = $worktreePath . '/vendor/bin/phpunit';
        if (!is_file($phpunit)) {
            return new TestResult(
                passed: false,
                output: "phpunit binary not found at $phpunit",
            );
        }

        $proc = new Process([$phpunit, '--no-coverage'], $worktreePath);
        $proc->setTimeout($this->timeout);
        $proc->run();

        $output = trim($proc->getOutput() . "\n" . $proc->getErrorOutput());
        $passed = $proc->getExitCode() === 0;

        [$testsRun, $assertions, $failures, $errors] = $this->parseSummary($output);

        return new TestResult(
            passed:     $passed,
            testsRun:   $testsRun,
            assertions: $assertions,
            failures:   $failures,
            errors:     $errors,
            // 출력은 끝에서 4000자만 (debugging 충분, JSON 컬럼 안 깨짐)
            output:     mb_substr($output, -4000),
        );
    }

    /**
     * phpunit summary 파싱.
     * 성공: "OK (12 tests, 34 assertions)"
     * 실패: "Tests: 12, Assertions: 34, Failures: 1, Errors: 0."
     *
     * @return array{0:int,1:int,2:int,3:int} [testsRun, assertions, failures, errors]
     */
    private function parseSummary(string $output): array
    {
        if (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/i', $output, $m)) {
            return [(int) $m[1], (int) $m[2], 0, 0];
        }
        if (preg_match('/Tests:\s*(\d+),\s*Assertions:\s*(\d+)(?:,\s*Failures:\s*(\d+))?(?:,\s*Errors:\s*(\d+))?/i', $output, $m)) {
            return [
                (int) $m[1],
                (int) $m[2],
                (int) ($m[3] ?? 0),
                (int) ($m[4] ?? 0),
            ];
        }
        return [0, 0, 0, 0];
    }
}
