<?php

namespace Tests\Feature\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\JobLogAppended;
use App\Events\AiWork\JobMessageAppended;
use App\Events\AiWork\JobStatusChanged;
use App\Events\AiWork\PermissionRequested;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobMessage;
use App\Models\AiWork\AiwPermissionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Phase 3 — 데몬 API·토큰 인증·브로드캐스트 인가.
 */
class DaemonApiTest extends TestCase
{
    use RefreshDatabase;

    private AiwAgent $agent;

    private string $token;

    private AiwJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\MaintenanceCheckMiddleware::class,
            \App\Http\Middleware\LogPageAccess::class,
            \App\Http\Middleware\CollabParticipantMiddleware::class,
        ]);

        $user = User::factory()->create();

        $this->token = AiwAgent::generateToken();
        $this->agent = AiwAgent::create([
            'name' => '테스트 PC',
            'token_hash' => AiwAgent::hashToken($this->token),
            'user_id' => $user->id,
            'expires_at' => now()->addDays(90),
        ]);

        $projectId = DB::table('projects')->insertGetId([
            'name' => 'AI Works 테스트',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AiwAgentProject::create([
            'agent_id' => $this->agent->id,
            'project_id' => $projectId,
            'local_path' => 'E:\\work\\sample',
            'default_branch' => 'master',
        ]);

        $this->job = AiwJob::create([
            'project_id' => $projectId,
            'agent_id' => $this->agent->id,
            'title' => '테스트 지시',
            'instruction' => 'README 한 줄 추가',
            'context_limit_tokens' => 200000,
            'allowed_tools' => ['Read', 'Edit'],
            'cost_limit_usd' => 2.0,
            'created_by' => $user->id,
            'status' => AiwJobStatus::Dispatched,
        ]);
    }

    private function daemon(?string $token = null)
    {
        return $this->withHeader('Authorization', 'Bearer '.($token ?? $this->token));
    }

    // ── 인증 ────────────────────────────────────────────────────────────────

    public function test_토큰_없이는_401(): void
    {
        $this->postJson('/api/aiw/heartbeat')->assertStatus(401);
    }

    public function test_잘못된_토큰은_401(): void
    {
        $this->daemon('aiw_wrong')->postJson('/api/aiw/heartbeat')->assertStatus(401);
    }

    public function test_만료된_토큰은_401(): void
    {
        $this->agent->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->daemon()->postJson('/api/aiw/heartbeat')->assertStatus(401);
    }

    public function test_허용되지_않은_IP는_403(): void
    {
        $this->agent->forceFill(['allowed_ips' => ['10.0.0.1']])->save();

        $this->daemon()->postJson('/api/aiw/heartbeat')->assertStatus(403);
    }

    public function test_다른_에이전트의_job은_404(): void
    {
        $other = AiwAgent::create([
            'name' => '남의 PC',
            'token_hash' => AiwAgent::hashToken($otherToken = AiwAgent::generateToken()),
            'user_id' => User::factory()->create()->id,
        ]);

        // 존재하는 job 이지만 내 것이 아니면 403 이 아니라 404 여야 한다.
        $this->daemon($otherToken)
            ->postJson("/api/aiw/jobs/{$this->job->id}/status", ['status' => 'running'])
            ->assertStatus(404);

        $this->assertNotNull($other->id);
    }

    // ── 하트비트 / 대기 job ─────────────────────────────────────────────────

    public function test_하트비트가_last_seen과_capabilities를_갱신한다(): void
    {
        $response = $this->daemon()->postJson('/api/aiw/heartbeat', [
            'capabilities' => ['max_parallel_jobs' => 2, 'os' => 'Windows'],
        ]);

        $response->assertOk()
            ->assertJsonPath('pending_job_ids.0', $this->job->id);

        $agent = $this->agent->fresh();
        $this->assertNotNull($agent->last_seen_at);
        $this->assertSame(2, $agent->capabilities['max_parallel_jobs']);
        $this->assertTrue($agent->is_online);
    }

    public function test_대기_job에_local_path가_실린다(): void
    {
        $this->daemon()->getJson('/api/aiw/jobs/pending')
            ->assertOk()
            ->assertJsonPath('jobs.0.job_id', $this->job->id)
            ->assertJsonPath('jobs.0.local_path', 'E:\\work\\sample')
            ->assertJsonPath('jobs.0.default_branch', 'master');
    }

    // ── 브로드캐스트 인가 ───────────────────────────────────────────────────

    public function test_자기_채널만_인가받는다(): void
    {
        config(['broadcasting.connections.reverb.key' => 'k', 'broadcasting.connections.reverb.secret' => 's']);

        $ok = $this->daemon()->postJson('/api/aiw/broadcasting/auth', [
            'channel_name' => 'private-aiw.agent.'.$this->agent->id,
            'socket_id' => '1.1',
        ]);

        $ok->assertOk();
        $this->assertSame('k:'.hash_hmac('sha256', '1.1:private-aiw.agent.'.$this->agent->id, 's'), $ok->json('auth'));

        $this->daemon()->postJson('/api/aiw/broadcasting/auth', [
            'channel_name' => 'private-aiw.agent.999999',
            'socket_id' => '1.1',
        ])->assertStatus(403);
    }

    // ── 보고 엔드포인트 ─────────────────────────────────────────────────────

    public function test_start가_세션을_열고_running으로_만든다(): void
    {
        Event::fake([JobStatusChanged::class]);

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/start", ['session_id' => 'sess-1'])
            ->assertOk()
            ->assertJsonPath('status', 'running');

        $job = $this->job->fresh();
        $this->assertSame('sess-1', $job->currentSessionId());
        $this->assertNotNull($job->started_at);

        Event::assertDispatched(JobStatusChanged::class);
    }

    public function test_resumed_start는_대기중_승인을_만료시킨다(): void
    {
        AiwPermissionRequest::create([
            'job_id' => $this->job->id, 'request_key' => 'old',
            'tool_name' => 'Bash', 'tool_input' => [], 'created_at' => now(),
        ]);

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/start", [
            'session_id' => 'sess-1', 'resumed' => true,
        ])->assertOk();

        $this->assertSame('expired', AiwPermissionRequest::first()->status);
    }

    public function test_로그_배치는_seq_중복을_무시한다(): void
    {
        Event::fake([JobLogAppended::class]);

        $payload = ['logs' => [
            ['seq' => 1, 'type' => 'system', 'content' => 'a'],
            ['seq' => 2, 'type' => 'tool_use', 'content' => 'b'],
        ]];

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/logs", $payload)
            ->assertOk()->assertJsonPath('accepted', 2);

        // 같은 배치를 다시 보내도 늘지 않는다.
        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/logs", $payload)->assertOk();

        $this->assertDatabaseCount('aiw_job_logs', 2);
    }

    public function test_status는_허용되지_않은_전이를_409로_막는다(): void
    {
        // dispatched -> waiting_input 은 허용표에 없다.
        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/status", ['status' => 'waiting_input'])
            ->assertStatus(409);
    }

    public function test_context_tokens는_덮어쓴다(): void
    {
        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/start", ['session_id' => 's']);

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/status", [
            'status' => 'running', 'context_tokens' => 50000, 'cost_usd' => 0.5,
        ])->assertOk();

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/status", [
            'status' => 'running', 'context_tokens' => 60000, 'cost_usd' => 0.9,
        ])->assertOk();

        $job = $this->job->fresh();
        // 누적이었다면 110000 이 됐을 것이다.
        $this->assertSame(60000, $job->context_tokens);
        $this->assertSame('0.9000', (string) $job->cost_usd);
    }

    public function test_승인요청은_idempotent하다(): void
    {
        Event::fake([PermissionRequested::class]);

        $payload = ['request_key' => 'uuid-1', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'npm test']];

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/permissions", $payload)
            ->assertOk()->assertJsonPath('decision.behavior', 'pending');

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/permissions", $payload)->assertOk();

        $this->assertDatabaseCount('aiw_permission_requests', 1);
        Event::assertDispatchedTimes(PermissionRequested::class, 1);
    }

    public function test_이미_결정된_승인은_즉시_결정을_돌려준다(): void
    {
        AiwPermissionRequest::create([
            'job_id' => $this->job->id, 'request_key' => 'k1', 'tool_name' => 'Bash',
            'tool_input' => [], 'status' => 'denied', 'deny_reason' => '위험', 'created_at' => now(),
        ]);

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/permissions", [
            'request_key' => 'k1', 'tool_name' => 'Bash',
        ])->assertOk()->assertJsonPath('decision.behavior', 'deny')
            ->assertJsonPath('decision.message', '위험');
    }

    public function test_inbox가_미전달_메시지와_결정을_돌려준다(): void
    {
        AiwJobMessage::create([
            'job_id' => $this->job->id, 'seq' => 1, 'role' => 'user',
            'content' => '이거 해줘', 'created_at' => now(),
        ]);
        AiwJobMessage::create([
            'job_id' => $this->job->id, 'seq' => 2, 'role' => 'user',
            'content' => '이미 전달', 'delivered_at' => now(), 'created_at' => now(),
        ]);

        $this->daemon()->getJson("/api/aiw/jobs/{$this->job->id}/inbox")
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.content', '이거 해줘');
    }

    public function test_delivered가_전달대기를_해제한다(): void
    {
        Event::fake([JobMessageAppended::class]);

        $msg = AiwJobMessage::create([
            'job_id' => $this->job->id, 'seq' => 1, 'role' => 'user',
            'content' => 'x', 'created_at' => now(),
        ]);

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/messages/{$msg->id}/delivered")->assertOk();

        $this->assertNotNull($msg->fresh()->delivered_at);
        Event::assertDispatched(JobMessageAppended::class);
    }

    public function test_handover가_컨텍스트를_리셋하고_요약을_남긴다(): void
    {
        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/start", ['session_id' => 's1']);
        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/status", [
            'status' => 'running', 'context_tokens' => 150000,
        ]);

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/handover", [
            'ended_session_id' => 's1',
            'new_session_id' => 's2',
            'document_path' => 'docs/aiw/handover/job-1-1.md',
            'summary' => '## 작업 목표 ...',
            'seq' => 10,
        ])->assertOk()->assertJsonPath('handover_count', 1);

        $job = $this->job->fresh();
        $this->assertSame(0, $job->context_tokens, '새 세션은 컨텍스트가 비어야 한다.');
        $this->assertSame('s2', $job->currentSessionId());
        $this->assertNotNull($job->session_chain[0]['ended_at']);
        $this->assertDatabaseHas('aiw_job_messages', ['job_id' => $job->id, 'role' => 'handover']);
    }

    public function test_complete는_결과를_기록하고_대기승인을_만료시킨다(): void
    {
        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/start", ['session_id' => 's']);

        AiwPermissionRequest::create([
            'job_id' => $this->job->id, 'request_key' => 'p', 'tool_name' => 'Bash',
            'tool_input' => [], 'created_at' => now(),
        ]);

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/complete", [
            'result_summary' => '끝',
            'changed_files' => ['README.md'],
            'git_diff' => 'diff --git a/README.md',
            'cost_usd' => 0.42,
            'duration_ms' => 1234,
        ])->assertOk()->assertJsonPath('status', 'completed');

        $job = $this->job->fresh();
        $this->assertSame('끝', $job->result_summary);
        $this->assertSame(['README.md'], $job->changed_files);
        $this->assertNotNull($job->finished_at);
        $this->assertSame('expired', AiwPermissionRequest::first()->status);
    }

    public function test_큰_diff는_파일로_옮겨진다(): void
    {
        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/start", ['session_id' => 's']);

        $big = str_repeat('x', (int) config('aiw.git_diff_max_inline') + 100);

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/complete", ['git_diff' => $big])->assertOk();

        $job = $this->job->fresh();
        $this->assertNull($job->git_diff);
        $this->assertSame("aiw/diffs/job-{$job->id}.diff", $job->git_diff_path);
    }

    public function test_비용_상한_초과는_job을_중단시키고_플래그로_알린다(): void
    {
        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/start", ['session_id' => 's']);

        // 상한($2)을 크게 넘는 누적 비용을 보고하면 CostGuard 가 job 을 끊는다.
        $response = $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/status", [
            'status' => 'running', 'cost_usd' => 99.0,
        ]);

        $response->assertOk()
            ->assertJsonPath('cost_over_limit', true)
            ->assertJsonPath('cancel_requested', true)
            ->assertJsonPath('status', 'failed');

        $this->assertSame(AiwJobStatus::Failed, $this->job->fresh()->status);
    }

    public function test_상한_이내면_중단되지_않는다(): void
    {
        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/start", ['session_id' => 's']);

        $this->daemon()->postJson("/api/aiw/jobs/{$this->job->id}/status", [
            'status' => 'running', 'cost_usd' => 0.5,
        ])->assertOk()
            ->assertJsonPath('cost_over_limit', false)
            ->assertJsonPath('cancel_requested', false);
    }
}
