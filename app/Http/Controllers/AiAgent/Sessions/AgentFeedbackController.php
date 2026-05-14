<?php

namespace App\Http\Controllers\AiAgent\Sessions;

use App\Http\Controllers\AiAgent\Concerns\AuthorizesProject;
use App\Http\Controllers\Controller;
use App\Models\Agent\AiAgentSession;
use App\Models\Agent\AiOutput;
use App\Models\Project;
use Illuminate\View\View;

class AgentFeedbackController extends Controller
{
    use AuthorizesProject;

    public function show(Project $project, AiAgentSession $session, AiOutput $output): View
    {
        $this->authorizeProject($project);
        abort_unless($session->project_id === $project->id && $output->session_id === $session->id, 404);

        return view('ai-agent.agent-sessions.feedback', [
            'project'   => $project,
            'session'   => $session,
            'output'    => $output,
            'feedbacks' => $output->feedbacks,
        ]);
    }

    // Phase 4+: store() — FeedbackAnalysisService
}
