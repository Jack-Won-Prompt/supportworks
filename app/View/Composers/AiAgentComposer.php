<?php

namespace App\View\Composers;

use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentUserCredential;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AiAgentComposer
{
    public function compose(View $view): void
    {
        $project = $this->resolveProject();

        if (!$project) {
            $view->with(array_merge([
                'aiProject'        => null,
                'aiConfig'         => null,
                'aiStages'         => collect(),
                'aiCurrentSection' => $this->currentSection(),
                'aiSwitchProjects' => collect(),
            ], $this->figmaData()));
            return;
        }

        $config = ProjectAiAgentConfig::where('project_id', $project->id)->first();

        $stages = $config
            ? AiAgentProjectStage::where('project_id', $project->id)
                ->orderBy('order')
                ->get()
                ->keyBy(fn($s) => $s->type->value)
            : collect();

        $view->with(array_merge([
            'aiProject'        => $project,
            'aiConfig'         => $config,
            'aiStages'         => $stages,
            'aiCurrentSection' => $this->currentSection(),
            'aiSwitchProjects' => $this->enabledProjectsExcept($project),
        ], $this->figmaData()));
    }

    private function figmaData(): array
    {
        $user       = Auth::user();
        $credential = $user ? AiAgentUserCredential::where('user_id', $user->id)->first() : null;
        $hasToken   = $credential && $credential->hasPat();

        return [
            'aiFigmaHasToken'      => $hasToken,
            'aiFigmaStatus'        => $credential?->figma_pat_validation_status,
            'aiFigmaLastValidated' => $credential?->figma_pat_validated_at,
            'aiFigmaMaskedPat'     => $hasToken ? $credential->maskedPat() : null,
        ];
    }

    private function enabledProjectsExcept(?Project $current): Collection
    {
        $user = Auth::user();
        if (!$user) {
            return collect();
        }

        $allProjects = $user->isAdmin()
            ? Project::orderBy('name')->get()
            : $user->projects()->orderBy('name')->get();

        $configuredIds = ProjectAiAgentConfig::whereIn('project_id', $allProjects->pluck('id'))
            ->pluck('project_id');

        return $allProjects
            ->filter(fn($p) => $configuredIds->contains($p->id) && (!$current || $p->id !== $current->id))
            ->values();
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
            request()->routeIs('ai-agent.projects.design.*')          => 'design',
            request()->routeIs('ai-agent.projects.pre-dev.*')         => 'pre-dev',
            request()->routeIs('ai-agent.projects.dev.*')             => 'dev',
            request()->routeIs('ai-agent.projects.release')           => 'release',
            request()->routeIs('ai-agent.projects.common.*')          => 'common',
            request()->routeIs('ai-agent.projects.agent-sessions.*')  => 'agent-sessions',
            request()->routeIs('ai-agent.projects.deliverables.*')    => 'deliverables',
            default                                                    => 'planning',
        };
    }
}
