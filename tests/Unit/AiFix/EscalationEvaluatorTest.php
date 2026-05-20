<?php

namespace Tests\Unit\AiFix;

use App\Services\AiFix\EscalationDecision;
use App\Services\AiFix\EscalationEvaluator;
use App\Services\AiFix\FixContext;
use PHPUnit\Framework\TestCase;

class EscalationEvaluatorTest extends TestCase
{
    /** 모든 테스트가 공유할 표준 정책 */
    private function defaultPolicy(): array
    {
        return [
            'auto_eligible' => [
                'app/Http/Requests/**/*Request.php',
                'resources/lang/**',
            ],
            'always_block' => [
                'app/Services/Payment/**',
                'app/Http/Middleware/Auth*.php',
                'database/migrations/**',
                'config/database.php',
                '.env*',
            ],
            'security_keywords' => ['password', 'token', 'secret'],
            'signals' => [
                'many_files_changed_threshold'  => 5,
                'classification_confidence_min' => 0.5,
                'same_error_repeat_threshold'   => 3,
                'same_error_window_minutes'     => 60,
                'external_api_keywords' => ['GuzzleHttp', 'cURL error', 'timed out'],
                'env_specific_keywords' => ['browser', 'locale'],
                'business_logic_paths' => [
                    'app/Services/**',
                    'app/Domain/**',
                ],
                'prod_data_keywords' => [
                    'row not found',
                    'duplicate entry',
                    'data inconsistency',
                ],
                'system_domains' => [
                    'auth'    => ['login', 'auth guard'],
                    'payment' => ['payment', 'stripe'],
                    'queue'   => ['queue', 'failed_jobs'],
                ],
            ],
            'decision' => [
                'red_max'    => 0,
                'yellow_max' => 1,
            ],
        ];
    }

    private function evaluator(?array $policyOverride = null): EscalationEvaluator
    {
        return new EscalationEvaluator($policyOverride ?? $this->defaultPolicy());
    }

    // ── BLOCK 분기 ───────────────────────────────────────────────────────────

