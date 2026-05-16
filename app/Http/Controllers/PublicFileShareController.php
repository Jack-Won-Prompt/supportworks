<?php

namespace App\Http\Controllers;

use App\Events\FileCommentPosted;
use App\Models\FileAnnotation;
use App\Models\FileComment;
use App\Models\ProjectFile;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\FileCommentNotificationService;
use App\Services\OfficeConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PublicFileShareController extends Controller
{
    public function show(string $token, Request $request)
    {
        $file = ProjectFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);

        $currentVersion = $file->currentVersionNumber();
        [$activeVersion, $useVersionRow] = $this->resolveVersion($file, $request, $currentVersion);
        $isCurrent = ($useVersionRow === null);
        $isEmbed   = $request->query('embed') === '1';

        $vParam = $isCurrent ? '' : ('?version=' . $activeVersion);

        if ($isCurrent && $file->isUrlType()) {
            $previewType = 'url';
            $serveUrl    = route('files.public-serve', $token);
        } else {
            $serveFileName = $isCurrent ? $file->original_name : $useVersionRow->original_name;
            $previewType   = $isCurrent
                ? $file->previewType()
                : ProjectFile::previewTypeFor($serveFileName, $useVersionRow->mime_type);
            $serveUrl = route('files.public-serve', $token) . $vParam;

            // Office 문서 → PDF 변환 (프로젝트 뷰어와 동일 동작)
            if ($previewType === 'office') {
                $sourcePath    = $isCurrent ? $file->path : $useVersionRow->path;
                $cachedPdfPath = $isCurrent ? $file->converted_pdf_path : $useVersionRow->converted_pdf_path;
                try {
                    set_time_limit(120);
                    if (!$cachedPdfPath || !Storage::disk('local')->exists($cachedPdfPath)) {
                        $cachedPdfPath = OfficeConverter::convertToPdf($sourcePath);
                        if ($isCurrent) {
                            $file->update(['converted_pdf_path' => $cachedPdfPath]);
                        } else {
                            $useVersionRow->update(['converted_pdf_path' => $cachedPdfPath]);
                        }
                    }
                    $serveUrl    = route('files.public-serve-pdf', $token) . $vParam;
                    $previewType = 'pdf';
                } catch (\Throwable $e) {
                    Log::warning('[PublicShare OfficeConverter] ' . $e->getMessage());
                    // 변환 실패 → office 온라인 뷰어로 폴백 (previewType 'office' 유지)
                }
            }
        }

        return view('files.public_share', [
            'file'              => $file,
            'token'             => $token,
            'customServeUrl'    => $serveUrl,
            'customPreviewType' => $previewType,
            'customCommentsUrl' => route('files.public-comments.index', $token) . $vParam,
            'versions'          => $this->versionsList($file, $currentVersion),
            'activeVersion'     => $activeVersion,
            'currentVersion'    => $currentVersion,
            'isEmbed'           => $isEmbed,
        ]);
    }

    public function servePdf(string $token, Request $request)
    {
        $file = ProjectFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);

        $pdfPath  = $file->converted_pdf_path;
        $baseName = $file->original_name;

        $version = (int) $request->query('version', 0);
        if ($version > 0 && $version < $file->currentVersionNumber()) {
            $vr = $file->versions()->where('version', $version)->first();
            if ($vr) {
                $pdfPath  = $vr->converted_pdf_path;
                $baseName = $vr->original_name;
            }
        }

        abort_unless($pdfPath && Storage::disk('local')->exists($pdfPath), 404);

        $pdfName = pathinfo($baseName, PATHINFO_FILENAME) . '.pdf';

        return Storage::disk('local')->response($pdfPath, $pdfName, [
            'Content-Type'                 => 'application/pdf',
            'Content-Disposition'          => 'inline; filename*=UTF-8\'\'' . rawurlencode($pdfName),
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET',
        ]);
    }

    public function serve(string $token, Request $request)
    {
        $file = ProjectFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable() && !$file->isUrlType(), 404);

        $path = $file->path;
        $name = $file->original_name;
        $mime = $file->mime_type;

        $version = (int) $request->query('version', 0);
        if ($version > 0 && $version < $file->currentVersionNumber()) {
            $vr = $file->versions()->where('version', $version)->first();
            if ($vr) {
                $path = $vr->path;
                $name = $vr->original_name;
                $mime = $vr->mime_type;
            }
        }

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        // BinaryFileResponse — Range 요청 지원으로 동영상 시킹 가능
        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type'                 => $mime ?: 'application/octet-stream',
            'Content-Disposition'          => 'inline; filename*=UTF-8\'\'' . rawurlencode($name),
            'Accept-Ranges'                => 'bytes',
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET',
        ]);
    }

    public function getComments(string $token, Request $request)
    {
        $file = ProjectFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);

        $query = FileComment::where('project_file_id', $file->id)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('page')
            ->orderBy('created_at');

        // 특정 이전 버전 요청 시 — 그 버전에서 해결/동결된 의견만 (프로젝트 뷰어와 동일 기준)
        $version = (int) $request->query('version', 0);
        if ($version > 0 && $version < $file->currentVersionNumber()) {
            $query->where(function ($q) use ($version) {
                $q->where('resolved_at_version', $version + 1)
                  ->orWhere('frozen_at_version', $version + 1);
            });
        }

        $comments = $query->get()->map(fn($c) => $this->commentToArray($c));

        return response()->json(['comments' => $comments]);
    }

    /** 요청된 버전을 해석한다. @return array{0:int,1:?\App\Models\FileVersion} */
    private function resolveVersion(ProjectFile $file, Request $request, int $currentVersion): array
    {
        $requestedVersion = (int) $request->query('version', 0);
        if ($requestedVersion <= 0 || $requestedVersion >= $currentVersion) {
            return [$currentVersion, null];
        }
        $row = $file->versions()->where('version', $requestedVersion)->first();
        return $row ? [$requestedVersion, $row] : [$currentVersion, null];
    }

    /**
     * 파일의 전체 버전 목록.
     * 버전이 1개뿐이거나 비교 미지원 형식(동영상·URL 등)이면 빈 배열 → 버전 비교 비활성.
     */
    private function versionsList(ProjectFile $file, int $currentVersion): array
    {
        // 버전 비교 대상: PDF·오피스 문서·이미지·동영상 (프로젝트 뷰어와 동일 정책)
        if (!in_array($file->previewType(), ['pdf', 'office', 'image', 'video'], true)) {
            return [];
        }

        $rows = $file->versions()->with('uploader:id,name')->orderBy('version')->get();
        if ($rows->count() < 2) {
            return [];
        }
        return $rows->map(fn($v) => [
            'version'    => $v->version,
            'name'       => $v->original_name,
            'uploader'   => $v->uploader?->name,
            'created_at' => $v->created_at?->format('Y-m-d H:i'),
            'is_current' => $v->version === $currentVersion,
        ])->values()->toArray();
    }

    public function storeComment(Request $request, string $token)
    {
        $file = ProjectFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);

        $request->validate([
            'guest_name' => 'required|string|max:100',
            'content'    => 'required|string|max:1000',
            'page'       => 'nullable|integer|min:1|max:9999',
            'video_time' => 'nullable|numeric|min:0',
        ]);

        $comment = FileComment::create([
            'project_file_id' => $file->id,
            'user_id'         => null,
            'guest_name'      => trim($request->guest_name),
            'page'            => $request->page ?: null,
            'video_time'      => $request->filled('video_time') ? (float) $request->video_time : null,
            'content'         => $request->content,
            'parent_id'       => null,
        ]);

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

        return response()->json($this->commentToArray($comment));
    }

    public function getAnnotations(string $token, Request $request)
    {
        $file = ProjectFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);

        $query = FileAnnotation::where('project_file_id', $file->id)->with('user:id,name');
        $page  = $request->query('page');
        if ($page !== null && $page !== '') {
            $query->where('page', (int) $page);
        }

        $annotations = $query->orderBy('created_at')->get()->map(fn($a) => [
            'id'         => $a->id,
            'type'       => $a->type,
            'page'       => $a->page,
            'data'       => $a->data,
            'user_name'  => $a->user?->name ?? $a->guest_name ?? '외부 리뷰어',
            'user_id'    => $a->user_id,
            'can_delete' => false,
            'created_at' => $a->created_at->diffForHumans(),
        ]);

        return response()->json(['ok' => true, 'annotations' => $annotations]);
    }

    public function destroyAnnotation(string $token, FileAnnotation $annotation)
    {
        $file = ProjectFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);
        abort_unless($annotation->project_file_id === $file->id, 404);

        $annotation->delete();
        return response()->json(['ok' => true]);
    }

    public function updateAnnotation(Request $request, string $token, FileAnnotation $annotation)
    {
        $file = ProjectFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);
        abort_unless($annotation->project_file_id === $file->id, 404);

        $request->validate(['data' => 'required|array']);
        $annotation->update(['data' => $request->data]);
        return response()->json(['ok' => true]);
    }

    public function storeAnnotation(Request $request, string $token)
    {
        $file = ProjectFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);

        $request->validate([
            'guest_name' => 'required|string|max:100',
            'type'       => 'required|in:number,rect,circle,line,text',
            'data'       => 'required|array',
            'page'       => 'nullable|integer|min:1|max:9999',
        ]);

        $ann = FileAnnotation::create([
            'project_file_id' => $file->id,
            'user_id'         => null,
            'guest_name'      => trim($request->guest_name),
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
                'user_name'  => trim($request->guest_name),
                'user_id'    => null,
                'can_delete' => false,
                'created_at' => $ann->created_at->diffForHumans(),
            ],
        ]);
    }

    /**
     * 공유받은 사람의 회원가입 폼.
     * 공유한 사용자의 회사 정보가 자동으로 결정됨.
     */
    public function signupForm(string $token)
    {
        $file = ProjectFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);

        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $file->load('uploader.companyGroup');
        $inviterName = $file->uploader?->name ?? '공유자';
        $companyName = $file->uploader?->companyGroup?->name ?? $file->uploader?->company ?? '';

        return view('files.public_share_signup', [
            'file'        => $file,
            'token'       => $token,
            'inviterName' => $inviterName,
            'companyName' => $companyName,
        ]);
    }

    /**
     * 공유받은 사람의 회원가입 처리.
     * 공유한 사용자의 company_group_id로 자동 소속.
     */
    public function signup(Request $request, string $token)
    {
        $file = ProjectFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);

        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:255|unique:users,email',
            'phone'    => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $file->load('uploader');
        $companyGroupId = $file->uploader?->company_group_id;

        $user = User::create([
            'name'             => trim($request->name),
            'email'            => trim($request->email),
            'phone'            => $request->filled('phone') ? preg_replace('/[^\d+\-\s]/', '', trim($request->phone)) : null,
            'password'         => Hash::make($request->password),
            'role'             => 'guest',
            'company_group_id' => $companyGroupId,
        ]);

        // 공유 파일이 속한 프로젝트에도 member로 등록(존재할 때만)
        if ($file->project_id) {
            ProjectMember::firstOrCreate(
                ['project_id' => $file->project_id, 'user_id' => $user->id],
                ['role' => 'member']
            );
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', '환영합니다, ' . $user->name . '님! SupportWorks에 가입되셨습니다 🎉');
    }

    private function commentToArray(FileComment $c): array
    {
        $data = [
            'id'         => $c->id,
            'page'       => $c->page,
            'video_time' => $c->video_time !== null ? (float) $c->video_time : null,
            'content'    => $c->content,
            'user_name'  => $c->user?->name ?? $c->guest_name ?? '외부 리뷰어',
            'user_id'    => $c->user_id,
            'parent_id'  => $c->parent_id,
            'created_at' => $c->created_at->diffForHumans(),
            'can_delete' => false,
            'replies'    => [],
        ];

        if ($c->relationLoaded('replies')) {
            $data['replies'] = $c->replies->map(fn($r) => [
                'id'         => $r->id,
                'content'    => $r->content,
                'user_name'  => $r->user?->name ?? $r->guest_name ?? '외부 리뷰어',
                'user_id'    => $r->user_id,
                'parent_id'  => $r->parent_id,
                'created_at' => $r->created_at->diffForHumans(),
                'can_delete' => false,
            ])->toArray();
        }

        return $data;
    }
}
