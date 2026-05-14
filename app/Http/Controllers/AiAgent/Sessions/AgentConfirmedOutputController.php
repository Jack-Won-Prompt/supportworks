<?php

namespace App\Http\Controllers\AiAgent\Sessions;

use App\Http\Controllers\AiAgent\Concerns\AuthorizesProject;
use App\Http\Controllers\Controller;
use App\Models\Agent\AiConfirmedOutput;
use App\Models\Project;
use Illuminate\View\View;

class AgentConfirmedOutputController extends Controller
{
    use AuthorizesProject;

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $confirmed = AiConfirmedOutput::forProject($project->id)
            ->with(['output.session.user:id,name', 'confirmer:id,name'])
            ->orderByDesc('confirmed_at')
            ->get();

        return view('ai-agent.agent-sessions.confirmed', [
            'project'   => $project,
            'confirmed' => $confirmed,
        ]);
    }

    // Phase 4+: confirm() — ConfirmedOutputService
}
