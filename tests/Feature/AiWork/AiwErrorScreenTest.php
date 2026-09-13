<?php

namespace Tests\Feature\AiWork;

use App\Models\AiWork\AiwErrorReport;
use App\Models\AiWork\AiwErrorSource;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 오류 수집 출처 관리와 오류 목록 화면.
 *
 * 자동 생성을 붙이기 전에 이 화면이 먼저 있어야 한다. 눈으로 보지 못한 채
 * 자동 생성을 켜면, 잘못 묶인 오류가 작업 지시 수십 개로 번진 뒤에야 안다.
 */
class AiwErrorScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operator;
    private User $outsider;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $this->project = Project::create([
            'name' => 'korsafety', 'status' => 'active', 'created_by' => $this->admin->id,
        ]);

        $this->operator = User::factory()->create(['role' => 'member', 'is_aiw_operator' => true]);
        ProjectMember::create([
            'project_id' => $this->project->id, 'user_id' => $this->operator->id, 'role' => 'member',
        ]);

        $this->outsider = User::factory()->create(['role' => 'member']);
    }

    private function source(): AiwErrorSource
    {
        return AiwErrorSource::create([
            'project_id' => $this->project->id,
            'name'       => 'korsafety.co.kr',
            'token_hash' => AiwErrorSource::hashToken(AiwErrorSource::generateToken()),
        ]);
    }

    private function report(array $attrs = []): AiwErrorReport
    {
        return AiwErrorReport::create($attrs + [
            'project_id'    => $this->project->id,
            'source_id'     => $this->source()->id,
            'fingerprint'   => str_repeat('a', 64),
            'exception'     => 'RuntimeException',
            'message'       => '주문 저장 실패',
            'file'          => 'app/Services/OrderService.php',
            'line'          => 42,
            'count'         => 7,
            'first_seen_at' => now(),
            'last_seen_at'  => now(),
            'status'        => AiwErrorReport::STATUS_NEW,
        ]);
    }

    // ── 출처 관리(관리자 전용) ──────────────────────────────────────────────

    public function test_관리자만_출처를_다룬다(): void
    {
        // 이 토큰이 그 프로젝트에 오류를 쌓을 자격이고, 그 오류는 작업 지시로
        // 이어진다. 담당자 토큰과 같은 급이다.
        $this->actingAs($this->admin)->get(route('settings.aiw-errors.index'))->assertOk();
        $this->actingAs($this->operator)->get(route('settings.aiw-errors.index'))->assertForbidden();
        $this->actingAs($this->outsider)->get(route('settings.aiw-errors.index'))->assertForbidden();
    }

    public function test_출처를_등록하면_토큰을_한_번_보여_준다(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.aiw-errors.store'), [
                'project_id' => $this->project->id,
                'name'       => 'korsafety.co.kr',
            ])
            ->assertRedirect()
            ->assertSessionHas('aiw_error_token');

        $source = AiwErrorSource::firstOrFail();

        $this->assertSame('korsafety.co.kr', $source->name);
        $this->assertTrue($source->enabled);

        // 저장되는 것은 해시뿐이다. 원문을 보관하면 유출 한 번에 다 털린다.
        $raw = session('aiw_error_token');
        $this->assertSame(AiwErrorSource::hashToken($raw), $source->token_hash);
        $this->assertNotSame($raw, $source->token_hash);
    }

    public function test_토큰을_재발급하면_기존_토큰이_무효가_된다(): void
    {
        $source = $this->source();
        $before = $source->token_hash;

        $this->actingAs($this->admin)
            ->post(route('settings.aiw-errors.reissue', $source))
            ->assertSessionHas('aiw_error_token');

        $this->assertNotSame($before, $source->fresh()->token_hash);
    }

    public function test_출처를_끄면_더_받지_않는다(): void
    {
        // 토큰이 샜을 때 지우지 않고 끌 수 있어야 되돌리기 쉽다.
        $source = $this->source();

        $this->actingAs($this->admin)->post(route('settings.aiw-errors.toggle', $source))->assertRedirect();

        $this->assertFalse($source->fresh()->enabled);
    }

    // ── 오류 목록 ───────────────────────────────────────────────────────────

    public function test_작업_지시_권한이_있으면_오류_목록을_본다(): void
    {
        $this->report();

        $this->actingAs($this->operator)
            ->get(route('projects.aiw-errors.index', $this->project))
            ->assertOk()
            ->assertSee('RuntimeException')
            ->assertSee('주문 저장 실패')
            // 몇 번 났는지가 우선순위다. 한 번과 천 번은 다른 일이다.
            ->assertSee('7회');
    }

    public function test_권한이_없으면_오류_목록도_막힌다(): void
    {
        // 오류 본문에는 운영에서 난 실제 상황이 적힌다. 작업 지시와 같은 선이다.
        $this->actingAs($this->outsider)
            ->get(route('projects.aiw-errors.index', $this->project))
            ->assertForbidden();
    }

    public function test_상태로_추릴_수_있다(): void
    {
        $this->report();
        $this->report([
            'fingerprint' => str_repeat('b', 64),
            'exception'   => 'LogicException',
            'message'     => '봇이 훑고 간 흔적',
            'status'      => AiwErrorReport::STATUS_IGNORED,
        ]);

        $this->actingAs($this->admin)
            ->get(route('projects.aiw-errors.index', [$this->project, 'status' => 'ignored']))
            ->assertOk()
            ->assertSee('봇이 훑고 간 흔적')
            ->assertDontSee('주문 저장 실패');
    }

    public function test_무시했다가_되돌릴_수_있다(): void
    {
        $report = $this->report();

        $this->actingAs($this->operator)
            ->post(route('projects.aiw-errors.ignore', [$this->project, $report]))
            ->assertRedirect();

        $this->assertSame(AiwErrorReport::STATUS_IGNORED, $report->fresh()->status);

        $this->actingAs($this->operator)
            ->post(route('projects.aiw-errors.ignore', [$this->project, $report]));

        $this->assertSame(AiwErrorReport::STATUS_NEW, $report->fresh()->status);
    }

    public function test_남의_프로젝트_오류는_이_화면에서_다룰_수_없다(): void
    {
        $other = Project::create(['name' => '남의 것', 'status' => 'active', 'created_by' => $this->admin->id]);
        $report = $this->report();

        $this->actingAs($this->admin)
            ->post(route('projects.aiw-errors.ignore', [$other, $report]))
            ->assertNotFound();
    }
}
