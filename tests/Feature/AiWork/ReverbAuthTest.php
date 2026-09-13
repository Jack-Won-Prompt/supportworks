<?php

namespace Tests\Feature\AiWork;

use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * AI Works 브라우저 브로드캐스트 인가 회귀 테스트.
 *
 * 고정하는 사실:
 *  1. AI Works 채널은 reverb 커넥션에 별도 등록해야 한다. 기본 Broadcast::channel() 로만
 *     등록하면 기본 커넥션(pusher) 인스턴스에만 들어가 /aiw/broadcasting/auth 가 항상 403 이 된다.
 *  2. 서명은 pusher secret 이 아니라 reverb secret 으로 계산돼야 한다. 기본 /broadcasting/auth 를
 *     공유하면 서명이 어긋나 Reverb 가 연결을 거부한다.
 *  3. aiw.job.{jobId} 는 해당 프로젝트 멤버만 구독할 수 있다.
 */
class ReverbAuthTest extends TestCase
{
    use RefreshDatabase;

    private const SOCKET_ID = '123.456';

    private int $jobId;

    private User $member;

    private User $outsider;

    protected function setUp(): void
    {
        parent::setUp();

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

        // 채널 인가는 AiwJobPolicy::view 를 그대로 탄다 = 시스템 관리자만.
        $this->member = User::factory()->create(['role' => 'admin']);
        $this->outsider = User::factory()->create();

        $projectId = DB::table('projects')->insertGetId([
            'name' => 'AI Works 채널 테스트',
            'created_by' => $this->member->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 관리자는 멤버가 아니어도 되지만, 실제 화면과 같은 상태로 둔다.
        DB::table('project_members')->insert([
            'project_id' => $projectId,
            'user_id' => $this->member->id,
            'role' => 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $agent = AiwAgent::create([
            'name' => 'PC',
            'token_hash' => AiwAgent::hashToken(AiwAgent::generateToken()),
            'user_id' => $this->member->id,
        ]);

        $this->jobId = AiwJob::create([
            'project_id' => $projectId,
            'agent_id' => $agent->id,
            'title' => '지시',
            'instruction' => '내용',
            'context_limit_tokens' => 200000,
            'allowed_tools' => ['Read'],
            'cost_limit_usd' => 2.0,
            'created_by' => $this->member->id,
        ])->id;
    }

    private function reverbConfigured(): bool
    {
        $c = config('broadcasting.connections.reverb');

        return filled($c['key'] ?? null) && filled($c['secret'] ?? null) && filled($c['app_id'] ?? null);
    }

    private function channel(): string
    {
        return 'private-aiw.job.'.$this->jobId;
    }

    private function authRequest(?User $user = null, ?string $channel = null)
    {
        $test = $user ? $this->actingAs($user) : $this;

        return $test->postJson('/aiw/broadcasting/auth', [
            'channel_name' => $channel ?? $this->channel(),
            'socket_id' => self::SOCKET_ID,
        ]);
    }

    public function test_관리자는_job_채널_인가를_받는다(): void
    {
        $response = $this->authRequest($this->member);

        // 403 이면 채널이 reverb 커넥션에 등록되지 않은 것이다(routes/channels.php 확인).
        $response->assertOk();
        $this->assertArrayHasKey('auth', $response->json());
    }

    public function test_프로젝트_비멤버는_거부된다(): void
    {
        $response = $this->authRequest($this->outsider);

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_프로젝트_멤버라도_작업_지시_가능이_아니면_거부된다(): void
    {
        // 화면은 403 인데 실시간 로그만 흘러가는 구멍을 막는다.
        $member = User::factory()->create(['role' => 'member']);
        DB::table('project_members')->insert([
            'project_id' => AiwJob::findOrFail($this->jobId)->project_id,
            'user_id' => $member->id,
            'role' => 'manager',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->authRequest($member);

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_서명은_reverb_secret으로_계산된다(): void
    {
        $auth = $this->authRequest($this->member)->json('auth');

        $reverb = config('broadcasting.connections.reverb');
        $expected = $reverb['key'].':'.hash_hmac(
            'sha256',
            self::SOCKET_ID.':'.$this->channel(),
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

        $auth = $this->authRequest($this->member)->json('auth');

        // 기본 /broadcasting/auth 를 공유했다면 이 값이 나왔을 것이고, Reverb 가 거부한다.
        $pusherSigned = $pusher['key'].':'.hash_hmac(
            'sha256',
            self::SOCKET_ID.':'.$this->channel(),
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
        $response = $this->authRequest($this->member, 'private-aiw.never-registered');

        $this->assertContains($response->status(), [401, 403]);
    }
}
