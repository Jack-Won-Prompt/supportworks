<?php

namespace Tests\Feature\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\JobCancelRequested;
use App\Events\AiWork\JobDispatched;
use App\Events\AiWork\JobEndRequested;
use App\Events\AiWork\JobUserMessage;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwDeploy;
use App\Models\AiWork\AiwDeployTarget;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwPermissionRequest;
use App\Models\AiWork\AiwPublish;
use App\Models\MobileToken;
use App\Models\User;
use App\Services\AiWork\AiwNotifier;
use App\Services\AiWork\JobStateMachine;
use App\Services\AiWork\MessageWriter;
use App\Services\AiWork\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * 모바일 앱의 작업 지시 — API 와 푸시.
 *
 * 전제: 웹과 같은 서비스를 탄다. 모바일 지시는 대화형 + acceptEdits + 기본 툴로
 * 고정되고, 사람이 필요한 순간(회신·승인·완료·실패·자동 배포 결과)은 푸시로 온다.
 */
class AiwMobileApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    /** 프로젝트 매니저지만 시스템 관리자는 아니다. 막혀야 한다. */
    private User $member;

    private AiwAgent $agent;

    private AiwDeployTarget $target;

    private int $projectId;

    private RecordingNotifier $pushes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pushes = new RecordingNotifier;
        $this->app->instance(AiwNotifier::class, $this->pushes);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->member = User::factory()->create(['role' => 'member']);

        $this->projectId = DB::table('projects')->insertGetId([
            'name' => '모바일 테스트 프로젝트',
            'created_by' => $this->member->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 관리자는 이 프로젝트의 멤버가 아니다 — 작업 지시는 멤버십을 보지 않는다.
        DB::table('project_members')->insert([
            'project_id' => $this->projectId,
            'user_id' => $this->member->id,
            'role' => 'manager',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->agent = AiwAgent::create([
            'name' => '테스트 PC',
            'token_hash' => AiwAgent::hashToken(AiwAgent::generateToken()),
            'user_id' => $this->admin->id,
            'last_seen_at' => now(),
        ]);

        AiwAgentProject::create([
            'agent_id' => $this->agent->id,
            'project_id' => $this->projectId,
            'display_name' => '로컬 PC',
            'local_path' => 'E:\\work\\sample',
            'default_branch' => 'master',
        ]);

        $this->target = AiwDeployTarget::create([
            'project_id' => $this->projectId, 'name' => '운영 배포',
            'working_dir' => sys_get_temp_dir(), 'command' => 'echo ok',
            'timeout_sec' => 60, 'enabled' => true, 'created_by' => $this->admin->id,
        ]);
    }

    // ── 권한 ────────────────────────────────────────────────────────────────

    public function test_토큰이_없으면_401(): void
    {
        $this->getJson('/api/mobile/ai-works/projects')->assertStatus(401);
    }

    public function test_관리자가_아니면_프로젝트_멤버라도_막힌다(): void
    {
        $job = $this->job();

        $this->as($this->member)->getJson('/api/mobile/ai-works/projects')->assertForbidden();
        $this->as($this->member)->getJson('/api/mobile/ai-works/attention')->assertForbidden();
        $this->as($this->member)->getJson($this->base())->assertForbidden();
        $this->as($this->member)->getJson($this->base()."/{$job->id}")->assertForbidden();
        $this->as($this->member)->postJson($this->base(), $this->payload())->assertForbidden();

        $this->assertSame(1, AiwJob::count());
    }

    public function test_관리자는_멤버가_아니어도_프로젝트와_확인할_작업을_본다(): void
    {
        $this->job(['status' => AiwJobStatus::WaitingInput]);
        $this->job(['status' => AiwJobStatus::Completed]);

        $this->as($this->admin)->getJson('/api/mobile/ai-works/projects')
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->projectId)
            ->assertJsonPath('data.0.open_count', 1)
            ->assertJsonPath('data.0.attention_count', 1)
            ->assertJsonPath('data.0.agents.0.name', '로컬 PC')
            ->assertJsonPath('data.0.agents.0.online', true);

        $this->as($this->admin)->getJson('/api/mobile/ai-works/attention')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.needs_action', true)
            ->assertJsonPath('data.0.project_name', '모바일 테스트 프로젝트');
    }

    // ── 등록 ────────────────────────────────────────────────────────────────

    public function test_등록은_대화형_acceptEdits_기본툴로_고정되고_작업_PC_로_전달된다(): void
    {
        Event::fake([JobDispatched::class]);

        $response = $this->as($this->admin)->postJson($this->base(), $this->payload([
            // 앱이 보내더라도 무시되어야 하는 값
            'mode' => 'batch',
            'permission_mode' => 'default',
            'allowed_tools' => ['WebFetch'],
        ]))->assertCreated()->assertJsonPath('sent', true)->assertJsonPath('notice', null);

        $job = AiwJob::findOrFail($response->json('id'));

        $this->assertSame('interactive', $job->mode);
        $this->assertSame('acceptEdits', $job->permission_mode);
        $this->assertSame(config('aiw.default_tools'), $job->allowed_tools);
        $this->assertSame(AiwJobStatus::Dispatched, $job->status);
        $this->assertSame($this->admin->id, $job->created_by);
        $this->assertTrue($job->use_branch);
        $this->assertEquals((float) config('aiw.default_cost_limit_usd'), (float) $job->cost_limit_usd);

        // 지시문은 웹과 같이 대화의 첫 메시지가 된다.
        $first = $job->messages()->orderBy('seq')->first();
        $this->assertSame(0, $first->seq);
        $this->assertSame('README 에 한 줄 추가', $first->content);

        Event::assertDispatched(JobDispatched::class);
    }

    public function test_비용_상한_없음을_고를_수_있다(): void
    {
        Event::fake([JobDispatched::class]);

        $id = $this->as($this->admin)->postJson($this->base(), $this->payload(['no_cost_limit' => true]))
            ->assertCreated()->json('id');

        $this->assertNull(AiwJob::findOrFail($id)->cost_limit_usd);
    }

    public function test_자동_배포는_브랜치_분리와_이_프로젝트의_대상이_있을_때만_켜진다(): void
    {
        Event::fake([JobDispatched::class]);

        $on = $this->as($this->admin)->postJson($this->base(), $this->payload([
            'auto_deploy' => true, 'auto_deploy_target_id' => $this->target->id,
        ]))->assertCreated();

        $this->assertTrue(AiwJob::findOrFail($on->json('id'))->auto_deploy);
        $this->assertNull($on->json('notice'));

        $noBranch = $this->as($this->admin)->postJson($this->base(), $this->payload([
            'use_branch' => false, 'auto_deploy' => true, 'auto_deploy_target_id' => $this->target->id,
        ]))->assertCreated();

        $this->assertFalse(AiwJob::findOrFail($noBranch->json('id'))->auto_deploy);
        $this->assertNotNull($noBranch->json('notice'));
    }

    public function test_이_프로젝트를_맡지_않은_작업_PC_로는_보낼_수_없다(): void
    {
        $other = AiwAgent::create([
            'name' => '남의 PC',
            'token_hash' => AiwAgent::hashToken(AiwAgent::generateToken()),
            'user_id' => $this->admin->id,
            'last_seen_at' => now(),
        ]);

        $this->as($this->admin)->postJson($this->base(), $this->payload(['agent_id' => $other->id]))
            ->assertNotFound();

        $this->assertSame(0, AiwJob::count());
    }

    public function test_작업_PC_가_오프라인이면_대기로_두고_알려_준다(): void
    {
        $this->agent->forceFill(['last_seen_at' => now()->subHour()])->save();
        AiwAgentProject::query()->update(['last_seen_at' => now()->subHour()]);

        $response = $this->as($this->admin)->postJson($this->base(), $this->payload())
            ->assertCreated()->assertJsonPath('sent', false);

        $this->assertNotNull($response->json('notice'));
        $this->assertSame(AiwJobStatus::Queued, AiwJob::findOrFail($response->json('id'))->status);
    }

    // ── 상세 · 회신 ─────────────────────────────────────────────────────────

    public function test_상세는_회신을_안전한_HTML_로_주고_선택지는_마지막_질문에만_단다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::WaitingInput]);
        $this->reply($job, '첫 질문', ['가', '나']);
        $this->reply($job, "**어느 쪽**으로 할까요? <script>alert(1)</script>", ['1안', '2안']);

        $response = $this->as($this->admin)->getJson($this->base()."/{$job->id}")->assertOk();
        $messages = $response->json('messages');

        $this->assertSame([], $messages[1]['choices']);   // 지난 질문의 선택지는 내리지 않는다
        $this->assertSame(['1안', '2안'], $messages[2]['choices']);
        $this->assertStringContainsString('<strong>어느 쪽</strong>', $messages[2]['html']);
        $this->assertStringNotContainsString('<script>', $messages[2]['html']);
        $this->assertNull($messages[0]['html']);          // 사람이 쓴 지시문은 그대로 준다

        $response->assertJsonPath('can.message', true)->assertJsonPath('can.end', true);
    }

    public function test_답을_보내면_작업_PC_로_전달된다(): void
    {
        Event::fake([JobUserMessage::class]);
        $job = $this->job(['status' => AiwJobStatus::WaitingInput]);

        $this->as($this->admin)->postJson($this->base()."/{$job->id}/messages", ['content' => '1안'])
            ->assertOk();

        $this->assertDatabaseHas('aiw_job_messages', [
            'job_id' => $job->id, 'role' => 'user', 'content' => '1안', 'user_id' => $this->admin->id,
        ]);
        Event::assertDispatched(JobUserMessage::class,
            fn (JobUserMessage $e) => $e->message->content === '1안' && $e->agentId === $this->agent->id);
    }

    public function test_끝난_작업에는_답을_보낼_수_없다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Completed]);
        $before = $job->messages()->count();   // 지시문(첫 메시지)

        $this->as($this->admin)->postJson($this->base()."/{$job->id}/messages", ['content' => 'x'])
            ->assertStatus(422)->assertJsonStructure(['error']);

        $this->assertSame($before, $job->messages()->count());
    }

    public function test_다른_프로젝트_경로로는_작업을_볼_수_없다(): void
    {
        $job = $this->job();
        $otherProject = DB::table('projects')->insertGetId([
            'name' => '다른 프로젝트', 'created_by' => $this->admin->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->as($this->admin)->getJson("/api/mobile/projects/{$otherProject}/ai-works/{$job->id}")
            ->assertNotFound();
    }

    // ── 승인 · 종료 · 취소 ──────────────────────────────────────────────────

    public function test_승인은_명령을_보여_주고_한_번만_결정된다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::WaitingPermission]);
        $permission = app(PermissionService::class)->request($job, 'rk-1', 'Bash', ['command' => 'npm test']);

        $this->as($this->admin)->getJson($this->base()."/{$job->id}")
            ->assertJsonPath('pending_permissions.0.summary', 'npm test')
            ->assertJsonPath('can.decide', true);

        $url = $this->base()."/{$job->id}/permissions/{$permission->id}";

        $this->as($this->admin)->postJson($url, ['decision' => 'allow'])
            ->assertOk()->assertJsonPath('notice', null);
        $this->assertSame('allowed', $permission->fresh()->status);

        // 웹에서 이미 눌렀다면 나중 결정은 반영되지 않는다.
        $this->as($this->admin)->postJson($url, ['decision' => 'deny'])
            ->assertOk()->assertJsonPath('notice', '이미 결정된 요청입니다.');
        $this->assertSame('allowed', $permission->fresh()->status);
    }

    public function test_세션_종료는_요청만_보내고_취소는_바로_끝낸다(): void
    {
        Event::fake([JobEndRequested::class, JobCancelRequested::class]);
        $job = $this->job(['status' => AiwJobStatus::WaitingInput]);

        $this->as($this->admin)->postJson($this->base()."/{$job->id}/end")->assertOk();
        Event::assertDispatched(JobEndRequested::class);
        // 완료는 데몬이 마지막 턴을 마치고 보고할 때 된다.
        $this->assertSame(AiwJobStatus::WaitingInput, $job->fresh()->status);

        $this->as($this->admin)->postJson($this->base()."/{$job->id}/cancel")->assertOk();
        Event::assertDispatched(JobCancelRequested::class);
        $this->assertSame(AiwJobStatus::Cancelled, $job->fresh()->status);

        $this->as($this->admin)->postJson($this->base()."/{$job->id}/end")->assertStatus(422);
    }

    // ── 푸시 ────────────────────────────────────────────────────────────────

    public function test_회신이_오면_지시한_관리자에게_턴마다_한_번_알린다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Running]);
        $states = app(JobStateMachine::class);
        $this->reply($job, '어느 쪽으로 할까요?', ['1안', '2안']);

        $states->transition($job, AiwJobStatus::WaitingInput);
        $states->transition($job, AiwJobStatus::WaitingInput);   // 같은 상태 재보고 — 울리지 않는다
        $states->transition($job, AiwJobStatus::Running);
        $states->transition($job, AiwJobStatus::Running);        // 데몬의 매 턴 게이지 보고

        $this->assertCount(1, $this->pushes->sent);

        $push = $this->pushes->sent[0];
        $this->assertSame($this->admin->id, $push['user_id']);
        $this->assertSame('aiw_job', $push['data']['type']);
        $this->assertSame('input', $push['data']['event']);
        $this->assertSame($job->id, $push['data']['job_id']);
        $this->assertSame($this->projectId, $push['data']['project_id']);
        $this->assertStringContainsString('선택지 2개', $push['body']);

        // 다음 턴의 회신은 다시 알린다.
        $states->transition($job, AiwJobStatus::WaitingInput);
        $this->assertCount(2, $this->pushes->sent);
    }

    public function test_완료와_실패는_알리고_사람이_누른_취소는_알리지_않는다(): void
    {
        $states = app(JobStateMachine::class);

        $done = $this->job([
            'status' => AiwJobStatus::Running,
            'auto_deploy' => true,
            'auto_deploy_target_id' => $this->target->id,
        ]);
        $states->transition($done, AiwJobStatus::Completed, ['result_summary' => 'README 를 고쳤습니다.']);

        $failed = $this->job(['status' => AiwJobStatus::Running]);
        $states->transition($failed, AiwJobStatus::Failed, ['error_message' => '담당자가 응답하지 않아 중단되었습니다.']);

        $cancelled = $this->job(['status' => AiwJobStatus::Running]);
        $states->transition($cancelled, AiwJobStatus::Cancelled, ['error_message' => '사용자가 취소했습니다.']);

        $this->assertSame(['completed', 'failed'], $this->events());
        $this->assertStringContainsString('배포', $this->pushes->sent[0]['body']);
        $this->assertStringContainsString('응답하지 않아', $this->pushes->sent[1]['body']);
    }

    public function test_승인_요청은_대기_중인_게_없을_때의_첫_건만_알린다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Running]);
        $service = app(PermissionService::class);

        $first = $service->request($job, 'k1', 'Bash', ['command' => 'npm test']);
        $second = $service->request($job, 'k2', 'Bash', ['command' => 'npm run build']);   // 첫 건이 아직 대기 중
        $service->request($job, 'k1', 'Bash', ['command' => 'npm test']);                  // 재요청 — 새로 만들지 않는다

        $service->decide($first, $this->admin, true);
        $service->decide($second, $this->admin, true);
        $service->request($job, 'k3', 'Bash', ['command' => 'git status']);                // 대기 중인 게 없으니 다시 알린다

        $this->assertSame(['permission', 'permission'], $this->events());
        $this->assertStringContainsString('npm test', $this->pushes->sent[0]['body']);
        $this->assertStringContainsString('git status', $this->pushes->sent[1]['body']);
    }

    public function test_자동_배포_결과를_알리고_사람이_누른_배포는_알리지_않는다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Completed]);

        $auto = $this->deploy($job, automatic: true);
        $manual = $this->deploy($job, automatic: false);

        $auto->forceFill(['status' => 'running'])->save();
        $auto->forceFill(['status' => 'failed', 'exit_code' => 1, 'finished_at' => now()])->save();
        $manual->forceFill(['status' => 'succeeded', 'finished_at' => now()])->save();

        $this->assertSame(['deploy_failed'], $this->events());
        $this->assertStringContainsString('운영 배포', $this->pushes->sent[0]['body']);
        $this->assertStringContainsString('exit 1', $this->pushes->sent[0]['body']);
    }

    public function test_자동_진행의_커밋_푸시가_실패하면_배포_중단을_알린다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Completed]);

        $publish = AiwPublish::create([
            'job_id' => $job->id, 'requested_by' => $this->admin->id, 'automatic' => true,
            'source_branch' => 'aiw/job-'.$job->id, 'target_branch' => 'master', 'status' => 'pending',
        ]);
        $publish->forceFill(['status' => 'failed', 'finished_at' => now()])->save();

        $this->assertSame(['publish_failed'], $this->events());
    }

    public function test_관리자가_아닌_작성자에게는_보내지_않는다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Running, 'created_by' => $this->member->id]);

        app(JobStateMachine::class)->transition($job, AiwJobStatus::Failed, ['error_message' => 'x']);

        $this->assertSame([], $this->pushes->sent);
    }

    // ── 도우미 ──────────────────────────────────────────────────────────────

    private function as(User $user): static
    {
        return $this->withHeader('Authorization', 'Bearer '.MobileToken::issue($user)['access_token']);
    }

    private function base(): string
    {
        return "/api/mobile/projects/{$this->projectId}/ai-works";
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => '문구 수정',
            'agent_id' => $this->agent->id,
            'instruction' => 'README 에 한 줄 추가',
            'use_branch' => true,
        ], $overrides);
    }

    private function job(array $overrides = []): AiwJob
    {
        $job = AiwJob::create(array_merge([
            'project_id' => $this->projectId,
            'agent_id' => $this->agent->id,
            'title' => '테스트 지시',
            'instruction' => 'README 한 줄 추가',
            'mode' => 'interactive',
            'context_limit_tokens' => 200000,
            'allowed_tools' => ['Read', 'Edit'],
            'cost_limit_usd' => 2.0,
            'created_by' => $this->admin->id,
        ], $overrides));

        app(MessageWriter::class)->appendOne($job, [
            'role' => 'user', 'content' => $job->instruction, 'user_id' => $job->created_by, 'session_index' => 0,
        ]);

        return $job;
    }

    private function reply(AiwJob $job, string $content, array $choices = []): void
    {
        app(MessageWriter::class)->appendOne($job, [
            'role' => 'assistant', 'content' => $content, 'choices' => $choices ?: null, 'session_index' => 0,
        ]);
    }

    private function deploy(AiwJob $job, bool $automatic): AiwDeploy
    {
        return AiwDeploy::create([
            'target_id' => $this->target->id, 'job_id' => $job->id, 'requested_by' => $this->admin->id,
            'automatic' => $automatic, 'status' => 'queued', 'created_at' => now(),
        ]);
    }

    /** @return list<string> 보낸 푸시의 event 값 */
    private function events(): array
    {
        return array_map(fn (array $p) => $p['data']['event'], $this->pushes->sent);
    }
}

/** FCM 대신 보낸 내용을 모아 둔다. */
class RecordingNotifier extends AiwNotifier
{
    /** @var list<array{user_id:int, title:string, body:string, data:array}> */
    public array $sent = [];

    protected function send(int $userId, string $title, string $body, array $data): void
    {
        $this->sent[] = ['user_id' => $userId, 'title' => $title, 'body' => $body, 'data' => $data];
    }
}
