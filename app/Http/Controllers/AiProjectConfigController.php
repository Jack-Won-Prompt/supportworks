<?php

namespace App\Http\Controllers;

use App\Enums\Agent\FrontendStack;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiProjectConfigController extends Controller
{
    /**
     * 웍스 Agent 워크플로우 신규 시작 (ProjectAiAgentConfig + 5 Stage 생성).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'project_id'     => ['required', 'integer', 'exists:projects,id'],
            'frontend_stack' => ['required', Rule::in(['html', 'react', 'vue'])],
        ], [
            'project_id.required'     => '프로젝트를 선택해주세요.',
            'frontend_stack.required' => '프론트엔드 스택을 선택해주세요.',
        ]);

        $project = Project::findOrFail($request->project_id);

        // 프로젝트 접근 권한 확인
        $user = auth()->user();
        if (!$user->isAdmin()) {
            abort_unless(
                ProjectMember::where('project_id', $project->id)
                    ->where('user_id', $user->id)
                    ->exists(),
                403,
                '해당 프로젝트에 접근 권한이 없습니다.'
            );
        }

        // 이미 웍스 Agent가 설정된 프로젝트 차단
        if (ProjectAiAgentConfig::forProject($project->id)) {
            return back()->withErrors([
                'project_id' => '이미 웍스 Agent가 설정된 프로젝트입니다.',
            ]);
        }

        // Config 생성 + 5단계 자동 생성
        ProjectAiAgentConfig::initializeForProject(
            $project->id,
            FrontendStack::from($request->frontend_stack),
            $user->id,
        );

        return redirect()
            ->route('ai-agent.projects.home', $project)
            ->with('success', "'{$project->name}' 프로젝트의 웍스 Agent 워크플로우가 시작되었습니다.");
    }
}
