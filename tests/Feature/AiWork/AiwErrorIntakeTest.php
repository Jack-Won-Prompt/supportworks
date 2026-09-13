<?php

namespace Tests\Feature\AiWork;

use App\Models\AiWork\AiwErrorReport;
use App\Models\AiWork\AiwErrorSource;
use App\Models\Project;
use App\Models\User;
use App\Services\AiWork\AiwNotifier;
use App\Services\AiWork\ErrorFingerprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * 운영 사이트가 보내오는 오류를 받아 묶는 부분.
 *
 * 여기서 지키려는 것은 둘이다 — 남의 프로젝트에 밀어 넣을 수 없을 것,
 * 그리고 같은 고장이 수백 건으로 흩어지지 않을 것.
 */
class AiwErrorIntakeTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private string $token;
    private AiwErrorSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->project = Project::create([
            'name' => 'korsafety', 'status' => 'active', 'created_by' => $admin->id,
        ]);

        $this->token  = AiwErrorSource::generateToken();
        $this->source = AiwErrorSource::create([
            'project_id' => $this->project->id,
            'name'       => 'korsafety.co.kr',
            'token_hash' => AiwErrorSource::hashToken($this->token),
            'created_by' => $admin->id,
        ]);

        // 알림은 따로 검증한다. 여기서는 FCM 을 타지 않게 막아 둔다.
        $this->instance(AiwNotifier::class, new class extends AiwNotifier {
            public array $reported = [];

            public function errorReported(AiwErrorReport $report): void
            {
                $this->reported[] = $report->id;
            }
        });
    }

    private function send(array $payload = [], ?string $token = null)
    {
        return $this->withHeader('Authorization', 'Bearer '.($token ?? $this->token))
            ->postJson('/api/aiw/errors', $payload + [
                'exception' => 'RuntimeException',
                'message'   => '주문 저장 실패',
                'file'      => '/home/ubuntu/www/korsafety/app/Services/OrderService.php',
                'line'      => 42,
                'url'       => 'https://korsafety.co.kr/orders/1',
            ]);
    }

    // ── 인증 ────────────────────────────────────────────────────────────────

    public function test_토큰이_없으면_받지_않는다(): void
    {
        $this->postJson('/api/aiw/errors', ['exception' => 'X'])->assertStatus(401);

        $this->assertSame(0, AiwErrorReport::count());
    }

    public function test_틀린_토큰은_받지_않는다(): void
    {
        $this->send([], 'aiwerr_틀린토큰')->assertStatus(401);
    }

    public function test_꺼진_출처는_받지_않는다(): void
    {
        // 사고가 났을 때 토큰을 지우지 않고 끌 수 있어야 한다.
        $this->source->update(['enabled' => false]);

        $this->send()->assertStatus(401);
    }

    public function test_프로젝트는_토큰으로_정한다(): void
    {
        // 본문에 남의 프로젝트를 적어도 무시된다. 믿으면 아무나 남의 프로젝트에
        // 오류를, 나아가 작업 지시를 밀어 넣을 수 있다.
        $admin = User::factory()->create(['role' => 'admin']);
        $other = Project::create(['name' => '남의 것', 'status' => 'active', 'created_by' => $admin->id]);

        $this->send(['project_id' => $other->id])->assertStatus(201);

        $this->assertSame($this->project->id, AiwErrorReport::first()->project_id);
    }

    // ── 묶기 ────────────────────────────────────────────────────────────────

    public function test_처음_보는_오류는_기록으로_남는다(): void
    {
        $this->send()
            ->assertStatus(201)
            ->assertJsonPath('is_new', true)
            ->assertJsonPath('count', 1);

        $report = AiwErrorReport::first();

        $this->assertSame('RuntimeException', $report->exception);
        $this->assertSame(42, $report->line);
        $this->assertSame(AiwErrorReport::STATUS_NEW, $report->status);
        $this->assertNotNull($report->first_seen_at);
    }

    public function test_같은_오류는_행을_늘리지_않고_센다(): void
    {
        // 운영에서 고장 하나가 나면 5분에 수백 건이 온다. 그대로 쌓이면
        // 화면도 못 읽고, 작업 지시를 자동으로 만들면 수백 개가 생긴다.
        for ($i = 0; $i < 5; $i++) {
            $this->send();
        }

        $this->assertSame(1, AiwErrorReport::count());
        $this->assertSame(5, AiwErrorReport::first()->count);
    }

    public function test_두_번째부터는_is_new_가_거짓이다(): void
    {
        $this->send()->assertStatus(201);
        $this->send()->assertStatus(200)->assertJsonPath('is_new', false)->assertJsonPath('count', 2);
    }

    public function test_다른_줄에서_난_오류는_다른_건이다(): void
    {
        $this->send();
        $this->send(['line' => 77]);

        $this->assertSame(2, AiwErrorReport::count());
    }

    public function test_메시지가_달라도_같은_자리면_한_건이다(): void
    {
        // 같은 코드가 내는 메시지에 주문번호나 이름이 섞여 매번 달라진다.
        // 메시지로 가르면 같은 고장이 수백 건으로 흩어진다.
        $this->send(['message' => '주문 1001 저장 실패']);
        $this->send(['message' => '주문 1002 저장 실패']);

        $this->assertSame(1, AiwErrorReport::count());
        $this->assertSame('주문 1002 저장 실패', AiwErrorReport::first()->message, '마지막 모습으로 갱신된다.');
    }

    public function test_다른_프로젝트의_같은_오류는_따로_쌓인다(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $other = Project::create(['name' => 'mangoshop', 'status' => 'active', 'created_by' => $admin->id]);
        $token = AiwErrorSource::generateToken();

        AiwErrorSource::create([
            'project_id' => $other->id,
            'name'       => 'mangoshop.co.kr',
            'token_hash' => AiwErrorSource::hashToken($token),
        ]);

        $this->send();
        $this->send([], $token);

        $this->assertSame(2, AiwErrorReport::count());
    }

    // ── 쏟아짐 ──────────────────────────────────────────────────────────────

    public function test_한_출처가_쏟아_부으면_막는다(): void
    {
        config(['aiw.error_intake_per_minute' => 3]);
        RateLimiter::clear('aiw-error-intake:'.$this->source->id);

        for ($i = 0; $i < 3; $i++) {
            $this->send()->assertSuccessful();
        }

        $this->send()->assertStatus(429)->assertJsonPath('reason', 'rate_limited');
    }

    // ── 지문 규칙 ───────────────────────────────────────────────────────────

    public function test_경로가_달라도_같은_파일이면_같은_지문이다(): void
    {
        // 운영은 /home/ubuntu/www/..., 로컬은 E:\xampp\htdocs\... 다.
        // 다르게 보면 운영 오류와 로컬 재현이 따로 놀아 고쳐도 살아 있는 것처럼 보인다.
        $prod  = ErrorFingerprint::make('RuntimeException', '/home/ubuntu/www/korsafety/app/X.php', 10);
        $local = ErrorFingerprint::make('RuntimeException', 'E:\\xampp\\htdocs\\korsafety\\app\\X.php', 10);

        $this->assertSame($prod, $local);
    }

    public function test_익명_클래스의_꼬리는_무시한다(): void
    {
        // Migration@anonymous/path/x.php:20$1b5 — 매번 달라지는 이름이다.
        $a = ErrorFingerprint::make('Migration@anonymous/a/b.php:20$1b5', 'app/X.php', 1);
        $b = ErrorFingerprint::make('Migration@anonymous/a/b.php:31$9zz', 'app/X.php', 1);

        $this->assertSame($a, $b);
    }

    public function test_다른_예외는_다른_지문이다(): void
    {
        $this->assertNotSame(
            ErrorFingerprint::make('RuntimeException', 'app/X.php', 1),
            ErrorFingerprint::make('LogicException', 'app/X.php', 1),
        );
    }
}
