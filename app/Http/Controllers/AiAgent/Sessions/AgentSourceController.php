<?php

namespace App\Http\Controllers\AiAgent\Sessions;

use App\Http\Controllers\AiAgent\Concerns\AuthorizesProject;
use App\Http\Controllers\Controller;
use App\Models\Agent\AiAgentSession;
use App\Models\Project;
use Illuminate\View\View;

class AgentSourceController extends Controller
{
    use AuthorizesProject;

    public function show(Project $project, AiAgentSession $session): View
    {
        $this->authorizeProject($project);
        abort_unless($session->project_id === $project->id, 404);

        return view('ai-agent.agent-sessions.source', [
            'project' => $project,
            'session' => $session,
            'sources' => $session->figmaSources()->latest()->get(),
        ]);
    }

    // store/connect는 Phase 4에서 FigmaSourceService와 함께 구현
}
