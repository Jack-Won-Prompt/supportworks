<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\UserManualService;
use Illuminate\Console\Command;

class GenerateUserManualCommand extends Command
{
    protected $signature = 'ai-agent:release:user-manual
                            {projectId : 프로젝트 ID}
                            {--user=         : 사용자 ID (미지정 시 첫 번째 사용자)}
                            {--output=       : 출력 파일 경로 (.md)}
                            {--with-images   : Figma 이미지 포함 ZIP 생성}';

    protected $description = '사용자 매뉴얼을 자동 생성합니다 (T50)';

    public function __construct(
        private readonly UserManualService $service,
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

        $start = microtime(true);

        if ($this->option('with-images')) {
            $this->info('사용자 매뉴얼 + Figma 이미지 ZIP 생성 중...');
            try {
                $zipPath = $this->service->generatePackage($project, $user);
            } catch (\Throwable $e) {
                $this->error("생성 실패: " . $e->getMessage());
                return self::FAILURE;
            }

            $elapsed = round(microtime(true) - $start, 1);
            $sizeMb  = round(filesize($zipPath) / 1024 / 1024, 2);

            $this->newLine();
            $this->info("✅ ZIP 생성 완료 ({$elapsed}초)");
            $this->line("📦 경로: {$zipPath}");
            $this->line("📊 크기: {$sizeMb} MB");

        } else {
            $this->info('사용자 매뉴얼 생성 중...');
            try {
                $artifact = $this->service->generate($project, $user);
            } catch (\Throwable $e) {
                $this->error("생성 실패: " . $e->getMessage());
                return self::FAILURE;
            }

            $elapsed = round(microtime(true) - $start, 1);
            $bytes   = strlen($artifact->content ?? '');

            $this->newLine();
            $this->info("✅ 매뉴얼 생성 완료 ({$elapsed}초)");
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
        }

        return self::SUCCESS;
    }
}
