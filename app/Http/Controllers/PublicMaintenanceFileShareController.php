<?php

namespace App\Http\Controllers;

use App\Events\FileCommentPosted;
use App\Models\FileAnnotation;
use App\Models\FileComment;
use App\Models\MaintenanceFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicMaintenanceFileShareController extends Controller
{
    public function show(string $token)
    {
        $file = MaintenanceFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);

        $hasPdf = $file->converted_pdf_path
            && Storage::disk('local')->exists($file->converted_pdf_path);

        return view('files.public_share', [
            'file'                => $file,
            'token'               => $token,
            'customServeUrl'      => $hasPdf
                ? route('maintenance-files.public-serve-pdf', $token)
                : route('maintenance-files.public-serve', $token),
            'customPreviewType'   => $hasPdf ? 'pdf' : null,
            'customCommentsUrl'   => route('maintenance-files.public-comments.index', $token),
            'customCommentPost'   => route('maintenance-files.public-comments.store', $token),
            'customAnnGet'        => route('maintenance-files.public-annotations.index', $token),
            'customAnnPost'       => route('maintenance-files.public-annotations.store', $token),
            'customAnnBase'       => url("share/maintenance-file/{$token}/annotations") . '/',
        ]);
    }

    public function serve(string $token)
    {
        $file = MaintenanceFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable() && !$file->isUrlType(), 404);

        if (!Storage::disk('local')->exists($file->path)) {
            abort(404);
        }

        return Storage::disk('local')->response($file->path, $file->original_name, [
            'Content-Type'                 => $file->mime_type ?: 'application/octet-stream',
            'Content-Disposition'          => 'inline; filename*=UTF-8\'\'' . rawurlencode($file->original_name),
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET',
        ]);
    }

    public function servePdf(string $token)
    {
        $file = MaintenanceFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);
        abort_unless($file->converted_pdf_path && Storage::disk('local')->exists($file->converted_pdf_path), 404);

        $pdfName = pathinfo($file->original_name, PATHINFO_FILENAME) . '.pdf';

        return Storage::disk('local')->response($file->converted_pdf_path, $pdfName, [
            'Content-Type'                 => 'application/pdf',
            'Content-Disposition'          => 'inline; filename*=UTF-8\'\'' . rawurlencode($pdfName),
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET',
        ]);
    }

    public function getComments(string $token)
    {
        $file = MaintenanceFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);

        $comments = FileComment::where('project_file_id', $file->id)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('page')
            ->orderBy('created_at')
            ->get()
            ->map(fn($c) => $this->commentToArray($c));

        return response()->json(['comments' => $comments]);
    }

    public function storeComment(Request $request, string $token)
    {
        $file = MaintenanceFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);

        $request->validate([
            'guest_name' => 'required|string|max:100',
            'content'    => 'required|string|max:1000',
            'page'       => 'nullable|integer|min:1|max:9999',
        ]);

        $comment = FileComment::create([
            'project_file_id' => $file->id,
            'user_id'         => null,
            'guest_name'      => trim($request->guest_name),
            'page'            => $request->page ?: null,
            'content'         => $request->content,
            'parent_id'       => null,
        ]);

        FileCommentPosted::dispatch($comment);

        return response()->json($this->commentToArray($comment));
    }

    public function getAnnotations(string $token, Request $request)
    {
        $file = MaintenanceFile::where('share_token', $token)->firstOrFail();
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

    public function storeAnnotation(Request $request, string $token)
    {
        $file = MaintenanceFile::where('share_token', $token)->firstOrFail();
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

    public function updateAnnotation(Request $request, string $token, FileAnnotation $annotation)
    {
        $file = MaintenanceFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);
        abort_unless($annotation->project_file_id === $file->id, 404);

        $request->validate(['data' => 'required|array']);
        $annotation->update(['data' => $request->data]);
        return response()->json(['ok' => true]);
    }

    public function destroyAnnotation(string $token, FileAnnotation $annotation)
    {
        $file = MaintenanceFile::where('share_token', $token)->firstOrFail();
        abort_unless($file->isShareable(), 404);
        abort_unless($annotation->project_file_id === $file->id, 404);

        $annotation->delete();
        return response()->json(['ok' => true]);
    }

    private function commentToArray(FileComment $c): array
    {
        $data = [
            'id'         => $c->id,
            'page'       => $c->page,
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
