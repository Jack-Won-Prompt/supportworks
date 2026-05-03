<?php

namespace App\View\Composers;

use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\Project;
use Illuminate\View\View;

class AiAgentComposer
{
    public function compose(View $view): void
    {
        $project = $this->resolveProject();

        if (!$project) {
            $view->with([
                'aiProject'       => null,
                'aiConfig'        => null,
                'aiStages'        => collect(),
                'aiCurrentSection' => $this->currentSection(),
            ]);
            return;
        }

        $config = ProjectAiAgentConfig::where('project_id', $project->id)->first();

        $stages = $config
            ? AiAgentProjectStage::where('project_id', $project->id)
                ->orderBy('order')
                ->get()
                ->keyBy(fn($s) => $s->type->value)
            : collect();

        $view->with([
            'aiProject'        => $project,
            'aiConfig'         => $config,
            'aiStages'         => $stages,
            'aiCurrentSection' => $this->currentSection(),
        ]);
    }

    private function resolveProject(): ?Project
    {
        $route  = request()->route();
        if (!$route) {
            return null;
        }

        $param = $route->parameter('project');

        if ($param instanceof Project) {
            return $param;
        }

        if (is_numeric($param)) {
            return Project::find($param);
        }

        return null;
    }

    private function currentSection(): string
    {
        return match (true) {
            request()->routeIs('ai-agent.projects.design.*')  => 'design',
            request()->routeIs('ai-agent.projects.pre-dev.*') => 'pre-dev',
            request()->routeIs('ai-agent.projects.dev.*')     => 'dev',
            request()->routeIs('ai-agent.projects.release')   => 'release',
            request()->routeIs('ai-agent.projects.common.*')  => 'common',
            default                                            => 'planning',
        };
    }
}
