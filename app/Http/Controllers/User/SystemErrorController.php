<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SystemErrorLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 사용자 영역(SR 담당자 + 관리자)의 시스템 에러 관리 컨트롤러.
 *
 * 권한 매트릭스 (RequireSrOrAdmin 통과 전제):
 *   - index/show/resolve(All): SR + Admin
 *   - destroy/destroyResolved: Admin 만 (인스턴스 메서드 내부에서 추가 가드)
 *
 * 적용 범위 결정 (2026-05-28):
 *   본 컨트롤러는 **withworks 에러만** 다룬다. supportworks 자체 에러는 admin 영역의
 *   AdminSystemErrorController 에서 별도 관리. source 필터는 코드에서 강제하고 사용자는
 *   변경할 수 없게 한다 (URL 파라미터로 다른 source 지정 무시).
 */
class SystemErrorController extends Controller
{
    /** 본 컨트롤러가 다루는 단일 source. */
    private const SCOPE_SOURCE = 'withworks';

    public function index(Request $request)
    {
        $status = $request->query('status', 'unresolved');
        $level  = $request->query('level');
        $origin = $request->query('origin');
        $search = $request->query('search');

        $query = $this->scopedQuery()->latest();

        if ($status === 'unresolved') {
            $query->unresolved();
        } elseif ($status === 'resolved') {
            $query->where('is_resolved', true);
        }

        if ($level === 'error') {
            $query->whereIn('level', ['error', 'critical', 'alert', 'emergency']);
        } elseif ($level) {
            $query->where('level', $level);
        }

        if ($origin) {
            $query->origin($origin);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('exception', 'like', "%{$search}%")
                  ->orWhere('file', 'like', "%{$search}%");
            });
        }

        $errorLogs = $query->paginate(30)->withQueryString();

        // 통계도 source=withworks 범위로 한정.
        $stats = [
            'total'      => $this->scopedQuery()->count(),
            'unresolved' => $this->scopedQuery()->unresolved()->count(),
            'resolved'   => $this->scopedQuery()->where('is_resolved', true)->count(),
            'error'      => $this->scopedQuery()->whereIn('level', ['error', 'critical', 'alert', 'emergency'])->count(),
            'warning'    => $this->scopedQuery()->where('level', 'warning')->count(),
            'info'       => $this->scopedQuery()->where('level', 'info')->count(),
        ];

        return view('user.system-errors.index', compact(
            'errorLogs', 'status', 'level', 'origin', 'search', 'stats'
        ));
    }

    public function show(SystemErrorLog $systemError)
    {
        $this->ensureWithinScope($systemError);
        return view('user.system-errors.show', ['error' => $systemError]);
    }

    public function resolve(SystemErrorLog $systemError, Request $request): RedirectResponse
    {
        $this->ensureWithinScope($systemError);
        $user = $request->user();
        $systemError->update([
            'is_resolved' => true,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
        ]);

        return back()->with('success', '에러가 해결됨으로 표시되었습니다.');
    }

    public function resolveAll(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->scopedQuery()->unresolved()->update([
            'is_resolved' => true,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
        ]);

        return back()->with('success', '모든 미해결 에러를 해결됨으로 표시했습니다.');
    }

    // ── 삭제 (Admin only) ──────────────────────────────────────────────

    public function destroy(SystemErrorLog $systemError, Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);
        $this->ensureWithinScope($systemError);
        $systemError->delete();
        return back()->with('success', '에러 로그가 삭제되었습니다.');
    }

    public function destroyResolved(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);
        $count = $this->scopedQuery()->where('is_resolved', true)->count();
        $this->scopedQuery()->where('is_resolved', true)->delete();
        return back()->with('success', "해결된 에러 {$count}건을 삭제했습니다.");
    }

    // ── 내부 헬퍼 ────────────────────────────────────────────────────

    /** source=withworks 로 한정된 기본 쿼리. 모든 read/write 의 시작점. */
    private function scopedQuery()
    {
        return SystemErrorLog::query()->where('source', self::SCOPE_SOURCE);
    }

    /** 단일 레코드가 withworks 범위 안에 있는지 검증. 아니면 404. */
    private function ensureWithinScope(SystemErrorLog $log): void
    {
        if ($log->source !== self::SCOPE_SOURCE) {
            abort(404);
        }
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, '삭제는 관리자만 수행할 수 있습니다.');
        }
    }
}
