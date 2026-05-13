<?php

namespace App\Http\Controllers;

use App\Events\FileCommentPosted;
use App\Events\FileCommentDeleted;
use App\Models\FileAnnotation;
use App\Models\MaintenanceFile;
use App\Models\MaintenanceFileCategory;
use App\Models\Project;
use App\Models\ProjectMaintenance;
use App\Models\FileComment;
use App\Services\OfficeConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class MaintenanceFileController extends Controller
{
    public function store(Request $request, ProjectMaintenance $maintenance)
    {
        $this->authorize($maintenance);

        if ($request->input('file_type') === 'url') {
            $request->validate([
                'source_url'              => 'required|url|max:2048',
                'original_name'           => 'required|string|max:255',
                'description'             => 'nullable|string|max:255',
                'maintenance_category_id' => 'nullable|integer|exists:maintenance_file_categories,id',
            ]);

            MaintenanceFile::create([
                'project_id'             => $maintenance->project_id,
                'maintenance_id'         => $maintenance->id,
                'uploaded_by'            => auth()->id(),
                'original_name'          => $request->original_name,
                'stored_name'            => (string) Str::uuid(),
                'path'                   => '',
                'mime_type'              => 'url',
                'size'                   => 0,
                'description'            => $request->description,
                'source_url'             => $request->source_url,
                'file_type'              => 'url',
                'maintenance_category_id'=> $request->maintenance_category_id ?: null,
            ]);

            return response()->json(['ok' => true]);
        }

        $request->validate([
            'file'                    => 'required|file|max:51200',
            'description'             => 'nullable|string|max:255',
            'maintenance_category_id' => 'nullable|integer|exists:maintenance_file_categories,id',
        ]);

        $uploaded   = $request->file('file');
        $storedName = Str::uuid() . '.' . $uploaded->getClientOriginalExtension();
        $path       = $uploaded->storeAs("projects/{$maintenance->project_id}", $storedName, 'local');

        MaintenanceFile::create([
            'project_id'             => $maintenance->project_id,
            'maintenance_id'         => $maintenance->id,
            'uploaded_by'            => auth()->id(),
            'original_name'          => $uploaded->getClientOriginalName(),
            'stored_name'            => $storedName,
            'path'                   => $path,
            'mime_type'              => $uploaded->getMimeType(),
            'size'                   => $uploaded->getSize(),
            'description'            => $request->description,
            'maintenance_category_id'=> $request->maintenance_category_id ?: null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function download(ProjectMaintenance $maintenance, MaintenanceFile $maintenanceFile)
    {
        $this->authorize($maintenance);
        abort_if($maintenanceFile->maintenance_id !== $maintenance->id, 404);

        if (!Storage::disk('local')->exists($maintenanceFile->path)) {
            abort(404, '파일을 찾을 수 없습니다.');
        }

        return Storage::disk('local')->download($maintenanceFile->path, $maintenanceFile->original_name);
    }

    public function destroy(ProjectMaintenance $maintenance, MaintenanceFile $maintenanceFile)
    {
        $this->authorize($maintenance);
        abort_if($maintenanceFile->maintenance_id !== $maintenance->id, 404);

        if ($maintenanceFile->uploaded_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        Storage::disk('local')->delete(array_filter([
            $maintenanceFile->path,
            $maintenanceFile->converted_pdf_path,
        ]));
        $maintenanceFile->delete();

        return response()->json(['ok' => true]);
    }

    public function toggleShare(ProjectMaintenance $maintenance, MaintenanceFile $maintenanceFile)
    {
        $this->authorize($maintenance);
        abort_if($maintenanceFile->maintenance_id !== $maintenance->id, 404);
        abort_unless($maintenanceFile->isShareable(), 422);

        if ($maintenanceFile->share_token) {
            $maintenanceFile->update(['share_token' => null]);
            return response()->json(['ok' => true, 'active' => false]);
        }

        $token = Str::random(48);
        $maintenanceFile->update(['share_token' => $token]);

        return response()->json([
            'ok'     => true,
            'active' => true,
            'token'  => $token,
            'url'    => route('maintenance-files.public-share', $token),
        ]);
    }

    public function updateCategory(Request $request, ProjectMaintenance $maintenance, MaintenanceFile $maintenanceFile)
    {
        $this->authorize($maintenance);
        abort_if($maintenanceFile->maintenance_id !== $maintenance->id, 404);

        $request->validate([
            'maintenance_category_id' => 'nullable|integer|exists:maintenance_file_categories,id',
        ]);

        $catId = $request->maintenance_category_id ?: null;
        $maintenanceFile->update(['maintenance_category_id' => $catId]);

        $cat = $catId ? MaintenanceFileCategory::find($catId) : null;

        return response()->json([
            'ok'    => true,
            'name'  => $cat?->name,
            'color' => $cat?->color,
        ]);
    }

    public function previewData(ProjectMaintenance $maintenance, MaintenanceFile $maintenanceFile)
    {
        $this->authorize($maintenance);
        abort_if($maintenanceFile->maintenance_id !== $maintenance->id, 404);

        $previewType = $maintenanceFile->previewType();
        if (!$previewType) {
            return response()->json(['error' => '미리보기 불가'], 422);
        }

        $ext = strtolower(pathinfo($maintenanceFile->original_name, PATHINFO_EXTENSION));

        $originalServeUrl = URL::temporarySignedRoute(
            'maintenance-files.serve',
            now()->addHours(2),
            ['maintenanceFile' => $maintenanceFile->id]
        );

        $serveUrl  = $originalServeUrl;
        $viewerUrl = $originalServeUrl;
        $hasPages  = in_array($previewType, ['office', 'pdf']);

        if ($previewType === 'office') {
            try {
                set_time_limit(120);

                if (!$maintenanceFile->converted_pdf_path || !Storage::disk('local')->exists($maintenanceFile->converted_pdf_path)) {
                    $pdfPath = OfficeConverter::convertToPdf($maintenanceFile->path);
                    $maintenanceFile->update(['converted_pdf_path' => $pdfPath]);
                }

                $pdfServeUrl = URL::temporarySignedRoute(
                    'maintenance-files.serve-pdf',
                    now()->addHours(2),
                    ['maintenanceFile' => $maintenanceFile->id]
                );

                $serveUrl    = $pdfServeUrl;
                $viewerUrl   = $pdfServeUrl;
                $previewType = 'pdf';

            } catch (\Exception $e) {
                Log::error('[MF OfficeConverter] ' . $e->getMessage());
                \App\Models\SystemErrorLog::record($e, 'warning');
                $viewerUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($originalServeUrl);
            }
        }

        $comments = $maintenanceFile->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('page')
            ->orderBy('created_at')
            ->get()
            ->map(fn($c) => $this->commentToArray($c));

        $apiBase = url("maintenance-files/{$maintenanceFile->id}");

        return response()->json([
            'viewerUrl'        => $viewerUrl,
            'serveUrl'         => $serveUrl,
            'previewType'      => $previewType,
            'hasPages'         => $hasPages,
            'ext'              => $ext,
            'fileName'         => $maintenanceFile->original_name,
            'fileId'           => $maintenanceFile->id,
            'comments'         => $comments,
            'commentApiBase'   => $apiBase,
            'annotationApiBase'=> $apiBase,
        ]);
    }

    public function serve(MaintenanceFile $maintenanceFile, Request $request)
    {
        if (!$request->hasValidSignature()) abort(403);
        if (!Storage::disk('local')->exists($maintenanceFile->path)) abort(404);

        $mimeType = $maintenanceFile->mime_type ?: 'application/octet-stream';

        return Storage::disk('local')->response($maintenanceFile->path, $maintenanceFile->original_name, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename*=UTF-8\'\'' . rawurlencode($maintenanceFile->original_name),
        ]);
    }

    public function servePdf(MaintenanceFile $maintenanceFile, Request $request)
    {
        if (!$request->hasValidSignature()) abort(403);

        $pdfPath = $maintenanceFile->converted_pdf_path;
        if (!$pdfPath || !Storage::disk('local')->exists($pdfPath)) abort(404);

        $pdfName = pathinfo($maintenanceFile->original_name, PATHINFO_FILENAME) . '.pdf';

        return Storage::disk('local')->response($pdfPath, $pdfName, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename*=UTF-8\'\'' . rawurlencode($pdfName),
        ]);
    }

    // ── 프로젝트 레벨 (SR 미연결) ──────────────────────────────────────

    public function storeProject(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        if ($request->input('file_type') === 'url') {
            $request->validate([
                'source_url'              => 'required|url|max:2048',
                'original_name'           => 'required|string|max:255',
                'description'             => 'nullable|string|max:255',
                'maintenance_category_id' => 'nullable|integer|exists:maintenance_file_categories,id',
            ]);

            MaintenanceFile::create([
                'project_id'             => $project->id,
                'maintenance_id'         => null,
                'uploaded_by'            => auth()->id(),
                'original_name'          => $request->original_name,
                'stored_name'            => (string) Str::uuid(),
                'path'                   => '',
                'mime_type'              => 'url',
                'size'                   => 0,
                'description'            => $request->description,
                'source_url'             => $request->source_url,
                'file_type'              => 'url',
                'maintenance_category_id'=> $request->maintenance_category_id ?: null,
            ]);

            return response()->json(['ok' => true]);
        }

        $request->validate([
            'file'                    => 'required|file|max:51200',
            'description'             => 'nullable|string|max:255',
            'maintenance_category_id' => 'nullable|integer|exists:maintenance_file_categories,id',
        ]);

        $uploaded   = $request->file('file');
        $storedName = Str::uuid() . '.' . $uploaded->getClientOriginalExtension();
        $path       = $uploaded->storeAs("projects/{$project->id}", $storedName, 'local');

        MaintenanceFile::create([
            'project_id'             => $project->id,
            'maintenance_id'         => null,
            'uploaded_by'            => auth()->id(),
            'original_name'          => $uploaded->getClientOriginalName(),
            'stored_name'            => $storedName,
            'path'                   => $path,
            'mime_type'              => $uploaded->getMimeType(),
            'size'                   => $uploaded->getSize(),
            'description'            => $request->description,
            'maintenance_category_id'=> $request->maintenance_category_id ?: null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function downloadProject(Project $project, MaintenanceFile $maintenanceFile)
    {
        $this->authorizeProject($project);
        abort_if($maintenanceFile->project_id !== $project->id, 404);

        if (!Storage::disk('local')->exists($maintenanceFile->path)) {
            abort(404, '파일을 찾을 수 없습니다.');
        }

        return Storage::disk('local')->download($maintenanceFile->path, $maintenanceFile->original_name);
    }

    public function destroyProject(Project $project, MaintenanceFile $maintenanceFile)
    {
        $this->authorizeProject($project);
        abort_if($maintenanceFile->project_id !== $project->id, 404);

        if ($maintenanceFile->uploaded_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        Storage::disk('local')->delete(array_filter([
            $maintenanceFile->path,
            $maintenanceFile->converted_pdf_path,
        ]));
        $maintenanceFile->delete();

        return response()->json(['ok' => true]);
    }

    public function toggleShareProject(Project $project, MaintenanceFile $maintenanceFile)
    {
        $this->authorizeProject($project);
        abort_if($maintenanceFile->project_id !== $project->id, 404);
        abort_unless($maintenanceFile->isShareable(), 422);

        if ($maintenanceFile->share_token) {
            $maintenanceFile->update(['share_token' => null]);
            return response()->json(['ok' => true, 'active' => false]);
        }

        $token = Str::random(48);
        $maintenanceFile->update(['share_token' => $token]);

        return response()->json([
            'ok'     => true,
            'active' => true,
            'token'  => $token,
            'url'    => route('maintenance-files.public-share', $token),
        ]);
    }

    public function previewDataProject(Project $project, MaintenanceFile $maintenanceFile)
    {
        $this->authorizeProject($project);
        abort_if($maintenanceFile->project_id !== $project->id, 404);

        $previewType = $maintenanceFile->previewType();
        if (!$previewType) {
            return response()->json(['error' => '미리보기 불가'], 422);
        }

        $ext = strtolower(pathinfo($maintenanceFile->original_name, PATHINFO_EXTENSION));

        $originalServeUrl = URL::temporarySignedRoute(
            'maintenance-files.serve',
            now()->addHours(2),
            ['maintenanceFile' => $maintenanceFile->id]
        );

        $serveUrl  = $originalServeUrl;
        $viewerUrl = $originalServeUrl;
        $hasPages  = in_array($previewType, ['office', 'pdf']);

        if ($previewType === 'office') {
            try {
                set_time_limit(120);

                if (!$maintenanceFile->converted_pdf_path || !Storage::disk('local')->exists($maintenanceFile->converted_pdf_path)) {
                    $pdfPath = OfficeConverter::convertToPdf($maintenanceFile->path);
                    $maintenanceFile->update(['converted_pdf_path' => $pdfPath]);
                }

                $pdfServeUrl = URL::temporarySignedRoute(
                    'maintenance-files.serve-pdf',
                    now()->addHours(2),
                    ['maintenanceFile' => $maintenanceFile->id]
                );

                $serveUrl    = $pdfServeUrl;
                $viewerUrl   = $pdfServeUrl;
                $previewType = 'pdf';

            } catch (\Exception $e) {
                Log::error('[MF OfficeConverter] ' . $e->getMessage());
                \App\Models\SystemErrorLog::record($e, 'warning');
                $viewerUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($originalServeUrl);
            }
        }

        $comments = $maintenanceFile->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('page')
            ->orderBy('created_at')
            ->get()
            ->map(fn($c) => $this->commentToArray($c));

        $apiBase = url("maintenance-files/{$maintenanceFile->id}");

        return response()->json([
            'viewerUrl'        => $viewerUrl,
            'serveUrl'         => $serveUrl,
            'previewType'      => $previewType,
            'hasPages'         => $hasPages,
            'ext'              => $ext,
            'fileName'         => $maintenanceFile->original_name,
            'fileId'           => $maintenanceFile->id,
            'comments'         => $comments,
            'commentApiBase'   => $apiBase,
            'annotationApiBase'=> $apiBase,
        ]);
    }

    public function updateCategoryProject(Request $request, Project $project, MaintenanceFile $maintenanceFile)
    {
        $this->authorizeProject($project);
        abort_if($maintenanceFile->project_id !== $project->id, 404);

        $request->validate([
            'maintenance_category_id' => 'nullable|integer|exists:maintenance_file_categories,id',
        ]);

        $catId = $request->maintenance_category_id ?: null;
        $maintenanceFile->update(['maintenance_category_id' => $catId]);

        $cat = $catId ? MaintenanceFileCategory::find($catId) : null;

        return response()->json([
            'ok'    => true,
            'name'  => $cat?->name,
            'color' => $cat?->color,
        ]);
    }

    // ── 인증 SR 파일 의견/주석 ─────────────────────────────────

    public function getComments(MaintenanceFile $maintenanceFile)
    {
        $this->authorizeFileAccess($maintenanceFile);
        $comments = $maintenanceFile->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('page')->orderBy('created_at')
            ->get()->map(fn($c) => $this->commentToArray($c));
        return response()->json(['comments' => $comments, 'can_preview' => (bool) $maintenanceFile->previewType()]);
    }

    public function storeComment(Request $request, MaintenanceFile $maintenanceFile)
    {
        $this->authorizeFileAccess($maintenanceFile);
        $request->validate([
            'content'   => 'required|string|max:1000',
            'page'      => 'nullable|integer|min:1|max:9999',
            'parent_id' => 'nullable|integer|exists:file_comments,id',
        ]);
        $comment = FileComment::create([
            'project_file_id' => $maintenanceFile->id,
            'user_id'         => auth()->id(),
            'page'            => $request->parent_id ? null : ($request->page ?: null),
            'content'         => $request->content,
            'parent_id'       => $request->parent_id ?? null,
        ]);
        $comment->load('user');
        FileCommentPosted::dispatch($comment);
        return response()->json([
            'id'         => $comment->id,
            'page'       => $comment->page,
            'content'    => $comment->content,
            'user_name'  => $comment->user->name,
            'user_id'    => $comment->user_id,
            'parent_id'  => $comment->parent_id,
            'created_at' => $comment->created_at->diffForHumans(),
            'can_delete' => true,
            'replies'    => [],
        ]);
    }

    public function destroyComment(MaintenanceFile $maintenanceFile, FileComment $comment)
    {
        $this->authorizeFileAccess($maintenanceFile);
        abort_if($comment->project_file_id !== $maintenanceFile->id, 404);
        if ($comment->user_id !== auth()->id() && !auth()->user()->isAdmin()) abort(403);
        $fileId = $comment->project_file_id;
        $commentId = $comment->id;
        $comment->delete();
        FileCommentDeleted::dispatch($commentId, $fileId);
        return response()->json(['ok' => true]);
    }

    public function getAnnotations(MaintenanceFile $maintenanceFile, Request $request)
    {
        $this->authorizeFileAccess($maintenanceFile);
        $query = FileAnnotation::where('project_file_id', $maintenanceFile->id)->with('user:id,name');
        $page  = $request->query('page');
        if ($page !== null && $page !== '') $query->where('page', (int) $page);
        $annotations = $query->orderBy('created_at')->get()->map(function (FileAnnotation $a) {
            return [
                'id'         => $a->id,
                'type'       => $a->type,
                'page'       => $a->page,
                'data'       => $a->data,
                'user_name'  => $a->user?->name ?? $a->guest_name ?? '외부 리뷰어',
                'user_id'    => $a->user_id,
                'can_delete' => auth()->id() === $a->user_id || auth()->user()->isAdmin(),
                'created_at' => $a->created_at->diffForHumans(),
            ];
        });
        return response()->json(['ok' => true, 'annotations' => $annotations]);
    }

    public function storeAnnotation(Request $request, MaintenanceFile $maintenanceFile)
    {
        $this->authorizeFileAccess($maintenanceFile);
        $request->validate([
            'type' => 'required|in:number,rect,circle,line,text',
            'data' => 'required|array',
            'page' => 'nullable|integer|min:1|max:9999',
        ]);
        $ann = FileAnnotation::create([
            'project_file_id' => $maintenanceFile->id,
            'user_id'         => auth()->id(),
            'page'            => $request->page ?: null,
            'type'            => $request->type,
            'data'            => $request->data,
        ]);
        return response()->json([
            'ok' => true,
            'annotation' => [
                'id'         => $ann->id,
                'type'       => $ann->type,
                'page'       => $ann->page,
                'data'       => $ann->data,
                'user_name'  => auth()->user()->name,
                'user_id'    => $ann->user_id,
                'can_delete' => true,
                'created_at' => $ann->created_at->diffForHumans(),
            ],
        ]);
    }

    public function updateAnnotation(Request $request, MaintenanceFile $maintenanceFile, FileAnnotation $annotation)
    {
        $this->authorizeFileAccess($maintenanceFile);
        abort_if($annotation->project_file_id !== $maintenanceFile->id, 404);
        if ($annotation->user_id !== auth()->id() && !auth()->user()->isAdmin()) abort(403);
        $request->validate(['data' => 'required|array']);
        $annotation->update(['data' => $request->data]);
        return response()->json(['ok' => true]);
    }

    public function destroyAnnotation(MaintenanceFile $maintenanceFile, FileAnnotation $annotation)
    {
        $this->authorizeFileAccess($maintenanceFile);
        abort_if($annotation->project_file_id !== $maintenanceFile->id, 404);
        if ($annotation->user_id !== auth()->id() && !auth()->user()->isAdmin()) abort(403);
        $annotation->delete();
        return response()->json(['ok' => true]);
    }

    private function authorizeFileAccess(MaintenanceFile $file): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$file->project->isMember($user)) abort(403, '접근 권한이 없습니다.');
    }

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403, '접근 권한이 없습니다.');
    }

    private function commentToArray(FileComment $c): array
    {
        $authId    = auth()->id();
        $authAdmin = auth()->check() && auth()->user()->isAdmin();

        $data = [
            'id'         => $c->id,
            'page'       => $c->page,
            'content'    => $c->content,
            'user_name'  => $c->user?->name ?? $c->guest_name ?? '외부 리뷰어',
            'user_id'    => $c->user_id,
            'parent_id'  => $c->parent_id,
            'created_at' => $c->created_at->diffForHumans(),
            'can_delete' => $authId !== null && ($authId === $c->user_id || $authAdmin),
            'replies'    => [],
        ];

        if ($c->relationLoaded('replies')) {
            $data['replies'] = $c->replies->map(fn($r) => [
                'id'         => $r->id,
                'content'    => $r->content,
                'user_name'  => $r->user?->name ?? $r->guest_name ?? '외부 리뷰어',
                'user_id'    => $r->user_id,
                'created_at' => $r->created_at->diffForHumans(),
                'can_delete' => $authId !== null && ($authId === $r->user_id || $authAdmin),
            ])->toArray();
        }

        return $data;
    }

    private function authorize(ProjectMaintenance $maintenance): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$maintenance->project->isMember($user)) abort(403);
    }
}
