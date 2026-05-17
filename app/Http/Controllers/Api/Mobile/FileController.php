<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
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

        $files = $project->files()
            ->with('uploader:id,name')
            ->latest()
            ->get();

        return response()->json($files->map(fn($f) => $this->resource($f)));
    }

    /** POST /projects/{project}/files - 파일 업로드 */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);

        $request->validate([
            'file'        => 'required|file|max:51200', // 50MB
            'description' => 'nullable|string|max:255',
        ]);

        $uploaded   = $request->file('file');
        $ext        = $uploaded->getClientOriginalExtension();
        $storedName = Str::uuid() . ($ext ? '.' . $ext : '');
        $path       = $uploaded->storeAs("projects/{$project->id}", $storedName, 'local');

        $file = $project->files()->create([
            'uploaded_by'   => $request->user()->id,
            'original_name' => $uploaded->getClientOriginalName(),
            'stored_name'   => $storedName,
            'path'          => $path,
            'mime_type'     => $uploaded->getMimeType(),
            'size'          => $uploaded->getSize(),
            'description'   => $request->description,
            'file_type'     => 'file',
        ]);

        $file->load('uploader:id,name');

        return response()->json($this->resource($file), 201);
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

    private function resource(ProjectFile $f): array
    {
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
            'description'    => $f->description,
            'uploader'       => $f->uploader ? ['id' => $f->uploader->id, 'name' => $f->uploader->name] : null,
            'created_at'     => $f->created_at,
        ];
    }

    private function authorizeProject($user, Project $project): void
    {
        if ($user->isAdmin()) return;
        $member = $project->projectMembers()->where('user_id', $user->id)->first();
        if (!$member) abort(403, '접근 권한이 없습니다.');
    }
}