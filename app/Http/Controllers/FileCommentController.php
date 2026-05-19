<?php

namespace App\Http\Controllers;

use App\Events\FileCommentDeleted;
use App\Events\FileCommentPosted;
use App\Models\Discussion;
use App\Models\FileAnnotation;
use App\Models\FileComment;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Services\FileCommentNotificationService;
use App\Services\FileCommentReport\ReportService;
use App\Services\OfficeConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class FileCommentController extends Controller
{
    public function index(Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);

        $comments = $file->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('page')
            ->orderBy('created_at')
            ->get()
            ->map(fn($c) => $this->commentToArray($c));

        return response()->json([
            'comments'   => $comments,
            'can_preview' => (bool) $file->previewType(),
            'file_id'    => $file->id,
        ]);
    }

    public function previewData(Project $project, ProjectFile $file, Request $request)
    {
        $this->authorizeProject($project);

        $currentVersion   = $file->currentVersionNumber();
        $requestedVersion = (int) $request->query('version', 0);
        if ($requestedVersion <= 0 || $requestedVersion >= $currentVersion) {
            $requestedVersion = $currentVersion;
        }

        $useVersionRow = null;
        if ($requestedVersion < $currentVersion) {
            $useVersionRow = $file->versions()->where('version', $requestedVersion)->first();
            if (!$useVersionRow) {
                $requestedVersion = $currentVersion;
            }
        }

        $serveFileName = $useVersionRow ? $useVersionRow->original_name : $file->original_name;
        $previewType   = $useVersionRow
            ? \App\Models\ProjectFile::previewTypeFor($serveFileName, $useVersionRow->mime_type)
            : $file->previewType();
        if (!$previewType) {
            return response()->json(['error' => '미리보기 불가'], 422);
        }

        $ext = strtolower(pathinfo($serveFileName, PATHINFO_EXTENSION));

        $serveRouteParams = ['file' => $file->id];
        if ($useVersionRow) $serveRouteParams['version'] = $requestedVersion;
        $originalServeUrl = URL::temporarySignedRoute('files.serve', now()->addHours(2), $serveRouteParams);

        $serveUrl  = $originalServeUrl;
        $viewerUrl = $originalServeUrl;
        $hasPages  = in_array($previewType, ['office', 'pdf']);

        $isCurrent     = ($useVersionRow === null);
        $sourcePath    = $isCurrent ? $file->path : $useVersionRow->path;
        $cachedPdfPath = $isCurrent ? $file->converted_pdf_path : $useVersionRow->converted_pdf_path;

        if ($previewType === 'office') {
            try {
                set_time_limit(120);

                if (!$cachedPdfPath || !Storage::disk('local')->exists($cachedPdfPath)) {
                    $pdfPath = OfficeConverter::convertToPdf($sourcePath);
                    if ($isCurrent) {
                        $file->update(['converted_pdf_path' => $pdfPath]);
                    } else {
                        $useVersionRow->update(['converted_pdf_path' => $pdfPath]);
                    }
                    $cachedPdfPath = $pdfPath;
                }

                $pdfRouteParams = ['file' => $file->id];
                if (!$isCurrent) $pdfRouteParams['version'] = $requestedVersion;
                $pdfServeUrl = URL::temporarySignedRoute('files.serve-pdf', now()->addHours(2), $pdfRouteParams);

                $serveUrl    = $pdfServeUrl;
                $viewerUrl   = $pdfServeUrl;
                $previewType = 'pdf';
            } catch (\Exception $e) {
                Log::error('[OfficeConverter] ' . $e->getMessage(), [
                    'file'    => $serveFileName,
                    'path'    => $sourcePath,
                    'os'      => PHP_OS_FAMILY,
                    'soffice' => config('services.libreoffice.path'),
                ]);
                \App\Models\SystemErrorLog::record($e, 'warning');
                $viewerUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($originalServeUrl);
            }
        }

        $sheetNames = [];
        if ($isCurrent && in_array($ext, ['xlsx', 'xls']) && $previewType === 'pdf') {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(
                    Storage::disk('local')->path($sourcePath)
                );
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $sheetNames[] = $sheet->getTitle();
                }
            } catch (\Throwable) {
                $sheetNames = [];
            }
        }

        // 의견 필터:
        //  · 현재 버전 → 미해결 + 동결되지 않은 코멘트
        //  · 이전 버전 V → 그 버전에서 해결되었거나(resolved_at_version=V+1) 동결된(frozen_at_version=V+1) 코멘트
        $commentsQuery = $file->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('page')
            ->orderBy('created_at');
        if ($isCurrent) {
            $commentsQuery->where('resolved', false)
                ->whereNull('frozen_at_version');
        } else {
            $commentsQuery->where(function ($q) use ($requestedVersion) {
                $q->where('resolved_at_version', $requestedVersion + 1)
                  ->orWhere('frozen_at_version',  $requestedVersion + 1);
            });
        }
        $comments = $commentsQuery->get()->map(fn($c) => $this->commentToArray($c));

        // 버전 목록 (오름차순)
        $versionRows = $file->versions()->with('uploader:id,name')->orderBy('version')->get();
        if ($versionRows->isEmpty()) {
            $versions = [[
                'version'    => 1,
                'name'       => $file->original_name,
                'uploader'   => null,
                'created_at' => $file->created_at?->format('Y-m-d H:i'),
                'is_current' => true,
            ]];
        } else {
            $versions = $versionRows->map(fn($v) => [
                'version'    => $v->version,
                'name'       => $v->original_name,
                'uploader'   => $v->uploader ? ['id' => $v->uploader->id, 'name' => $v->uploader->name] : null,
                'created_at' => $v->created_at?->format('Y-m-d H:i'),
                'is_current' => $v->version === $currentVersion,
            ])->values()->toArray();
        }

        return response()->json([
            'viewerUrl'      => $viewerUrl,
            'serveUrl'       => $serveUrl,
            'previewType'    => $previewType,
            'hasPages'       => $hasPages,
            'ext'            => $ext,
            'fileName'       => $serveFileName,
            'fileId'         => $file->id,
            'comments'       => $comments,
            'sheetNames'     => $sheetNames,
            'version'        => $requestedVersion,
            'currentVersion' => $currentVersion,
            'versions'       => $versions,
        ]);
    }

    public function store(Project $project, ProjectFile $file, Request $request)
    {
        $this->authorizeProject($project);

        $request->validate([
            'content'    => 'required|string|max:1000',
            'page'       => 'nullable|integer|min:1|max:9999',
            'video_time' => 'nullable|numeric|min:0',
            'parent_id'  => 'nullable|integer|exists:file_comments,id',
        ]);

        $comment = FileComment::create([
            'project_file_id' => $file->id,
            'user_id'         => auth()->id(),
            'page'            => $request->parent_id ? null : ($request->page ?: null),
            'video_time'      => $request->parent_id ? null : ($request->filled('video_time') ? (float) $request->video_time : null),
            'content'         => $request->content,
            'parent_id'       => $request->parent_id ?? null,
        ]);

        $comment->load('user');
        FileCommentPosted::dispatch($comment);

        $commentId = $comment->id;
        app()->terminating(static function () use ($commentId) {
            set_time_limit(0);
            try {
                $fresh = FileComment::find($commentId);
                if ($fresh) {
                    FileCommentNotificationService::notifyUploader($fresh);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[FileCommentNotify] ' . $e->getMessage());
            }
        });

        return response()->json([
            'id'            => $comment->id,
            'page'          => $comment->page,
            'video_time'    => $comment->video_time,
            'content'       => $comment->content,
            'user_name'     => $comment->user->name,
            'user_id'       => $comment->user_id,
            'parent_id'     => $comment->parent_id,
            'created_at'    => $comment->created_at->diffForHumans(),
            'can_delete'    => true,
            'discussion_id' => null,
            'plan_do_act_id' => null,
            'replies'       => [],
        ]);
    }

    public function convertToDiscussion(Project $project, ProjectFile $file, FileComment $comment, Request $request)
    {
        $this->authorizeProject($project);

        abort_if($comment->project_file_id !== $file->id, 404);
        abort_if($comment->parent_id !== null, 422, '답글은 논의사항으로 등록할 수 없습니다.');

        $existing = Discussion::where('source_file_comment_id', $comment->id)->first();
        if ($existing) {
            return response()->json([
                'ok'            => false,
                'already'       => true,
                'discussion_id' => $existing->id,
                'message'       => '이미 논의사항으로 등록된 의견입니다.',
            ], 409);
        }

        $request->validate([
            'title'           => 'nullable|string|max:255',
            'discussion_date' => 'nullable|date',
        ]);

        $comment->loadMissing('user');
        $authorName = $comment->user?->name ?? $comment->guest_name ?? '외부 리뷰어';
        $fileName   = $file->original_name;

        $defaultTitle = mb_substr(sprintf('[%s] %s', $fileName, $authorName), 0, 255);
        $title        = trim((string) $request->input('title')) ?: $defaultTitle;

        $escapeMd = static fn(string $s): string => str_replace(['*','_','[',']','`','#','>'], ['\\*','\\_','\\[','\\]','\\`','\\#','\\>'], $s);

        $content = sprintf(
            "> *원본 의견 — 파일: %s · 작성자: %s · 등록일: %s*\n\n%s",
            $escapeMd($fileName),
            $escapeMd($authorName),
            optional($comment->created_at)->format('Y-m-d H:i'),
            $comment->content
        );

        $discussion = Discussion::create([
            'project_id'             => $project->id,
            'user_id'                => auth()->id(),
            'source_file_comment_id' => $comment->id,
            'title'                  => $title,
            'content'                => $content,
            'discussion_date'        => $request->input('discussion_date') ?: now()->toDateString(),
            'status'                 => 'open',
        ]);

        return response()->json([
            'ok'            => true,
            'discussion_id' => $discussion->id,
            'url'           => route('projects.discussions.index', $project) . '?open=' . $discussion->id,
        ]);
    }

    public function destroy(Project $project, ProjectFile $file, FileComment $comment)
    {
        $this->authorizeProject($project);

        abort_if($comment->project_file_id !== $file->id, 404);

        if ($comment->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $fileId = $comment->project_file_id;
        $commentId = $comment->id;
        $comment->delete();
        FileCommentDeleted::dispatch($commentId, $fileId);

        return response()->json(['ok' => true]);
    }

    // ── 도형 주석 ──────────────────────────────────────────────

    public function getAnnotations(Project $project, ProjectFile $file, Request $request)
    {
        $this->authorizeProject($project);

        $page = $request->query('page');

        // 버전별 주석 — 미지정 시 현재(최신) 버전
        $version = (int) $request->query('version', 0);
        if ($version <= 0) {
            $version = $file->currentVersionNumber();
        }

        $query = FileAnnotation::where('project_file_id', $file->id)
            ->where('version', $version)
            ->with('user:id,name');

        if ($page !== null && $page !== '') {
            $query->where('page', (int) $page);
        }

        $annotations = $query->orderBy('created_at')->get()
            ->map(function (FileAnnotation $a) {
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

    public function storeAnnotation(Project $project, ProjectFile $file, Request $request)
    {
        $this->authorizeProject($project);

        $request->validate([
            'type'    => 'required|in:number,rect,circle,line,text',
            'data'    => 'required|array',
            'page'    => 'nullable|integer|min:1|max:9999',
            'version' => 'nullable|integer|min:1',
        ]);

        // 주석은 현재 보고 있는 버전에 귀속 — 미지정 시 현재(최신) 버전
        $version = (int) $request->input('version', 0);
        if ($version <= 0) {
            $version = $file->currentVersionNumber();
        }

        $ann = FileAnnotation::create([
            'project_file_id' => $file->id,
            'version'         => $version,
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

    public function updateAnnotation(Project $project, ProjectFile $file, FileAnnotation $annotation, Request $request)
    {
        $this->authorizeProject($project);

        abort_if($annotation->project_file_id !== $file->id, 404);

        if ($annotation->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $request->validate(['data' => 'required|array']);

        $annotation->update(['data' => $request->data]);

        return response()->json(['ok' => true]);
    }

    public function destroyAnnotation(Project $project, ProjectFile $file, FileAnnotation $annotation)
    {
        $this->authorizeProject($project);

        abort_if($annotation->project_file_id !== $file->id, 404);

        if ($annotation->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $annotation->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * 의견(+답글) 보고서 다운로드.
     * - PDF/이미지/기타: 별도 PDF 보고서
     * - Word/Excel/PowerPoint: 원본 파일 끝에 "페이지별 의견" 섹션/시트/슬라이드 추가
     */
    public function downloadCommentsReport(Project $project, ProjectFile $file, Request $request, ReportService $reports)
    {
        $this->authorizeProject($project);
        abort_if($file->project_id !== $project->id, 404);

        $version = (int) $request->query('version', 0);

        $built = $reports->build($file, $version);

        // 한글/유니코드 파일명 보존 — Symfony 기본 makeDisposition 의 ASCII 음역 fallback 우회
        $filename = $built['download_name'];
        $encoded  = rawurlencode($filename);

        $response = response()->download(
            $built['path'],
            $filename,
            ['Content-Type' => $built['mime']],
        )->deleteFileAfterSend(true);

        $response->headers->set(
            'Content-Disposition',
            "attachment; filename*=UTF-8''{$encoded}"
        );

        return $response;
    }

    private function commentToArray(FileComment $c): array
    {
        $authId    = auth()->id();
        $authAdmin = auth()->check() && auth()->user()->isAdmin();

        $discussionId = $c->parent_id === null
            ? Discussion::where('source_file_comment_id', $c->id)->value('id')
            : null;
        $planDoActId = $c->parent_id === null
            ? \App\Models\PlanDoAct::where('source_file_comment_id', $c->id)->value('id')
            : null;

        $data = [
            'id'             => $c->id,
            'page'           => $c->page,
            'video_time'     => $c->video_time !== null ? (float) $c->video_time : null,
            'content'        => $c->content,
            'user_name'      => $c->user?->name ?? $c->guest_name ?? '외부 리뷰어',
            'user_id'        => $c->user_id,
            'parent_id'      => $c->parent_id,
            'created_at'     => $c->created_at->diffForHumans(),
            'can_delete'     => $authId !== null && ($authId === $c->user_id || $authAdmin),
            'discussion_id'  => $discussionId,
            'plan_do_act_id' => $planDoActId,
            'replies'        => [],
        ];

        if ($c->relationLoaded('replies')) {
            $data['replies'] = $c->replies->map(fn($r) => [
                'id'         => $r->id,
                'content'    => $r->content,
                'user_name'  => $r->user?->name ?? $r->guest_name ?? '외부 리뷰어',
                'user_id'    => $r->user_id,
                'parent_id'  => $r->parent_id,
                'created_at' => $r->created_at->diffForHumans(),
                'can_delete' => $authId !== null && ($authId === $r->user_id || $authAdmin),
            ])->toArray();
        }

        return $data;
    }

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403);
    }
}
