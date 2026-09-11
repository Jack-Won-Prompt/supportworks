<?php

namespace Tests\Feature\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\PublishRequested;
use App\Jobs\AiWork\RunDeploy;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwDeploy;
use App\Models\AiWork\AiwDeployTarget;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwPublish;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * "배포까지 자동으로".
 *
 * 사람이 결과를 보고 누르는 단계를 건너뛴다. 그래서 어디서 멈추는지가 중요하다 —
 * 실패한 결과가 운영에 가면 안 되고, 푸시가 안 된 것을 배포하면 서버는 옛 코드를
 * 받아 가면서 성공한 것처럼 보인다.
 */
class AiwAutoDeployTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private AiwAgent $agent;

    private string $token;

    private int $projectId;

    private AiwDeployTarget $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\MaintenanceCheckMiddleware::class,
            \App\Http\Middleware\LogPageAccess::class,
            \App\Http\Middleware\CollabParticipantMiddleware::class,
        ]);

        $this->member = User::factory()->create();

        $this->projectId = DB::table('projects')->insertGetId([
            'name' => '자동 배포 테스트', 'created_by' => $this->member->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('project_members')->insert([
            'project_id' => $this->projectId, 'user_id' => $this->member->id,
            'role' => 'manager', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->token = AiwAgent::generateToken();
        $this->agent = AiwAgent::create([
            'name' => 'PC', 'token_hash' => AiwAgent::hashToken($this->token),
            'user_id' => $this->member->id, 'last_seen_at' => now(),
        ]);

        AiwAgentProject::create([
            'agent_id' => $this->agent->id, 'project_id' => $this->projectId,
            'local_path' => 'E:/work/sample', 'default_branch' => 'main',
        ]);

        $this->target = AiwDeployTarget::create([
            'project_id' => $this->projectId, 'name' => '운영 배포',
            'working_dir' => sys_get_temp_dir(), 'command' => 'echo ok',
            'timeout_sec' => 60, 'enabled' => true, 'created_by' => $this->member->id,
        ]);
    }

    private function job(array $overrides = []): AiwJob
    {
        return AiwJob::create(array_merge([
            'project_id' => $this->projectId, 'agent_id' => $this->agent->id,
            'title' => '변경', 'instruction' => 'x', 'context_limit_tokens' => 200000,
            'allowed_tools' => ['Read'], 'cost_limit_usd' => 2.0,
            'created_by' => $this->member->id, 'use_branch' => true,
            'auto_deploy' => true, 'auto_deploy_target_id' => $this->target->id,
            'status' => AiwJobStatus::Running,
        ], $overrides));
    }

    private function daemon()
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->token);
    }

    private function complete(AiwJob $job)
    {
        $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/start", ['session_id' => 's']);

        return $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/complete", ['result_summary' => '끝']);
    }

    // ── 등록 ────────────────────────────────────────────────────────────────

    public function test_체크하면_작업에_기록된다(): void
    {
        Event::fake();

        $this->actingAs($this->member)->post(route('projects.ai-works.store', $this->projectId), [
            'title' => '자동', 'agent_id' => $this->agent->id, 'instruction' => 'x',
            'mode' => 'batch', 'allowed_tools' => ['Read'], 'permission_mode' => 'acceptEdits',
            'cost_limit_usd' => 2.0, 'use_branch' => '1',
            'auto_deploy' => '1', 'auto_deploy_target_id' => $this->target->id,
        ])->assertRedirect();

        $job = AiwJob::latest('id')->first();

        $this->assertTrue($job->auto_deploy);
        $this->assertSame($this->target->id, $job->auto_deploy_target_id);
    }

    public function test_브랜치_분리를_끄면_자동_배포도_꺼진다(): void
    {
        Event::fake();

        // 변경이 현재 브랜치에 섞이면 이 작업만 골라 올릴 수 없다.
        $this->actingAs($this->member)->post(route('projects.ai-works.store', $this->projectId), [
            'title' => '자동', 'agent_id' => $this->agent->id, 'instruction' => 'x',
            'mode' => 'batch', 'allowed_tools' => ['Read'], 'permission_mode' => 'acceptEdits',
            'cost_limit_usd' => 2.0, 'use_branch' => '0',
            'auto_deploy' => '1', 'auto_deploy_target_id' => $this->target->id,
        ])->assertRedirect();

        $this->assertFalse(AiwJob::latest('id')->first()->auto_deploy);
    }

    public function test_다른_프로젝트의_배포_대상은_받지_않는다(): void
    {
        Event::fake();

        $otherProject = DB::table('projects')->insertGetId([
            'name' => '남의 것', 'created_by' => $this->member->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherTarget = AiwDeployTarget::create([
            'project_id' => $otherProject, 'name' => '남의 배포',
            'working_dir' => sys_get_temp_dir(), 'command' => 'echo x',
            'timeout_sec' => 60, 'enabled' => true, 'created_by' => $this->member->id,
        ]);

        $this->actingAs($this->member)->post(route('projects.ai-works.store', $this->projectId), [
            'title' => '자동', 'agent_id' => $this->agent->id, 'instruction' => 'x',
            'mode' => 'batch', 'allowed_tools' => ['Read'], 'permission_mode' => 'acceptEdits',
            'cost_limit_usd' => 2.0, 'use_branch' => '1',
            'auto_deploy' => '1', 'auto_deploy_target_id' => $otherTarget->id,
        ])->assertRedirect();

        $this->assertFalse(AiwJob::latest('id')->first()->auto_deploy);
    }

    // ── 대화 도중 전환 ──────────────────────────────────────────────────────

    /** 대화형 작업 하나. 메시지를 보낼 수 있는 상태로 만든다. */
    private function talking(array $overrides = []): AiwJob
    {
        return $this->job(array_merge([
            'mode' => 'interactive', 'auto_deploy' => false, 'auto_deploy_target_id' => null,
        ], $overrides));
    }

    private function say(AiwJob $job, array $extra = [])
    {
        return $this->actingAs($this->member)->post(
            route('projects.ai-works.message', [$this->projectId, $job]),
            array_merge(['content' => '이어서 해주세요'], $extra),
        );
    }

    public function test_대화_도중_체크하면_켜진다(): void
    {
        Event::fake();
        $job = $this->talking();

        // 등록할 때 한 번만 정하게 하면, 결과를 보고 마음이 바뀐 사람은
        // 새 지시를 만드는 수밖에 없다.
        $this->say($job, ['auto_deploy' => '1', 'auto_deploy_target_id' => $this->target->id])
            ->assertRedirect()
            ->assertSessionHas('status');

        $job->refresh();

        $this->assertTrue($job->auto_deploy);
        $this->assertSame($this->target->id, $job->auto_deploy_target_id);
        $this->assertDatabaseHas('aiw_job_messages', ['job_id' => $job->id, 'content' => '이어서 해주세요']);
    }

    public function test_대화_도중_체크를_풀면_꺼진다(): void
    {
        Event::fake();
        $job = $this->talking(['auto_deploy' => true, 'auto_deploy_target_id' => $this->target->id]);

        // 해제한 체크박스는 아무것도 보내지 않으므로 폼의 숨은 값이 "0" 을 보낸다.
        $this->say($job, ['auto_deploy' => '0'])->assertRedirect();

        $job->refresh();

        $this->assertFalse($job->auto_deploy);
        $this->assertNull($job->auto_deploy_target_id);
    }

    public function test_체크박스를_보내지_않으면_설정을_건드리지_않는다(): void
    {
        Event::fake();
        $job = $this->talking(['auto_deploy' => true, 'auto_deploy_target_id' => $this->target->id]);

        // 배포 대상이 없는 프로젝트에서는 체크박스를 그리지 않는다. 그때 폼이
        // 보내지 않은 것을 "끔" 으로 읽으면 켜 둔 설정이 조용히 꺼진다.
        $this->say($job)->assertRedirect();

        $this->assertTrue($job->refresh()->auto_deploy);
    }

    public function test_브랜치_분리가_없으면_켜지지_않고_알려준다(): void
    {
        Event::fake();
        $job = $this->talking(['use_branch' => false]);

        $this->say($job, ['auto_deploy' => '1', 'auto_deploy_target_id' => $this->target->id])
            ->assertRedirect()
            // 말없이 무시하면 사용자는 켰다고 믿고 기다린다.
            ->assertSessionHas('error');

        $this->assertFalse($job->refresh()->auto_deploy);
        // 경고가 떠도 메시지 자체는 전달돼야 한다.
        $this->assertDatabaseHas('aiw_job_messages', ['job_id' => $job->id, 'content' => '이어서 해주세요']);
    }

    public function test_대화에서도_남의_배포_대상은_받지_않는다(): void
    {
        Event::fake();

        $otherProject = DB::table('projects')->insertGetId([
            'name' => '남의 것', 'created_by' => $this->member->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $otherTarget = AiwDeployTarget::create([
            'project_id' => $otherProject, 'name' => '남의 배포',
            'working_dir' => sys_get_temp_dir(), 'command' => 'echo no',
            'timeout_sec' => 60, 'enabled' => true, 'created_by' => $this->member->id,
        ]);

        $job = $this->talking();

        $this->say($job, ['auto_deploy' => '1', 'auto_deploy_target_id' => $otherTarget->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse($job->refresh()->auto_deploy);
    }

    public function test_대화창에_체크박스가_그려진다(): void
    {
        $job = $this->talking();

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->projectId, $job]))
            ->assertOk()
            ->assertSee('배포까지 자동으로')
            // 해제한 체크박스는 아무것도 보내지 않는다. 숨은 값이 있어야 끌 수 있다.
            ->assertSee('name="auto_deploy" value="0"', false);
    }

    public function test_브랜치_분리가_없으면_체크박스를_그리지_않는다(): void
    {
        $job = $this->talking(['use_branch' => false]);

        // 켤 수 없는 것을 보여 주면 사용자는 켰다고 믿는다.
        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->projectId, $job]))
            ->assertOk()
            ->assertDontSee('배포까지 자동으로');
    }

    // ── 1단계: 완료 → 커밋·푸시 ─────────────────────────────────────────────

    public function test_완료되면_커밋_푸시가_자동으로_시작된다(): void
    {
        Event::fake();
        $job = $this->job();

        $this->complete($job)->assertOk();

        $publish = AiwPublish::where('job_id', $job->id)->first();

        $this->assertNotNull($publish);
        $this->assertTrue($publish->automatic, '자동인지 사람이 눌렀는지 구분되어야 한다.');
        Event::assertDispatched(PublishRequested::class);
    }

    public function test_체크하지_않았으면_아무_일도_없다(): void
    {
        Event::fake();
        $job = $this->job(['auto_deploy' => false, 'auto_deploy_target_id' => null]);

        $this->complete($job)->assertOk();

        $this->assertSame(0, AiwPublish::count());
    }

    public function test_실패한_작업은_올리지_않는다(): void
    {
        Event::fake();
        $job = $this->job();

        $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/start", ['session_id' => 's']);
        $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/fail", ['error_message' => '실패'])->assertOk();

        // 실패한 결과를 운영에 올리면 안 된다.
        $this->assertSame(0, AiwPublish::count());
    }

    // ── 2단계: 푸시 성공 → 배포 ─────────────────────────────────────────────

    private function publishOf(AiwJob $job): AiwPublish
    {
        $this->complete($job);

        return AiwPublish::where('job_id', $job->id)->firstOrFail();
    }

    public function test_푸시가_성공하면_배포가_시작된다(): void
    {
        Event::fake();
        Queue::fake();
        $job = $this->job();
        $publish = $this->publishOf($job);

        $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/publishes/{$publish->id}", [
            'status' => 'succeeded', 'output' => '푸시 완료', 'commit_sha' => 'abc',
        ])->assertOk();

        $deploy = AiwDeploy::where('job_id', $job->id)->first();

        $this->assertNotNull($deploy);
        $this->assertTrue($deploy->automatic);
        // 자동 실행은 확인 문구를 요구하지 않는다 — 등록할 때 이미 동의했다.
        Queue::assertPushed(RunDeploy::class);
    }

    public function test_푸시가_실패하면_배포하지_않는다(): void
    {
        Event::fake();
        Queue::fake();
        $job = $this->job();
        $publish = $this->publishOf($job);

        $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/publishes/{$publish->id}", [
            'status' => 'failed', 'output' => '머지 충돌',
        ])->assertOk();

        // 푸시가 안 된 것을 배포하면 서버는 옛 코드를 받아 가면서 성공한 것처럼 보인다.
        $this->assertSame(0, AiwDeploy::count());
        Queue::assertNotPushed(RunDeploy::class);
    }

    public function test_중단되면_이유가_활동_로그에_남는다(): void
    {
        Event::fake();
        Queue::fake();
        $job = $this->job();
        $publish = $this->publishOf($job);

        $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/publishes/{$publish->id}", [
            'status' => 'failed', 'output' => '머지 충돌',
        ])->assertOk();

        // 자동이라도 무슨 일이 있었는지 화면에 보여야 한다.
        $this->assertDatabaseHas('aiw_job_logs', [
            'job_id'  => $job->id,
            'type'    => 'error',
            'content' => '자동 진행 중단 — 커밋·푸시가 실패해 배포하지 않습니다.',
        ]);
    }

    public function test_배포_대상이_비활성이면_멈춘다(): void
    {
        Event::fake();
        Queue::fake();
        $job = $this->job();
        $publish = $this->publishOf($job);

        $this->target->forceFill(['enabled' => false])->save();

        $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/publishes/{$publish->id}", [
            'status' => 'succeeded', 'output' => 'ok', 'commit_sha' => 'abc',
        ])->assertOk();

        $this->assertSame(0, AiwDeploy::count());
        $this->assertDatabaseHas('aiw_job_logs', ['job_id' => $job->id, 'type' => 'error']);
    }

    public function test_사람이_누른_푸시는_자동_배포로_이어지지_않는다(): void
    {
        Event::fake();
        Queue::fake();

        // 자동 파이프라인이 아닌 수동 푸시까지 배포로 이어지면 예상 밖의 배포가 된다.
        $job = $this->job(['auto_deploy' => false, 'auto_deploy_target_id' => null]);
        $this->complete($job);

        $this->actingAs($this->member)
            ->post(route('projects.ai-works.publish', [$this->projectId, $job]))
            ->assertRedirect();

        $publish = AiwPublish::where('job_id', $job->id)->firstOrFail();
        $this->assertFalse($publish->automatic);

        $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/publishes/{$publish->id}", [
            'status' => 'succeeded', 'output' => 'ok', 'commit_sha' => 'abc',
        ])->assertOk();

        $this->assertSame(0, AiwDeploy::count());
    }
}
