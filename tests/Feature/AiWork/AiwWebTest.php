<?php

namespace Tests\Feature\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\HandoverRequested;
use App\Events\AiWork\JobCancelRequested;
use App\Events\AiWork\JobUserMessage;
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
 * Phase 5 — 웹 UI 와 권한.
 */
class AiwWebTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private User $viewer;

    private User $outsider;

    private User $admin;

    private AiwAgent $agent;

    private int $projectId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\MaintenanceCheckMiddleware::class,
            \App\Http\Middleware\LogPageAccess::class,
            \App\Http\Middleware\CollabParticipantMiddleware::class,
        ]);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->outsider = User::factory()->create(['role' => 'member']);

        $this->projectId = DB::table('projects')->insertGetId([
            'name' => 'UI 테스트 프로젝트',
            'created_by' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->member = $this->addMember('member');
        $this->viewer = $this->addMember('viewer');

        $this->agent = AiwAgent::create([
            'name' => '테스트 PC',
            'token_hash' => AiwAgent::hashToken(AiwAgent::generateToken()),
            'user_id' => $this->admin->id,
            'last_seen_at' => now(),
        ]);

        AiwAgentProject::create([
            'agent_id' => $this->agent->id,
            'project_id' => $this->projectId,
            'local_path' => 'E:\\work\\sample',
            'default_branch' => 'master',
        ]);
    }

    private function addMember(string $role): User
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

    private function project(): \App\Models\Project
    {
        return \App\Models\Project::findOrFail($this->projectId);
    }

    private function job(array $overrides = []): AiwJob
    {
        return AiwJob::create(array_merge([
            'project_id' => $this->projectId,
            'agent_id' => $this->agent->id,
            'title' => '테스트 지시',
            'instruction' => 'README 한 줄 추가',
            'context_limit_tokens' => 200000,
            'allowed_tools' => ['Read', 'Edit'],
            'cost_limit_usd' => 2.0,
            'created_by' => $this->member->id,
        ], $overrides));
    }

    // ── 목록 / 상세 ─────────────────────────────────────────────────────────

    public function test_멤버는_목록을_볼_수_있다(): void
    {
        $job = $this->job();

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.index', $this->project()))
            ->assertOk()
            ->assertSee($job->title)
            ->assertSee('테스트 PC');
    }

    public function test_운영_정보는_관리자에게만_보인다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Completed]);
        $job->forceFill(['duration_ms' => 5000, 'cost_usd' => 0.42])->save();

        $url = route('projects.ai-works.index', $this->project());

        // 소스 경로는 담당자 PC 의 내부 구조다. 지시하는 사람에게는 필요 없다.
        $this->actingAs($this->member)->get($url)
            ->assertOk()
            ->assertDontSee('E:\work\sample')
            ->assertDontSee('등록자')
            ->assertDontSee('소요')
            ->assertDontSee('작업량')
            ->assertSee('등록일');
    }

    public function test_관리자는_운영_정보를_본다(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        DB::table('project_members')->insert([
            'project_id' => $this->projectId, 'user_id' => $admin->id,
            'role' => 'manager', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->job(['status' => AiwJobStatus::Completed]);

        $this->actingAs($admin)->get(route('projects.ai-works.index', $this->project()))
            ->assertOk()
            ->assertSee('등록자')
            ->assertSee('작업량')
            ->assertSee('E:\work\sample');
    }

    public function test_담당자가_오프라인이면_이유를_알려준다(): void
    {
        // 매핑은 있는데 접속한 적이 없다 = 설치가 안 된 것.
        $this->agent->forceFill(['last_seen_at' => null])->saveQuietly();

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.index', $this->project()))
            ->assertOk()
            ->assertSee('모두 오프라인')
            ->assertSee('한 번도 접속한 적이 없습니다');
    }

    public function test_오래전_접속했으면_실행_여부를_묻는다(): void
    {
        $this->agent->forceFill(['last_seen_at' => now()->subDay()])->saveQuietly();

        // 설치는 됐는데 지금 꺼져 있는 것이라 조치가 다르다.
        $this->actingAs($this->member)
            ->get(route('projects.ai-works.index', $this->project()))
            ->assertOk()
            ->assertSee('데몬이 실행 중인지 확인하세요')
            ->assertDontSee('한 번도 접속한 적이 없습니다');
    }

    public function test_매핑별_표시_이름을_쓴다(): void
    {
        // 담당자 하나(= PC 한 대)가 여러 프로젝트를 맡을 때, 프로젝트마다
        // 실제 책임자가 다를 수 있다. 데몬을 늘리지 않고 이름만 나눈다.
        AiwAgentProject::where('agent_id', $this->agent->id)
            ->where('project_id', $this->projectId)
            ->update(['display_name' => '이윤석']);

        $job = $this->job(['status' => AiwJobStatus::Completed]);

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.index', $this->project()))
            ->assertOk()
            ->assertSee('이윤석')
            ->assertDontSee('테스트 PC');

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('이윤석');
    }

    public function test_표시_이름이_없으면_담당자_이름을_쓴다(): void
    {
        $this->actingAs($this->member)
            ->get(route('projects.ai-works.index', $this->project()))
            ->assertOk()
            ->assertSee('테스트 PC');
    }

    public function test_비멤버는_목록에_접근할_수_없다(): void
    {
        $this->actingAs($this->outsider)
            ->get(route('projects.ai-works.index', $this->project()))
            ->assertForbidden();
    }

    public function test_상세_화면이_대화와_로그를_보여준다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Running]);

        AiwJobMessage::create([
            'job_id' => $job->id, 'seq' => 0, 'role' => 'user',
            'content' => '첫 지시문입니다', 'user_id' => $this->member->id, 'created_at' => now(),
        ]);
        DB::table('aiw_job_logs')->insert([
            'job_id' => $job->id, 'seq' => 1, 'type' => 'tool_use',
            'content' => 'Read README.md', 'created_at' => now(),
        ]);

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('첫 지시문입니다')
            ->assertSee('Read README.md')
            ->assertSee('컨텍스트');
    }

    public function test_pending_승인카드는_새로고침해도_남는다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::WaitingPermission]);
        AiwPermissionRequest::create([
            'job_id' => $job->id, 'request_key' => 'k1', 'tool_name' => 'Bash',
            'tool_input' => ['command' => 'npm test'], 'created_at' => now(),
        ]);

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('승인 요청')
            ->assertSee('npm test');
    }

    // ── 등록 ────────────────────────────────────────────────────────────────

    public function test_지시를_등록하면_첫_메시지가_함께_생긴다(): void
    {
        Event::fake();

        $this->actingAs($this->member)
            ->post(route('projects.ai-works.store', $this->project()), [
                'title' => '새 지시',
                'agent_id' => $this->agent->id,
                'instruction' => '작업 내용',
                'mode' => 'interactive',
                'allowed_tools' => ['Read', 'Edit'],
                'permission_mode' => 'acceptEdits',
                'cost_limit_usd' => 2.0,
                'use_branch' => 1,
            ])
            ->assertRedirect();

        $job = AiwJob::latest('id')->first();
        $this->assertSame('새 지시', $job->title);
        $this->assertDatabaseHas('aiw_job_messages', [
            'job_id' => $job->id, 'seq' => 0, 'role' => 'user', 'content' => '작업 내용',
        ]);
    }

    public function test_브랜치_분리를_끌_수_있다(): void
    {
        Event::fake();

        $base = [
            'title' => '브랜치 없이',
            'agent_id' => $this->agent->id,
            'instruction' => '작업 내용',
            'mode' => 'interactive',
            'allowed_tools' => ['Read'],
            'permission_mode' => 'acceptEdits',
            'cost_limit_usd' => 2.0,
        ];

        // 폼의 hidden 이 보내는 값. 예전에는 값이 없으면 true 로 봤는데, 해제한
        // 체크박스는 아무것도 보내지 않아 브랜치 분리를 끌 방법이 없었다.
        $this->actingAs($this->member)
            ->post(route('projects.ai-works.store', $this->project()), $base + ['use_branch' => '0'])
            ->assertRedirect();

        $this->assertFalse((bool) AiwJob::latest('id')->first()->use_branch);

        $this->actingAs($this->member)
            ->post(route('projects.ai-works.store', $this->project()), $base + ['use_branch' => '1'])
            ->assertRedirect();

        $this->assertTrue((bool) AiwJob::latest('id')->first()->use_branch);
    }

    public function test_실패_화면이_복구_버튼을_보여준다(): void
    {
        $job = $this->job([
            'status'        => AiwJobStatus::Failed,
            'error_message' => '작업 폴더가 깨끗하지 않습니다.',
            'error_code'    => 'dirty_tree',
            'error_detail'  => ['files' => ['app/Foo.php'], 'count' => 1],
        ]);

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('작업 폴더 정리 필요')
            ->assertSee('app/Foo.php')
            ->assertSee('브랜치 없이 다시 지시')
            // 버튼은 바로 실행하지 않고 프리필된 등록 폼으로 보낸다.
            // href 안의 & 는 HTML 이스케이프되므로 파라미터로 확인한다.
            ->assertSee('use_branch=0', false)
            ->assertSee('parent='.$job->id, false);
    }

    public function test_코드_없는_실패에는_복구_버튼이_없다(): void
    {
        $job = $this->job([
            'status'        => AiwJobStatus::Failed,
            'error_message' => '알 수 없는 오류',
        ]);

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('알 수 없는 오류')
            ->assertDontSee('이렇게 해결할 수 있습니다');
    }

    public function test_use_branch_쿼리로_체크박스를_미리_끈다(): void
    {
        $parent = $this->job(['status' => AiwJobStatus::Failed, 'use_branch' => true]);

        // 실패 화면의 "브랜치 없이 다시 지시" 가 보내는 링크다.
        $this->actingAs($this->member)
            ->get(route('projects.ai-works.create', [$this->project(), 'parent' => $parent->id, 'use_branch' => 0]))
            ->assertOk()
            ->assertSee("useBranch: false", false);

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.create', [$this->project(), 'parent' => $parent->id]))
            ->assertOk()
            ->assertSee("useBranch: true", false);
    }

    public function test_선택지가_버튼으로_보인다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::WaitingInput, 'mode' => 'interactive']);

        AiwJobMessage::create([
            'job_id' => $job->id, 'seq' => 1, 'role' => 'assistant',
            'content' => '어떻게 반영할까요?',
            'choices' => ['관리자 화면에서 켜기', '마이그레이션으로 켜기'],
        ]);

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('관리자 화면에서 켜기')
            ->assertSee('마이그레이션으로 켜기')
            ->assertSee('직접 입력해서 답해도 됩니다');
    }

    public function test_지난_질문의_선택지는_숨긴다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::WaitingInput, 'mode' => 'interactive']);

        AiwJobMessage::create([
            'job_id' => $job->id, 'seq' => 1, 'role' => 'assistant',
            'content' => '첫 질문', 'choices' => ['지난 선택지'],
        ]);
        AiwJobMessage::create([
            'job_id' => $job->id, 'seq' => 2, 'role' => 'assistant',
            'content' => '두 번째 질문', 'choices' => ['지금 선택지'],
        ]);

        // 이미 답한 질문의 버튼이 남아 있으면 같은 답을 다시 보내게 된다.
        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('지금 선택지')
            ->assertDontSee('지난 선택지');
    }

    public function test_종료된_job은_선택지_버튼을_숨긴다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Completed, 'mode' => 'interactive']);

        AiwJobMessage::create([
            'job_id' => $job->id, 'seq' => 1, 'role' => 'assistant',
            'content' => '질문', 'choices' => ['누를 수 없는 선택지'],
        ]);

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertDontSee('누를 수 없는 선택지');
    }

    public function test_선택지_버튼을_누르면_사용자_메시지가_된다(): void
    {
        Event::fake();
        $job = $this->job(['status' => AiwJobStatus::WaitingInput, 'mode' => 'interactive']);

        // 버튼은 그 문구를 그대로 사용자 메시지로 보낸다.
        $this->actingAs($this->member)
            ->post(route('projects.ai-works.message', [$this->project(), $job]), [
                'content' => '관리자 화면에서 켜기',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('aiw_job_messages', [
            'job_id' => $job->id, 'role' => 'user', 'content' => '관리자 화면에서 켜기',
        ]);
    }

    public function test_대기_중인_이유를_화면이_알려준다(): void
    {
        $running = $this->job(['status' => AiwJobStatus::Running, 'title' => '먼저 온 작업']);
        $queued = $this->job(['status' => AiwJobStatus::Dispatched]);

        // 같은 폴더에서 둘이 동시에 돌 수 없어 직렬로 기다린다. 화면이 말해 주지
        // 않으면 "보냈는데 아무 일도 없는" 상태로 보인다.
        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->project(), $queued]))
            ->assertOk()
            ->assertSee('먼저 온 작업')
            ->assertSee('자동으로 시작');
    }

    public function test_실행_중인_작업에는_대기_안내가_없다(): void
    {
        $this->job(['status' => AiwJobStatus::Running, 'title' => '먼저 온 작업']);
        $running = $this->job(['status' => AiwJobStatus::Running]);

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->project(), $running]))
            ->assertOk()
            ->assertDontSee('자동으로 시작');
    }

    public function test_미지원_툴은_422로_거부된다(): void
    {
        $this->actingAs($this->member)
            ->post(route('projects.ai-works.store', $this->project()), [
                'title' => 'x', 'agent_id' => $this->agent->id, 'instruction' => 'y',
                'mode' => 'batch', 'allowed_tools' => ['RmRf'],
                'permission_mode' => 'default', 'cost_limit_usd' => 1,
            ])
            ->assertSessionHasErrors('allowed_tools');
    }

    public function test_viewer는_지시를_등록할_수_없다(): void
    {
        $this->actingAs($this->viewer)
            ->get(route('projects.ai-works.create', $this->project()))
            ->assertForbidden();
    }

    // ── 액션 ────────────────────────────────────────────────────────────────

    public function test_메시지_전송이_이벤트를_발행한다(): void
    {
        Event::fake([JobUserMessage::class]);

        $job = $this->job(['status' => AiwJobStatus::WaitingInput, 'mode' => 'interactive']);

        $this->actingAs($this->member)
            ->post(route('projects.ai-works.message', [$this->project(), $job]), ['content' => '이렇게 해줘'])
            ->assertRedirect();

        $this->assertDatabaseHas('aiw_job_messages', ['job_id' => $job->id, 'content' => '이렇게 해줘']);
        Event::assertDispatched(JobUserMessage::class);
    }

    public function test_종료된_job에는_메시지를_보낼_수_없다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Completed]);

        // 화면을 열어 둔 사이 작업이 끝나는 건 흔한 일이다. 오류 페이지로 끊으면
        // 사용자는 이유도 모르고 입력하던 내용도 잃는다.
        $this->actingAs($this->member)
            ->post(route('projects.ai-works.message', [$this->project(), $job]), ['content' => '이어서 해주세요'])
            ->assertRedirect()
            ->assertSessionHas('error')
            ->assertSessionHasInput('content', '이어서 해주세요');

        $this->assertDatabaseMissing('aiw_job_messages', [
            'job_id'  => $job->id,
            'content' => '이어서 해주세요',
        ]);
    }

    public function test_단발_job에는_메시지를_보낼_수_없다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Running, 'mode' => 'batch']);

        $this->actingAs($this->member)
            ->post(route('projects.ai-works.message', [$this->project(), $job]), ['content' => 'x'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_viewer는_메시지를_보낼_수_없다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Running]);

        $this->actingAs($this->viewer)
            ->post(route('projects.ai-works.message', [$this->project(), $job]), ['content' => 'x'])
            ->assertForbidden();
    }

    public function test_취소가_상태를_바꾸고_이벤트를_발행한다(): void
    {
        Event::fake([JobCancelRequested::class]);

        $job = $this->job(['status' => AiwJobStatus::Running]);

        $this->actingAs($this->member)
            ->post(route('projects.ai-works.action', [$this->project(), $job, 'cancel']))
            ->assertRedirect();

        $this->assertSame(AiwJobStatus::Cancelled, $job->fresh()->status);
        Event::assertDispatched(JobCancelRequested::class);
    }

    public function test_컨텍스트_정리는_대화형_실행중에만_가능하다(): void
    {
        Event::fake([HandoverRequested::class]);

        $batch = $this->job(['status' => AiwJobStatus::Running, 'mode' => 'batch']);
        $this->actingAs($this->member)
            ->post(route('projects.ai-works.action', [$this->project(), $batch, 'handover']))
            ->assertRedirect()
            ->assertSessionHas('error');

        $interactive = $this->job(['status' => AiwJobStatus::Running, 'mode' => 'interactive']);
        $this->actingAs($this->member)
            ->post(route('projects.ai-works.action', [$this->project(), $interactive, 'handover']))
            ->assertRedirect();

        Event::assertDispatchedTimes(HandoverRequested::class, 1);
    }

    public function test_승인_결정이_기록된다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::WaitingPermission]);
        $permission = AiwPermissionRequest::create([
            'job_id' => $job->id, 'request_key' => 'k1', 'tool_name' => 'Bash',
            'tool_input' => [], 'created_at' => now(),
        ]);

        $this->actingAs($this->member)
            ->post(route('projects.ai-works.decide', [$this->project(), $job, $permission]), [
                'decision' => 'deny', 'deny_reason' => '위험합니다',
            ])
            ->assertRedirect();

        $permission->refresh();
        $this->assertSame('denied', $permission->status);
        $this->assertSame($this->member->id, $permission->decided_by);
    }

    public function test_CLAUDE_md_승격이_후속job을_만든다(): void
    {
        Event::fake();

        $job = $this->job(['status' => AiwJobStatus::Completed]);

        $this->actingAs($this->member)
            ->post(route('projects.ai-works.promote', [$this->project(), $job]), [
                'sections' => ['커밋 전 테스트를 돌린다'],
            ])
            ->assertRedirect();

        $follow = AiwJob::where('parent_job_id', $job->id)->first();
        $this->assertNotNull($follow);
        $this->assertStringContainsString('CLAUDE.md', $follow->instruction);
    }

    // ── 과금 주체 표시 ──────────────────────────────────────────────────────

    public function test_구독_로그인_PC는_비용을_추정치로_표시한다(): void
    {
        // 데몬이 auth_mode 를 보고하지 않았거나 subscription 이면 추정치다.
        $this->agent->forceFill(['capabilities' => ['auth_mode' => 'subscription']])->save();
        $job = $this->job();

        $this->assertFalse($this->agent->fresh()->usesApiKey());
        $this->assertSame('예상 사용량', $this->agent->fresh()->costLabel());

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('예상 사용량')
            ->assertSee('실제 청구액이 아닙니다');
    }

    public function test_API키_PC는_비용으로_표시한다(): void
    {
        $this->agent->forceFill(['capabilities' => ['auth_mode' => 'api_key']])->save();
        $job = $this->job();

        $this->assertTrue($this->agent->fresh()->usesApiKey());
        $this->assertSame('비용', $this->agent->fresh()->costLabel());

        $this->actingAs($this->member)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertDontSee('실제 청구액이 아닙니다');
    }

    public function test_auth_mode_미보고시_보수적으로_추정치로_본다(): void
    {
        // 구버전 데몬은 auth_mode 를 보내지 않는다. 실제 청구액이라고
        // 잘못 말하는 것보다 추정치로 읽는 편이 안전하다.
        $this->agent->forceFill(['capabilities' => ['max_parallel_jobs' => 2]])->save();

        $this->assertFalse($this->agent->fresh()->usesApiKey());
        $this->assertSame('예상 사용량', $this->agent->fresh()->costLabel());
    }

    // ── 작업 PC 관리 ────────────────────────────────────────────────────────

    public function test_관리자만_작업PC_화면에_접근한다(): void
    {
        $this->actingAs($this->admin)->get(route('settings.aiw-agents.index'))->assertOk();
        $this->actingAs($this->member)->get(route('settings.aiw-agents.index'))->assertForbidden();
    }

    public function test_작업PC_등록시_토큰_원문이_한번_표시된다(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.aiw-agents.store'), ['name' => '새 PC', 'expires_days' => 30]);

        $response->assertRedirect()->assertSessionHas('aiw_new_token');

        $raw = session('aiw_new_token');
        $this->assertNotNull(AiwAgent::findByToken($raw));

        // 저장된 것은 해시뿐이다.
        $this->assertDatabaseMissing('aiw_agents', ['token_hash' => $raw]);
    }

    public function test_토큰_재발급하면_기존_토큰이_무효가_된다(): void
    {
        $old = AiwAgent::generateToken();
        $agent = AiwAgent::create([
            'name' => 'PC', 'token_hash' => AiwAgent::hashToken($old),
            'user_id' => $this->admin->id, 'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('settings.aiw-agents.regenerate', $agent))
            ->assertRedirect();

        $this->assertNull(AiwAgent::findByToken($old));
        $this->assertNotNull(AiwAgent::findByToken(session('aiw_new_token')));
    }

    public function test_프로젝트_매핑을_추가하고_삭제한다(): void
    {
        $agent = AiwAgent::create([
            'name' => 'PC2', 'token_hash' => AiwAgent::hashToken(AiwAgent::generateToken()),
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('settings.aiw-agents.mappings.store', $agent), [
                'project_id' => $this->projectId,
                'local_path' => 'D:\\repo',
                'default_branch' => 'main',
            ])->assertRedirect();

        $mapping = AiwAgentProject::where('agent_id', $agent->id)->firstOrFail();
        $this->assertSame('D:\\repo', $mapping->local_path);

        $this->actingAs($this->admin)
            ->delete(route('settings.aiw-agents.mappings.destroy', [$agent, $mapping]))
            ->assertRedirect();

        $this->assertDatabaseMissing('aiw_agent_projects', ['id' => $mapping->id]);
    }
}
