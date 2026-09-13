<?php

namespace Tests\Feature\AiWork;

use App\Models\AiWork\AiwErrorSource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 토큰을 발급해 사이트 .env 에 바로 써 넣는 명령.
 *
 * 이 명령이 있는 이유는 하나다 — 토큰 원문이 사람의 눈과 클립보드를 거치지
 * 않게 하는 것. 그래서 여기서 지킬 것도 하나다: 원문이 출력에 나오지 않을 것.
 */
class AiwErrorTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private string $envPath;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->project = Project::create([
            'name' => 'korsafety', 'status' => 'active', 'created_by' => $admin->id,
        ]);

        $this->envPath = tempnam(sys_get_temp_dir(), 'aiwenv');

        file_put_contents($this->envPath, "APP_NAME=korsafety\nDB_HOST=127.0.0.1\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->envPath);

        parent::tearDown();
    }

    private function env(): string
    {
        return (string) file_get_contents($this->envPath);
    }

    public function test_토큰을_만들어_env_에_적는다(): void
    {
        $this->artisan('aiw:issue-error-token', [
            'project' => 'korsafety',
            '--env'   => $this->envPath,
        ])->assertSuccessful();

        $source = AiwErrorSource::firstOrFail();

        $this->assertSame($this->project->id, $source->project_id);
        $this->assertTrue($source->enabled);

        $env = $this->env();

        $this->assertStringContainsString('SW_ERROR_TOKEN=aiwerr_', $env);
        $this->assertStringContainsString('SW_ERROR_URL=', $env);
        // 원래 있던 줄을 건드리면 사이트가 뜨지 않는다.
        $this->assertStringContainsString('APP_NAME=korsafety', $env);
        $this->assertStringContainsString('DB_HOST=127.0.0.1', $env);
    }

    public function test_적은_토큰이_저장된_해시와_맞는다(): void
    {
        $this->artisan('aiw:issue-error-token', [
            'project' => 'korsafety',
            '--env'   => $this->envPath,
        ]);

        preg_match('/^SW_ERROR_TOKEN=(\S+)$/m', $this->env(), $m);

        $this->assertNotEmpty($m[1] ?? null, '.env 에 토큰이 적혀 있어야 한다.');
        $this->assertSame(
            AiwErrorSource::hashToken($m[1]),
            AiwErrorSource::firstOrFail()->token_hash,
        );
    }

    public function test_토큰_원문은_출력에_나오지_않는다(): void
    {
        // 이 명령의 존재 이유다. 화면에 찍히면 화면에서 발급하는 것과 같아진다.
        $this->artisan('aiw:issue-error-token', [
            'project' => 'korsafety',
            '--env'   => $this->envPath,
        ])->doesntExpectOutputToContain('aiwerr_');
    }

    public function test_다시_발급하면_기존_줄을_덮는다(): void
    {
        // 줄이 두 번 적히면 어느 것이 살아 있는지 알 수 없다.
        $this->artisan('aiw:issue-error-token', ['project' => 'korsafety', '--env' => $this->envPath]);
        $this->artisan('aiw:issue-error-token', ['project' => 'korsafety', '--env' => $this->envPath]);

        $this->assertSame(1, preg_match_all('/^SW_ERROR_TOKEN=/m', $this->env()));
    }

    public function test_없는_프로젝트는_거부한다(): void
    {
        $this->artisan('aiw:issue-error-token', ['project' => '없는것', '--env' => $this->envPath])
            ->assertFailed();

        $this->assertSame(0, AiwErrorSource::count());
    }

    public function test_env_경로가_틀리면_아무것도_만들지_않는다(): void
    {
        // 토큰만 만들어 두고 .env 에 적지 못하면, 아무도 모르는 자격이 남는다.
        $this->artisan('aiw:issue-error-token', [
            'project' => 'korsafety',
            '--env'   => '/없는/경로/.env',
        ])->assertFailed();

        $this->assertSame(0, AiwErrorSource::count());
    }
}
