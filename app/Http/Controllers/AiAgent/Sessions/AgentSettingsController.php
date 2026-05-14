<?php

namespace App\Http\Controllers\AiAgent\Sessions;

use App\Http\Controllers\AiAgent\Concerns\AuthorizesProject;
use App\Http\Controllers\Controller;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\Project;
use App\Services\Agent\AiProviderFactory;
use Illuminate\View\View;

class AgentSettingsController extends Controller
{
    use AuthorizesProject;

    public function show(Project $project): View
    {
        $this->authorizeProject($project);

        return view('ai-agent.agent-sessions.settings', [
            'project'            => $project,
            'config'             => ProjectAiAgentConfig::forProject($project->id),
            'availableProviders' => AiProviderFactory::available(),
            'defaultProvider'    => (string) config('ai-agent.sessions.default_provider'),
            'mockEnabled'        => (bool) config('ai-agent.sessions.mock_when_unconfigured'),
        ]);
    }
}
