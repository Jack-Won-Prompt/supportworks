<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMaintenance;
use App\Models\ProjectMaintenanceReply;
use Illuminate\Http\Request;

class AdminMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $query = ProjectMaintenance::with(['project', 'srTarget', 'user', 'replies'])
            ->withCount('replies')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $maintenances = $query->paginate(20)->withQueryString();
        $projects     = Project::orderBy('name')->get(['id', 'name']);

        $counts = [
            'all'         => ProjectMaintenance::count(),
            'pending'     => ProjectMaintenance::where('status', 'pending')->count(),
            'in_progress' => ProjectMaintenance::where('status', 'in_progress')->count(),
            'completed'   => ProjectMaintenance::where('status', 'completed')->count(),
            'rejected'    => ProjectMaintenance::where('status', 'rejected')->count(),
        ];

        return view('admin.maintenances.index', compact('maintenances', 'projects', 'counts'));
    }

    public function show(ProjectMaintenance $maintenance)
    {
        $maintenance->load(['project', 'srTarget', 'user', 'replies.user', 'replies.adminUser']);
        return view('admin.maintenances.show', compact('maintenance'));
    }

    public function detail(ProjectMaintenance $maintenance)
    {
        $maintenance->load(['project', 'srTarget', 'user', 'replies.user', 'replies.adminUser']);
        return view('admin.maintenances.detail_partial', compact('maintenance'));
    }

    public function updateStatus(Request $request, ProjectMaintenance $maintenance)
    {
        $request->validate(['status' => 'required|in:pending,in_progress,completed,rejected']);
        $maintenance->update(['status' => $request->status]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', '처리 상태가 변경되었습니다.');
    }

    public function updateSchedule(Request $request, ProjectMaintenance $maintenance)
    {
        $request->validate(['scheduled_date' => 'nullable|date']);
        $maintenance->update(['scheduled_date' => $request->scheduled_date ?: null]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', '처리 예정일이 저장되었습니다.');
    }

    public function storeReply(Request $request, ProjectMaintenance $maintenance)
    {
        $request->validate(['content' => 'required|string']);

        $maintenance->replies()->create([
            'admin_user_id' => auth('admin')->id(),
            'content'       => $request->content,
        ]);

        if ($maintenance->status === 'pending') {
            $maintenance->update(['status' => 'in_progress']);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', '답글이 등록되었습니다.');
    }

    public function destroyReply(Request $request, ProjectMaintenanceReply $reply)
    {
        $reply->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', '답글이 삭제되었습니다.');
    }
}
