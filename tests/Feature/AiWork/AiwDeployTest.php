<?php

namespace Tests\Feature\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Jobs\AiWork\RunDeploy;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwDeploy;
use App\Models\AiWork\AiwDeployTarget;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwPublish;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * 배포: 화이트리스트·확인 문구·동시 실행 방지·기록.
 *
 * 웹 애플리케이션이 서버 셸 명령을 실행하는 기능이라, 안전장치가 실제로
 * 동작하는지가 이 테스트의 전부다.
 */
class AiwDeployTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    private int $projectId;

    private AiwAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\MaintenanceCheckMiddleware::class,
            \App\Http\Middleware\LogPageAccess::class,
            \App\Http\Middleware\CollabParticipantMiddleware::class,
        ]);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->member = User::factory()->create();

        $this->projectId = DB::table('projects')->insertGetId([
            'name' => '배포 테스트', 'created_by' => $this->admin->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ([$this->admin, $this->member] as $user) {
            DB::table('project_members')->insert([
                'project_id' => $this->projectId, 'user_id' => $user->id,
                'role' => 'manager', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->agent = AiwAgent::create([
            'name' => 'PC', 'token_hash' => AiwAgent::hashToken(AiwAgent::generateToken()),
            'user_id' => $this->admin->id, 'last_seen_at' => now(),
        ]);
    }

    private function target(array $overrides = []): AiwDeployTarget
    {
        return AiwDeployTarget::create(array_merge([
            'project_id'  => $this->projectId,
            'name'        => '운영 배포',
            'working_dir' => sys_get_temp_dir(),
            'command'     => 'echo ok',
            'timeout_sec' => 60,
            'enabled'     => true,
            'created_by'  => $this->admin->id,
        ], $overrides));
    }

    /** 배포는 원격에 올린 뒤에만 의미가 있다. */
    private function publishedJob(): AiwJob
    {
        $job = AiwJob::create([
            'project_id' => $this->projectId, 'agent_id' => $this->agent->id,
            'title' => '변경', 'instruction' => 'x', 'context_limit_tokens' => 200000,
            'allowed_tools' => ['Read'], 'cost_limit_usd' => 2.0,
            'created_by' => $this->member->id, 'status' => AiwJobStatus::Completed,
        ]);

        AiwPublish::create([
            'job_id' => $job->id, 'requested_by' => $this->member->id,
            'source_branch' => 'aiw/job-'.$job->id, 'target_branch' => 'main',
            'commit_message' => 'x', 'status' => 'succeeded', 'created_at' => now(),
        ]);

        return $job;
    }

    private function deploy(AiwJob $job, AiwDeployTarget $target, string $confirmation)
    {
        return $this->actingAs($this->member)
            ->post(route('projects.ai-works.deploy', [$this->projectId, $job]), [
                'target_id'    => $target->id,
                'confirmation' => $confirmation,
            ]);
    }

    // ── 등록 권한 ───────────────────────────────────────────────────────────

    public function test_관리자만_배포_대상을_등록한다(): void
    {
        // 여기 등록한 명령이 서버에서 그대로 실행된다. 사실상 셸 권한이다.
        $this->actingAs($this->member)->get(route('settings.aiw-deploys.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('settings.aiw-deploys.index'))->assertOk();

        $this->actingAs($this->member)->post(route('settings.aiw-deploys.store'), [
            'project_id' => $this->projectId, 'name' => '몰래', 'working_dir' => '/tmp', 'command' => 'rm -rf /',
        ])->assertForbidden();

        $this->assertSame(0, AiwDeployTarget::count());
    }

    // ── 실행 안전장치 ───────────────────────────────────────────────────────

    public function test_확인_문구가_맞아야_실행한다(): void
    {
        Queue::fake();
        $target = $this->target();
        $job = $this->publishedJob();

        // 실수 클릭 방지. 무엇이 도는지 알고 있어야 한다.
        $this->deploy($job, $target, '아무거나')->assertRedirect()->assertSessionHas('error');
        $this->assertSame(0, AiwDeploy::count());

        $this->deploy($job, $target, '운영 배포')->assertRedirect()->assertSessionHas('status');
        $this->assertSame(1, AiwDeploy::count());

        Queue::assertPushed(RunDeploy::class);
    }

    public function test_동시_실행을_막는다(): void
    {
        Queue::fake();
        $target = $this->target();
        $job = $this->publishedJob();

        $this->deploy($job, $target, '운영 배포')->assertRedirect();

        // 배포 중 배포는 저장소를 망가뜨린다.
        $this->deploy($job, $target, '운영 배포')->assertRedirect()->assertSessionHas('error');

        $this->assertSame(1, AiwDeploy::count());
    }

    public function test_비활성_대상은_실행하지_않는다(): void
    {
        Queue::fake();
        $target = $this->target(['enabled' => false]);
        $job = $this->publishedJob();

        $this->deploy($job, $target, '운영 배포')->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, AiwDeploy::count());
    }

    public function test_다른_프로젝트의_대상은_404다(): void
    {
        Queue::fake();

        $otherProject = DB::table('projects')->insertGetId([
            'name' => '남의 프로젝트', 'created_by' => $this->admin->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $target = $this->target(['project_id' => $otherProject]);

        $this->deploy($this->publishedJob(), $target, '운영 배포')->assertStatus(404);
    }

    public function test_올리지_않은_작업에는_배포_버튼이_없다(): void
    {
        $this->target();

        $job = AiwJob::create([
            'project_id' => $this->projectId, 'agent_id' => $this->agent->id,
            'title' => '아직', 'instruction' => 'x', 'context_limit_tokens' => 200000,
            'allowed_tools' => ['Read'], 'cost_limit_usd' => 2.0,
            'created_by' => $this->member->id, 'status' => AiwJobStatus::Completed,
        ]);

        // push 하지 않았다면 서버가 받아 갈 커밋이 없다.
        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->projectId, $job]))
            ->assertOk()
            ->assertDontSee('배포 실행');
    }

    public function test_올린_작업에는_배포_버튼이_보인다(): void
    {
        $this->target();

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->projectId, $this->publishedJob()]))
            ->assertOk()
            ->assertSee('배포 실행')
            ->assertSee('운영 배포');
    }

    public function test_어느_이름을_입력해야_하는지_화면이_알려준다(): void
    {
        $this->target(['name' => '운영 배포']);

        // "대상 이름을 입력하세요" 만으로는 어느 이름인지 알 수 없다.
        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->projectId, $this->publishedJob()]))
            ->assertOk()
            ->assertSee('그대로 입력하세요')
            ->assertSee('운영 배포');
    }

    // ── 실제 실행 ───────────────────────────────────────────────────────────

    public function test_명령을_실행하고_출력과_종료코드를_남긴다(): void
    {
        $target = $this->target(['command' => 'echo 배포완료']);
        $job = $this->publishedJob();

        $deploy = AiwDeploy::create([
            'target_id' => $target->id, 'job_id' => $job->id,
            'requested_by' => $this->member->id, 'status' => 'queued', 'created_at' => now(),
        ]);

        (new RunDeploy($deploy->id))->handle();

        $deploy->refresh();

        $this->assertSame('succeeded', $deploy->status);
        $this->assertSame(0, $deploy->exit_code);
        $this->assertStringContainsString('배포완료', $deploy->output);
        $this->assertNotNull($deploy->finished_at);
    }

    public function test_실패한_명령은_출력과_함께_실패로_남는다(): void
    {
        $target = $this->target(['command' => 'exit 3']);
        $job = $this->publishedJob();

        $deploy = AiwDeploy::create([
            'target_id' => $target->id, 'job_id' => $job->id,
            'requested_by' => $this->member->id, 'status' => 'queued', 'created_at' => now(),
        ]);

        (new RunDeploy($deploy->id))->handle();

        $deploy->refresh();

        $this->assertSame('failed', $deploy->status);
        $this->assertSame(3, $deploy->exit_code);
    }

    public function test_없는_디렉터리는_실행_전에_막는다(): void
    {
        $target = $this->target(['working_dir' => '/definitely/not/here']);
        $job = $this->publishedJob();

        $deploy = AiwDeploy::create([
            'target_id' => $target->id, 'job_id' => $job->id,
            'requested_by' => $this->member->id, 'status' => 'queued', 'created_at' => now(),
        ]);

        (new RunDeploy($deploy->id))->handle();

        $deploy->refresh();

        $this->assertSame('failed', $deploy->status);
        $this->assertStringContainsString('작업 디렉터리', $deploy->output);
    }

    public function test_큐에서_돌린다(): void
    {
        Bus::fake();
        $target = $this->target();

        $this->deploy($this->publishedJob(), $target, '운영 배포')->assertRedirect();

        // 웹 요청 안에서 돌리면 타임아웃으로 끊기고 어디까지 갔는지 알 수 없다.
        Bus::assertDispatched(RunDeploy::class);
    }
}
