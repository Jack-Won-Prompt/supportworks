<?php

namespace App\Services\AiWork;

use App\Models\AiWork\AiwErrorReport;

/**
 * 올라온 오류를 자동으로 고쳐도 되는지 가른다.
 *
 * 세 갈래뿐이다.
 *
 * | 판정 | 뜻 | 다음에 벌어질 일 |
 * |---|---|---|
 * | `ignore` | 고칠 대상이 아니다 | 조용히 덮는다. 알림도 보내지 않는다 |
 * | `human`  | 사람이 봐야 한다   | 알림은 가되 지시는 만들지 않는다 |
 * | `auto`   | 자동 수정 대상     | (다음 단계에서) 작업 지시를 만든다 |
 *
 * 판단 근거는 **오류가 난 자리** 다. 무엇을 고치게 될지는 아직 모르지만, 결제나
 * 인증 코드에서 난 오류를 자동으로 건드리게 두지는 않는다. 이 기준은
 * config/ai-fix.php 의 always_block 에서 왔다 — 그쪽에서 이미 한 번 고민한 목록이다.
 *
 * 순서가 중요하다. 무시가 먼저다. 404 가 결제 경로에서 났다고 사람을 부르면,
 * 봇이 훑고 갈 때마다 휴대폰이 울린다.
 */
class ErrorTriage
{
    public const AUTO   = 'auto';
    public const HUMAN  = 'human';
    public const IGNORE = 'ignore';

    /** @return array{0: string, 1: string}  [판정, 이유] */
    public function decide(AiwErrorReport $report): array
    {
        $exception = (string) $report->exception;

        if ($match = $this->matchesException($exception, (array) config('aiw.error_triage.ignore_exceptions', []))) {
            return [self::IGNORE, "고칠 대상이 아닙니다 — {$match}"];
        }

        $path = str_replace('\\', '/', (string) $report->file);

        if ($pattern = $this->matchesPath($path, (array) config('aiw.error_triage.block_paths', []))) {
            return [self::HUMAN, "사람이 봐야 하는 자리입니다 — {$pattern}"];
        }

        if ($this->matchesException($exception, (array) config('aiw.error_triage.human_exceptions', []))) {
            return [self::HUMAN, '원인이 코드 밖일 수 있어 사람이 봐야 합니다.'];
        }

        if ($path === '') {
            // 어느 파일인지 모르면 고칠 자리도 모른다.
            return [self::HUMAN, '오류가 난 파일을 알 수 없습니다.'];
        }

        return [self::AUTO, '자동 수정 대상입니다.'];
    }

    /**
     * 자동으로 지시를 만들어도 되는 상태인가.
     *
     * 판정이 auto 라는 것만으로는 부족하다. 한 번 나고 마는 오류는 대개
     * 일시적인 것이라(네트워크 끊김 등) 고칠 것이 없고, 그때마다 지시를 만들면
     * 밤새 헛일을 한다. 그리고 이미 손대 본 횟수가 상한이면 더 하지 않는다 —
     * 고친 코드가 또 에러를 내는 고리를 끊는 자리다.
     */
    public function shouldQueue(AiwErrorReport $report): bool
    {
        if ($report->verdict !== self::AUTO) {
            return false;
        }

        if (! $report->canAttemptPatch()) {
            return false;
        }

        return $report->count >= (int) config('aiw.error_triage.min_count_for_auto', 2);
    }

    /** @param array<int, string> $names */
    private function matchesException(string $exception, array $names): ?string
    {
        foreach ($names as $name) {
            // 네임스페이스를 적든 클래스 이름만 적든 걸리게 한다.
            if ($exception !== '' && str_contains($exception, $name)) {
                return $name;
            }
        }

        return null;
    }

    /** @param array<int, string> $patterns */
    private function matchesPath(string $path, array $patterns): ?string
    {
        if ($path === '') {
            return null;
        }

        foreach ($patterns as $pattern) {
            // fnmatch 는 ai-fix 와 같은 방식이다. `**` 도 `*` 로 동작한다.
            if (fnmatch($pattern, $path) || fnmatch('*/'.ltrim($pattern, '/'), $path)) {
                return $pattern;
            }
        }

        return null;
    }
}
