<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectFileReviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /** GET /projects/{project}/files */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);

        $query = $project->files()
            ->with(['uploader:id,name', 'reviewRequests.reviewer:id,name', 'category:id,name,color']);

        // 카테고리 필터
        if ($request->category === 'none') {
            $query->whereNull('category_id');
        } elseif ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $files = $query->latest()->get();
        $uid   = $request->user()->id;

        $categories = $project->fileCategories()
            ->withCount('files')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'color'       => $c->color,
                'files_count' => $c->files_count,
            ]);

        return response()->json([
            'categories' => $categories,
            'files'      => $files->map(fn($f) => $this->resource($f, $uid)),
        ]);
    }

    /** GET /projects/{project}/files/{file}/reviews - 리뷰 현황 + 리뷰어 후보 */
    public function reviews(Request $request, Project $project, ProjectFile $file): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);
        abort_if($file->project_id !== $project->id, 404);

        $file->load('reviewRequests.reviewer:id,name', 'reviewRequests.requester:id,name');

        $members = $project->projectMembers()->with('user:id,name')->get()
            ->filter(fn($m) => $m->user)
            ->map(fn($m) => ['id' => $m->user->id, 'name' => $m->user->name])
            ->values();

        return response()->json([
            'file'    => $this->resource($file, $request->user()->id),
            'reviews' => $file->reviewRequests->map(fn($r) => [
                'id'          => $r->id,
                'reviewer'    => $r->reviewer ? ['id' => $r->reviewer->id, 'name' => $r->reviewer->name] : null,
                'requester'   => $r->requester ? ['id' => $r->requester->id, 'name' => $r->requester->name] : null,
                'message'     => $r->message,
                'reviewed_at' => $r->reviewed_at,
                'is_done'     => $r->reviewed_at !== null,
            ]),
            'members' => $members,
        ]);
    }

    /** POST /projects/{project}/files/{file}/review-request - 리뷰 요청 */
    public function requestReview(Request $request, Project $project, ProjectFile $file): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);
        abort_if($file->project_id !== $project->id, 404);

        $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'message'    => 'nullable|string|max:2000',
        ]);

        $message = trim($request->message ?? '') ?: null;

        // 기존 리뷰 요청 교체
        ProjectFileReviewRequest::where('project_file_id', $file->id)->delete();
        $rows = array_map(fn($uid) => [
            'project_file_id' => $file->id,
            'requester_id'    => $request->user()->id,
            'reviewer_id'     => $uid,
            'message'         => $message,
            'created_at'      => now(),
            'updated_at'      => now(),
        ], $request->user_ids);
        ProjectFileReviewRequest::insert($rows);

        // 리뷰어들에게 푸시 알림
        \App\Services\FcmService::notifyUsers(
            $request->user_ids,
            '파일 리뷰 요청',
            "{$request->user()->name}님이 '{$file->original_name}' 파일 리뷰를 요청했습니다.",
            ['type' => 'file_review', 'project_id' => (string) $project->id, 'file_id' => (string) $file->id],
        );

        return response()->json(['message' => '리뷰 요청이 등록되었습니다.', 'count' => count($rows)]);
    }

    /** POST /projects/{project}/files/{file}/review-complete - 내 리뷰 완료 */
    public function completeReview(Request $request, Project $project, ProjectFile $file): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);
        abort_if($file->project_id !== $project->id, 404);

        $review = ProjectFileReviewRequest::where('project_file_id', $file->id)
            ->where('reviewer_id', $request->user()->id)
            ->first();

        abort_if($review === null, 403, '이 파일의 리뷰어가 아닙니다.');

        $review->update(['reviewed_at' => now()]);

        return response()->json(['message' => '리뷰를 완료했습니다.']);
    }

    /** POST /projects/{project}/files - 파일 업로드 */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);

        $request->validate([
            'file'        => 'required|file|max:51200', // 50MB
            'description' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:project_file_categories,id',
        ]);

        $uploaded   = $request->file('file');
        $ext        = $uploaded->getClientOriginalExtension();
        $storedName = Str::uuid() . ($ext ? '.' . $ext : '');
        $path       = $uploaded->storeAs("projects/{$project->id}", $storedName, 'local');

        $file = $project->files()->create([
            'uploaded_by'   => $request->user()->id,
            'category_id'   => $request->category_id,
            'original_name' => $uploaded->getClientOriginalName(),
            'stored_name'   => $storedName,
            'path'          => $path,
            'mime_type'     => $uploaded->getMimeType(),
            'size'          => $uploaded->getSize(),
            'description'   => $request->description,
            'file_type'     => 'file',
        ]);

        $file->load('uploader:id,name', 'category:id,name,color');

        return response()->json($this->resource($file, $request->user()->id), 201);
    }

    /** GET /projects/{project}/files/{file}/download */
    public function download(Request $request, Project $project, ProjectFile $file)
    {
        $this->authorizeProject($request->user(), $project);
        abort_if($file->project_id !== $project->id, 404);
        abort_if($file->isUrlType(), 404, 'URL 링크는 다운로드할 수 없습니다.');
        abort_unless(Storage::disk('local')->exists($file->path), 404, '파일을 찾을 수 없습니다.');

        return Storage::disk('local')->download(
            $file->path,
            $file->original_name,
            ['Content-Type' => $file->mime_type ?? 'application/octet-stream']
        );
    }

    /** DELETE /projects/{project}/files/{file} */
    public function destroy(Request $request, Project $project, ProjectFile $file): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);
        abort_if($file->project_id !== $project->id, 404);
        abort_if($file->uploaded_by !== $request->user()->id && !$request->user()->isAdmin(), 403);

        if (!$file->isUrlType() && $file->path && Storage::disk('local')->exists($file->path)) {
            Storage::disk('local')->delete($file->path);
        }
        $file->delete();

        return response()->json(['message' => '파일이 삭제되었습니다.']);
    }

    private function resource(ProjectFile $f, ?int $uid = null): array
    {
        $reviews  = $f->relationLoaded('reviewRequests') ? $f->reviewRequests : collect();
        $myReview = $uid !== null ? $reviews->firstWhere('reviewer_id', $uid) : null;

        return [
            'id'             => $f->id,
            'original_name'  => $f->original_name,
            'mime_type'      => $f->mime_type,
            'size'           => $f->size,
            'formatted_size' => $f->formatted_size,
            'icon'           => $f->icon,
            'file_type'      => $f->file_type ?? 'file',
            'is_url'         => $f->isUrlType(),
            'source_url'     => $f->source_url,
            'preview_type'   => $f->isUrlType() ? null : $f->previewType(), // image/video/pdf/office/null
            'description'    => $f->description,
            'uploader'       => $f->uploader ? ['id' => $f->uploader->id, 'name' => $f->uploader->name] : null,
            'created_at'     => $f->created_at,
            'category'       => $f->relationLoaded('category') && $f->category
                ? ['id' => $f->category->id, 'name' => $f->category->name, 'color' => $f->category->color]
                : null,
            // 리뷰 요약
            'review_total'   => $reviews->count(),
            'review_done'    => $reviews->whereNotNull('reviewed_at')->count(),
            'i_am_reviewer'  => $myReview !== null,
            'i_reviewed'     => $myReview !== null && $myReview->reviewed_at !== null,
        ];
    }

    private function authorizeProject($user, Project $project): void
    {
        if ($user->isAdmin()) return;
        $member = $project->projectMembers()->where('user_id', $user->id)->first();
        if (!$member) abort(403, '접근 권한이 없습니다.');
    }
}