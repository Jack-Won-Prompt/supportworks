<?php

namespace App\Http\Controllers\PromptBuilder;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\PromptBuilder\UserPreference;
use Illuminate\Support\Facades\Auth;

class ProjectSelectController extends Controller
{
    public function forSequences()
    {
        return $this->renderProjectSelect('sequences', 'builder.sequences.index');
    }

    public function forHistory()
    {
        return $this->renderProjectSelect('history', 'builder.history.index');
    }

    public function forFeedback()
    {
        return $this->renderProjectSelect('feedback', 'builder.feedback.index');
    }

    public function forTemplates()
    {
        return $this->renderProjectSelect('templates', 'builder.templates.index');
    }

    private function renderProjectSelect(string $context, string $nextRoute)
    {
        $user = Auth::user();

        $projects = Project::where('created_by', $user->id)
            ->orWhereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->withCount(['pbBuilders', 'pbSequences'])
            ->orderByDesc('updated_at')
            ->get();

        $preference = UserPreference::where('user_id', $user->id)->first();
        $lastProjectId = $preference?->last_project_id;

        $contextLabels = [
            'sequences' => '빌더 시퀀스를 관리할 프로젝트를 선택하세요',
            'history'   => '빌더 이력을 조회할 프로젝트를 선택하세요',
            'feedback'  => '결과 피드백을 관리할 프로젝트를 선택하세요',
            'templates' => '빌더 템플릿을 관리할 프로젝트를 선택하세요',
        ];

        return view('prompt-builder.project-select', [
            'projects'     => $projects,
            'lastProjectId' => $lastProjectId,
            'context'      => $context,
            'contextLabel' => $contextLabels[$context] ?? '프로젝트를 선택하세요',
            'nextRoute'    => $nextRoute,
        ]);
    }
}
