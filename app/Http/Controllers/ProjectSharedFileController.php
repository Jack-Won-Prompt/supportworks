<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SharedFile;
use Illuminate\Http\Request;

/**
 * 프로젝트 ↔ 공유폴더 파일 링크 관리.
 *
 * 링크만 — 파일 자체는 shared_files 에 남아있음. 다운로드 시 원본 사용.
 *
 * 권한:
 *   - link/unlink: 프로젝트 멤버 (Project::isMember)
 *   - 링크 대상 파일: 같은 회사 그룹 + 공유 파일 (is_personal=false) 만
 */
class ProjectSharedFileController extends Controller
{
    /** 공유폴더 파일을 프로젝트에 링크 */
    public function store(Request $request, Project $project)
    {
        $this->authorizeProjectMember($project);

        $data = $request->validate([
            'shared_file_id' => 'required|integer|exists:shared_files,id',
        ]);

        $file = SharedFile::findOrFail($data['shared_file_id']);

        // 같은 회사 그룹의 공유 파일만 허용 (개인자료 / 다른 회사 차단)
        abort_unless(
            (int) $file->company_group_id === (int) $project->company_group_id && ! $file->is_personal,
            403,
            __('shared-folder.link_forbidden')
        );

        // 멱등성: 이미 링크 있으면 그대로
        $project->sharedFiles()->syncWithoutDetaching([
            $file->id => ['attached_by' => auth()->id()],
        ]);

        return back()->with('success', __('shared-folder.linked'));
    }

    /** 프로젝트에서 링크 해제 (파일 자체는 안 지움) */
    public function destroy(Project $project, SharedFile $sharedFile)
    {
        $this->authorizeProjectMember($project);

        $project->sharedFiles()->detach($sharedFile->id);

        return back()->with('success', __('shared-folder.unlinked'));
    }

    private function authorizeProjectMember(Project $project): void
    {
        $user = auth()->user();
        abort_unless($user && $project->isMember($user), 403);
    }
}
