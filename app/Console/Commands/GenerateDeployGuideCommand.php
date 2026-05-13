<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\DeployGuideService;
use Illuminate\Console\Command;

class GenerateDeployGuideCommand extends Command
{
    protected $signature = 'ai-agent:release:deploy-guide
                            {projectId : 프로젝트 ID}
                            {--user=   : 사용자 ID (미지정 시 첫 번째 사용자)}
                            {--output= : 출력 파일 경로 (미지정 시 artifact로만 저장)}';

    protected $description = '배포 가이드를 자동 생성합니다 (T49)';

    public function __construct(
        private readonly DeployGuideService $service,
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

        $user = $this->option('user')
            ? User::findOrFail((int) $this->option('user'))
            : (User::first() ?? throw new \RuntimeException('사용자가 없습니다.'));

        $this->info("프로젝트: {$project->name}");
        $this->info('배포 가이드 생성 중...');

        $start = microtime(true);

        try {
            $artifact = $this->service->generate($project, $user);
        } catch (\Throwable $e) {
            $this->error("생성 실패: " . $e->getMessage());
            return self::FAILURE;
        }

        $elapsed = round(microtime(true) - $start, 1);
        $bytes   = strlen($artifact->content ?? '');

        $this->newLine();
        $this->info("✅ 배포 가이드 생성 완료 ({$elapsed}초)");
        $this->line("📄 Artifact ID: {$artifact->id}");
        $this->line("📊 크기: " . number_format($bytes) . " bytes");

        if ($output = $this->option('output')) {
            file_put_contents($output, $artifact->content ?? '');
            $this->line("💾 저장 경로: {$output}");
        }

        // 미리보기 (첫 5줄)
        $lines = array_slice(explode("\n", $artifact->content ?? ''), 0, 5);
        $this->newLine();
        $this->info('미리보기:');
        foreach ($lines as $line) {
            $this->line("  " . $line);
        }

        return self::SUCCESS;
    }
}
