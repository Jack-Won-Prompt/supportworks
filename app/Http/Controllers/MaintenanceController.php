<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceScreen;
use App\Models\MaintenanceVersion;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    private MaintenanceService $svc;

    public function __construct()
    {
        $this->svc = new MaintenanceService();
    }

    // ── 화면 정보 조회 (AJAX) ──────────────────────────────────────

    public function info(string $screenKey)
    {
        $screen = MaintenanceScreen::where('screen_key', $screenKey)
                    ->withCount('versions')
                    ->firstOrFail();
        return response()->json(['ok' => true, 'screen' => $screen]);
    }

    // ── 화면 등록 ───────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'screen_key'  => 'required|string|max:100|unique:maintenance_screens,screen_key',
            'name'        => 'required|string|max:200',
            'blade_path'  => 'required|string|max:500',
            'url_pattern' => 'nullable|string|max:300',
            'description' => 'nullable|string|max:1000',
        ]);

        $screen = MaintenanceScreen::create([
            'screen_key'  => $request->screen_key,
            'name'        => $request->name,
            'blade_path'  => $request->blade_path,
            'url_pattern' => $request->url_pattern,
            'description' => $request->description,
            'user_id'     => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'screen' => $screen]);
        }
        return redirect()->route('maintenance.show', $screen->screen_key)
                         ->with('success', '화면이 등록되었습니다.');
    }

    // ── 화면 삭제 ───────────────────────────────────────────────────

    public function destroy(string $screenKey)
    {
        $screen = MaintenanceScreen::where('screen_key', $screenKey)->firstOrFail();
        $screen->delete();
        return response()->json(['ok' => true]);
    }

    // ── 현재 파일 내용 조회 (AJAX) ─────────────────────────────────

    public function readFiles(string $screenKey)
    {
        $screen = MaintenanceScreen::where('screen_key', $screenKey)->firstOrFail();
        $files  = $this->svc->readScreenContent($screen);
        return response()->json(['ok' => true, 'files' => $files]);
    }

    // ── 미리보기 저장 ───────────────────────────────────────────────

    public function storePreview(Request $request, string $screenKey)
    {
        $screen = MaintenanceScreen::where('screen_key', $screenKey)->firstOrFail();
        $token  = \Illuminate\Support\Str::random(40);

        cache()->put("mn_preview_{$token}", [
            'screen_key' => $screenKey,
            'content'    => $request->input('content', ''),
        ], 600); // 10분

        return response()->json([
            'ok'  => true,
            'url' => route('maintenance.preview.render', [$screenKey, $token]),
        ]);
    }

    // ── 미리보기 렌더 ───────────────────────────────────────────────

    public function renderPreview(string $screenKey, string $token)
    {
        $cached = cache("mn_preview_{$token}");
        if (!$cached || ($cached['screen_key'] ?? '') !== $screenKey) {
            abort(404, '미리보기가 만료되었거나 존재하지 않습니다.');
        }

        $screen  = MaintenanceScreen::where('screen_key', $screenKey)->firstOrFail();
        $content = $cached['content'];

        // @section('content') 블록만 추출 (없으면 전체 사용)
        if (preg_match('/@section\s*\(\s*[\'"]content[\'"]\s*\)(.*?)@endsection/s', $content, $m)) {
            $body = trim($m[1]);
        } else {
            $body = $content;
        }

        // 렌더링 방해 지시문 제거
        $html = $this->svc->stripBladeForPreview($body);

        return view('maintenance.preview_shell', [
            'screen' => $screen,
            'html'   => $html,
        ]);
    }

    // ── 구조화 프롬프트 생성 ────────────────────────────────────────

    public function generatePrompt(Request $request, string $screenKey)
    {
        $request->validate(['user_request' => 'required|string']);
        $screen = MaintenanceScreen::where('screen_key', $screenKey)->firstOrFail();

        try {
            $result = $this->svc->generatePrompt($screen, $request->user_request);
            return response()->json(['ok' => true, 'draft' => $result['draft'], 'provider' => $result['provider']]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // ── 수정안 생성 ─────────────────────────────────────────────────

    public function generatePatch(Request $request, string $screenKey)
    {
        $request->validate([
            'user_request' => 'required|string',
            'prompt'       => 'required|array',
        ]);
        $screen = MaintenanceScreen::where('screen_key', $screenKey)->firstOrFail();

        try {
            $result = $this->svc->generatePatch($screen, $request->prompt, $request->user_request);
            return response()->json(['ok' => true, 'patch' => $result['patch'], 'provider' => $result['provider']]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // ── 버전 저장 + 적용 ────────────────────────────────────────────

    public function applyPatch(Request $request, string $screenKey)
    {
        $request->validate([
            'change_summary' => 'required|string',
            'files'          => 'required|array',
            'prompt'         => 'nullable|array',
            'user_request'   => 'nullable|string',
        ]);

        $screen    = MaintenanceScreen::where('screen_key', $screenKey)->firstOrFail();
        $versionNo = $screen->versions()->max('version_no') + 1;

        $version = MaintenanceVersion::create([
            'screen_id'      => $screen->id,
            'version_no'     => $versionNo,
            'change_summary' => $request->change_summary,
            'files'          => $request->input('files'),
            'prompt'         => $request->prompt,
            'user_request'   => $request->user_request,
            'status'         => 'applied',
            'applied_at'     => now(),
            'applied_by'     => auth()->id(),
        ]);

        try {
            $this->svc->applyVersion($version);
        } catch (\Throwable $e) {
            $version->delete();
            return response()->json(['ok' => false, 'error' => '파일 적용 오류: ' . $e->getMessage()], 500);
        }

        $screen->touch();
        return response()->json(['ok' => true, 'version' => $version->load('appliedBy')]);
    }

    // ── 버전 목록 ───────────────────────────────────────────────────

    public function versions(string $screenKey)
    {
        $screen   = MaintenanceScreen::where('screen_key', $screenKey)->firstOrFail();
        $versions = $screen->versions()->with('appliedBy')->get();
        return response()->json(['ok' => true, 'versions' => $versions]);
    }

    // ── 버전 상세 ───────────────────────────────────────────────────

    public function versionDetail(string $screenKey, int $versionId)
    {
        $screen  = MaintenanceScreen::where('screen_key', $screenKey)->firstOrFail();
        $version = MaintenanceVersion::where('id', $versionId)
                     ->where('screen_id', $screen->id)
                     ->firstOrFail();
        return response()->json(['ok' => true, 'version' => $version]);
    }

    // ── 롤백 ────────────────────────────────────────────────────────

    public function rollback(Request $request, string $screenKey, int $versionId)
    {
        $screen  = MaintenanceScreen::where('screen_key', $screenKey)->firstOrFail();
        $version = MaintenanceVersion::where('id', $versionId)
                     ->where('screen_id', $screen->id)
                     ->firstOrFail();

        try {
            $this->svc->rollbackTo($version);

            // 롤백 기록 저장
            $versionNo = $screen->versions()->max('version_no') + 1;
            $rollback  = MaintenanceVersion::create([
                'screen_id'      => $screen->id,
                'version_no'     => $versionNo,
                'change_summary' => "v{$version->version_no} 롤백",
                'files'          => array_map(fn($f) => array_merge($f, [
                    'modified_content' => $f['original_content'] ?? '',
                    'original_content' => $f['modified_content'] ?? '',
                    'diff_content'     => '',
                ]), $version->files),
                'prompt'     => null,
                'user_request' => "v{$version->version_no} 버전으로 롤백",
                'status'     => 'applied',
                'applied_at' => now(),
                'applied_by' => auth()->id(),
            ]);

            $screen->touch();
            return response()->json(['ok' => true, 'version' => $rollback]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
