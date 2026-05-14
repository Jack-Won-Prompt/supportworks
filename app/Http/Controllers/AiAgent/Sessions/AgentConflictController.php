<?php

namespace App\Http\Controllers\AiAgent\Sessions;

use App\Http\Controllers\AiAgent\Concerns\AuthorizesProject;
use App\Http\Controllers\Controller;
use App\Models\Agent\AiAgentSession;
use App\Models\Project;
use Illuminate\View\View;

class AgentConflictController extends Controller
{
    use AuthorizesProject;

    public function index(Project $project, AiAgentSession $session): View
    {
        $this->authorizeProject($project);
        abort_unless($session->project_id === $project->id, 404);

        return view('ai-agent.agent-sessions.conflicts', [
            'project'   => $project,
            'session'   => $session,
            'conflicts' => $session->conflicts()->with('output')->orderByDesc('created_at')->get(),
        ]);
    }

    // Phase 4+: storeDecision() — ConflictDetectionService
}
