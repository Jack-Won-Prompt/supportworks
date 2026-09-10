<?php

namespace Tests\Feature\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobLog;
use App\Models\AiWork\AiwJobMessage;
use App\Models\AiWork\AiwPermissionRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 2 — 스키마·모델 규칙 검증.
 *
 * 여기서 고정하는 것은 Phase 4 의 서비스 계층이 의존하는 불변식들이다.
 */
class AiwSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    private function agent(User $owner): AiwAgent
    {
        $raw = AiwAgent::generateToken();

        return AiwAgent::create([
            'name' => '테스트 PC',
            'token_hash' => AiwAgent::hashToken($raw),
            'user_id' => $owner->id,
            'expires_at' => now()->addDays(90),
        ]);
    }

    private function project(User $owner): int
    {
        // projects 테이블은 이 기능 밖의 것이라 최소 컬럼만 채워 직접 만든다.
        return DB::table('projects')->insertGetId([
            'name' => 'AI Works 테스트 프로젝트',
            'created_by' => $owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function job(array $overrides = []): AiwJob
    {
        $user = $overrides['_user'] ?? $this->user();
        unset($overrides['_user']);

        return AiwJob::create(array_merge([
            'project_id' => $this->project($user),
            'agent_id' => $this->agent($user)->id,
            'title' => '테스트 지시',
            'instruction' => 'README 에 한 줄 추가',
            'mode' => 'interactive',
            'context_limit_tokens' => config('aiw.default_context_limit_tokens'),
            'allowed_tools' => config('aiw.default_tools'),
            'permission_mode' => 'acceptEdits',
            'cost_limit_usd' => config('aiw.default_cost_limit_usd'),
            'created_by' => $user->id,
        ], $overrides));
    }

    public function test_여섯_테이블이_모두_생성된다(): void
    {
        foreach ([
            'aiw_agents', 'aiw_agent_projects', 'aiw_jobs',
            'aiw_job_logs', 'aiw_job_messages', 'aiw_permission_requests',
        ] as $table) {
            $this->assertTrue(
                \Schema::hasTable($table),
                "{$table} 테이블이 없다."
            );
        }
    }

    public function test_에이전트는_원문_토큰으로_조회된다(): void
    {
        $raw = AiwAgent::generateToken();
        $agent = AiwAgent::create([
            'name' => 'PC',
            'token_hash' => AiwAgent::hashToken($raw),
            'user_id' => $this->user()->id,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertTrue($agent->is(AiwAgent::findByToken($raw)));
        $this->assertNull(AiwAgent::findByToken('aiw_wrong'));
    }

    public function test_만료된_토큰은_조회되지_않는다(): void
    {
        $raw = AiwAgent::generateToken();
        AiwAgent::create([
            'name' => 'PC',
            'token_hash' => AiwAgent::hashToken($raw),
            'user_id' => $this->user()->id,
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertNull(AiwAgent::findByToken($raw));
    }

    public function test_온라인_판정은_last_seen_at으로_계산된다(): void
    {
        $agent = $this->agent($this->user());

        $this->assertFalse($agent->is_online, 'last_seen_at 이 null 이면 오프라인이어야 한다.');

        $agent->update(['last_seen_at' => now()]);
        $this->assertTrue($agent->fresh()->is_online);

        $agent->update(['last_seen_at' => now()->subSeconds(config('aiw.offline_after_sec') + 10)]);
        $this->assertFalse($agent->fresh()->is_online);
    }

    public function test_허용_IP_규칙(): void
    {
        $agent = $this->agent($this->user());

        $this->assertTrue($agent->allowsIp('1.2.3.4'), '목록이 비면 제한 없음이어야 한다.');

        $agent->allowed_ips = ['10.0.0.5', '192.168.1.0/24'];
        $this->assertTrue($agent->allowsIp('10.0.0.5'));
        $this->assertTrue($agent->allowsIp('192.168.1.77'));
        $this->assertFalse($agent->allowsIp('192.168.2.1'));
    }

    public function test_종료된_job의_지시는_수정할_수_없다(): void
    {
        $job = $this->job();
        $job->forceFill(['status' => AiwJobStatus::Completed])->save();

        $this->expectException(RuntimeException::class);

        $job->fresh()->update(['instruction' => '바꿔치기']);
    }

    public function test_종료_전이_시점에는_결과를_함께_기록할_수_있다(): void
    {
        $job = $this->job();

        // running -> completed 로 가면서 결과를 쓰는 것은 허용되어야 한다.
        $job->update([
            'status' => AiwJobStatus::Completed,
            'result_summary' => '완료했습니다',
            'cost_usd' => 0.12,
        ]);

        $this->assertSame('완료했습니다', $job->fresh()->result_summary);
    }

    public function test_종료된_job도_결과_필드는_갱신할_수_있다(): void
    {
        $job = $this->job();
        $job->forceFill(['status' => AiwJobStatus::Failed])->save();

        // 불변 대상이 아닌 컬럼은 막지 않는다(예: 사후 diff 이관).
        $job->fresh()->update(['git_diff_path' => 'aiw/diffs/job-1.diff']);

        $this->assertSame('aiw/diffs/job-1.diff', $job->fresh()->git_diff_path);
    }

    public function test_비용_상한은_0이하일_수_없다(): void
    {
        $this->expectException(RuntimeException::class);

        $this->job(['cost_limit_usd' => 0]);
    }

    public function test_로그_seq는_job당_유일하다(): void
    {
        $job = $this->job();

        AiwJobLog::create(['job_id' => $job->id, 'seq' => 1, 'type' => 'system', 'content' => 'a']);

        $this->expectException(QueryException::class);

        AiwJobLog::create(['job_id' => $job->id, 'seq' => 1, 'type' => 'system', 'content' => 'b']);
    }

    public function test_메시지_seq는_로그와_별도_시퀀스다(): void
    {
        $job = $this->job();

        AiwJobLog::create(['job_id' => $job->id, 'seq' => 1, 'type' => 'system', 'content' => 'log']);
        $msg = AiwJobMessage::create([
            'job_id' => $job->id, 'seq' => 1, 'role' => 'assistant', 'content' => 'msg',
        ]);

        $this->assertSame(1, $msg->seq, '같은 seq 라도 테이블이 다르면 충돌하지 않아야 한다.');
    }

    public function test_승인요청_request_key는_유일하다(): void
    {
        $job = $this->job();
        $payload = [
            'job_id' => $job->id, 'request_key' => 'uuid-1',
            'tool_name' => 'Bash', 'tool_input' => ['command' => 'npm test'],
        ];

        AiwPermissionRequest::create($payload);

        $this->expectException(QueryException::class);

        AiwPermissionRequest::create($payload);
    }

    public function test_승인요청_결정이_데몬_형식으로_변환된다(): void
    {
        $job = $this->job();

        $req = AiwPermissionRequest::create([
            'job_id' => $job->id, 'request_key' => 'k1',
            'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls'],
        ]);

        $this->assertSame(['behavior' => 'pending'], $req->decisionForDaemon());

        $req->update(['status' => 'denied', 'deny_reason' => '위험합니다']);
        $this->assertSame(
            ['behavior' => 'deny', 'message' => '위험합니다'],
            $req->fresh()->decisionForDaemon()
        );

        $req->update(['status' => 'expired']);
        $this->assertSame('timeout', $req->fresh()->decisionForDaemon()['message']);
    }

    public function test_미전달_사용자_메시지만_inbox에_잡힌다(): void
    {
        $job = $this->job();

        AiwJobMessage::create(['job_id' => $job->id, 'seq' => 1, 'role' => 'user', 'content' => '대기']);
        AiwJobMessage::create([
            'job_id' => $job->id, 'seq' => 2, 'role' => 'user', 'content' => '전달됨',
            'delivered_at' => now(),
        ]);
        AiwJobMessage::create(['job_id' => $job->id, 'seq' => 3, 'role' => 'assistant', 'content' => '응답']);

        $pending = AiwJobMessage::undelivered()->where('job_id', $job->id)->get();

        $this->assertCount(1, $pending);
        $this->assertSame('대기', $pending->first()->content);
    }

    public function test_job_삭제_시_하위_레코드가_함께_지워진다(): void
    {
        $job = $this->job();
        AiwJobLog::create(['job_id' => $job->id, 'seq' => 1, 'type' => 'system', 'content' => 'x']);
        AiwJobMessage::create(['job_id' => $job->id, 'seq' => 1, 'role' => 'user', 'content' => 'y']);
        AiwPermissionRequest::create([
            'job_id' => $job->id, 'request_key' => 'k', 'tool_name' => 'Bash', 'tool_input' => [],
        ]);

        $job->delete();

        $this->assertDatabaseCount('aiw_job_logs', 0);
        $this->assertDatabaseCount('aiw_job_messages', 0);
        $this->assertDatabaseCount('aiw_permission_requests', 0);
    }

    public function test_세션_체인에서_현재_세션을_읽는다(): void
    {
        $job = $this->job();

        $this->assertNull($job->currentSessionId());

        $job->update(['session_chain' => [
            ['session_id' => 's1', 'started_at' => '2026-09-10T00:00:00Z', 'ended_at' => '2026-09-10T01:00:00Z', 'reason' => 'context'],
            ['session_id' => 's2', 'started_at' => '2026-09-10T01:00:00Z'],
        ]]);

        $job = $job->fresh();
        $this->assertSame('s2', $job->currentSessionId());
        $this->assertSame(1, $job->currentSessionIndex());
    }

    public function test_브랜치명은_use_branch에_따라_결정된다(): void
    {
        $job = $this->job();
        $this->assertSame('aiw/job-'.$job->id, $job->branchName());

        $job->update(['use_branch' => false]);
        $this->assertNull($job->fresh()->branchName());
    }

    public function test_raw_로그는_한도를_넘으면_잘린다(): void
    {
        $small = ['type' => 'tool_use', 'name' => 'Read'];
        $this->assertSame($small, AiwJobLog::truncateRaw($small));

        $big = ['blob' => str_repeat('가', 5000)];
        $out = AiwJobLog::truncateRaw($big);

        $this->assertTrue($out['truncated']);
        $this->assertArrayHasKey('preview', $out);
    }
}
