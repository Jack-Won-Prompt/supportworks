<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemErrorLog;
use Illuminate\Http\Request;

class AdminSystemErrorController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'unresolved');
        $level  = $request->query('level');
        $search = $request->query('search');

        $query = SystemErrorLog::query()->latest();

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

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('exception', 'like', "%{$search}%")
                  ->orWhere('file', 'like', "%{$search}%");
            });
        }

        $errorLogs = $query->paginate(30)->withQueryString();

        $stats = [
            'total'      => SystemErrorLog::count(),
            'unresolved' => SystemErrorLog::unresolved()->count(),
            'resolved'   => SystemErrorLog::where('is_resolved', true)->count(),
            'error'      => SystemErrorLog::whereIn('level', ['error', 'critical', 'alert', 'emergency'])->count(),
            'warning'    => SystemErrorLog::where('level', 'warning')->count(),
            'info'       => SystemErrorLog::where('level', 'info')->count(),
        ];

        return view('admin.system-errors.index', compact('errorLogs', 'status', 'level', 'search', 'stats'));
    }

    public function show(SystemErrorLog $systemError)
    {
        return view('admin.system-errors.show', ['error' => $systemError]);
    }

    public function resolve(SystemErrorLog $systemError)
    {
        $admin = auth('admin')->user();
        $systemError->update([
            'is_resolved' => true,
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
        ]);

        return back()->with('success', '에러가 해결됨으로 표시되었습니다.');
    }

    public function resolveAll(Request $request)
    {
        $admin = auth('admin')->user();
        SystemErrorLog::unresolved()->update([
            'is_resolved' => true,
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
        ]);

        return back()->with('success', '모든 미해결 에러를 해결됨으로 표시했습니다.');
    }

    public function destroy(SystemErrorLog $systemError)
    {
        $systemError->delete();
        return back()->with('success', '에러 로그가 삭제되었습니다.');
    }

    public function destroyResolved()
    {
        $count = SystemErrorLog::where('is_resolved', true)->count();
        SystemErrorLog::where('is_resolved', true)->delete();
        return back()->with('success', "해결된 에러 {$count}건을 삭제했습니다.");
    }
}
