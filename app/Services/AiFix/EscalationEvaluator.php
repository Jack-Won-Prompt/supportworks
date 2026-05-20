<?php

namespace App\Services\AiFix;

/**
 * AI Fix 자동화 가부를 판정하는 평가기.
 *
 * 결정 순서 (config/ai-fix.php 의 정책에 따라):
 *   1) 변경 파일 중 always_block 매칭 → BLOCK
 *   2) red 신호 누적 (configured red_max 초과) → ESCALATE
 *   3) yellow 신호 누적 (configured yellow_max 초과) → ESCALATE
 *   4) 모든 변경 파일이 auto_eligible 패턴에 매칭 → AUTO
 *   5) 그 외 → ESCALATE (안전 기본값)
 */
final class EscalationEvaluator
{
    public function __construct(
        /** config/ai-fix.php 의 배열 (DI 주입을 단순화하려고 주입 가능하게 둠) */
        private readonly array $policy
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('ai-fix', []));
    }

    public function evaluate(FixContext $ctx): EscalationDecision
    {
        // ── 1) BLOCK 판정 (always_block 또는 schema 변경) ──────────────────
        $blocked = $this->findBlockedPath($ctx->changedFiles);
        if ($blocked !== null) {
            return new EscalationDecision(
                verdict:     EscalationDecision::BLOCK,
                reason:      "변경 파일이 always_block 패턴에 매칭: {$blocked}",
                blockedPath: $blocked,
            );
        }
        if ($ctx->touchesSchema) {
            return new EscalationDecision(
                verdict: EscalationDecision::BLOCK,
                reason:  "schema/migration 변경은 자동 수정 불가",
            );
        }

        // ── 2) 신호 수집 ───────────────────────────────────────────────────
        $red    = $this->collectRedSignals($ctx);
        $yellow = $this->collectYellowSignals($ctx);

        $redMax    = $this->policy['decision']['red_max']    ?? 0;
        $yellowMax = $this->policy['decision']['yellow_max'] ?? 1;

        // ── 3) red 임계 초과 → escalate ────────────────────────────────────
        if (count($red) > $redMax) {
            return new EscalationDecision(
                verdict:       EscalationDecision::ESCALATE,
                redSignals:    $red,
                yellowSignals: $yellow,
                reason:        'red 신호 발동: ' . implode(', ', $red),
            );
        }

        // ── 4) yellow 임계 초과 → escalate ─────────────────────────────────
        if (count($yellow) > $yellowMax) {
            return new EscalationDecision(
                verdict:       EscalationDecision::ESCALATE,
                redSignals:    $red,
                yellowSignals: $yellow,
                reason:        'yellow 신호 누적: ' . implode(', ', $yellow),
            );
        }

        // ── 5) 변경 파일 전체가 auto_eligible 매칭 → AUTO ──────────────────
        if ($ctx->changedFiles !== [] && $this->allMatch($ctx->changedFiles, $this->policy['auto_eligible'] ?? [])) {
            return new EscalationDecision(
                verdict:       EscalationDecision::AUTO,
                yellowSignals: $yellow,
                reason:        '변경 파일 전체가 auto_eligible 매칭, 신호 임계 미만',
            );
        }

        // ── 6) 기본값: 안전을 위해 escalate ────────────────────────────────
        return new EscalationDecision(
            verdict:       EscalationDecision::ESCALATE,
            redSignals:    $red,
            yellowSignals: $yellow,
            reason:        '기본 안전 정책: auto_eligible 미매칭 → 사람 검토',
        );
    }

    // ── 내부 헬퍼 ────────────────────────────────────────────────────────────

    /** 변경 파일 중 always_block 패턴에 처음 매칭되는 경로 반환 */
    private function findBlockedPath(array $changedFiles): ?string
    {
        $patterns = $this->policy['always_block'] ?? [];
        foreach ($changedFiles as $file) {
            foreach ($patterns as $pattern) {
                if ($this->matches($file, $pattern)) return $file;
            }
        }
        return null;
    }

    /** 모든 경로가 적어도 하나의 패턴에 매칭되는지 */
    private function allMatch(array $files, array $patterns): bool
    {
        if ($patterns === []) return false;
        foreach ($files as $file) {
            $hit = false;
            foreach ($patterns as $pattern) {
                if ($this->matches($file, $pattern)) { $hit = true; break; }
            }
            if (!$hit) return false;
        }
        return true;
    }

    // glob -> regex 변환 후 매칭. 지원하는 와일드카드:
    //   *   -> [^/]* (디렉터리 경계 안에서만)
    //   ?   -> [^/]
    //   ** + slash -> (?:.*/)? (0개 이상의 디렉터리 prefix)
    //   **  -> .* (남은 모든 경로)
    // 도큐멘트 주석 안에 '*' + '/' 조합을 직접 쓰면 PHP가 주석 종료로 해석하므로
    // 일반 한 줄 주석으로 작성한다.
    private function matches(string $path, string $pattern): bool
    {
        $target = str_replace('\\', '/', $path);
        $regex  = $this->globToRegex(str_replace('\\', '/', $pattern));
        return (bool) preg_match($regex, $target);
    }

    private function globToRegex(string $pattern): string
    {
        $regex = '';
        $len   = strlen($pattern);
        for ($i = 0; $i < $len; $i++) {
            $ch   = $pattern[$i];
            $next = $i + 1 < $len ? $pattern[$i + 1] : '';

            if ($ch === '*' && $next === '*') {
                // ** 소비
                $i++;
                // 뒤에 / 가 붙어있으면 같이 소비 → '(?:.*/)?' (0개 이상의 dir prefix)
                if ($i + 1 < $len && $pattern[$i + 1] === '/') {
                    $i++;
                    $regex .= '(?:.*/)?';
                } else {
                    $regex .= '.*';
                }
            } elseif ($ch === '*') {
                $regex .= '[^/]*';
            } elseif ($ch === '?') {
                $regex .= '[^/]';
            } elseif (in_array($ch, ['.', '+', '(', ')', '[', ']', '^', '$', '|', '\\'], true)) {
                $regex .= '\\' . $ch;
            } else {
                $regex .= $ch;
            }
        }
        return '#^' . $regex . '$#';
    }

    private function collectRedSignals(FixContext $ctx): array
    {
        $signals = [];

        $maxFiles = $this->policy['signals']['many_files_changed_threshold'] ?? 5;
        if (count($ctx->changedFiles) > $maxFiles) {
            $signals[] = 'many_files_changed';
        }

        if ($this->hasSecurityKeyword($ctx)) {
            $signals[] = 'security_keyword_match';
        }

        return $signals;
    }

    private function collectYellowSignals(FixContext $ctx): array
    {
        $signals = [];

        $minConfidence = $this->policy['signals']['classification_confidence_min'] ?? 0.5;
        if ($ctx->classificationConfidence < $minConfidence) {
            $signals[] = 'classification_confidence_low';
        }

        $repeatThreshold = $this->policy['signals']['same_error_repeat_threshold'] ?? 3;
        if ($ctx->sameErrorOccurrenceCount >= $repeatThreshold) {
            $signals[] = 'same_error_repeated';
        }

        if ($ctx->testsPassed && $ctx->coverageDeltaLines === 0) {
            // 테스트는 통과했는데 새로 커버된 라인이 0 → 그 경로를 잡지 못한 것
            $signals[] = 'tests_pass_but_no_coverage_delta';
        }

        if (!$ctx->testsPassed) {
            $signals[] = 'tests_failed';
        }

        if ($ctx->aiSelfUnsure) {
            $signals[] = 'ai_self_unsure';
        }

        if ($this->matchesAny($ctx->errorBlob, $this->policy['signals']['external_api_keywords'] ?? [])) {
            $signals[] = 'external_api_dependency';
        }

        if ($this->matchesAny($ctx->errorBlob, $this->policy['signals']['env_specific_keywords'] ?? [])) {
            $signals[] = 'env_specific_error';
        }

        return $signals;
    }

    /** security_keywords 가 변경 파일 경로 또는 errorCategory 에 매칭되는지 */
    private function hasSecurityKeyword(FixContext $ctx): bool
    {
        $keywords = $this->policy['security_keywords'] ?? [];
        if ($keywords === []) return false;

        $haystack = strtolower(implode("\n", $ctx->changedFiles) . "\n" . $ctx->errorCategory);
        foreach ($keywords as $kw) {
            if (str_contains($haystack, strtolower($kw))) return true;
        }
        return false;
    }

    private function matchesAny(string $haystack, array $keywords): bool
    {
        if ($haystack === '' || $keywords === []) return false;
        $h = strtolower($haystack);
        foreach ($keywords as $kw) {
            if (str_contains($h, strtolower($kw))) return true;
        }
        return false;
    }
}