    public function test_blocks_when_payment_file_touched(): void
    {
        $ctx = new FixContext(changedFiles: ['app/Services/Payment/StripeGateway.php']);
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::BLOCK, $d->verdict);
        $this->assertSame('app/Services/Payment/StripeGateway.php', $d->blockedPath);
    }

    public function test_blocks_when_auth_middleware_touched(): void
    {
        $ctx = new FixContext(changedFiles: ['app/Http/Middleware/AuthAdmin.php']);
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::BLOCK, $d->verdict);
    }

    public function test_blocks_when_migration_touched(): void
    {
        $ctx = new FixContext(changedFiles: ['database/migrations/2026_05_20_000000_add_field.php']);
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::BLOCK, $d->verdict);
    }

    public function test_blocks_when_env_file_touched(): void
    {
        $ctx = new FixContext(changedFiles: ['.env.production']);
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::BLOCK, $d->verdict);
    }

    public function test_blocks_when_touches_schema_flag_set(): void
    {
        $ctx = new FixContext(
            changedFiles:  ['app/Models/User.php'],
            testsPassed:   true,
            coverageDeltaLines: 10,
            touchesSchema: true,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::BLOCK, $d->verdict);
    }

    // ── ESCALATE 분기: red 신호 ─────────────────────────────────────────────

    public function test_escalates_when_too_many_files_changed(): void
    {
        $files = array_map(fn($i) => "app/Http/Requests/F{$i}Request.php", range(1, 6));
        $ctx = new FixContext(
            changedFiles:             $files,
            classificationConfidence: 0.9,
            testsPassed:              true,
            coverageDeltaLines:       5,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::ESCALATE, $d->verdict);
        $this->assertContains('many_files_changed', $d->redSignals);
    }

    public function test_escalates_when_security_keyword_in_path(): void
    {
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/PasswordResetRequest.php'],
            classificationConfidence: 0.9,
            testsPassed:              true,
            coverageDeltaLines:       5,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::ESCALATE, $d->verdict);
        $this->assertContains('security_keyword_match', $d->redSignals);
    }

    public function test_escalates_when_security_keyword_in_error_category(): void
    {
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            errorCategory:            'token_expiration_unhandled',
            classificationConfidence: 0.9,
            testsPassed:              true,
            coverageDeltaLines:       5,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::ESCALATE, $d->verdict);
        $this->assertContains('security_keyword_match', $d->redSignals);
    }

    // ── ESCALATE 분기: yellow 신호 누적 ─────────────────────────────────────

    public function test_escalates_on_multiple_yellow_signals(): void
    {
        // 2개의 yellow 신호: 낮은 신뢰도 + 반복 에러
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            classificationConfidence: 0.3,    // < 0.5 → yellow
            sameErrorOccurrenceCount: 5,      // >= 3 → yellow
            testsPassed:              true,
            coverageDeltaLines:       5,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::ESCALATE, $d->verdict);
        $this->assertContains('classification_confidence_low', $d->yellowSignals);
        $this->assertContains('same_error_repeated', $d->yellowSignals);
    }

    public function test_single_yellow_alone_does_not_escalate_for_auto_eligible(): void
    {
        // 1개 yellow (yellow_max=1, 즉 >1 일 때만 escalate) → auto 가능
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            classificationConfidence: 0.3,
            sameErrorOccurrenceCount: 1,
            testsPassed:              true,
            coverageDeltaLines:       5,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::AUTO, $d->verdict);
        $this->assertContains('classification_confidence_low', $d->yellowSignals);
    }

    public function test_external_api_keyword_in_error_blob_emits_yellow(): void
    {
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            classificationConfidence: 0.9,
            errorBlob:                "GuzzleHttp\\Exception\\ConnectException: cURL error 28: connection timed out",
            testsPassed:              true,
            coverageDeltaLines:       5,
        );
        $d = $this->evaluator()->evaluate($ctx);

        // 외부API 1개만 발동, yellow_max=1 이므로 auto
        $this->assertSame(EscalationDecision::AUTO, $d->verdict);
        $this->assertContains('external_api_dependency', $d->yellowSignals);
    }

    public function test_tests_pass_but_no_coverage_delta_emits_yellow(): void
    {
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            classificationConfidence: 0.9,
            testsPassed:              true,
            coverageDeltaLines:       0,
            testsRan:                 true,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertContains('tests_pass_but_no_coverage_delta', $d->yellowSignals);
    }

    public function test_tests_failed_emits_yellow(): void
    {
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            classificationConfidence: 0.9,
            testsPassed:              false,
            testsRan:                 true,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertContains('tests_failed', $d->yellowSignals);
    }

    public function test_tests_not_run_does_not_emit_test_signals(): void
    {
        // testsRan=false 인 분석 단계 시뮬레이션: 테스트 신호 발동 X
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            classificationConfidence: 0.9,
            testsPassed:              false,
            coverageDeltaLines:       0,
            testsRan:                 false,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertNotContains('tests_failed', $d->yellowSignals);
        $this->assertNotContains('tests_pass_but_no_coverage_delta', $d->yellowSignals);
    }

    public function test_ai_self_unsure_emits_yellow(): void
    {
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            classificationConfidence: 0.9,
            testsPassed:              true,
            coverageDeltaLines:       5,
            aiSelfUnsure:             true,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertContains('ai_self_unsure', $d->yellowSignals);
    }

    public function test_untested_code_path_emits_yellow(): void
    {
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            classificationConfidence: 0.9,
            testsPassed:              true,
            coverageDeltaLines:       5,
            hasExistingTests:         false,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertContains('untested_code_path', $d->yellowSignals);
    }

    public function test_business_logic_modified_emits_yellow(): void
    {
        $ctx = new FixContext(
            changedFiles:             ['app/Services/UserService.php'],
            classificationConfidence: 0.9,
            testsPassed:              true,
            coverageDeltaLines:       5,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertContains('business_logic_modified', $d->yellowSignals);
    }

    public function test_business_logic_not_emitted_for_non_business_paths(): void
    {
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            classificationConfidence: 0.9,
            testsPassed:              true,
            coverageDeltaLines:       5,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertNotContains('business_logic_modified', $d->yellowSignals);
    }

    public function test_requires_prod_data_check_emits_yellow(): void
    {
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            classificationConfidence: 0.9,
            errorBlob:                "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'foo' for key",
            testsPassed:              true,
            coverageDeltaLines:       5,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertContains('requires_prod_data_check', $d->yellowSignals);
    }

    public function test_cross_system_concern_emits_yellow_when_two_domains_match(): void
    {
        // auth(login) + payment(stripe) 둘 다 등장
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            classificationConfidence: 0.9,
            errorBlob:                "Failed during login while finalizing stripe charge",
            testsPassed:              true,
            coverageDeltaLines:       5,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertContains('cross_system_concern', $d->yellowSignals);
    }

    public function test_cross_system_concern_not_emitted_for_single_domain(): void
    {
        // login 만 등장 — 단일 도메인
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/LoginRequest.php'],
            classificationConfidence: 0.9,
            errorBlob:                "Authentication failed during login",
            testsPassed:              true,
            coverageDeltaLines:       5,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertNotContains('cross_system_concern', $d->yellowSignals);
    }

    // ── AUTO 분기 ───────────────────────────────────────────────────────────

    public function test_auto_when_all_files_auto_eligible_and_no_signals(): void
    {
        $ctx = new FixContext(
            changedFiles:             ['app/Http/Requests/CreatePostRequest.php', 'resources/lang/ko/post.php'],
            classificationConfidence: 0.95,
            sameErrorOccurrenceCount: 1,
            testsPassed:              true,
            coverageDeltaLines:       8,
            aiSelfUnsure:             false,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::AUTO, $d->verdict);
        $this->assertSame([], $d->redSignals);
    }

    // ── 기본 안전 분기 (auto_eligible 미매칭) ───────────────────────────────

    public function test_escalates_when_files_not_in_auto_eligible_even_with_no_signals(): void
    {
        // 비즈니스 로직 파일 (Service) — auto_eligible 아님
        $ctx = new FixContext(
            changedFiles:             ['app/Services/UserService.php'],
            classificationConfidence: 0.95,
            testsPassed:              true,
            coverageDeltaLines:       10,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::ESCALATE, $d->verdict);
        $this->assertStringContainsString('기본 안전 정책', $d->reason);
    }

    public function test_partial_auto_eligible_match_escalates(): void
    {
        // 한 파일은 auto_eligible, 다른 파일은 아님
        $ctx = new FixContext(
            changedFiles:             [
                'app/Http/Requests/LoginRequest.php',
                'app/Services/UserService.php',
            ],
            classificationConfidence: 0.95,
            testsPassed:              true,
            coverageDeltaLines:       10,
        );
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::ESCALATE, $d->verdict);
    }

    // ── 경로 매칭 모서리 ─────────────────────────────────────────────────────

    public function test_double_star_pattern_matches_nested_paths(): void
    {
        // database/migrations/**가 중첩 디렉터리도 잡아야
        $ctx = new FixContext(changedFiles: ['database/migrations/2026/05/add_field.php']);
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::BLOCK, $d->verdict);
    }

    public function test_windows_backslash_paths_are_normalized(): void
    {
        // 윈도우 backslash 가 와도 매칭
        $ctx = new FixContext(changedFiles: ['database\\migrations\\2026_05_20_000000_add.php']);
        $d = $this->evaluator()->evaluate($ctx);

        $this->assertSame(EscalationDecision::BLOCK, $d->verdict);
    }

    // ── toArray 직렬화 ──────────────────────────────────────────────────────

    public function test_decision_to_array(): void
    {
        $ctx = new FixContext(changedFiles: ['app/Services/Payment/Stripe.php']);
        $arr = $this->evaluator()->evaluate($ctx)->toArray();

        $this->assertSame('block', $arr['verdict']);
        $this->assertNotNull($arr['blocked_path']);
        $this->assertArrayHasKey('reason', $arr);
    }
}