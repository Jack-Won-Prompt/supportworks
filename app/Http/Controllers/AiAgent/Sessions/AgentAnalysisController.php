<?php

namespace App\Http\Controllers\AiAgent\Sessions;

use App\Http\Controllers\AiAgent\Concerns\AuthorizesProject;
use App\Http\Controllers\Controller;
use App\Models\Agent\AiAgentSession;
use App\Models\Project;
use Illuminate\View\View;

class AgentAnalysisController extends Controller
{
    use AuthorizesProject;

    public function show(Project $project, AiAgentSession $session): View
    {
        $this->authorizeProject($project);
        abort_unless($session->project_id === $project->id, 404);

        return view('ai-agent.agent-sessions.analysis', [
            'project' => $project,
            'session' => $session,
            'steps'   => $session->analysisSteps,
        ]);
    }

    // Phase 4+: analyze() — Figma snapshot → OpenAI/Anthropic 구조 분석
}
