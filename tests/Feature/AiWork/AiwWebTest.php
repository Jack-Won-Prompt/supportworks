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

    /** 지시를 내리는 사람. AI Works 는 시스템 관리자 전용이다. */
    private User $operator;

    /** 관리자가 아닌 프로젝트 멤버. 접근이 막혀야 한다. */
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

        $this->operator = $this->addMember('manager', 'admin');
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

    private function addMember(string $role, string $appRole = 'member'): User
    {
        $user = User::factory()->create(['role' => $appRole]);
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
            'created_by' => $this->operator->id,
        ], $overrides));
    }

    // ── 목록 / 상세 ─────────────────────────────────────────────────────────

    public function test_관리자는_목록을_볼_수_있다(): void
    {
        $job = $this->job();

        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.index', $this->project()))
            ->assertOk()
            ->assertSee($job->title)
            ->assertSee('테스트 PC');
    }

    public function test_프로젝트_멤버라도_작업_지시_가능이_아니면_막힌다(): void
    {
        // 지시 한 줄이 작업 PC 의 소스를 고치고 운영 서버에 배포까지 한다.
        // 프로젝트 역할만으로는 열리지 않는다.
        $urls = [
            route('projects.ai-works.index', $this->project()),
            route('projects.ai-works.create', $this->project()),
        ];

        foreach ([$this->member, $this->viewer] as $user) {
            foreach ($urls as $url) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }
        }
    }

    public function test_작업_지시_가능이면서_구성원이면_화면을_쓴다(): void
    {
        // 관리자 계정을 나눠 주는 대신 이 옵션을 켜 준다.
        $this->member->forceFill(['is_aiw_operator' => true])->save();

        $this->actingAs($this->member->fresh())
            ->get(route('projects.ai-works.index', $this->project()))
            ->assertOk();

        $this->actingAs($this->member->fresh())
            ->get(route('projects.ai-works.create', $this->project()))
            ->assertOk();
    }

    public function test_작업_지시_가능이어도_구성원이_아니면_막힌다(): void
    {
        $this->outsider->forceFill(['is_aiw_operator' => true])->save();

        $this->actingAs($this->outsider->fresh())
            ->get(route('projects.ai-works.index', $this->project()))
            ->assertForbidden();
    }

    public function test_관리자는_멤버가_아니어도_볼_수_있다(): void
    {
        // 관리자가 모든 프로젝트의 멤버는 아니다. 멤버까지 요구하면 대부분의
        // 프로젝트에서 탭만 보이고 눌리지 않는다.
        $this->assertDatabaseMissing('project_members', [
            'project_id' => $this->projectId,
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('projects.ai-works.index', $this->project()))
            ->assertOk();
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
            // 소스 경로는 관리자에게도 이 화면에서는 보이지 않는다. 여기서 할 일은
            // 지시를 내리고 진행을 보는 것이고, 경로는 설정 › 담당자 에서 다룬다.
            ->assertDontSee('E:\work\sample');
    }

    public function test_담당자가_오프라인이면_이유를_알려준다(): void
    {
        // 매핑은 있는데 접속한 적이 없다 = 설치가 안 된 것.
        $this->agent->forceFill(['last_seen_at' => null])->saveQuietly();

        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.index', $this->project()))
            ->assertOk()
            ->assertSee('모두 오프라인')
            ->assertSee('한 번도 접속한 적이 없습니다');
    }

    public function test_오래전_접속했으면_실행_여부를_묻는다(): void
    {
        $this->agent->forceFill(['last_seen_at' => now()->subDay()])->saveQuietly();

        // 설치는 됐는데 지금 꺼져 있는 것이라 조치가 다르다.
        $this->actingAs($this->operator)
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

        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.index', $this->project()))
            ->assertOk()
            ->assertSee('이윤석')
            ->assertDontSee('테스트 PC');

        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('이윤석');
    }

    public function test_표시_이름이_없으면_담당자_이름을_쓴다(): void
    {
        $this->actingAs($this->operator)
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
            'content' => '첫 지시문입니다', 'user_id' => $this->operator->id, 'created_at' => now(),
        ]);
        DB::table('aiw_job_logs')->insert([
            'job_id' => $job->id, 'seq' => 1, 'type' => 'tool_use',
            'content' => 'Read README.md', 'created_at' => now(),
        ]);

        $this->actingAs($this->operator)
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

        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('승인 요청')
            ->assertSee('npm test');
    }

    // ── 등록 ────────────────────────────────────────────────────────────────

    public function test_지시를_등록하면_첫_메시지가_함께_생긴다(): void
    {
        Event::fake();

        $this->actingAs($this->operator)
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
        $this->actingAs($this->operator)
            ->post(route('projects.ai-works.store', $this->project()), $base + ['use_branch' => '0'])
            ->assertRedirect();

        $this->assertFalse((bool) AiwJob::latest('id')->first()->use_branch);

        $this->actingAs($this->operator)
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

        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('작업 폴더 정리 필요')
            ->assertSee('app/Foo.php')
            ->assertSee('브랜치 없이 다시 지시')
            // 폴더를 정리하고 나면 브랜치를 유지한 채 다시 보내는 것이 맞다.
            // 이것이 없으면 사람이 브랜치를 끄게 되고, 나중에 결과 반영·배포를
            // 버튼으로 할 수 없는 상태가 된다.
            ->assertSee('같은 설정으로 다시 지시')
            ->assertSee('작업 정리')
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

        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertDontSee('이렇게 해결할 수 있습니다')
            // 결과 칸은 사유를 되풀이하지 않는다. 같은 내용이 활동 로그에 남는다.
            ->assertDontSee('알 수 없는 오류')
            ->assertSee('활동 로그');
    }

    public function test_비용_상한_중단에는_금액_대신_이어갈_방법을_보여준다(): void
    {
        // 화면에 금액을 띄우면 구독 로그인으로 도는 담당자에게는 청구된 돈으로
        // 읽힌다. 여기 남길 것은 "어떻게 이어가나" 뿐이다.
        $job = $this->job([
            'status'        => AiwJobStatus::Failed,
            'error_message' => '예상 사용량이 상한에 닿아 자동으로 멈췄습니다 ($2.0255 / $2.0000).',
            'error_code'    => 'cost_limit',
            'error_detail'  => ['cost' => 2.0255, 'limit' => 2.0, 'branch' => 'aiw/job-'.$this->projectId],
        ]);

        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('비용 상한 도달')
            ->assertSee('상한을 올려 후속 지시')
            ->assertSee('상한 없이 후속 지시')
            ->assertSee('no_cost_limit=1', false)
            ->assertDontSee('2.0255')
            ->assertDontSee('$2.0000');
    }

    public function test_use_branch_쿼리로_체크박스를_미리_끈다(): void
    {
        $parent = $this->job(['status' => AiwJobStatus::Failed, 'use_branch' => true]);

        // 실패 화면의 "브랜치 없이 다시 지시" 가 보내는 링크다.
        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.create', [$this->project(), 'parent' => $parent->id, 'use_branch' => 0]))
            ->assertOk()
            ->assertSee("useBranch: false", false);

        $this->actingAs($this->operator)
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

        $this->actingAs($this->operator)
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
        $this->actingAs($this->operator)
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

        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertDontSee('누를 수 없는 선택지');
    }

    public function test_선택지_버튼을_누르면_사용자_메시지가_된다(): void
    {
        Event::fake();
        $job = $this->job(['status' => AiwJobStatus::WaitingInput, 'mode' => 'interactive']);

        // 버튼은 그 문구를 그대로 사용자 메시지로 보낸다.
        $this->actingAs($this->operator)
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
        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.show', [$this->project(), $queued]))
            ->assertOk()
            ->assertSee('먼저 온 작업')
            ->assertSee('자동으로 시작');
    }

    public function test_실행_중인_작업에는_대기_안내가_없다(): void
    {
        $this->job(['status' => AiwJobStatus::Running, 'title' => '먼저 온 작업']);
        $running = $this->job(['status' => AiwJobStatus::Running]);

        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.show', [$this->project(), $running]))
            ->assertOk()
            ->assertDontSee('자동으로 시작');
    }

    public function test_미지원_툴은_422로_거부된다(): void
    {
        $this->actingAs($this->operator)
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

        $this->actingAs($this->operator)
            ->post(route('projects.ai-works.message', [$this->project(), $job]), ['content' => '이렇게 해줘'])
            ->assertRedirect();

        $this->assertDatabaseHas('aiw_job_messages', ['job_id' => $job->id, 'content' => '이렇게 해줘']);
        Event::assertDispatched(JobUserMessage::class);
    }

    public function test_따라잡기가_구독_전에_지나간_로그를_돌려준다(): void
    {
        // 지시를 등록한 같은 초에 실패하면, 페이지가 그려질 때는 로그가 없고
        // 이벤트는 Echo 가 구독하기 전에 지나간다. 그때 사유가 영영 뜨지 않았다.
        $job = $this->job(['status' => AiwJobStatus::Failed]);

        \App\Models\AiWork\AiwJobLog::create([
            'job_id' => $job->id, 'seq' => 0, 'type' => 'error', 'content' => '작업 폴더가 깨끗하지 않습니다',
        ]);

        $this->actingAs($this->operator)
            ->getJson(route('projects.ai-works.feed', [$this->project(), $job]).'?log_after=-1')
            ->assertOk()
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('logs.0.content', '작업 폴더가 깨끗하지 않습니다');
    }

    public function test_따라잡기는_이미_받은_것을_다시_주지_않는다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Running]);

        \App\Models\AiWork\AiwJobLog::create([
            'job_id' => $job->id, 'seq' => 0, 'type' => 'system', 'content' => '이미 봤다',
        ]);
        \App\Models\AiWork\AiwJobLog::create([
            'job_id' => $job->id, 'seq' => 1, 'type' => 'system', 'content' => '새 것',
        ]);

        $this->actingAs($this->operator)
            ->getJson(route('projects.ai-works.feed', [$this->project(), $job]).'?log_after=0')
            ->assertOk()
            ->assertJsonCount(1, 'logs')
            ->assertJsonPath('logs.0.content', '새 것');
    }

    public function test_남의_프로젝트_작업은_따라잡을_수_없다(): void
    {
        $job = $this->job();

        $this->actingAs($this->outsider)
            ->getJson(route('projects.ai-works.feed', [$this->project(), $job]))
            ->assertForbidden();
    }

    public function test_종료된_job에는_메시지를_보낼_수_없다(): void
    {
        $job = $this->job(['status' => AiwJobStatus::Completed]);

        // 화면을 열어 둔 사이 작업이 끝나는 건 흔한 일이다. 오류 페이지로 끊으면
        // 사용자는 이유도 모르고 입력하던 내용도 잃는다.
        $this->actingAs($this->operator)
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

        $this->actingAs($this->operator)
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

        $this->actingAs($this->operator)
            ->post(route('projects.ai-works.action', [$this->project(), $job, 'cancel']))
            ->assertRedirect();

        $this->assertSame(AiwJobStatus::Cancelled, $job->fresh()->status);
        Event::assertDispatched(JobCancelRequested::class);
    }

    public function test_컨텍스트_정리는_대화형_실행중에만_가능하다(): void
    {
        Event::fake([HandoverRequested::class]);

        $batch = $this->job(['status' => AiwJobStatus::Running, 'mode' => 'batch']);
        $this->actingAs($this->operator)
            ->post(route('projects.ai-works.action', [$this->project(), $batch, 'handover']))
            ->assertRedirect()
            ->assertSessionHas('error');

        $interactive = $this->job(['status' => AiwJobStatus::Running, 'mode' => 'interactive']);
        $this->actingAs($this->operator)
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

        $this->actingAs($this->operator)
            ->post(route('projects.ai-works.decide', [$this->project(), $job, $permission]), [
                'decision' => 'deny', 'deny_reason' => '위험합니다',
            ])
            ->assertRedirect();

        $permission->refresh();
        $this->assertSame('denied', $permission->status);
        $this->assertSame($this->operator->id, $permission->decided_by);
    }

    public function test_CLAUDE_md_승격이_후속job을_만든다(): void
    {
        Event::fake();

        $job = $this->job(['status' => AiwJobStatus::Completed]);

        $this->actingAs($this->operator)
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

        // 상세 화면은 더 이상 금액을 그리지 않는다(게이지 제거). 라벨은 목록의
        // 상한 칸 툴팁에만 쓰이므로 여기서는 모델 수준으로만 확인한다.
        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertDontSee('실제 청구액이 아닙니다');
    }

    public function test_API키_PC는_비용으로_표시한다(): void
    {
        $this->agent->forceFill(['capabilities' => ['auth_mode' => 'api_key']])->save();
        $job = $this->job();

        $this->assertTrue($this->agent->fresh()->usesApiKey());
        $this->assertSame('비용', $this->agent->fresh()->costLabel());

        $this->actingAs($this->operator)
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

    // ── 중단 안내(system 메시지) ───────────────────────────────────────────

    public function test_데몬이_중단_안내를_대화에_남긴다(): void
    {
        // 중단되면 데몬이 작업 폴더를 되돌리고 그 사실을 여기로 알린다.
        // assistant 로 보내면 모델이 한 말처럼 보이므로 역할을 나눈다.
        $job = $this->job(['status' => AiwJobStatus::Cancelled]);
        $agent = $this->agent->fresh();
        $token = AiwAgent::generateToken();
        $agent->forceFill(['token_hash' => AiwAgent::hashToken($token)])->saveQuietly();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.aiw.jobs.messages', $job), [
                'messages' => [[
                    'role' => 'system',
                    'content' => '작업 폴더를 수정 이전 상태(`master`)로 되돌렸습니다.',
                    'client_key' => 'restore-'.$job->id,
                ]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('aiw_job_messages', [
            'job_id' => $job->id,
            'role' => 'system',
        ]);

        // 화면에도 그대로 보여야 한다.
        $this->actingAs($this->operator)
            ->get(route('projects.ai-works.show', [$this->project(), $job]))
            ->assertOk()
            ->assertSee('시스템')
            // 본문은 @js 를 거쳐 JSON 으로 들어가므로 한글은 유니코드 이스케이프로 바뀐다.
            // 화면에 들어갔는지는 ASCII 부분으로 확인한다.
            ->assertSee('master', false);
    }

    // ── 매핑 점검·정리 ──────────────────────────────────────────────────────

    public function test_점검과_정리를_담당자_PC_에_요청한다(): void
    {
        Event::fake([\App\Events\AiWork\MappingSetupRequested::class]);

        foreach (['recheck', 'cleanup'] as $action) {
            $this->actingAs($this->operator)
                ->post(route('projects.ai-works.mappings.action', [$this->project(), $this->agent, $action]))
                ->assertRedirect()
                ->assertSessionHas('status');
        }

        Event::assertDispatched(
            \App\Events\AiWork\MappingSetupRequested::class,
            fn ($e) => $e->action === 'recheck' && $e->projectId === $this->projectId,
        );
        Event::assertDispatched(
            \App\Events\AiWork\MappingSetupRequested::class,
            fn ($e) => $e->action === 'cleanup',
        );
    }

    public function test_오프라인_담당자에게는_요청하지_않는다(): void
    {
        // 요청은 실시간 채널로만 간다. 꺼져 있으면 아무 일도 일어나지 않는데
        // 화면이 "요청했습니다" 라고 하면 사람이 결과를 기다리게 된다.
        Event::fake([\App\Events\AiWork\MappingSetupRequested::class]);

        $this->agent->forceFill(['last_seen_at' => now()->subDay()])->saveQuietly();
        AiwAgentProject::where('agent_id', $this->agent->id)
            ->update(['last_seen_at' => now()->subDay()]);

        $this->actingAs($this->operator)
            ->post(route('projects.ai-works.mappings.action', [$this->project(), $this->agent, 'recheck']))
            ->assertRedirect()
            ->assertSessionHas('error');

        Event::assertNotDispatched(\App\Events\AiWork\MappingSetupRequested::class);
    }

    public function test_알_수_없는_동작은_라우트가_받지_않는다(): void
    {
        $this->actingAs($this->operator)
            ->post(url("/projects/{$this->projectId}/ai-works/mappings/{$this->agent->id}/wipe"))
            ->assertNotFound();
    }

    public function test_점검_상태를_json_으로_따라잡는다(): void
    {
        AiwAgentProject::where('agent_id', $this->agent->id)
            ->where('project_id', $this->projectId)
            ->update([
                'setup_status'     => 'dirty_tree',
                'setup_message'    => '커밋되지 않은 변경 2건',
                'setup_checked_at' => now(),
            ]);

        $this->actingAs($this->operator)
            ->getJson(route('projects.ai-works.mappings.status', $this->project()))
            ->assertOk()
            ->assertJsonPath('mappings.0.status', 'dirty_tree')
            ->assertJsonPath('mappings.0.ready', false)
            ->assertJsonPath('mappings.0.agent_id', $this->agent->id);
    }

    public function test_관리자가_아니면_점검도_정리도_할_수_없다(): void
    {
        foreach ([$this->member, $this->viewer] as $user) {
            $this->actingAs($user)
                ->post(route('projects.ai-works.mappings.action', [$this->project(), $this->agent, 'cleanup']))
                ->assertForbidden();
        }
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
