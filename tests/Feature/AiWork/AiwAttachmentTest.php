<?php

namespace Tests\Feature\AiWork;

use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobAttachment;
use App\Models\AiWork\AiwJobMessage;
use App\Models\User;
use App\Services\AiWork\AttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** 지시에 붙는 이미지: 저장·리사이즈·권한·담당자 전달. */
class AiwAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private User $outsider;

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
        $this->outsider = User::factory()->create();

        $this->projectId = DB::table('projects')->insertGetId([
            'name' => '첨부 테스트', 'created_by' => $this->member->id,
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
        ]);

        AiwAgentProject::create([
            'agent_id' => $this->agent->id, 'project_id' => $this->projectId,
            'local_path' => 'E:/work/sample',
        ]);
    }

    private function job(): AiwJob
    {
        return AiwJob::create([
            'project_id' => $this->projectId, 'agent_id' => $this->agent->id,
            'title' => '첨부', 'instruction' => '이 화면을 고쳐 주세요',
            'context_limit_tokens' => 200000, 'allowed_tools' => ['Read'],
            'cost_limit_usd' => 2.0, 'created_by' => $this->member->id,
        ]);
    }

    private function message(AiwJob $job): AiwJobMessage
    {
        return AiwJobMessage::create([
            'job_id' => $job->id, 'seq' => 0, 'role' => 'user',
            'content' => $job->instruction, 'user_id' => $this->member->id,
        ]);
    }

    /** 지정한 크기의 진짜 PNG 를 만든다. UploadedFile::fake()->image() 는 GD 로 열린다. */
    private function png(int $width, int $height): UploadedFile
    {
        return UploadedFile::fake()->image('shot.png', $width, $height);
    }

    // ── 저장·리사이즈 ───────────────────────────────────────────────────────

    public function test_긴_변을_1568로_줄인다(): void
    {
        Storage::fake('local');
        $message = $this->message($this->job());

        $attachment = app(AttachmentService::class)
            ->attach($message, [$this->png(3000, 1500)], $this->member)[0];

        // 이미지 한 장이 컨텍스트를 1,000~1,600 토큰 먹는다. 원본을 그대로 넣으면
        // 전송량만 늘고 정확도는 늘지 않는다.
        $this->assertSame(AttachmentService::MAX_EDGE, $attachment->width);
        $this->assertSame(784, $attachment->height, '비율이 유지되어야 한다.');
        $this->assertTrue(Storage::disk('local')->exists($attachment->path));
    }

    public function test_작은_이미지는_늘리지_않는다(): void
    {
        Storage::fake('local');
        $message = $this->message($this->job());

        $attachment = app(AttachmentService::class)
            ->attach($message, [$this->png(400, 300)], $this->member)[0];

        $this->assertSame(400, $attachment->width);
        $this->assertSame(300, $attachment->height);
    }

    public function test_이미지가_아니면_거부한다(): void
    {
        Storage::fake('local');
        $message = $this->message($this->job());

        $this->expectException(ValidationException::class);

        app(AttachmentService::class)->attach(
            $message,
            [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')],
            $this->member,
        );
    }

    public function test_장수_상한을_넘기면_거부한다(): void
    {
        Storage::fake('local');
        $message = $this->message($this->job());

        $files = array_fill(0, AttachmentService::MAX_PER_MESSAGE + 1, $this->png(100, 100));

        $this->expectException(ValidationException::class);

        app(AttachmentService::class)->attach($message, $files, $this->member);
    }

    public function test_첨부를_지우면_파일도_지운다(): void
    {
        Storage::fake('local');
        $message = $this->message($this->job());

        $attachment = app(AttachmentService::class)
            ->attach($message, [$this->png(100, 100)], $this->member)[0];
        $path = $attachment->path;

        $attachment->delete();

        // DB 행만 지우면 담당자가 404 를 받는 고아 파일이 남는다.
        $this->assertFalse(Storage::disk('local')->exists($path));
    }

    // ── 화면 ────────────────────────────────────────────────────────────────

    public function test_지시_등록에_이미지를_붙일_수_있다(): void
    {
        Storage::fake('local');
        Event::fake();

        $this->actingAs($this->member)
            ->post(route('projects.ai-works.store', $this->projectId), [
                'title' => '화면 수정',
                'agent_id' => $this->agent->id,
                'instruction' => '이 부분을 고쳐 주세요',
                'mode' => 'interactive',
                'allowed_tools' => ['Read'],
                'permission_mode' => 'acceptEdits',
                'cost_limit_usd' => 2.0,
                'images' => [$this->png(800, 600)],
            ])
            ->assertRedirect();

        $job = AiwJob::latest('id')->first();

        $this->assertSame(1, $job->attachments()->count());
        // 최초 지시문(seq 0)에 붙어야 담당자가 첫 프롬프트에 함께 받는다.
        $this->assertSame(0, (int) $job->attachments()->first()->message->seq);
    }

    public function test_프로젝트_멤버만_첨부를_볼_수_있다(): void
    {
        Storage::fake('local');
        $job = $this->job();
        $attachment = app(AttachmentService::class)
            ->attach($this->message($job), [$this->png(100, 100)], $this->member)[0];

        $url = route('projects.ai-works.attachment', [$this->projectId, $job, $attachment]);

        $this->actingAs($this->member)->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($this->outsider)->get($url)->assertForbidden();
    }

    // ── 담당자 전달 ─────────────────────────────────────────────────────────

    public function test_담당자가_첨부_목록과_원본을_받는다(): void
    {
        Storage::fake('local');
        $job = $this->job();
        $attachment = app(AttachmentService::class)
            ->attach($this->message($job), [$this->png(100, 100)], $this->member)[0];

        $daemon = $this->withHeader('Authorization', 'Bearer '.$this->token);

        $daemon->getJson('/api/aiw/jobs/pending')
            ->assertOk()
            ->assertJsonPath('jobs.0.attachments.0.id', $attachment->id)
            ->assertJsonPath('jobs.0.attachments.0.mime', 'image/png');

        $daemon->get("/api/aiw/jobs/{$job->id}/attachments/{$attachment->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_남의_job_첨부는_받을_수_없다(): void
    {
        Storage::fake('local');
        $job = $this->job();
        $attachment = app(AttachmentService::class)
            ->attach($this->message($job), [$this->png(100, 100)], $this->member)[0];

        $otherToken = AiwAgent::generateToken();
        AiwAgent::create([
            'name' => '남의 PC', 'token_hash' => AiwAgent::hashToken($otherToken),
            'user_id' => $this->outsider->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->get("/api/aiw/jobs/{$job->id}/attachments/{$attachment->id}")
            ->assertStatus(404);
    }

    public function test_job이_지워지면_첨부도_사라진다(): void
    {
        Storage::fake('local');
        $job = $this->job();
        app(AttachmentService::class)
            ->attach($this->message($job), [$this->png(100, 100)], $this->member);

        $job->delete();

        $this->assertSame(0, AiwJobAttachment::count());
    }
}
