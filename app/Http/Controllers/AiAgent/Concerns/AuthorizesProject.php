<?php

namespace App\Http\Controllers\AiAgent\Concerns;

use App\Models\Project;
use App\Models\ProjectMember;

/**
 * AiAgentController::authorizeProject() 와 동일한 권한 검사를 새 컨트롤러에서도 사용.
 *
 * - admin: 모든 프로젝트 접근 가능
 * - 그 외: ProjectMember 행이 존재해야 접근 가능
 */
trait AuthorizesProject
{
    protected function authorizeProject(Project $project): void
    {
        $user = auth()->user();

        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return;
        }

        abort_unless(
            ProjectMember::where('project_id', $project->id)
                ->where('user_id', auth()->id())
                ->exists(),
            403,
            '해당 프로젝트에 접근 권한이 없습니다.'
        );
    }
}
