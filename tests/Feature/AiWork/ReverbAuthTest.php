<?php

namespace Tests\Feature\AiWork;

use App\Models\User;
use Tests\TestCase;

/**
 * AI Works 전용 Reverb 브로드캐스트 인가 회귀 테스트.
 *
 * 고정하는 사실 두 가지:
 *  1. AI Works 채널은 reverb 커넥션에 별도 등록해야 한다. 기본 Broadcast::channel() 로만 등록하면
 *     기본 커넥션(pusher) 인스턴스에만 들어가 /aiw/broadcasting/auth 가 항상 403 이 된다.
 *  2. 인가 서명은 pusher secret 이 아니라 reverb secret 으로 계산돼야 한다. 기본 /broadcasting/auth 를
 *     공유하면 서명이 어긋나 Reverb 서버가 연결을 거부한다.
 */
class ReverbAuthTest extends TestCase
{
    private const CHANNEL = 'private-aiw.ping';

    private const SOCKET_ID = '123.456';

    protected function setUp(): void
    {
        parent::setUp();

        // 이 테스트는 브로드캐스트 인가 배관만 검증한다. web 그룹에 붙은 앱 미들웨어 중
        // DB 를 조회하는 것들(system_settings 등)은 제외한다 — 이 저장소의 마이그레이션이
        // MySQL 전용 문법(UPDATE ... JOIN)을 포함해 sqlite 로 RefreshDatabase 를 쓸 수 없다.
        $this->withoutMiddleware([
            \App\Http\Middleware\MaintenanceCheckMiddleware::class,
            \App\Http\Middleware\LogPageAccess::class,
            \App\Http\Middleware\CollabParticipantMiddleware::class,
        ]);

        if (! $this->reverbConfigured()) {
            $this->markTestSkipped('REVERB_APP_KEY/SECRET/APP_ID 미설정 — routes/channels.php 가드가 채널 등록을 건너뛴다.');
        }

        // 채널은 routes/channels.php 가 부팅 시 reverb 커넥션에 등록한다.
        // 여기서 일부러 재등록하지 않는다 — 등록이 빠지면 이 테스트가 실패해서 회귀를 잡아야 한다.
    }

    private function reverbConfigured(): bool
    {
        $c = config('broadcasting.connections.reverb');

        return filled($c['key'] ?? null) && filled($c['secret'] ?? null) && filled($c['app_id'] ?? null);
    }

    /**
     * DB 를 쓰지 않는다. 채널 콜백이 `$user instanceof User` 만 보므로 영속되지 않은 인스턴스로 충분하다.
     */
    private function user(): User
    {
        $u = new User();
        $u->id = 1;
        $u->name = 'tester';
        $u->email = 'tester@example.com';

        return $u;
    }

    private function authRequest(?User $user = null, string $channel = self::CHANNEL)
    {
        $test = $user ? $this->actingAs($user) : $this;

        return $test->postJson('/aiw/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => self::SOCKET_ID,
        ]);
    }

    public function test_인증된_사용자는_reverb_채널_인가를_받는다(): void
    {
        $response = $this->authRequest($this->user());

        // 403 이면 채널이 reverb 커넥션에 등록되지 않은 것이다(routes/channels.php 확인).
        $response->assertOk();
        $this->assertArrayHasKey('auth', $response->json());
    }

    public function test_서명은_reverb_secret으로_계산된다(): void
    {
        $auth = $this->authRequest($this->user())->json('auth');

        $reverb = config('broadcasting.connections.reverb');
        $expected = $reverb['key'].':'.hash_hmac(
            'sha256',
            self::SOCKET_ID.':'.self::CHANNEL,
            $reverb['secret']
        );

        $this->assertSame($expected, $auth);
    }

    public function test_서명에_pusher_자격증명이_쓰이지_않는다(): void
    {
        $pusher = config('broadcasting.connections.pusher');

        if (blank($pusher['key'] ?? null) || blank($pusher['secret'] ?? null)) {
            $this->markTestSkipped('pusher 커넥션 미설정 — 비교 대상이 없다.');
        }

        $auth = $this->authRequest($this->user())->json('auth');

        // 기본 /broadcasting/auth 를 공유했다면 이 값이 나왔을 것이고, Reverb 가 거부한다.
        $pusherSigned = $pusher['key'].':'.hash_hmac(
            'sha256',
            self::SOCKET_ID.':'.self::CHANNEL,
            $pusher['secret']
        );

        $this->assertNotSame($pusherSigned, $auth);
        $this->assertStringStartsNotWith($pusher['key'].':', $auth);
    }

    public function test_비로그인_사용자는_인가받지_못한다(): void
    {
        $response = $this->authRequest();

        $this->assertContains($response->status(), [401, 403, 302]);
    }

    public function test_등록되지_않은_채널은_거부된다(): void
    {
        $response = $this->authRequest($this->user(), 'private-aiw.never-registered');

        $this->assertContains($response->status(), [401, 403]);
    }
}
