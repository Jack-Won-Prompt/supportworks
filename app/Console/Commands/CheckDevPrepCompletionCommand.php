<?php

namespace App\Console\Commands;

use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Services\Agent\DevPrepCompletionService;
use Illuminate\Console\Command;

class CheckDevPrepCompletionCommand extends Command
{
    protected $signature = 'ai-agent:dev-prep:check-completion
                            {projectId : 프로젝트 ID}';

    protected $description = '개발 준비 단계 완성도를 진단하고 승인 가능 여부를 확인합니다 (T42)';

    public function __construct(
        private readonly DevPrepCompletionService $service,
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
            ->where('type', StageType::DEV_PREP)
            ->first();

        if (!$stage) {
            $this->error("개발 준비 단계가 존재하지 않습니다. 프로젝트를 먼저 초기화하세요.");
            return self::FAILURE;
        }

        $this->info("프로젝트: {$project->name}");
        $this->newLine();

        $d = $this->service->analyze($project->id, $stage->id);

        $this->line("전체 완성도: <comment>{$d['overall_percent']}%</comment>");
        $this->newLine();

        // Blocking
        $this->line("── 필수 산출물 ({$d['blocking_complete']}/{$d['blocking_total']}) ──");
        foreach ($d['blocking'] as $item) {
            $icon = $item['complete'] ? '✅' : '❌';
            $this->line("  {$icon}  {$item['label']}");
        }

        $this->newLine();

        // Warnings
        $this->line("── 권장 산출물 ({$d['warning_complete']}/{$d['warning_total']}) ──");
        foreach ($d['warnings'] as $item) {
            $icon = $item['complete'] ? '✅' : '⚠️ ';
            $label = $item['label'];

            if (isset($item['covered'])) {
                $label .= " ({$item['covered']}/{$item['total']}";
                if ($item['coverage'] !== null) $label .= " · {$item['coverage']}%";
                $label .= ')';
            }

            if ($item['type'] === 'code_validation' && isset($item['avg_score'])) {
                $label .= " — 평균 {$item['avg_score']}점";
                if (($item['critical_count'] ?? 0) > 0) {
                    $label .= ", Critical {$item['critical_count']}건";
                }
            }

            $this->line("  {$icon}  {$label}");
            if ($item['note']) {
                $this->line("       <fg=gray>{$item['note']}</>");
            }
        }

        $this->newLine();

        if ($d['can_request']) {
            $this->info("승인 요청 가능합니다.");
        } else {
            $this->warn("승인 요청 불가 — 필수 산출물 미완성: " . implode(', ', $d['missing_required']));
        }

        return self::SUCCESS;
    }
}
