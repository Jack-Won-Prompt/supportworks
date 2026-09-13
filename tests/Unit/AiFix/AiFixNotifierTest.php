<?php

namespace Tests\Unit\AiFix;

use App\Models\AiFixJob;
use App\Models\SystemErrorLog;
use App\Models\User;
use App\Services\AiFix\AiFixNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiFixNotifierTest extends TestCase
{
    // 손으로 만든 테이블 대신 실제 스키마 위에서 돈다.
    //
    // 예전에는 setUp 에서 system_error_logs·users·ai_fix_jobs 를 직접 만들고
    // tearDown 에서 drop 했다. sqlite 로 돌던 시절의 방식인데, 지금 테스트는
    // MySQL(supportworks_test)에서 돈다. 그 결과 두 가지가 한꺼번에 깨졌다 —
    // 이미 있는 테이블을 만들려다 실패하고, tearDown 이 공용 테스트 DB 의
    // 진짜 users 테이블까지 지워 뒤따르는 다른 테스트를 무너뜨렸다.
    // 손으로 적은 컬럼이 실제 스키마와 어긋나기 시작한 것은 덤이다.
    use RefreshDatabase;

    // ── shouldNotify 정책 ─────────────────────────────────────────────────────

    public function test_should_notify_for_awaiting_approval_and_blocked(): void
    {
        $n = new AiFixNotifier();
        $this->assertTrue($n->shouldNotify(AiFixJob::STATUS_AWAITING_APPROVAL));
        $this->assertTrue($n->shouldNotify(AiFixJob::STATUS_BLOCKED));
        $this->assertTrue($n->shouldNotify(AiFixJob::STATUS_DEPLOYED));
        $this->assertTrue($n->shouldNotify(AiFixJob::STATUS_DEPLOY_FAILED));
        $this->assertTrue($n->shouldNotify(AiFixJob::STATUS_ROLLED_BACK));
        $this->assertTrue($n->shouldNotify(AiFixJob::STATUS_TESTS_FAILED));
    }

    public function test_should_not_notify_for_transient_statuses(): void
    {
        $n = new AiFixNotifier();
        $this->assertFalse($n->shouldNotify(AiFixJob::STATUS_PENDING));
        $this->assertFalse($n->shouldNotify(AiFixJob::STATUS_ANALYZING));
        $this->assertFalse($n->shouldNotify(AiFixJob::STATUS_AUTO_APPROVED));
        $this->assertFalse($n->shouldNotify(AiFixJob::STATUS_APPLYING));
        $this->assertFalse($n->shouldNotify(AiFixJob::STATUS_TESTING));
        $this->assertFalse($n->shouldNotify(AiFixJob::STATUS_DEPLOYING));
    }

    // ── buildPayload ─────────────────────────────────────────────────────────

    public function test_build_payload_for_awaiting_approval(): void
    {
        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'TypeError', 'message' => 'cannot pass null',
            'file' => 'app/Foo.php', 'line' => 12,
        ]);
        $job = AiFixJob::create([
            'system_error_log_id'  => $err->id,
            'status'               => AiFixJob::STATUS_AWAITING_APPROVAL,
            'decision'             => 'escalate',
            'proposed_fix_summary' => 'Add null guard before access',
        ]);

        $p = (new AiFixNotifier())->buildPayload($job);

        $this->assertSame('AI 수정 검토 요청', $p['title']);
        $this->assertSame('Add null guard before access', $p['body']);
        $this->assertSame('ai_fix_review',                $p['data']['type']);
        $this->assertSame((string) $job->id,              $p['data']['job_id']);
        $this->assertSame('awaiting_approval',            $p['data']['status']);
        $this->assertSame('escalate',                     $p['data']['decision']);
        $this->assertSame((string) $err->id,              $p['data']['error_id']);
    }

    public function test_build_payload_for_blocked(): void
    {
        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => 'm',
            'file' => 'f', 'line' => 1,
        ]);
        $job = AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => AiFixJob::STATUS_BLOCKED,
            'decision'            => 'block',
            'blocked_path'        => 'app/Services/Payment/Stripe.php',
        ]);

        $p = (new AiFixNotifier())->buildPayload($job);

        $this->assertSame('AI 수정 차단 — 사람 처리 필요', $p['title']);
        $this->assertStringContainsString('X', $p['body']);
        $this->assertSame('block', $p['data']['decision']);
    }

    public function test_build_payload_falls_back_to_error_message_when_no_summary(): void
    {
        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'RuntimeException',
            'message' => 'connection lost', 'file' => 'f', 'line' => 1,
        ]);
        $job = AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => AiFixJob::STATUS_AWAITING_APPROVAL,
        ]);

        $p = (new AiFixNotifier())->buildPayload($job);

        $this->assertStringContainsString('RuntimeException', $p['body']);
        $this->assertStringContainsString('connection lost', $p['body']);
    }

    public function test_build_payload_truncates_long_body(): void
    {
        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => str_repeat('A', 500),
            'file' => 'f', 'line' => 1,
        ]);
        $job = AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => AiFixJob::STATUS_AWAITING_APPROVAL,
        ]);

        $p = (new AiFixNotifier())->buildPayload($job);

        $this->assertLessThanOrEqual(160, mb_strlen($p['body']));
    }

    public function test_data_payload_all_values_are_strings(): void
    {
        // FCM data 필드는 문자열만 허용. 정수가 섞이면 발송 실패.
        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => 'm', 'file' => 'f', 'line' => 1,
        ]);
        $job = AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => AiFixJob::STATUS_AWAITING_APPROVAL,
        ]);

        $p = (new AiFixNotifier())->buildPayload($job);

        foreach ($p['data'] as $k => $v) {
            $this->assertIsString($v, "data.$k must be string");
        }
    }

    // ── adminUserIds ─────────────────────────────────────────────────────────

    public function test_admin_user_ids_returns_only_role_admin(): void
    {
        User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin']);
        User::create(['name' => 'B', 'email' => 'b@x.com', 'role' => 'member']);
        $admin2 = User::create(['name' => 'C', 'email' => 'c@x.com', 'role' => 'admin']);
        // 실제 users.role 은 NOT NULL enum(admin/manager/member/client) 이다.
        // "관리자가 아닌 또 한 명" 이 이 줄의 뜻이라 client 로 적는다.
        User::create(['name' => 'D', 'email' => 'd@x.com', 'role' => 'client']);

        $ids = (new AiFixNotifier())->adminUserIds();

        $this->assertCount(2, $ids);
        $this->assertContains($admin2->id, $ids);
    }

    public function test_admin_user_ids_empty_when_no_admins(): void
    {
        User::create(['name' => 'B', 'email' => 'b@x.com', 'role' => 'member']);
        $this->assertSame([], (new AiFixNotifier())->adminUserIds());
    }

    // ── notify (FCM 직접 호출은 하지 않고 단락 분기만 검증) ───────────────────

    public function test_notify_returns_zero_when_status_not_in_policy(): void
    {
        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => 'm', 'file' => 'f', 'line' => 1,
        ]);
        $job = AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => AiFixJob::STATUS_PENDING,
        ]);

        // pending 상태이므로 shouldNotify=false → 0 반환 (FCM 호출 없음)
        $this->assertSame(0, (new AiFixNotifier())->notify($job));
    }

    public function test_notify_returns_zero_when_no_admin_users(): void
    {
        // 관리자 사용자 0명
        User::create(['name' => 'B', 'email' => 'b@x.com', 'role' => 'member']);

        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => 'm', 'file' => 'f', 'line' => 1,
        ]);
        $job = AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => AiFixJob::STATUS_AWAITING_APPROVAL,
        ]);

        // shouldNotify=true 이지만 adminUserIds()==[] → 0 반환 (FCM 호출 없음)
        $this->assertSame(0, (new AiFixNotifier())->notify($job));
    }

    // ── 3채널 분기 (스파이로 각 채널 호출 검증) ──────────────────────────────

    public function test_notify_dispatches_to_all_three_channels(): void
    {
        User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin', 'phone' => '01011112222']);
        User::create(['name' => 'B', 'email' => 'b@x.com', 'role' => 'admin', 'phone' => '01033334444']);

        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => 'm', 'file' => 'f', 'line' => 1,
        ]);
        $job = AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => AiFixJob::STATUS_AWAITING_APPROVAL,
        ]);

        $spy = new class extends AiFixNotifier {
            public array $calls = ['fcm' => 0, 'email' => 0, 'sms' => 0];
            public array $payloads = ['fcm' => null, 'email' => null, 'sms' => null];
            protected function sendFcm(\Illuminate\Support\Collection $admins, array $p): void
            { $this->calls['fcm']++;   $this->payloads['fcm']   = ['admins' => $admins->count(), 'payload' => $p]; }
            protected function sendEmail(\Illuminate\Support\Collection $admins, array $p): void
            { $this->calls['email']++; $this->payloads['email'] = ['admins' => $admins->count(), 'payload' => $p]; }
            protected function sendSms(\Illuminate\Support\Collection $admins, array $p): void
            { $this->calls['sms']++;   $this->payloads['sms']   = ['admins' => $admins->count(), 'payload' => $p]; }
        };

        $count = $spy->notify($job);

        $this->assertSame(2, $count);
        $this->assertSame(1, $spy->calls['fcm']);
        $this->assertSame(1, $spy->calls['email']);
        $this->assertSame(1, $spy->calls['sms']);
        $this->assertSame(2, $spy->payloads['fcm']['admins']);
        $this->assertSame('AI 수정 검토 요청', $spy->payloads['email']['payload']['title']);
    }

    public function test_notify_skips_all_channels_when_policy_says_no(): void
    {
        User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin']);

        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => 'm', 'file' => 'f', 'line' => 1,
        ]);
        $job = AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => AiFixJob::STATUS_ANALYZING,  // 정책상 침묵
        ]);

        $spy = new class extends AiFixNotifier {
            public int $fcm = 0; public int $email = 0; public int $sms = 0;
            protected function sendFcm(\Illuminate\Support\Collection $a, array $p): void   { $this->fcm++; }
            protected function sendEmail(\Illuminate\Support\Collection $a, array $p): void { $this->email++; }
            protected function sendSms(\Illuminate\Support\Collection $a, array $p): void   { $this->sms++; }
        };

        $this->assertSame(0, $spy->notify($job));
        $this->assertSame(0, $spy->fcm);
        $this->assertSame(0, $spy->email);
        $this->assertSame(0, $spy->sms);
    }

    public function test_build_email_body_includes_metadata(): void
    {
        $n = new class extends AiFixNotifier {
            public function exposeEmail(array $p): string { return $this->buildEmailBody($p); }
        };
        $body = $n->exposeEmail([
            'title' => 'T', 'body' => 'B',
            'data'  => ['type' => 'ai_fix_review', 'job_id' => '7',
                        'status' => 'awaiting_approval', 'decision' => 'escalate', 'error_id' => '99'],
        ]);

        $this->assertStringContainsString('B', $body);
        $this->assertStringContainsString('Job ID: 7', $body);
        $this->assertStringContainsString('escalate',  $body);
        $this->assertStringContainsString('Error ID: 99', $body);
    }
}