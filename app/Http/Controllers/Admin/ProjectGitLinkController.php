<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectGitLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 프로젝트 ↔ WITHWORKS 저장소 연결 관리.
 *
 * 규칙: source/repo 는 'withworks' / 'dhlogitsticsPlatform/withworks' 로 강제 고정.
 * 다른 저장소는 절대 연결 불가.
 */
class ProjectGitLinkController extends Controller
{
    /** 토글: link=1 면 연결, link=0 면 해제 */
    public function toggle(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'link'        => 'required|boolean',
            'path_prefix' => 'nullable|string|max:200',
        ]);

        // 관리자(AdminUser) 또는 일반 admin 사용자 모두 허용
        $admin = auth('admin')->user();
        $user  = auth()->user();
        abort_unless($admin || ($user && $user->isAdmin()), 403);

        if ($request->boolean('link')) {
            $prefix = $this->normalizePrefix((string) $request->input('path_prefix', ''));
            $link = ProjectGitLink::updateOrCreate(
                ['project_id' => $project->id, 'source' => ProjectGitLink::ALLOWED_SOURCE],
                [
                    'repo'        => ProjectGitLink::ALLOWED_REPO,
                    'path_prefix' => $prefix,
                    'linked_by'   => $user?->id,
                ]
            );
            return response()->json([
                'ok'          => true,
                'linked'      => true,
                'project'     => ['id' => $project->id, 'name' => $project->name],
                'repo'        => $link->repo,
                'path_prefix' => $link->path_prefix,
            ]);
        } else {
            ProjectGitLink::where('project_id', $project->id)
                ->where('source', ProjectGitLink::ALLOWED_SOURCE)
                ->delete();
            return response()->json(['ok' => true, 'linked' => false]);
        }
    }

    /** path_prefix 만 갱신 (이미 연결된 프로젝트의 prefix 수정) */
    public function updatePrefix(Request $request, Project $project): JsonResponse
    {
        $request->validate(['path_prefix' => 'nullable|string|max:200']);

        $admin = auth('admin')->user();
        $user  = auth()->user();
        abort_unless($admin || ($user && $user->isAdmin()), 403);

        $link = ProjectGitLink::where('project_id', $project->id)
            ->where('source', ProjectGitLink::ALLOWED_SOURCE)
            ->first();
        if (!$link) {
            return response()->json(['ok' => false, 'message' => '연결되지 않은 프로젝트입니다.'], 422);
        }
        $link->path_prefix = $this->normalizePrefix((string) $request->input('path_prefix', ''));
        $link->save();

        return response()->json([
            'ok'          => true,
            'path_prefix' => $link->path_prefix,
        ]);
    }

    /** prefix 정규화: 양 끝 공백/슬래시 제거, 빈 문자열 → null */
    private function normalizePrefix(string $prefix): ?string
    {
        $p = trim($prefix);
        $p = ltrim($p, '/\\');
        $p = rtrim($p, '/\\');
        return $p === '' ? null : $p;
    }
}
