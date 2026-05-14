<?php

namespace App\Http\Controllers\AiAgent\Sessions;

use App\Http\Controllers\AiAgent\Concerns\AuthorizesProject;
use App\Http\Controllers\Controller;
use App\Models\Agent\AiAgentSession;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\Project;
use App\Services\Agent\AiProviderFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentSessionController extends Controller
{
    use AuthorizesProject;

    public function create(Project $project): View
    {
        $this->authorizeProject($project);

        $config = ProjectAiAgentConfig::forProject($project->id);

        return view('ai-agent.agent-sessions.create', [
            'project'             => $project,
            'config'              => $config,
            'availableProviders'  => AiProviderFactory::available(),
            'defaultProvider'     => (string) config('ai-agent.sessions.default_provider', 'anthropic'),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'ai_provider' => ['required', 'in:anthropic,openai,auto'],
        ]);

        $config = ProjectAiAgentConfig::forProject($project->id);
        abort_unless($config, 422, 'AI Agent 설정이 없는 프로젝트입니다. 먼저 stack을 지정하세요.');

        $session = AiAgentSession::create([
            'project_id'       => $project->id,
            'user_id'          => auth()->id(),
            'title'            => $validated['title'],
            'output_type'      => $config->frontend_stack->value,
            'status'           => \App\Enums\Agent\AgentSessionStatus::DRAFT->value,
            'current_step'     => \App\Enums\Agent\AgentSessionStep::OUTPUT_TYPE_SELECTED->value,
            'ai_provider'      => $validated['ai_provider'],
            'last_activity_at' => now(),
        ]);

        return redirect()->route('ai-agent.projects.agent-sessions.show', [$project, $session]);
    }

    public function show(Project $project, AiAgentSession $session): View
    {
        $this->authorizeProject($project);
        abort_unless($session->project_id === $project->id, 404);

        $session->load(['user:id,name', 'activeFigmaSource', 'analysisSteps', 'outputs', 'conflicts']);

        return view('ai-agent.agent-sessions.show', [
            'project' => $project,
            'session' => $session,
        ]);
    }

    public function destroy(Project $project, AiAgentSession $session): RedirectResponse
    {
        $this->authorizeProject($project);
        abort_unless($session->project_id === $project->id, 404);
        // draft 또는 paused 상태의 본인 세션만 삭제 허용
        abort_unless(
            $session->user_id === auth()->id() && $session->isEditable(),
            403,
            '삭제할 수 없는 세션입니다.'
        );

        $session->delete();

        return redirect()->route('ai-agent.projects.agent-sessions.index', $project);
    }
}
