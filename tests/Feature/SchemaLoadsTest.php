<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 테스트 DB 부트스트랩이 실제로 동작하는지 고정한다.
 *
 * 이 저장소의 마이그레이션 이력은 처음부터 순서대로 재생할 수 없다.
 * (예: 2026_05_04_000001_rename_task_id_to_schedule_id_in_prompt_tables 가
 *  2026_05_04_600000_create_prompt_sessions_table 보다 먼저 실행된다.)
 * 그래서 database/schema/mysql-schema.sql 스쿼시 스키마로 테스트 DB를 만든다.
 * 이 테스트가 깨지면 RefreshDatabase 를 쓰는 모든 테스트가 함께 죽는다.
 */
class SchemaLoadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_스쿼시_스키마로_테스트DB가_구성된다(): void
    {
        $tables = DB::select('SHOW TABLES');

        $this->assertGreaterThan(150, count($tables), '스키마 파일이 로드되지 않았다.');
        $this->assertGreaterThan(300, DB::table('migrations')->count());
    }

    public function test_모델을_실제로_저장하고_읽을_수_있다(): void
    {
        $user = User::factory()->create([
            'name' => '테스트 사용자',
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => '테스트 사용자']);
        $this->assertSame('테스트 사용자', User::find($user->id)->name);
    }

    public function test_테스트간_DB가_롤백된다(): void
    {
        // 앞 테스트에서 만든 사용자가 남아 있으면 안 된다.
        $this->assertSame(0, User::query()->count());
    }
}
