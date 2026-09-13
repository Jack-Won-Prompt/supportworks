<?php

namespace Tests\Feature\AiWork;

use App\Models\AiWork\AiwErrorReport;
use App\Models\AiWork\AiwErrorSource;
use App\Models\Project;
use App\Models\User;
use App\Services\AiWork\AiwNotifier;
use App\Services\AiWork\ErrorTriage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 올라온 오류를 자동으로 고쳐도 되는지 가르는 규칙.
 *
 * 이 판정이 곧 "밤에 무엇이 저절로 고쳐질 것인가" 다. 느슨하면 결제 코드가
 * 자동으로 고쳐지고, 빡빡하면 아무것도 자동화되지 않는다.
 */
class AiwErrorTriageTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private string $token;

    /** 알림이 실제로 나갔는지 세는 대역. */
    private object $notifier;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->project = Project::create([
            'name' => 'korsafety', 'status' => 'active', 'created_by' => $admin->id,
        ]);

        $this->token = AiwErrorSource::generateToken();

        AiwErrorSource::create([
            'project_id' => $this->project->id,
            'name'       => 'korsafety.co.kr',
            'token_hash' => AiwErrorSource::hashToken($this->token),
        ]);

        $this->notifier = new class extends AiwNotifier {
            public array $sent = [];

            public function errorReported(AiwErrorReport $report): void
            {
                $this->sent[] = $report->id;
            }
        };

        $this->instance(AiwNotifier::class, $this->notifier);
    }

    private function send(array $over = [])
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/aiw/errors', $over + [
                'exception' => 'RuntimeException',
                'message'   => '주문 저장 실패',
                'file'      => 'app/Services/OrderService.php',
                'line'      => 42,
            ]);
    }

    private function triage(): ErrorTriage
    {
        return app(ErrorTriage::class);
    }

    private function report(array $attrs): AiwErrorReport
    {
        return new AiwErrorReport($attrs + ['exception' => 'RuntimeException', 'file' => 'app/X.php', 'line' => 1]);
    }

    // ── 규칙 ────────────────────────────────────────────────────────────────

    public function test_클라이언트가_만든_상황은_고칠_것이_없다(): void
    {
        foreach ([
            'Symfony\Component\HttpKernel\Exception\NotFoundHttpException',
            'Illuminate\Validation\ValidationException',
            'Illuminate\Auth\AuthenticationException',
            'Illuminate\Session\TokenMismatchException',
        ] as $exception) {
            [$verdict] = $this->triage()->decide($this->report(['exception' => $exception]));

            $this->assertSame(ErrorTriage::IGNORE, $verdict, $exception);
        }
    }

    public function test_돈과_신원이_걸린_자리는_사람이_본다(): void
    {
        foreach ([
            'app/Services/Payment/TossClient.php',
            'app/Services/Billing/Invoice.php',
            'app/Http/Middleware/AuthGate.php',
            'database/migrations/2026_01_01_000000_create_x_table.php',
            'config/database.php',
        ] as $file) {
            [$verdict, $reason] = $this->triage()->decide($this->report(['file' => $file]));

            $this->assertSame(ErrorTriage::HUMAN, $verdict, $file);
            $this->assertNotSame('', $reason, '왜 막혔는지 적혀 있어야 한다.');
        }
    }

    public function test_운영_절대경로도_같은_규칙에_걸린다(): void
    {
        // 보내는 쪽은 /home/ubuntu/www/korsafety/app/... 로 보낸다.
        [$verdict] = $this->triage()->decide($this->report([
            'file' => '/home/ubuntu/www/korsafety/app/Services/Payment/TossClient.php',
        ]));

        $this->assertSame(ErrorTriage::HUMAN, $verdict);
    }

    public function test_무시가_사람_확인보다_먼저다(): void
    {
        // 404 가 결제 경로에서 났다고 사람을 부르면, 봇이 훑고 갈 때마다 울린다.
        [$verdict] = $this->triage()->decide($this->report([
            'exception' => 'NotFoundHttpException',
            'file'      => 'app/Services/Payment/TossClient.php',
        ]));

        $this->assertSame(ErrorTriage::IGNORE, $verdict);
    }

    public function test_코드_밖에_원인이_있을_수_있으면_사람이_본다(): void
    {
        // DB 가 끊겨서 난 것을 코드로 고쳐 봐야 헛일이고, 잘못 고치면 멀쩡한
        // 코드를 망친다.
        [$verdict] = $this->triage()->decide($this->report(['exception' => 'Illuminate\Database\QueryException']));

        $this->assertSame(ErrorTriage::HUMAN, $verdict);
    }

    public function test_파일을_모르면_사람이_본다(): void
    {
        [$verdict] = $this->triage()->decide($this->report(['file' => null]));

        $this->assertSame(ErrorTriage::HUMAN, $verdict);
    }

    public function test_평범한_애플리케이션_오류는_자동_수정_대상이다(): void
    {
        [$verdict] = $this->triage()->decide($this->report(['file' => 'app/Services/OrderService.php']));

        $this->assertSame(ErrorTriage::AUTO, $verdict);
    }

    // ── 받는 순간 붙는다 ────────────────────────────────────────────────────

    public function test_받는_즉시_판정과_이유가_남는다(): void
    {
        $this->send()->assertStatus(201);

        $report = AiwErrorReport::firstOrFail();

        $this->assertSame(ErrorTriage::AUTO, $report->verdict);
        $this->assertNotNull($report->verdict_reason);
        $this->assertSame(AiwErrorReport::STATUS_NEW, $report->status);
    }

    public function test_고칠_것이_없으면_처음부터_덮어_둔다(): void
    {
        $this->send(['exception' => 'NotFoundHttpException'])->assertStatus(201);

        $this->assertSame(AiwErrorReport::STATUS_IGNORED, AiwErrorReport::firstOrFail()->status);
    }

    public function test_사람이_봐야_하면_그렇게_표시된다(): void
    {
        $this->send(['file' => 'app/Services/Payment/TossClient.php'])->assertStatus(201);

        $this->assertSame(AiwErrorReport::STATUS_BLOCKED, AiwErrorReport::firstOrFail()->status);
    }

    // ── 알림 ────────────────────────────────────────────────────────────────

    public function test_고칠_것이_없는_오류로는_알리지_않는다(): void
    {
        // 404 와 봇 스캔으로 휴대폰이 울리면, 사람은 몇 번 겪고 알림을 믿지 않게 된다.
        $this->send(['exception' => 'NotFoundHttpException']);

        $this->assertSame([], $this->notifier->sent);
    }

    public function test_고쳐야_할_오류는_알린다(): void
    {
        $this->send();

        $this->assertCount(1, $this->notifier->sent);
    }

    public function test_같은_오류로_두_번_알리지_않는다(): void
    {
        $this->send();
        $this->send();

        $this->assertCount(1, $this->notifier->sent);
    }

    // ── 지시를 만들어도 되는가 ──────────────────────────────────────────────

    public function test_한_번_나고_만_오류로는_지시를_만들지_않는다(): void
    {
        // 일시적인 것(네트워크 끊김 등)이라 고칠 것이 없고, 그때마다 지시를
        // 만들면 밤새 헛일을 한다.
        config(['aiw.error_triage.min_count_for_auto' => 2]);

        $this->send();

        $this->assertFalse($this->triage()->shouldQueue(AiwErrorReport::firstOrFail()));

        $this->send();

        $this->assertTrue($this->triage()->shouldQueue(AiwErrorReport::firstOrFail()));
    }

    public function test_상한만큼_손댔으면_더_만들지_않는다(): void
    {
        // 고친 코드가 또 에러를 내는 고리를 여기서 끊는다.
        config(['aiw.error_triage.min_count_for_auto' => 1, 'aiw.error_patch_max_attempts' => 2]);

        $this->send();

        $report = AiwErrorReport::firstOrFail();

        $this->assertTrue($this->triage()->shouldQueue($report));

        $report->update(['patch_attempts' => 2]);

        $this->assertFalse($this->triage()->shouldQueue($report->fresh()));
    }

    public function test_사람_확인_대상은_지시를_만들지_않는다(): void
    {
        config(['aiw.error_triage.min_count_for_auto' => 1]);

        $this->send(['file' => 'app/Services/Payment/TossClient.php']);

        $this->assertFalse($this->triage()->shouldQueue(AiwErrorReport::firstOrFail()));
    }

    public function test_기본값은_자동_생성_꺼짐이다(): void
    {
        // 판정이 맞는지 화면에서 보고 켠다. 한 번에 켜면 잘못 판정된 오류가
        // 첫날 밤에 지시로 나가고, 그때는 이미 운영에 무언가 올라간 뒤다.
        $this->assertFalse((bool) config('aiw.error_triage.auto_create_jobs'));
    }
}
