<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;

class AppVersionWebController extends Controller
{
    public function index()
    {
        $this->authorizeSuperAdmin();
        $versions = AppVersion::orderBy('created_at', 'desc')->get();
        return view('admin.app-versions.index', compact('versions'));
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();
        $request->validate([
            'version'       => 'required|string|max:20',
            'release_notes' => 'nullable|string',
            'installer'     => 'required|file|mimes:exe,msi,zip|max:524288',
        ]);

        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('downloads');

        $file     = $request->file('installer');
        $fileName = 'SupportWorks_Setup_' . $request->version . '.' . $file->getClientOriginalExtension();
        $file->storeAs('downloads', $fileName, 'public');

        AppVersion::create([
            'version'       => $request->version,
            'download_url'  => url('storage/downloads/' . $fileName),
            'release_notes' => $request->release_notes,
            'is_active'     => false,
        ]);

        return redirect()->route('admin.app-versions.index')
            ->with('success', 'v' . $request->version . ' 버전이 등록되었습니다.');
    }

    public function update(Request $request, AppVersion $appVersion)
    {
        $this->authorizeSuperAdmin();
        $request->validate([
            'version'       => 'required|string|max:20',
            'release_notes' => 'nullable|string',
            'installer'     => 'nullable|file|mimes:exe,msi,zip|max:524288',
        ]);

        $data = [
            'version'       => $request->version,
            'release_notes' => $request->release_notes,
        ];

        if ($request->hasFile('installer')) {
            $file     = $request->file('installer');
            $fileName = 'SupportWorks_Setup_' . $request->version . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/downloads', $fileName);
            $data['download_url'] = url('storage/downloads/' . $fileName);
        }

        $appVersion->update($data);

        return redirect()->route('admin.app-versions.index')
            ->with('success', 'v' . $request->version . ' 버전이 수정되었습니다.');
    }

    public function destroy(AppVersion $appVersion)
    {
        $this->authorizeSuperAdmin();
        $appVersion->delete();
        return redirect()->route('admin.app-versions.index')
            ->with('success', '버전이 삭제되었습니다.');
    }

    public function activate(AppVersion $appVersion)
    {
        $this->authorizeSuperAdmin();
        AppVersion::where('is_active', true)->update(['is_active' => false]);
        $appVersion->update(['is_active' => true]);
        return redirect()->route('admin.app-versions.index')
            ->with('success', 'v' . $appVersion->version . '이 활성화되었습니다.');
    }

    private function authorizeSuperAdmin(): void
    {
        if (!auth('admin')->user()->isSuperAdmin()) {
            abort(403);
        }
    }
}
