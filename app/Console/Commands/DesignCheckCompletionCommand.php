<?php

namespace App\Console\Commands;

use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Services\Agent\DesignCompletionService;
use Illuminate\Console\Command;

class DesignCheckCompletionCommand extends Command
{
    protected $signature = 'ai-agent:design:check-completion
                            {projectId : 프로젝트 ID}';

    protected $description = '디자인 단계 완성도를 진단하고 승인 가능 여부를 출력합니다 (T35)';

    public function __construct(
        private readonly DesignCompletionService $service,
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

        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::DESIGN)
            ->first();

        if (!$stage) {
            $this->error("디자인 단계 레코드를 찾을 수 없습니다. 프로젝트 초기화를 확인하세요.");
            return self::FAILURE;
        }

        $this->info("프로젝트: {$project->name}");
        $this->newLine();

        $d = $this->service->analyze($project->id, $stage->id);

        $this->line("전체 완성도: <comment>{$d['overall_percent']}%</comment>");
        $this->newLine();

        // Blocking
        $this->line("<options=bold>필수 산출물 ({$d['blocking_complete']}/{$d['blocking_total']})</>");
        foreach ($d['blocking'] as $item) {
            $mark = $item['complete'] ? '✅' : '❌';
            $this->line("  {$mark} {$item['label']}");
        }
        $this->newLine();

        // Warnings
        $this->line("<options=bold>권장 산출물 ({$d['warning_complete']}/{$d['warning_total']})</>");
        foreach ($d['warnings'] as $item) {
            $mark = $item['complete'] ? '✅' : '⚠️';
            $extra = '';
            if ($item['coverage'] !== null) {
                if ($item['type'] === 'screen_mapping') {
                    $extra = " ({$item['covered']}/{$item['total']}, {$item['coverage']}%)";
                } else {
                    $extra = " ({$item['coverage']}점)";
                }
            }
            $this->line("  {$mark} {$item['label']}{$extra}");
            if ($item['note']) {
                $this->line("      <fg=gray>{$item['note']}</>");
            }
        }
        $this->newLine();

        if ($d['can_request']) {
            $this->info('승인 요청 가능 상태입니다.');
        } else {
            $this->warn('승인 요청 불가 — 필수 산출물 미완성: ' . implode(', ', $d['missing_required']));
        }

        return self::SUCCESS;
    }
}
