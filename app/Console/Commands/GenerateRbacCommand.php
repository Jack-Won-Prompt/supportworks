<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\RbacAiService;
use Illuminate\Console\Command;

class GenerateRbacCommand extends Command
{
    protected $signature = 'ai-agent:dev:rbac-generate
                            {projectId : 프로젝트 ID}
                            {--user=   : 사용자 ID (미지정 시 첫 번째 사용자)}';

    protected $description = 'RBAC 권한 모델을 AI로 자동 생성하고 산출물로 저장합니다 (T38)';

    public function __construct(
        private readonly RbacAiService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $projectId = (int) $this->argument('projectId');
        $project   = Project::find($projectId);

        if (!$project) {
            $this->error("프로젝트 ID {$projectId}를 찾을 수 없습니다.");
            return self::FAILURE;
        }

        $userId = $this->option('user')
            ? (int) $this->option('user')
            : (User::first()?->id ?? 1);

        $this->info("프로젝트: {$project->name}");
        $this->newLine();

        $context = $this->service->buildContext($projectId);
        $this->line("사전 조건:");
        $this->line("  ERD: "         . ($context['erd_artifact_id']      ? "✅ (테이블 {$context['tables_count']}개)"    : '⚠️ (없음, 기본 모델로 진행)'));
        $this->line("  API 명세: "    . ($context['api_spec_artifact_id'] ? "✅ (엔드포인트 {$context['endpoints_count']}개)" : '⚠️ (없음)'));
        $this->line("  화면: "        . ($context['screen_count'] > 0     ? "✅ {$context['screen_count']}건"            : '⚠️ 없음'));
        $this->newLine();

        $this->line('RBAC 권한 모델 생성 중 (AI 호출)...');

        $bar = $this->output->createProgressBar(100);
        $bar->start();

        try {
            $result = $this->service->generate(
                projectId:  $projectId,
                userId:     $userId,
                onProgress: function (array $p) use ($bar) {
                    $bar->setProgress($p['progress'] ?? 0);
                    if (!empty($p['message'])) {
                        $bar->setMessage($p['message']);
                    }
                },
            );

            $bar->finish();
            $this->newLine(2);

            $this->info("RBAC 권한 모델 생성 완료!");
            $this->line("  역할 수: {$result['roles_count']}개");
            $this->line("  권한 수: {$result['permissions_count']}개");
            $this->line("  산출물 ID: {$result['artifact']->id} (v{$result['artifact']->version})");
            $this->line("  모델: {$result['model']}");
            $this->line("  토큰: in={$result['tokens_in']}, out={$result['tokens_out']}");
            $this->line("  비용: $" . number_format($result['cost'], 4));

            // Print role summary
            $this->newLine();
            $this->line("역할 목록:");
            $rbacData = json_decode($result['artifact']->content, true);
            foreach ($rbacData['roles'] ?? [] as $role) {
                $permCount = count($role['permissions'] ?? []);
                $this->line("  [{$role['key']}] {$role['name']} — 권한 {$permCount}개");
            }

        } catch (\Throwable $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error("RBAC 생성 실패: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
