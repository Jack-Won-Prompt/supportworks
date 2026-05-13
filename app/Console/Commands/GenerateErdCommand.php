<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\ErdAiService;
use Illuminate\Console\Command;

class GenerateErdCommand extends Command
{
    protected $signature = 'ai-agent:dev:erd-generate
                            {projectId : 프로젝트 ID}
                            {--user=   : 사용자 ID (미지정 시 첫 번째 사용자)}';

    protected $description = 'ERD(데이터 모델)를 AI로 자동 생성하고 산출물로 저장합니다 (T36)';

    public function __construct(
        private readonly ErdAiService $service,
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
        $this->line("  기획서: "         . ($context['planning_doc_id'] ? '✅' : '❌ (없음)'));
        $this->line("  요구사항: "        . ($context['requirements_count'] > 0 ? "✅ {$context['requirements_count']}건" : '⚠️ 없음'));
        $this->line("  화면: "            . ($context['screen_count'] > 0 ? "✅ {$context['screen_count']}건" : '⚠️ 없음'));
        $this->newLine();

        $this->line('ERD 생성 중 (AI 호출)...');

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

            $this->info("ERD 생성 완료!");
            $this->line("  테이블 수: {$result['tables_count']}개");
            $this->line("  산출물 ID: {$result['artifact']->id} (v{$result['artifact']->version})");
            $this->line("  모델: {$result['model']}");
            $this->line("  토큰: in={$result['tokens_in']}, out={$result['tokens_out']}");
            $this->line("  비용: $" . number_format($result['cost'], 4));

        } catch (\Throwable $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error("ERD 생성 실패: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
