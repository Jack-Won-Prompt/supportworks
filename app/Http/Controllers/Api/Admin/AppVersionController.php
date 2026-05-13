<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    // GET /api/app-version  (public, no auth)
    public function publicVersion(): JsonResponse
    {
        $active = AppVersion::where('is_active', true)->first();
        if (!$active) {
            return response()->json(['version' => '1.0.0', 'download_url' => '', 'release_notes' => '']);
        }
        return response()->json([
            'version'       => $active->version,
            'download_url'  => $active->download_url,
            'release_notes' => $active->release_notes ?? '',
        ]);
    }

    // GET /api/admin/app-versions
    public function index(): JsonResponse
    {
        $versions = AppVersion::orderBy('created_at', 'desc')->get();
        return response()->json($versions);
    }

    // POST /api/admin/app-versions
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'version'       => 'required|string|max:20',
            'download_url'  => 'required|string',
            'release_notes' => 'nullable|string',
        ]);

        $version = AppVersion::create([
            'version'       => $request->version,
            'download_url'  => $request->download_url,
            'release_notes' => $request->release_notes,
            'is_active'     => false,
        ]);

        return response()->json($version, 201);
    }

    // PUT /api/admin/app-versions/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'version'       => 'sometimes|string|max:20',
            'download_url'  => 'sometimes|string',
            'release_notes' => 'nullable|string',
        ]);

        $version = AppVersion::findOrFail($id);
        $version->update($request->only(['version', 'download_url', 'release_notes']));

        return response()->json($version);
    }

    // DELETE /api/admin/app-versions/{id}
    public function destroy(int $id): JsonResponse
    {
        AppVersion::findOrFail($id)->delete();
        return response()->json(['message' => '삭제되었습니다.']);
    }

    // POST /api/admin/app-versions/{id}/activate
    public function activate(int $id): JsonResponse
    {
        AppVersion::where('is_active', true)->update(['is_active' => false]);
        AppVersion::findOrFail($id)->update(['is_active' => true]);

        return response()->json(['message' => '활성화되었습니다.']);
    }

    // POST /api/admin/app-versions/upload
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:exe,msi,dmg,zip|max:524288', // 512MB
        ]);

        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('downloads');

        $file     = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $file->storeAs('downloads', $fileName, 'public');

        $url = url('storage/downloads/' . $fileName);

        return response()->json(['url' => $url]);
    }
}
