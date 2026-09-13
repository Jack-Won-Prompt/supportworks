<?php

namespace Tests\Feature\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwDeployTarget;
use App\Models\AiWork\AiwErrorReport;
use App\Models\AiWork\AiwErrorSource;
use App\Models\AiWork\AiwJob;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\AiWork\ErrorPatchDispatcher;
use App\Services\AiWork\ErrorTriage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * 판정이 끝난 오류를 작업 지시로 만드는 부분.
 *
 * 밤에 사람 없이 도는 자리라, 여기서 검증할 것은 "만들 수 있는가" 보다
 * **"만들지 말아야 할 때 만들지 않는가"** 다. 잘못 만들어진 지시는 곧바로
 * 운영에 배포된다.
 */
class AiwErrorPatchDispatchTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private AiwErrorSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config([
            'aiw.error_triage.auto_create_jobs'     => true,
            'aiw.error_triage.min_count_for_auto'   => 1,
            'aiw.error_triage.max_open_per_project' => 1,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->project = Project::create([
            'name' => 'korsafety', 'status' => 'active', 'created_by' => $admin->id,
        ]);

        $operator = User::factory()->create(['role' => 'member', 'is_aiw_operator' => true]);
        ProjectMember::create([
            'project_id' => $this->project->id, 'user_id' => $operator->id, 'role' => 'member',
        ]);

        $agent = AiwAgent::create([
            'name' => 'LORVIS', 'token_hash' => AiwAgent::hashToken(AiwAgent::generateToken()), 'user_id' => $admin->id,
        ]);
        AiwAgentProject::create([
            'agent_id' => $agent->id, 'project_id' => $this->project->id,
            'local_path' => 'E:/work/korsafety', 'default_branch' => 'main',
        ]);

        $this->source = AiwErrorSource::create([
            'project_id' => $this->project->id,
            'name'       => 'korsafety.co.kr',
            'token_hash' => AiwErrorSource::hashToken(AiwErrorSource::generateToken()),
        ]);
    }

    private function deployTarget(): AiwDeployTarget
    {
        return AiwDeployTarget::create([
            'created_by'  => User::where('role', 'admin')->value('id'),
            'project_id'  => $this->project->id,
            'name'        => '운영 배포',
            'working_dir' => '/home/ubuntu/www/korsafety',
            'command'     => 'bash deploy.sh',
            'kind'        => AiwDeployTarget::KIND_DEPLOY,
            'enabled'     => true,
        ]);
    }

    private function report(array $attrs = []): AiwErrorReport
    {
        static $n = 0;
        $n++;

        return AiwErrorReport::create($attrs + [
            'project_id'    => $this->project->id,
            'source_id'     => $this->source->id,
            'fingerprint'   => str_pad((string) $n, 64, 'a'),
            'exception'     => 'RuntimeException',
            'message'       => '주문 저장 실패',
            'file'          => 'app/Services/OrderService.php',
            'line'          => 42,
            'count'         => 5,
            'first_seen_at' => now()->subHour(),
            'last_seen_at'  => now(),
            'status'        => AiwErrorReport::STATUS_NEW,
            'verdict'       => ErrorTriage::AUTO,
        ]);
    }

    private function dispatcher(): ErrorPatchDispatcher
    {
        return app(ErrorPatchDispatcher::class);
    }

    // ── 만든다 ──────────────────────────────────────────────────────────────

    public function test_자동_수정_대상을_지시로_만든다(): void
    {
        $this->deployTarget();
        $report = $this->report();

        $made = $this->dispatcher()->run();

        $this->assertCount(1, $made);

        $job = $made[0];

        $this->assertSame(AiwJob::KIND_ERROR_PATCH, $job->kind);
        $this->assertSame($report->id, $job->error_report_id);
        // 사람이 없는 시간에 도는 작업이라 물어볼 상대가 없다. 대화형이면 답을
        // 기다리다 수명이 다한다.
        $this->assertSame('batch', $job->mode);
        $this->assertTrue($job->use_branch, '브랜치가 없으면 이 작업만 골라 올릴 수 없다.');
        $this->assertTrue($job->auto_deploy);
        $this->assertNull($job->cost_limit_usd, '중간에 끊기면 고치다 만 코드가 남는다.');
    }

    public function test_지시문에_오류_내용이_그대로_들어간다(): void
    {
        // 사람이 쓰는 지시와 달리 배경을 설명해 줄 사람이 없다.
        $this->deployTarget();
        $this->report(['message' => '주문번호가 비어 있습니다', 'count' => 137]);

        $job = $this->dispatcher()->run()[0];

        $this->assertStringContainsString('RuntimeException', $job->instruction);
        $this->assertStringContainsString('주문번호가 비어 있습니다', $job->instruction);
        $this->assertStringContainsString('137', $job->instruction);
        $this->assertStringContainsString('app/Services/OrderService.php', $job->instruction);
        // 증상만 덮는 수정을 막는 문장이 들어 있어야 한다.
        $this->assertStringContainsString('증상만 덮지 마세요', $job->instruction);
        $this->assertStringContainsString('자동으로 운영에 배포', $job->instruction);
    }

    public function test_오류_기록에_지시가_연결되고_시도_횟수가_오른다(): void
    {
        $report = $this->report();

        $job = $this->dispatcher()->run()[0];

        $report->refresh();

        $this->assertSame(AiwErrorReport::STATUS_QUEUED, $report->status);
        $this->assertSame($job->id, $report->job_id);
        $this->assertSame(1, $report->patch_attempts);
    }

    public function test_배포_대상이_없으면_고치기만_한다(): void
    {
        // 배포까지 못 가도 고치는 데까지는 간다. 사람이 아침에 눌러 주면 된다.
        $this->report();

        $job = $this->dispatcher()->run()[0];

        $this->assertFalse($job->auto_deploy);
        $this->assertStringContainsString('사람이 배포합니다', $job->instruction);
    }

    // ── 만들지 않는다 ───────────────────────────────────────────────────────

    public function test_스위치가_꺼져_있으면_아무것도_만들지_않는다(): void
    {
        // 판정이 맞는지 화면에서 보고 켠다. 이 기본값이 뒤집히면 첫날 밤에
        // 잘못 판정된 오류가 그대로 운영에 나간다.
        config(['aiw.error_triage.auto_create_jobs' => false]);
        $this->report();

        $this->assertSame([], $this->dispatcher()->run());
        $this->assertSame(0, AiwJob::count());
    }

    public function test_사람이_봐야_하는_오류는_만들지_않는다(): void
    {
        $this->report(['verdict' => ErrorTriage::HUMAN, 'status' => AiwErrorReport::STATUS_BLOCKED]);

        $this->assertSame([], $this->dispatcher()->run());
    }

    public function test_고칠_것이_없는_오류는_만들지_않는다(): void
    {
        $this->report(['verdict' => ErrorTriage::IGNORE, 'status' => AiwErrorReport::STATUS_IGNORED]);

        $this->assertSame([], $this->dispatcher()->run());
    }

    public function test_한_번_나고_만_오류는_만들지_않는다(): void
    {
        config(['aiw.error_triage.min_count_for_auto' => 3]);
        $this->report(['count' => 2]);

        $this->assertSame([], $this->dispatcher()->run());
    }

    public function test_시도_상한을_넘으면_더_만들지_않는다(): void
    {
        // 고친 코드가 또 에러를 내는 고리를 여기서 끊는다. 사람이 없는 시간에
        // 그 고리가 돌면 아무도 멈추지 못한다.
        config(['aiw.error_patch_max_attempts' => 2]);
        $this->report(['patch_attempts' => 2]);

        $this->assertSame([], $this->dispatcher()->run());
    }

    public function test_한_프로젝트에서_동시에_하나만_돈다(): void
    {
        // 한 폴더를 여러 작업이 동시에 고치면 서로의 변경을 덮는다.
        $this->report();
        $this->report();

        $this->assertCount(1, $this->dispatcher()->run());
        $this->assertSame(1, AiwJob::where('kind', AiwJob::KIND_ERROR_PATCH)->count());
    }

    public function test_앞선_패치가_끝나면_다음_것이_돈다(): void
    {
        $this->report();
        $this->report();

        $first = $this->dispatcher()->run()[0];

        $first->forceFill(['status' => AiwJobStatus::Completed])->save();

        $this->assertCount(1, $this->dispatcher()->run());
    }

    public function test_담당자가_없는_프로젝트는_건너뛴다(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $other = Project::create(['name' => '매핑 없음', 'status' => 'active', 'created_by' => $admin->id]);

        $source = AiwErrorSource::create([
            'project_id' => $other->id,
            'name'       => 'x',
            'token_hash' => AiwErrorSource::hashToken(AiwErrorSource::generateToken()),
        ]);

        AiwErrorReport::create([
            'project_id'  => $other->id,
            'source_id'   => $source->id,
            'fingerprint' => str_repeat('z', 64),
            'exception'   => 'RuntimeException',
            'file'        => 'app/X.php',
            'line'        => 1,
            'count'       => 9,
            'status'      => AiwErrorReport::STATUS_NEW,
            'verdict'     => ErrorTriage::AUTO,
        ]);

        // 담당자가 있는 프로젝트의 기록도 하나 둔다. 한 프로젝트의 설정 누락이
        // 다른 프로젝트의 패치까지 멈추면 안 된다는 것이 이 시험의 뜻이다.
        $this->report();

        $made = $this->dispatcher()->run();

        $this->assertSame($this->project->id, $made[0]->project_id);
        $this->assertCount(1, $made);
    }

    public function test_자주_나는_것부터_만든다(): void
    {
        $rare   = $this->report(['count' => 3]);
        $common = $this->report(['count' => 900]);

        $job = $this->dispatcher()->run()[0];

        $this->assertSame($common->id, $job->error_report_id);
        $this->assertSame(AiwErrorReport::STATUS_NEW, $rare->fresh()->status, '못 만든 것은 그대로 남아 다음 차례를 기다린다.');
    }
}
