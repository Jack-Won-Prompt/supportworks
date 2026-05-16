<?php

namespace App\Http\Controllers;

use App\Models\FileActionLog;
use App\Models\FileComment;
use App\Models\FileVersion;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectFileReviewRequest;
use App\Services\ProjectNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ProjectFileController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $categories = $project->fileCategories()->withCount('files')->get();
        $categoryId = $request->query('category');
        $scheduleId = $request->query('schedule');

        $query = $project->files()
            ->with('uploader', 'category', 'schedule', 'reviewRequests.reviewer')
            ->withCount(['comments', 'versions'])
            ->latest();
        if ($categoryId === 'none') {
            $query->whereNull('category_id');
        } elseif ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($scheduleId) {
            $query->where('schedule_id', $scheduleId);
        }

        $files   = $query->paginate(20)->withQueryString();
        $members = $project->members()->where('users.id', '!=', auth()->id())->get(['users.id', 'users.name', 'users.email']);

        $user = auth()->user();
        $copyableProjects = $user->isAdmin()
            ? Project::where('id', '!=', $project->id)->orderBy('name')->get(['id', 'name'])
            : $user->projects()->where('projects.id', '!=', $project->id)->orderBy('name')->get(['projects.id', 'projects.name']);

        $totalCount         = $project->files()->count();
        $uncategorizedCount = $project->files()->whereNull('category_id')->count();

        $uploadableProjects = $user->isAdmin()
            ? Project::orderBy('name')->get(['id', 'name'])
            : $user->projects()->orderBy('projects.name')->get(['projects.id', 'projects.name']);

        $schedules  = $project->schedules()->orderBy('start_date')->get(['id', 'title']);
        $subTasks   = $project->subTasks()->with('taskGroup')->orderBy('task_group_id')->orderBy('display_order')->get(['id', 'title', 'task_group_id']);

        $activeSchedule = $scheduleId ? $project->schedules()->find($scheduleId) : null;

        return view('files.index', compact('project', 'files', 'members', 'categories', 'categoryId', 'scheduleId', 'activeSchedule', 'copyableProjects', 'totalCount', 'uncategorizedCount', 'uploadableProjects', 'schedules', 'subTasks'));
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        // ── URL 등록 ─────────────────────────────────────────
        if ($request->input('file_type') === 'url') {
            $request->validate([
                'source_url'    => 'required|url|max:2048',
                'original_name' => 'required|string|max:255',
                'description'   => 'nullable|string|max:255',
                'category_id'   => 'nullable|integer|exists:project_file_categories,id',
                'schedule_id'   => 'nullable|integer|exists:schedules,id',
                'sub_task_id'   => 'nullable|integer|exists:sub_tasks,id',
            ]);

            $project->files()->create([
                'uploaded_by'   => auth()->id(),
                'category_id'   => $request->category_id,
                'schedule_id'   => $request->schedule_id,
                'sub_task_id'   => $request->sub_task_id,
                'original_name' => $request->original_name,
                'stored_name'   => (string) Str::uuid(),
                'path'          => '',
                'mime_type'     => 'url',
                'size'          => 0,
                'description'   => $request->description,
                'source_url'    => $request->source_url,
                'file_type'     => 'url',
            ]);

            if ($request->boolean('notify_email')) {
                app(ProjectNotificationService::class)->notify(
                    $project, auth()->user(), 'file_uploaded',
                    $request->original_name,
                    route('projects.files.index', $project),
                );
            }

            return $request->expectsJson()
                ? response()->json(['ok' => true])
                : back()->with('success', 'URL이 등록되었습니다.');
        }

        // ── 파일 업로드 ───────────────────────────────────────
        $request->validate([
            'file'        => 'required|file|max:51200',
            'description' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:project_file_categories,id',
            'schedule_id' => 'nullable|integer|exists:schedules,id',
            'sub_task_id' => 'nullable|integer|exists:sub_tasks,id',
        ]);

        $uploaded = $request->file('file');
        $storedName = Str::uuid().'.'.$uploaded->getClientOriginalExtension();
        $path = $uploaded->storeAs("projects/{$project->id}", $storedName, 'local');

        $file = $project->files()->create([
            'uploaded_by'   => auth()->id(),
            'category_id'   => $request->category_id,
            'schedule_id'   => $request->schedule_id,
            'sub_task_id'   => $request->sub_task_id,
            'original_name' => $uploaded->getClientOriginalName(),
            'stored_name'   => $storedName,
            'path'          => $path,
            'mime_type'     => $uploaded->getMimeType(),
            'size'          => $uploaded->getSize(),
            'description'   => $request->description,
        ]);

        if ($request->boolean('notify_email')) {
            app(ProjectNotificationService::class)->notify(
                $project, auth()->user(), 'file_uploaded',
                $uploaded->getClientOriginalName(),
                route('projects.files.index', $project),
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'file' => [
                'id'           => $file->id,
                'name'         => $file->original_name,
                'size'         => $file->size,
                'preview_type' => $file->previewType(),
            ]]);
        }

        return back()->with('success', '파일이 업로드되었습니다.');
    }

    public function urlViewer(Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);
        abort_unless($file->isUrlType(), 404);
        return view('files.url_viewer', compact('project', 'file'));
    }

    public function preview(Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);

        if (!$file->previewType()) {
            return redirect()->route('projects.files.index', $project)
                ->with('error', '이 파일 형식은 미리보기를 지원하지 않습니다.');
        }

        return redirect(route('projects.files.index', $project) . '?preview=' . $file->id);
    }

    /**
     * 버전 비교용 임베드 뷰어 — 비교 모달의 iframe 안에서 로드되는 단독 뷰어 페이지.
     * 기존 미리보기 모달(partials.file-preview-modal)을 그대로 재사용한다.
     */
    public function viewerEmbed(Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);
        abort_unless($file->project_id === $project->id, 404);
        abort_unless($file->previewType(), 404, '이 파일 형식은 미리보기를 지원하지 않습니다.');

        return view('files.viewer_embed', compact('project', 'file'));
    }

    public function servePreview(ProjectFile $file, Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $version  = (int) $request->query('version', 0);
        $path     = $file->path;
        $fileName = $file->original_name;
        $mime     = $file->mime_type;

        if ($version > 0) {
            $vr = $file->versions()->where('version', $version)->first();
            if ($vr) {
                $path = $vr->path;
                $fileName = $vr->original_name;
                $mime = $vr->mime_type;
            }
        }

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $absPath = Storage::disk('local')->path($path);

        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $isVideo  = in_array($ext, ['mp4', 'webm', 'ogv', 'ogg', 'mov', 'm4v'])
                    || ($mime && str_starts_with($mime, 'video/'));

        $headers = [
            'Content-Disposition'          => 'inline; filename*=UTF-8\'\'' . rawurlencode($fileName),
            'Accept-Ranges'                => 'bytes',
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET',
        ];

        if ($isVideo) {
            $videoMimeMap = [
                'mp4' => 'video/mp4',  'm4v' => 'video/mp4',
                'webm'=> 'video/webm',
                'mov' => 'video/quicktime',
                'ogv' => 'video/ogg',  'ogg' => 'video/ogg',
            ];
            $headers['Content-Type'] = $videoMimeMap[$ext] ?? 'video/mp4';
        } elseif ($mime) {
            $headers['Content-Type'] = $mime;
        }

        return response()->file($absPath, $headers);
    }

    public function serveConvertedPdf(ProjectFile $file, Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $version  = (int) $request->query('version', 0);
        $pdfPath  = $file->converted_pdf_path;
        $baseName = $file->original_name;

        if ($version > 0) {
            $vr = $file->versions()->where('version', $version)->first();
            if ($vr) {
                $pdfPath  = $vr->converted_pdf_path;
                $baseName = $vr->original_name;
            }
        }

        if (!$pdfPath || !Storage::disk('local')->exists($pdfPath)) {
            abort(404, '변환된 PDF가 없습니다.');
        }

        $pdfName = pathinfo($baseName, PATHINFO_FILENAME) . '.pdf';

        return Storage::disk('local')->response($pdfPath, $pdfName, [
            'Content-Type'                => 'application/pdf',
            'Content-Disposition'         => 'inline; filename*=UTF-8\'\'' . rawurlencode($pdfName),
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods'=> 'GET',
        ]);
    }

    public function download(Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);

        if (!Storage::disk('local')->exists($file->path)) {
            abort(404, '파일을 찾을 수 없습니다.');
        }

        FileActionLog::create([
            'project_file_id' => $file->id,
            'user_id'         => auth()->id(),
            'action'          => 'download',
        ]);

        // 한글/유니코드 파일명 보존 — Symfony 기본 makeDisposition 이 만드는
        // ASCII fallback(`filename="...transliterated..."`) 때문에 일부 환경에서
        // 음역된 이름으로 저장되는 문제를 우회.
        $filename = $file->original_name;
        $encoded  = rawurlencode($filename);

        return Storage::disk('local')->download($file->path, $filename, [
            'Content-Disposition' => "attachment; filename*=UTF-8''{$encoded}",
        ]);
    }

    public function copy(Request $request, Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);

        $request->validate([
            'target_project_id' => 'required|integer|exists:projects,id',
        ]);

        $target = Project::findOrFail($request->target_project_id);
        $this->authorizeProject($target);

        if ($file->isUrlType()) {
            $target->files()->create([
                'uploaded_by'   => auth()->id(),
                'category_id'   => null,
                'original_name' => $file->original_name,
                'stored_name'   => (string) Str::uuid(),
                'path'          => '',
                'mime_type'     => 'url',
                'size'          => 0,
                'description'   => $file->description,
                'source_url'    => $file->source_url,
                'file_type'     => 'url',
            ]);
        } else {
            if (!Storage::disk('local')->exists($file->path)) {
                return response()->json(['ok' => false, 'message' => '원본 파일을 찾을 수 없습니다.'], 422);
            }

            $ext        = pathinfo($file->original_name, PATHINFO_EXTENSION);
            $storedName = Str::uuid() . ($ext ? '.' . $ext : '');
            $newPath    = "projects/{$target->id}/{$storedName}";

            Storage::disk('local')->copy($file->path, $newPath);

            $target->files()->create([
                'uploaded_by'   => auth()->id(),
                'category_id'   => null,
                'original_name' => $file->original_name,
                'stored_name'   => $storedName,
                'path'          => $newPath,
                'mime_type'     => $file->mime_type,
                'size'          => $file->size,
                'description'   => $file->description,
                'file_type'     => 'file',
            ]);
        }

        FileActionLog::create([
            'project_file_id' => $file->id,
            'user_id'         => auth()->id(),
            'action'          => 'copy',
        ]);

        return response()->json(['ok' => true, 'project_name' => $target->name]);
    }

    public function uploadVersion(Request $request, Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);

        abort_if($file->isUrlType(), 422, 'URL 항목은 수정본 업로드를 지원하지 않습니다.');

        $request->validate([
            'file'        => 'required|file|max:204800',
            'change_note' => 'nullable|string|max:2000',
        ]);

        $uploaded = $request->file('file');

        return DB::transaction(function () use ($file, $uploaded, $request) {
            // 기존 파일에 v1 백업이 없으면 자동 생성
            $hasAnyVersion = $file->versions()->exists();
            if (!$hasAnyVersion) {
                FileVersion::create([
                    'project_file_id' => $file->id,
                    'version'         => 1,
                    'original_name'   => $file->original_name,
                    'stored_name'     => $file->stored_name,
                    'path'            => $file->path,
                    'mime_type'       => $file->mime_type,
                    'size'            => $file->size,
                    'uploaded_by'     => $file->uploaded_by,
                    'change_note'     => null,
                    'created_at'      => $file->created_at,
                    'updated_at'      => $file->updated_at,
                ]);
            }

            // 새 버전 번호
            $nextVersion = ((int) $file->versions()->max('version')) + 1;
            if ($nextVersion < 2) $nextVersion = 2;

            // 새 파일 저장
            $storedPath = $uploaded->store('project_files', 'local');
            $storedName = basename($storedPath);

            // 새 버전 row
            FileVersion::create([
                'project_file_id' => $file->id,
                'version'         => $nextVersion,
                'original_name'   => $uploaded->getClientOriginalName(),
                'stored_name'     => $storedName,
                'path'            => $storedPath,
                'mime_type'       => $uploaded->getMimeType(),
                'size'            => $uploaded->getSize(),
                'uploaded_by'     => auth()->id(),
                'change_note'     => $request->input('change_note'),
            ]);

            // ProjectFile 자체도 최신으로 업데이트 (미리보기/다운로드가 최신 본을 보도록)
            $file->update([
                'original_name'      => $uploaded->getClientOriginalName(),
                'stored_name'        => $storedName,
                'path'               => $storedPath,
                'mime_type'          => $uploaded->getMimeType(),
                'size'               => $uploaded->getSize(),
                'converted_pdf_path' => null,
                'uploaded_by'        => auth()->id(),
            ]);

            // 기존 의견 일괄 '반영 완료' 마킹
            FileComment::where('project_file_id', $file->id)
                ->where('resolved', false)
                ->update([
                    'resolved'            => true,
                    'resolved_at'         => now(),
                    'resolved_by'         => auth()->id(),
                    'resolved_at_version' => $nextVersion,
                ]);

            FileActionLog::create([
                'project_file_id' => $file->id,
                'user_id'         => auth()->id(),
                'action'          => 'upload_version',
            ]);

            return response()->json([
                'ok'       => true,
                'version'  => $nextVersion,
                'message'  => "v{$nextVersion}으로 업로드되었습니다.",
            ]);
        });
    }

    public function versionList(Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);
        $versions = $file->versions()
            ->with('uploader:id,name')
            ->orderByDesc('version')
            ->get()
            ->map(fn($v) => [
                'id'            => $v->id,
                'version'       => $v->version,
                'original_name' => $v->original_name,
                'size'          => $v->size,
                'size_human'    => $v->formattedSize(),
                'uploader'      => $v->uploader ? ['id' => $v->uploader->id, 'name' => $v->uploader->name] : null,
                'change_note'   => $v->change_note,
                'created_at'    => $v->created_at?->format('Y-m-d H:i'),
            ]);
        return response()->json(['ok' => true, 'versions' => $versions]);
    }

    public function destroy(Request $request, Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);

        if ($file->uploaded_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $fileName = $file->original_name;
        Storage::disk('local')->delete(array_filter([
            $file->path,
            $file->converted_pdf_path,
        ]));
        $file->delete();

        app(ProjectNotificationService::class)->notify(
            $project, auth()->user(), 'file_deleted',
            $fileName,
            route('projects.files.index', $project),
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', '파일이 삭제되었습니다.');
    }

    public function toggleShare(Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);
        abort_unless($file->isShareable(), 422);

        if ($file->share_token) {
            $file->update(['share_token' => null]);
            return response()->json(['ok' => true, 'active' => false]);
        }

        $token = \Illuminate\Support\Str::random(48);
        $file->update(['share_token' => $token]);

        return response()->json([
            'ok'     => true,
            'active' => true,
            'token'  => $token,
            'url'    => route('files.public-share', $token),
        ]);
    }

    public function update(Request $request, Project $project, ProjectFile $file): \Illuminate\Http\JsonResponse
    {
        $this->authorizeProject($project);

        if ($file->uploaded_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'original_name' => 'required|string|max:255',
            'description'   => 'nullable|string|max:500',
            'sub_task_id'   => 'nullable|integer|exists:sub_tasks,id',
            'project_id'    => 'nullable|integer|exists:projects,id',
        ]);

        $newProjectId = (int) ($validated['project_id'] ?? $project->id);

        $updates = [
            'original_name' => $validated['original_name'],
            'description'   => $validated['description'] ?? null,
            'sub_task_id'   => $validated['sub_task_id'] ?? null,
        ];

        $moved = false;
        if ($newProjectId !== (int) $file->project_id) {
            $targetProject = Project::findOrFail($newProjectId);
            $this->authorizeProject($targetProject);

            if (!$file->isUrlType() && Storage::disk('local')->exists($file->path)) {
                $ext           = pathinfo($file->stored_name, PATHINFO_EXTENSION);
                $newStoredName = Str::uuid() . ($ext ? '.' . $ext : '');
                $newPath       = "projects/{$newProjectId}/{$newStoredName}";
                Storage::disk('local')->copy($file->path, $newPath);
                Storage::disk('local')->delete($file->path);
                $updates['stored_name'] = $newStoredName;
                $updates['path']        = $newPath;
            }

            $updates['project_id']  = $newProjectId;
            $updates['category_id'] = null;
            $updates['sub_task_id'] = null;
            $moved = true;
        }

        $file->update($updates);

        return response()->json(['ok' => true, 'moved' => $moved]);
    }

    public function updateCategory(Request $request, Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);

        $request->validate([
            'category_id' => 'nullable|integer|exists:project_file_categories,id',
        ]);

        $file->update(['category_id' => $request->category_id ?: null]);

        return response()->json(['ok' => true]);
    }

    public function requestReview(Request $request, Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);

        $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'message'    => 'nullable|string|max:2000',
        ]);

        $url = $file->previewType()
            ? route('projects.files.index', $project) . '?preview=' . $file->id
            : route('projects.files.index', $project);

        $message = trim($request->message ?? '') ?: null;

        // ── 1) 이력 먼저 저장 (메일/SMS가 실패해도 이력은 보존) ───────────
        ProjectFileReviewRequest::where('project_file_id', $file->id)->delete();
        $rows = array_map(fn($uid) => [
            'project_file_id' => $file->id,
            'requester_id'    => auth()->id(),
            'reviewer_id'     => $uid,
            'message'         => $message,
            'created_at'      => now(),
            'updated_at'      => now(),
        ], $request->user_ids);
        ProjectFileReviewRequest::insert($rows);

        FileActionLog::create([
            'project_file_id' => $file->id,
            'user_id'         => auth()->id(),
            'action'          => 'review_request',
        ]);

        // ── 2) 알림 발송 (메일은 동기, SMS는 응답 후 비동기) ──────────────
        $count = 0;
        try {
            $count = app(ProjectNotificationService::class)->notifySpecific(
                $project,
                auth()->user(),
                $request->user_ids,
                'file_review_requested',
                $file->original_name,
                $url,
                $message,
            );
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
        }

        return response()->json(['ok' => true, 'count' => $count]);
    }

    public function completeReview(Request $request, Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);

        $reviewRequest = ProjectFileReviewRequest::where('project_file_id', $file->id)
            ->where('reviewer_id', auth()->id())
            ->firstOrFail();

        $reviewRequest->update(['reviewed_at' => now()]);

        FileActionLog::create([
            'project_file_id' => $file->id,
            'user_id'         => auth()->id(),
            'action'          => 'review_complete',
        ]);

        return response()->json(['ok' => true]);
    }

    public function logAction(Request $request, Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);

        $action = $request->input('action');
        if (!in_array($action, ['view', 'share'])) {
            abort(422);
        }

        FileActionLog::create([
            'project_file_id' => $file->id,
            'user_id'         => auth()->id(),
            'action'          => $action,
        ]);

        return response()->json(['ok' => true]);
    }

    public function actionLogs(Request $request, Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);

        $logs = FileActionLog::where('project_file_id', $file->id)
            ->when($request->query('action'), fn($q, $a) => $q->where('action', $a))
            ->with('user:id,name')
            ->latest('created_at')
            ->take(5)
            ->get();

        return response()->json(
            $logs->map(fn($log) => [
                'user_name'  => $log->user->name ?? '알 수 없음',
                'created_at' => $log->created_at->format('Y-m-d H:i'),
            ])
        );
    }

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403, '접근 권한이 없습니다.');
    }
}
