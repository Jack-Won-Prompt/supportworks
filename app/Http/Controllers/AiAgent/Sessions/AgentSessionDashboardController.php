<?php

namespace App\Http\Controllers\AiAgent\Sessions;

use App\Http\Controllers\AiAgent\Concerns\AuthorizesProject;
use App\Http\Controllers\Controller;
use App\Models\Agent\AiAgentSession;
use App\Models\Project;
use Illuminate\View\View;

class AgentSessionDashboardController extends Controller
{
    use AuthorizesProject;

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $sessions = AiAgentSession::forProject($project->id)
            ->with(['user:id,name', 'latestOutput'])
            ->orderByDesc('last_activity_at')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return view('ai-agent.agent-sessions.dashboard', [
            'project'  => $project,
            'sessions' => $sessions,
        ]);
    }
}
