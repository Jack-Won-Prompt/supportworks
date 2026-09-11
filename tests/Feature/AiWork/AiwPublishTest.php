<?php

namespace Tests\Feature\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\PublishRequested;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwPublish;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * 결과 반영(커밋·푸시).
 *
 * 담당자는 push 를 할 수 없다. 사람이 결과를 보고 누른 버튼만이 실행시킨다.
 */
class AiwPublishTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private AiwAgent $agent;

    private string $token;

    private int $projectId;

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
            'name' => '반영 테스트', 'created_by' => $this->member->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('project_members')->insert([
            'project_id' => $this->projectId, 'user_id' => $this->member->id,
            'role' => 'manager', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->token = AiwAgent::generateToken();
        $this->agent = AiwAgent::create([
            'name' => '테스트 PC', 'token_hash' => AiwAgent::hashToken($this->token),
            'user_id' => $this->member->id, 'expires_at' => now()->addDays(90),
            // 온라인이어야 요청을 받을 수 있다.
            'last_seen_at' => now(),
        ]);

        AiwAgentProject::create([
            'agent_id' => $this->agent->id, 'project_id' => $this->projectId,
            'local_path' => 'E:/work/sample', 'default_branch' => 'main',
        ]);
    }

    private function job(array $overrides = []): AiwJob
    {
        return AiwJob::create(array_merge([
            'project_id' => $this->projectId, 'agent_id' => $this->agent->id,
            'title' => '버튼 위치 변경', 'instruction' => '옮겨 주세요',
            'context_limit_tokens' => 200000, 'allowed_tools' => ['Read', 'Edit'],
            'cost_limit_usd' => 2.0, 'created_by' => $this->member->id,
            'use_branch' => true, 'status' => AiwJobStatus::Completed,
        ], $overrides));
    }

    private function publish(AiwJob $job, array $data = [])
    {
        return $this->actingAs($this->member)
            ->post(route('projects.ai-works.publish', [$this->projectId, $job]), $data);
    }

    // ── 요청 ────────────────────────────────────────────────────────────────

    public function test_끝난_작업을_담당자에게_올리라고_요청한다(): void
    {
        Event::fake([PublishRequested::class]);
        $job = $this->job();

        $this->publish($job, ['commit_message' => '헤더로 이동'])->assertRedirect();

        $publish = AiwPublish::firstOrFail();

        $this->assertSame('pending', $publish->status);
        $this->assertSame('aiw/job-'.$job->id, $publish->source_branch);
        $this->assertSame('main', $publish->target_branch);
        $this->assertSame('헤더로 이동', $publish->commit_message);
        $this->assertSame($this->member->id, $publish->requested_by, '누가 눌렀는지 남아야 한다.');

        Event::assertDispatched(PublishRequested::class);
    }

    public function test_커밋_메시지를_비우면_제목을_쓴다(): void
    {
        Event::fake([PublishRequested::class]);

        $this->publish($this->job())->assertRedirect();

        $this->assertStringContainsString('버튼 위치 변경', AiwPublish::firstOrFail()->commit_message);
    }

    public function test_끝나지_않은_작업은_올릴_수_없다(): void
    {
        Event::fake([PublishRequested::class]);

        $this->publish($this->job(['status' => AiwJobStatus::Running]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, AiwPublish::count());
    }

    public function test_브랜치_분리를_끈_작업은_올릴_수_없다(): void
    {
        Event::fake([PublishRequested::class]);

        // 변경이 현재 브랜치에 섞여 있어 이 작업만 골라낼 수 없다.
        $this->publish($this->job(['use_branch' => false]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, AiwPublish::count());
    }

    public function test_담당자가_오프라인이면_거부한다(): void
    {
        Event::fake([PublishRequested::class]);
        $this->agent->forceFill(['last_seen_at' => now()->subDay()])->saveQuietly();

        $this->publish($this->job())->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, AiwPublish::count());
    }

    public function test_중복_요청을_막는다(): void
    {
        Event::fake([PublishRequested::class]);
        $job = $this->job();

        $this->publish($job)->assertRedirect();

        // 같은 저장소에 두 번 밀어 넣으면 서로 덮거나 충돌한다.
        $this->publish($job)->assertRedirect()->assertSessionHas('error');

        $this->assertSame(1, AiwPublish::count());
    }

    public function test_이미_올린_작업은_다시_올리지_않는다(): void
    {
        Event::fake([PublishRequested::class]);
        $job = $this->job();

        $this->publish($job)->assertRedirect();
        AiwPublish::firstOrFail()->forceFill(['status' => 'succeeded'])->save();

        $this->publish($job)->assertRedirect()->assertSessionHas('error');

        $this->assertSame(1, AiwPublish::count());
    }

    // ── 결과 보고 ───────────────────────────────────────────────────────────

    private function daemon()
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->token);
    }

    public function test_담당자가_결과를_보고한다(): void
    {
        Event::fake();
        $job = $this->job();
        $this->publish($job);
        $publish = AiwPublish::firstOrFail();

        $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/publishes/{$publish->id}", [
            'status'     => 'succeeded',
            'output'     => "커밋: abc1234\n머지 완료\n푸시 완료",
            'commit_sha' => 'abc1234',
        ])->assertOk();

        $publish->refresh();

        $this->assertSame('succeeded', $publish->status);
        $this->assertSame('abc1234', $publish->commit_sha);
        $this->assertNotNull($publish->finished_at);
        // 실행 내용이 남아야 나중에 무슨 일이 있었는지 볼 수 있다.
        $this->assertStringContainsString('푸시 완료', $publish->output);
    }

    public function test_실패는_사유와_함께_활동_로그에도_남는다(): void
    {
        Event::fake();
        $job = $this->job();
        $this->publish($job);
        $publish = AiwPublish::firstOrFail();

        $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/publishes/{$publish->id}", [
            'status' => 'failed',
            'output' => '머지 충돌로 중단했습니다',
        ])->assertOk();

        $this->assertSame('failed', $publish->fresh()->status);
        $this->assertDatabaseHas('aiw_job_logs', ['job_id' => $job->id, 'type' => 'error']);
    }

    public function test_끝난_보고는_덮지_않는다(): void
    {
        Event::fake();
        $job = $this->job();
        $this->publish($job);
        $publish = AiwPublish::firstOrFail();

        $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/publishes/{$publish->id}", [
            'status' => 'succeeded', 'output' => '첫 결과', 'commit_sha' => 'aaa',
        ])->assertOk();

        // 재전송이 결과를 바꾸면 기록을 믿을 수 없다.
        $this->daemon()->postJson("/api/aiw/jobs/{$job->id}/publishes/{$publish->id}", [
            'status' => 'failed', 'output' => '뒤늦은 실패',
        ])->assertOk();

        $publish->refresh();

        $this->assertSame('succeeded', $publish->status);
        $this->assertSame('첫 결과', $publish->output);
    }

    public function test_남의_job_의_보고는_404다(): void
    {
        Event::fake();
        $job = $this->job();
        $this->publish($job);
        $publish = AiwPublish::firstOrFail();

        $otherToken = AiwAgent::generateToken();
        AiwAgent::create([
            'name' => '남의 PC', 'token_hash' => AiwAgent::hashToken($otherToken),
            'user_id' => User::factory()->create()->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->postJson("/api/aiw/jobs/{$job->id}/publishes/{$publish->id}", ['status' => 'succeeded'])
            ->assertStatus(404);
    }

    // ── 화면 ────────────────────────────────────────────────────────────────

    public function test_끝난_작업에_반영_버튼이_보인다(): void
    {
        $job = $this->job();

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->projectId, $job]))
            ->assertOk()
            ->assertSee('결과 반영')
            ->assertSee('커밋 &amp; 푸시', false);
    }

    public function test_브랜치_없는_작업에는_반영_영역이_없다(): void
    {
        $job = $this->job(['use_branch' => false]);

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->projectId, $job]))
            ->assertOk()
            ->assertDontSee('결과 반영');
    }
}
