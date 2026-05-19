<?php

namespace App\Http\Controllers;

use App\Models\SharedFile;
use App\Models\SharedFileCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SharedFileController extends Controller
{
    /** 공유폴더 목록 (회사 단위 · 검색 · 페이징) */
    public function index(Request $request)
    {
        $cgId = $this->companyGroupId();

        $categories = SharedFileCategory::where('company_group_id', $cgId)
            ->withCount('files')
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $categoryId = $request->query('category');
        $q          = trim((string) $request->query('q', ''));

        $query = SharedFile::where('company_group_id', $cgId)
            ->with(['category', 'uploader'])
            ->latest();

        if ($categoryId === 'none') {
            $query->whereNull('category_id');
        } elseif ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('original_name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $files = $query->paginate(10)->withQueryString();

        $totalCount         = SharedFile::where('company_group_id', $cgId)->count();
        $uncategorizedCount = SharedFile::where('company_group_id', $cgId)->whereNull('category_id')->count();

        return view('shared-folder.index', compact(
            'categories', 'files', 'categoryId', 'q', 'totalCount', 'uncategorizedCount'
        ));
    }

    /** 파일 업로드 (복수) */
    public function store(Request $request)
    {
        $cgId = $this->companyGroupId();

        $request->validate([
            'files'       => 'required|array|max:20',
            'files.*'     => 'file|max:51200',          // 50MB/파일
            'category_id' => 'nullable|integer',
            'description' => 'nullable|string|max:500',
        ]);

        $categoryId = $request->input('category_id') ?: null;
        if ($categoryId && !SharedFileCategory::where('id', $categoryId)->where('company_group_id', $cgId)->exists()) {
            $categoryId = null;
        }

        $count = 0;
        foreach (array_filter((array) $request->file('files', [])) as $file) {
            $ext        = $file->getClientOriginalExtension();
            $storedName = (string) Str::uuid() . ($ext ? '.' . $ext : '');
            $path       = $file->storeAs("shared_folders/{$cgId}", $storedName, 'local');

            SharedFile::create([
                'company_group_id' => $cgId,
                'category_id'      => $categoryId,
                'uploaded_by'      => auth()->id(),
                'original_name'    => $file->getClientOriginalName(),
                'stored_name'      => $storedName,
                'path'             => $path,
                'mime_type'        => $file->getClientMimeType(),
                'size'             => $file->getSize() ?: 0,
                'description'      => $request->input('description'),
            ]);
            $count++;
        }

        return back()->with('success', __('shared-folder.uploaded', ['n' => $count]));
    }

    /** 파일 다운로드 */
    public function download(SharedFile $sharedFile)
    {
        $this->authorizeFile($sharedFile);
        abort_unless(Storage::disk('local')->exists($sharedFile->path), 404);

        return Storage::disk('local')->download($sharedFile->path, $sharedFile->original_name);
    }

    /** 파일 삭제 (업로더 본인 또는 관리자) */
    public function destroy(SharedFile $sharedFile)
    {
        $this->authorizeFile($sharedFile);
        $user = auth()->user();
        abort_unless($sharedFile->uploaded_by === $user->id || $user->isAdmin(), 403);

        Storage::disk('local')->delete($sharedFile->path);
        $sharedFile->delete();

        return back()->with('success', __('shared-folder.deleted'));
    }

    /** 카테고리(폴더) 추가 */
    public function storeCategory(Request $request)
    {
        $cgId = $this->companyGroupId();

        $data = $request->validate([
            'name'  => 'required|string|max:80',
            'color' => 'nullable|string|max:7',
        ]);

        SharedFileCategory::create([
            'company_group_id' => $cgId,
            'name'             => $data['name'],
            'color'            => ($data['color'] ?? null) ?: '#6366f1',
            'sort_order'       => (int) SharedFileCategory::where('company_group_id', $cgId)->max('sort_order') + 1,
        ]);

        return back()->with('success', __('shared-folder.category_added'));
    }

    /** 카테고리 삭제 (소속 파일은 미분류로 이동) */
    public function destroyCategory(SharedFileCategory $sharedFileCategory)
    {
        abort_unless($sharedFileCategory->company_group_id === $this->companyGroupId(), 403);

        $sharedFileCategory->delete();

        return back()->with('success', __('shared-folder.category_deleted'));
    }

    // ────────────────────────────────────────────────────────

    private function companyGroupId(): int
    {
        $user = auth()->user();
        abort_unless($user && $user->hasCompany(), 403, __('shared-folder.no_company'));

        return (int) $user->company_group_id;
    }

    private function authorizeFile(SharedFile $file): void
    {
        abort_unless($file->company_group_id === $this->companyGroupId(), 403);
    }
}
