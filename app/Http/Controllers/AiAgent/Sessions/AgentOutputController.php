<?php

namespace App\Http\Controllers\AiAgent\Sessions;

use App\Http\Controllers\AiAgent\Concerns\AuthorizesProject;
use App\Http\Controllers\Controller;
use App\Models\Agent\AiAgentSession;
use App\Models\Agent\AiOutput;
use App\Models\Project;
use Illuminate\View\View;

class AgentOutputController extends Controller
{
    use AuthorizesProject;

    public function index(Project $project, AiAgentSession $session): View
    {
        $this->authorizeProject($project);
        abort_unless($session->project_id === $project->id, 404);

        return view('ai-agent.agent-sessions.outputs', [
            'project' => $project,
            'session' => $session,
            'outputs' => $session->outputs()->with('feedbacks')->get(),
        ]);
    }

    public function show(Project $project, AiAgentSession $session, AiOutput $output): View
    {
        $this->authorizeProject($project);
        abort_unless($session->project_id === $project->id && $output->session_id === $session->id, 404);

        return view('ai-agent.agent-sessions.output-show', [
            'project' => $project,
            'session' => $session,
            'output'  => $output,
            'files'   => $output->files,
        ]);
    }

    // Phase 4+: generate(), download() — OutputGenerationService + ZipService와 함께
}
