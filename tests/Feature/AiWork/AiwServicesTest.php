<?php

namespace Tests\Feature\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\JobCancelRequested;
use App\Events\AiWork\JobDispatched;
use App\Events\AiWork\PermissionDecided;
use App\Exceptions\AiWork\InvalidJobTransitionException;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwPermissionRequest;
use App\Models\User;
use App\Services\AiWork\CostGuard;
use App\Services\AiWork\HandoverService;
use App\Services\AiWork\JobDispatcher;
use App\Services\AiWork\JobStateMachine;
use App\Services\AiWork\PermissionService;
use App\Services\AiWork\ToolPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 4 — 서비스 계층·정책·스케줄러.
 */
class AiwServicesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private AiwAgent $agent;

    private int $projectId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'member']);

        $this->agent = AiwAgent::create([
            'name' => 'PC',
            'token_hash' => AiwAgent::hashToken(AiwAgent::generateToken()),
            'user_id' => $this->owner->id,
            'last_seen_at' => now(),
        ]);

        $this->projectId = DB::table('projects')->insertGetId([
            'name' => '서비스 테스트',
            'created_by' => $this->owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AiwAgentProject::create([
            'agent_id' => $this->agent->id,
            'project_id' => $this->projectId,
            'local_path' => 'E:\\work\\sample',
            'default_branch' => 'master',
        ]);
    }

    private function job(array $overrides = []): AiwJob
    {
        return AiwJob::create(array_merge([
            'project_id' => $this->projectId,
            'agent_id' => $this->agent->id,
            'title' => '지시',
            'instruction' => '내용',
            'context_limit_tokens' => 200000,
            'allowed_tools' => ['Read', 'Edit'],
            'cost_limit_usd' => 2.0,
            'created_by' => $this->owner->id,
        ], $overrides));
    }

    private function member(string $role): User
    {
        $user = User::factory()->create(['role' => 'member']);
        DB::table('project_members')->insert([
            'project_id' => $this->projectId,
            'user_id' => $user->id,
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    // ── JobStateMachine ─────────────────────────────────────────────────────

    public function test_허용되지_않은_전이는_예외를_던진다(): void
    {
        $job = $this->job();   // queued

        $this->expectException(InvalidJobTransitionException::class);

        app(JobStateMachine::class)->transition($job, AiwJobStatus::WaitingInput);
    }

    public function test_전이가_타임스탬프를_자동으로_채운다(): void
    {
        $states = app(JobStateMachine::class);
        $job = $this->job();

        $states->transition($job, AiwJobStatus::Dispatched);
        $this->assertNotNull($job->fresh()->dispatched_at);

        $states->transition($job, AiwJobStatus::Running);
        $this->assertNotNull($job->fresh()->started_at);

        $states->transition($job, AiwJobStatus::Completed);
        $this->assertNotNull($job->fresh()->finished_at);
    }

    public function test_종료_전이가_대기중_승인을_만료시킨다(): void
    {
        $states = app(JobStateMachine::class);
        $job = $this->job(['status' => AiwJobStatus::Running]);

        AiwPermissionRequest::create([
            'job_id' => $job->id, 'request_key' => 'k',
            'tool_name' => 'Bash', 'tool_input' => [], 'created_at' => now(),
        ]);

        $states->transition($job, AiwJobStatus::Cancelled);

        $this->assertSame('expired', AiwPermissionRequest::first()->status);
    }

    public function test_종료된_job의_instruction_수정이_차단된다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Running]);
        app(JobStateMachine::class)->transition($job, AiwJobStatus::Completed);

        $this->expectException(\RuntimeException::class);

        $job->fresh()->update(['instruction' => '바꿔치기']);
    }

    // ── ToolPolicy ──────────────────────────────────────────────────────────

    public function test_미지원_툴은_거부된다(): void
    {
        $this->expectException(ValidationException::class);

        app(ToolPolicy::class)->sanitize(['Read', 'RmRf']);
    }

    public function test_툴_목록은_중복_제거되고_순서가_정규화된다(): void
    {
        $out = app(ToolPolicy::class)->sanitize(['Grep', 'Read', 'Read']);

        $this->assertSame(['Read', 'Grep'], $out);
    }

    public function test_acceptEdits여도_Bash는_자동승인되지_않는다(): void
    {
        $policy = app(ToolPolicy::class);

        $this->assertTrue($policy->isAutoApprovable('Edit', 'acceptEdits'));
        $this->assertTrue($policy->isAutoApprovable('Write', 'acceptEdits'));

        // 임의 명령 실행까지 자동 승인되면 승인 카드라는 방어선이 사라진다.
        $this->assertFalse($policy->isAutoApprovable('Bash', 'acceptEdits'));
        $this->assertFalse($policy->isAutoApprovable('Bash', 'default'));
        $this->assertFalse($policy->isAutoApprovable('Edit', 'default'));
    }

    public function test_Bash_선택시_경고대상이다(): void
    {
        $policy = app(ToolPolicy::class);

        $this->assertTrue($policy->requiresBashWarning(['Read', 'Bash']));
        $this->assertFalse($policy->requiresBashWarning(['Read', 'Edit']));
    }

    public function test_WebFetch는_기본_비활성이다(): void
    {
        $this->assertContains('WebFetch', app(ToolPolicy::class)->optIn());
        $this->assertNotContains('WebFetch', ToolPolicy::defaults());
    }

    // ── PermissionService ───────────────────────────────────────────────────

    public function test_같은_request_key로_두번_요청해도_한건이다(): void
    {
        $service = app(PermissionService::class);
        $job = $this->job();

        $a = $service->request($job, 'k1', 'Bash', ['command' => 'ls']);
        $b = $service->request($job, 'k1', 'Bash', ['command' => 'ls']);

        $this->assertTrue($a->is($b));
        $this->assertDatabaseCount('aiw_permission_requests', 1);
    }

    public function test_이미_결정된_승인은_재결정되지_않는다(): void
    {
        $service = app(PermissionService::class);
        $job = $this->job();
        $req = $service->request($job, 'k1', 'Bash', []);

        $first = $service->decide($req, $this->owner, false, '위험합니다');
        $second = $service->decide($req->fresh(), $this->owner, true, null);

        $this->assertTrue($first);
        $this->assertFalse($second, '두 번째 결정은 무시되어야 한다.');
        $this->assertSame('denied', $req->fresh()->status);
        $this->assertSame('위험합니다', $req->fresh()->deny_reason);
    }

    public function test_expireStale은_시간이_지난_것만_만료시킨다(): void
    {
        Event::fake([PermissionDecided::class]);

        $job = $this->job();
        $timeout = (int) config('aiw.permission_timeout_min', 30);

        $old = AiwPermissionRequest::create([
            'job_id' => $job->id, 'request_key' => 'old', 'tool_name' => 'Bash', 'tool_input' => [],
        ]);
        // created_at 은 fillable 이 아니므로 create() 로는 과거 시각을 심을 수 없다.
        $old->forceFill(['created_at' => now()->subMinutes($timeout + 1)])->save();
        $fresh = AiwPermissionRequest::create([
            'job_id' => $job->id, 'request_key' => 'fresh', 'tool_name' => 'Bash', 'tool_input' => [],
        ]);

        $count = app(PermissionService::class)->expireStale();

        $this->assertSame(1, $count);
        $this->assertSame('expired', $old->fresh()->status);
        $this->assertSame('timeout', $old->fresh()->deny_reason);
        $this->assertSame('pending', $fresh->fresh()->status);

        Event::assertDispatched(PermissionDecided::class);
    }

    // ── CostGuard ───────────────────────────────────────────────────────────

    public function test_비용_상한_초과시_failed_전이와_취소_이벤트(): void
    {
        Event::fake([JobCancelRequested::class]);

        $job = $this->job(['status' => AiwJobStatus::Running, 'cost_limit_usd' => 1.0]);

        $over = app(CostGuard::class)->apply($job, 5.0);

        $this->assertTrue($over);
        $this->assertSame(AiwJobStatus::Failed, $job->fresh()->status);
        $this->assertStringContainsString('비용 상한', $job->fresh()->error_message);

        Event::assertDispatched(JobCancelRequested::class);
    }

    public function test_상한_이내면_비용만_갱신된다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Running, 'cost_limit_usd' => 10.0]);

        $over = app(CostGuard::class)->apply($job, 3.5);

        $this->assertFalse($over);
        $this->assertSame(AiwJobStatus::Running, $job->fresh()->status);
        $this->assertSame('3.5000', (string) $job->fresh()->cost_usd);
    }

    // ── JobDispatcher ───────────────────────────────────────────────────────

    public function test_오프라인_에이전트면_queued로_남는다(): void
    {
        Event::fake([JobDispatched::class]);

        $this->agent->forceFill(['last_seen_at' => now()->subHour()])->save();
        $job = $this->job();

        $sent = app(JobDispatcher::class)->dispatch($job->fresh());

        $this->assertFalse($sent, '오프라인이면 발행하지 않는다.');
        $this->assertSame(AiwJobStatus::Queued, $job->fresh()->status);
        Event::assertNotDispatched(JobDispatched::class);
    }

    public function test_온라인이면_local_path와_함께_발행된다(): void
    {
        Event::fake([JobDispatched::class]);

        $job = $this->job();

        $this->assertTrue(app(JobDispatcher::class)->dispatch($job));
        $this->assertSame(AiwJobStatus::Dispatched, $job->fresh()->status);

        Event::assertDispatched(JobDispatched::class, function (JobDispatched $e) {
            return $e->localPath === 'E:\\work\\sample' && $e->defaultBranch === 'master';
        });
    }

    public function test_매핑이_없으면_발행할_수_없다(): void
    {
        AiwAgentProject::query()->delete();

        $this->expectException(\RuntimeException::class);

        app(JobDispatcher::class)->dispatch($this->job());
    }

    // ── HandoverService ─────────────────────────────────────────────────────

    public function test_인수인계가_컨텍스트를_리셋하고_비용은_유지한다(): void
    {
        $job = $this->job([
            'status' => AiwJobStatus::Handover,
            'context_tokens' => 150000,
            'cost_usd' => 1.2,
            'session_chain' => [['session_id' => 's1', 'started_at' => now()->toIso8601String()]],
        ]);

        app(HandoverService::class)->record($job, [
            'ended_session_id' => 's1',
            'new_session_id' => 's2',
            'summary' => '## 작업 목표',
            'seq' => 5,
        ]);

        $job = $job->fresh();
        $this->assertSame(0, $job->context_tokens);
        $this->assertSame('1.2000', (string) $job->cost_usd, '비용은 job 전체 누적이라 리셋하지 않는다.');
        $this->assertSame(1, $job->handover_count);
        $this->assertSame('s2', $job->currentSessionId());
        $this->assertSame(AiwJobStatus::Running, $job->status);
    }

    public function test_CLAUDE_md_승격은_파일을_쓰지_않고_후속job을_만든다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Running]);
        app(JobStateMachine::class)->transition($job, AiwJobStatus::Completed);

        $follow = app(HandoverService::class)->promoteToClaudeMd(
            $job->fresh(),
            ['커밋 전 항상 테스트를 돌린다'],
            $this->owner,
        );

        $this->assertSame($job->id, $follow->parent_job_id);
        $this->assertSame('batch', $follow->mode);
        $this->assertStringContainsString('CLAUDE.md', $follow->instruction);
        $this->assertStringContainsString('커밋 전 항상 테스트를 돌린다', $follow->instruction);
        // 편집 대상이 문서 한 개라 Bash 는 필요 없다.
        $this->assertNotContains('Bash', $follow->allowed_tools);
    }

    // ── 정책 ────────────────────────────────────────────────────────────────

    public function test_비멤버는_job을_볼_수_없다(): void
    {
        $job = $this->job();
        $outsider = User::factory()->create(['role' => 'member']);

        $this->assertFalse(Gate::forUser($outsider)->allows('view', $job));
    }

    public function test_viewer는_조회만_가능하다(): void
    {
        $job = $this->job();
        $viewer = $this->member('viewer');

        $this->assertTrue(Gate::forUser($viewer)->allows('view', $job));
        $this->assertFalse(Gate::forUser($viewer)->allows('sendMessage', $job));
        $this->assertFalse(Gate::forUser($viewer)->allows('decidePermission', $job));
        $this->assertFalse(Gate::forUser($viewer)->allows('cancel', $job));
    }

    public function test_member는_지시와_승인이_가능하다(): void
    {
        $job = $this->job();
        $member = $this->member('member');

        $this->assertTrue(Gate::forUser($member)->allows('view', $job));
        $this->assertTrue(Gate::forUser($member)->allows('sendMessage', $job));
        $this->assertTrue(Gate::forUser($member)->allows('decidePermission', $job));
    }

    public function test_작업PC_관리는_관리자만(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        $this->assertTrue(Gate::forUser($admin)->allows('manageAgents', AiwJob::class));
        $this->assertFalse(Gate::forUser($member)->allows('manageAgents', AiwJob::class));
    }

    // ── 스케줄러 커맨드 ─────────────────────────────────────────────────────

    public function test_reap_stale_jobs가_무응답_에이전트의_job을_정리한다(): void
    {
        $threshold = (int) config('aiw.offline_after_sec', 90) * 4;

        $this->agent->forceFill(['last_seen_at' => now()->subSeconds($threshold + 60)])->save();
        $running = $this->job(['status' => AiwJobStatus::Running]);
        $done = $this->job(['status' => AiwJobStatus::Completed]);

        $this->artisan('aiw:reap-stale-jobs')->assertSuccessful();

        $this->assertSame(AiwJobStatus::Failed, $running->fresh()->status);
        $this->assertStringContainsString('응답하지 않아', $running->fresh()->error_message);
        $this->assertSame(AiwJobStatus::Completed, $done->fresh()->status, '종료된 job 은 건드리지 않는다.');
    }

    public function test_reap_stale_jobs가_살아있는_에이전트는_건드리지_않는다(): void
    {
        $running = $this->job(['status' => AiwJobStatus::Running]);

        $this->artisan('aiw:reap-stale-jobs')->assertSuccessful();

        $this->assertSame(AiwJobStatus::Running, $running->fresh()->status);
    }

    public function test_expire_permissions_커맨드가_동작한다(): void
    {
        $job = $this->job();
        AiwPermissionRequest::create([
            'job_id' => $job->id, 'request_key' => 'old', 'tool_name' => 'Bash', 'tool_input' => [],
        ])->forceFill(['created_at' => now()->subHours(2)])->save();

        $this->artisan('aiw:expire-permissions')->assertSuccessful();

        $this->assertSame('expired', AiwPermissionRequest::first()->status);
    }

    public function test_prune이_오래된_로그_원문만_비운다(): void
    {
        $old = $this->job(['status' => AiwJobStatus::Completed, 'finished_at' => now()->subDays(200)]);
        $recent = $this->job(['status' => AiwJobStatus::Completed, 'finished_at' => now()->subDay()]);

        foreach ([$old, $recent] as $job) {
            DB::table('aiw_job_logs')->insert([
                'job_id' => $job->id, 'seq' => 1, 'type' => 'system',
                'content' => 'x', 'raw' => json_encode(['big' => 'payload']), 'created_at' => now(),
            ]);
        }

        $this->artisan('aiw:prune')->assertSuccessful();

        $this->assertNull(DB::table('aiw_job_logs')->where('job_id', $old->id)->value('raw'));
        $this->assertNotNull(DB::table('aiw_job_logs')->where('job_id', $recent->id)->value('raw'));
        // job 레코드 자체는 남긴다 — 감사 추적이 끊기면 안 된다.
        $this->assertDatabaseHas('aiw_jobs', ['id' => $old->id]);
    }
}
