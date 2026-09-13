<?php

namespace Tests\Feature\AiWork;

use App\Enums\AiWork\AiwFailureCode;
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
use App\Services\AiWork\AiwNotifier;
use App\Services\AiWork\CostGuard;
use App\Services\AiWork\FailureRecovery;
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

    public function test_default_모드는_어떤_툴도_자동승인하지_않는다(): void
    {
        $policy = app(ToolPolicy::class);

        // 매번 사람이 확인해야 하는 작업은 이 모드로 등록한다.
        // auto_approvable 목록과 무관하게 전부 승인을 거쳐야 한다.
        foreach (['Read', 'Edit', 'Write', 'Bash', 'Glob', 'Grep'] as $tool) {
            $this->assertFalse($policy->isAutoApprovable($tool, 'default'), $tool);
        }
    }

    public function test_acceptEdits는_설정된_목록만_자동승인한다(): void
    {
        $policy = app(ToolPolicy::class);

        foreach (config('aiw.auto_approvable') as $tool) {
            $this->assertTrue($policy->isAutoApprovable($tool, 'acceptEdits'), $tool);
        }

        // 목록 밖은 acceptEdits 여도 승인을 거친다.
        $this->assertFalse($policy->isAutoApprovable('WebFetch', 'acceptEdits'));
        $this->assertFalse($policy->isAutoApprovable('PowerShell', 'acceptEdits'));
    }

    public function test_자동승인_목록은_지원_툴의_부분집합이다(): void
    {
        // 목록에 오타가 있으면 조용히 "승인 필요"로 떨어져 아무도 눈치채지 못한다.
        $this->assertSame(
            [],
            array_diff(config('aiw.auto_approvable'), ToolPolicy::supported()),
        );
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
        $fresh = $job->fresh();

        $this->assertTrue($over);
        $this->assertSame(AiwJobStatus::Failed, $fresh->status);
        $this->assertStringContainsString('상한에 닿아 자동으로 멈췄습니다', $fresh->error_message);

        // 코드가 있어야 화면이 "어떻게 이어가나" 를 버튼으로 보여 준다.
        $this->assertSame(AiwFailureCode::CostLimit, $fresh->failureCode());
        // JSON 을 거치면 1.0 이 정수 1 로 돌아온다. 값만 본다.
        $this->assertEquals(1.0, $fresh->error_detail['limit']);
        $this->assertEquals(5.0, $fresh->error_detail['cost']);

        Event::assertDispatched(JobCancelRequested::class);
    }

    public function test_구독_로그인이면_실제_청구가_아님을_밝힌다(): void
    {
        // 금액만 보여 주면 돈이 빠져나간 것으로 읽힌다. 실제로 그 질문을 받았다.
        Event::fake([JobCancelRequested::class]);

        $this->agent->forceFill(['capabilities' => ['auth_mode' => 'subscription']])->save();

        $job = $this->job(['status' => AiwJobStatus::Running, 'cost_limit_usd' => 1.0]);
        app(CostGuard::class)->apply($job, 5.0);

        $this->assertStringContainsString('실제 청구는 없습니다', $job->fresh()->error_message);
    }

    public function test_API키_PC_면_청구_문구를_붙이지_않는다(): void
    {
        Event::fake([JobCancelRequested::class]);

        $this->agent->forceFill(['capabilities' => ['auth_mode' => 'api_key']])->save();

        $job = $this->job(['status' => AiwJobStatus::Running, 'cost_limit_usd' => 1.0]);
        app(CostGuard::class)->apply($job, 5.0);

        $message = $job->fresh()->error_message;

        $this->assertStringContainsString('비용이 상한에 닿아', $message);
        $this->assertStringNotContainsString('실제 청구는 없습니다', $message);
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

    public function test_프로젝트_멤버라도_작업_지시_가능이_아니면_막힌다(): void
    {
        // 프로젝트 역할(manager/member/viewer)만으로는 열리지 않는다.
        // 지시 한 줄이 작업 PC 의 소스를 고치고 배포까지 하기 때문이다.
        $job = $this->job();

        foreach (['manager', 'member', 'viewer'] as $role) {
            $user = $this->member($role);

            foreach (['view', 'sendMessage', 'decidePermission', 'cancel'] as $ability) {
                $this->assertFalse(
                    Gate::forUser($user)->allows($ability, $job),
                    sprintf('%s 는 %s 를 할 수 없어야 한다', $role, $ability),
                );
            }
        }
    }

    public function test_작업_지시_가능이면서_구성원이면_쓸_수_있다(): void
    {
        $job = $this->job();
        $user = $this->member('member');
        $user->forceFill(['is_aiw_operator' => true])->save();

        foreach (['view', 'sendMessage', 'decidePermission', 'cancel', 'end', 'handover'] as $ability) {
            $this->assertTrue(Gate::forUser($user->fresh())->allows($ability, $job), $ability);
        }
    }

    public function test_작업_지시_가능이어도_남의_프로젝트는_못_본다(): void
    {
        // 플래그만 켜고 아무 프로젝트나 다루게 하지 않는다. 둘 다여야 열린다.
        $job = $this->job();
        $outsider = User::factory()->create(['role' => 'member', 'is_aiw_operator' => true]);

        $this->assertFalse(Gate::forUser($outsider)->allows('view', $job));
    }

    public function test_작업_지시_가능이어도_작업PC_관리는_못_한다(): void
    {
        // 토큰은 그 PC 를 장악할 수 있는 자격이라 관리자만 다룬다.
        $user = $this->member('manager');
        $user->forceFill(['is_aiw_operator' => true])->save();

        $this->assertFalse(Gate::forUser($user->fresh())->allows('manageAgents', AiwJob::class));
    }

    public function test_관리자는_멤버가_아니어도_전부_가능하다(): void
    {
        // 관리자는 플래그와 무관하게 항상 열린다.
        $job = $this->job();
        $admin = User::factory()->create(['role' => 'admin', 'is_aiw_operator' => false]);

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $this->projectId,
            'user_id' => $admin->id,
        ]);

        foreach (['view', 'sendMessage', 'decidePermission', 'cancel', 'end', 'handover'] as $ability) {
            $this->assertTrue(Gate::forUser($admin)->allows($ability, $job), $ability);
        }
    }

    public function test_작업PC_관리는_관리자만(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        $this->assertTrue(Gate::forUser($admin)->allows('manageAgents', AiwJob::class));
        $this->assertFalse(Gate::forUser($member)->allows('manageAgents', AiwJob::class));
    }

    // ── 스케줄러 커맨드 ─────────────────────────────────────────────────────

    public function test_대기가_길어지면_다시_알린다(): void
    {
        // 첫 알림을 놓치면 작업은 세션 최대 수명까지 서 있다가 조용히 중단된다.
        config(['aiw.nudge_after_min' => 10, 'aiw.nudge_max' => 2]);

        $notifier = new NudgeRecordingNotifier();

        $this->app->instance(AiwNotifier::class, $notifier);

        // 알림은 작업 지시를 쓸 수 있는 사람에게만 간다(관리자 또는 작업 지시 가능).
        $this->owner->forceFill(['is_aiw_operator' => true])->save();

        $job = $this->job(['status' => AiwJobStatus::Running]);

        app(JobStateMachine::class)->transition($job, AiwJobStatus::WaitingInput);

        $this->assertNotNull($job->fresh()->waiting_since, '대기 시작 시각이 기록돼야 한다.');

        // 대기에 들어갈 때 이미 첫 알림이 한 번 간다. 여기서 세는 것은 그 뒤의 재알림이다.
        $nudges = fn () => count(array_filter(
            $notifier->sent,
            fn (array $p) => str_starts_with((string) $p['data']['event'], 'waiting_nudge'),
        ));

        // 아직 이르다.
        $this->artisan('aiw:nudge-waiting')->assertSuccessful();
        $this->assertSame(0, $nudges());

        // 10분 뒤 — 한 번.
        $job->forceFill(['waiting_since' => now()->subMinutes(11)])->saveQuietly();
        $this->artisan('aiw:nudge-waiting')->assertSuccessful();
        $this->assertSame(1, $nudges());
        $this->assertSame(1, $job->fresh()->nudge_count);

        // 같은 분에 또 돌아도 더 보내지 않는다.
        $this->artisan('aiw:nudge-waiting')->assertSuccessful();
        $this->assertSame(1, $nudges());

        // 20분 뒤 — 두 번째. 상한(2)에 닿는다.
        $job->forceFill(['waiting_since' => now()->subMinutes(21)])->saveQuietly();
        $this->artisan('aiw:nudge-waiting')->assertSuccessful();
        $this->assertSame(2, $nudges());

        // 상한을 넘겨서는 울리지 않는다. 답하지 않기로 한 것도 사람의 선택이다.
        $job->forceFill(['waiting_since' => now()->subMinutes(99)])->saveQuietly();
        $this->artisan('aiw:nudge-waiting')->assertSuccessful();
        $this->assertSame(2, $nudges());
    }

    public function test_작업_지시를_쓸_수_없는_사람에게는_알리지_않는다(): void
    {
        config(['aiw.nudge_after_min' => 10, 'aiw.nudge_max' => 3]);

        $notifier = new NudgeRecordingNotifier();

        $this->app->instance(AiwNotifier::class, $notifier);

        // owner 는 관리자도 아니고 작업 지시 가능도 아니다.
        $job = $this->job(['status' => AiwJobStatus::Running]);

        app(JobStateMachine::class)->transition($job, AiwJobStatus::WaitingInput);
        $job->forceFill(['waiting_since' => now()->subMinutes(30)])->saveQuietly();

        $this->artisan('aiw:nudge-waiting')->assertSuccessful();

        $this->assertCount(0, $notifier->sent);
    }

    public function test_대기를_벗어나면_재알림_상태가_지워진다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Running]);
        $machine = app(JobStateMachine::class);

        $machine->transition($job, AiwJobStatus::WaitingInput);
        $job->forceFill(['nudge_count' => 2])->saveQuietly();

        $machine->transition($job->fresh(), AiwJobStatus::Running);

        $fresh = $job->fresh();

        $this->assertNull($fresh->waiting_since);
        $this->assertSame(0, $fresh->nudge_count);
    }

    // ── 자동 재시도 ─────────────────────────────────────────────────────────

    public function test_담당자가_사라져_끊긴_작업은_자동으로_다시_보낸다(): void
    {
        config(['aiw.auto_retry_max' => 1]);

        $job = $this->job(['status' => AiwJobStatus::Running]);

        app(JobStateMachine::class)->transition($job, AiwJobStatus::Failed, [
            'error_message' => '담당자가 응답하지 않아 중단되었습니다.',
            'error_code'    => AiwFailureCode::AgentUnreachable->value,
        ]);

        $retry = app(FailureRecovery::class)->afterFail($job->fresh());

        $this->assertNotNull($retry);
        $this->assertSame($job->id, $retry->parent_job_id);
        $this->assertSame($job->instruction, $retry->instruction);
        $this->assertSame(1, $retry->retry_count, '횟수를 물려받아 올린다.');
        // 설정이 그대로여야 자동 배포까지 이어진다.
        // DB 에서 다시 읽어 비교한다 — 만들 때 생략한 값은 메모리에 null 로 남는다.
        $original = $job->fresh();

        $this->assertSame((bool) $original->auto_deploy, (bool) $retry->auto_deploy);
        $this->assertSame((bool) $original->use_branch, (bool) $retry->use_branch);
        $this->assertSame($original->allowed_tools, $retry->allowed_tools);
    }

    public function test_상한에_닿으면_다시_보내지_않는다(): void
    {
        config(['aiw.auto_retry_max' => 1]);

        $job = $this->job(['status' => AiwJobStatus::Running]);
        $job->forceFill(['retry_count' => 1])->saveQuietly();

        app(JobStateMachine::class)->transition($job, AiwJobStatus::Failed, [
            'error_message' => '또 끊겼습니다.',
            'error_code'    => AiwFailureCode::AgentUnreachable->value,
        ]);

        $this->assertNull(app(FailureRecovery::class)->afterFail($job->fresh()));
    }

    public function test_판단이_필요한_실패는_자동으로_다시_보내지_않는다(): void
    {
        // 상한·설정·코드 문제를 그대로 다시 보내면 같은 자리에서 또 멈춘다.
        config(['aiw.auto_retry_max' => 3]);

        foreach ([AiwFailureCode::CostLimit, AiwFailureCode::DirtyTree, AiwFailureCode::PathMissing] as $code) {
            $job = $this->job(['status' => AiwJobStatus::Running]);

            app(JobStateMachine::class)->transition($job, AiwJobStatus::Failed, [
                'error_message' => '멈춤',
                'error_code'    => $code->value,
            ]);

            $this->assertNull(app(FailureRecovery::class)->afterFail($job->fresh()), $code->value);
        }
    }

    public function test_자동_재시도를_끄면_아무것도_하지_않는다(): void
    {
        config(['aiw.auto_retry_max' => 0]);

        $job = $this->job(['status' => AiwJobStatus::Running]);

        app(JobStateMachine::class)->transition($job, AiwJobStatus::Failed, [
            'error_message' => '끊김',
            'error_code'    => AiwFailureCode::AgentUnreachable->value,
        ]);

        $this->assertNull(app(FailureRecovery::class)->afterFail($job->fresh()));
    }

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

/** FCM 대신 보낸 내용을 모아 둔다. */
class NudgeRecordingNotifier extends AiwNotifier
{
    /** @var list<array{user_id:int, title:string, body:string, data:array}> */
    public array $sent = [];

    protected function send(int $userId, string $title, string $body, array $data): void
    {
        $this->sent[] = ['user_id' => $userId, 'title' => $title, 'body' => $body, 'data' => $data];
    }
}
