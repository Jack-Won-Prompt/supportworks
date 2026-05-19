<?php

namespace App\Http\Controllers;

use App\Models\ProjectMaintenance;
use App\Models\ProjectMaintenanceReply;
use App\Services\ProjectNotificationService;
use Illuminate\Http\Request;

class ProjectMaintenanceReplyController extends Controller
{
    public function store(Request $request, ProjectMaintenance $maintenance)
    {
        $user = auth()->user();

        $isHandler = $user->isAdmin() || $user->isSrAgent();

        if (!$isHandler && $maintenance->user_id !== $user->id) {
            abort(403);
        }

        $request->validate(['content' => 'required|string']);

        $maintenance->replies()->create([
            'user_id' => $user->id,
            'content' => $request->content,
        ]);

        if ($isHandler && $maintenance->status === 'pending') {
            $maintenance->update(['status' => 'in_progress']);
        }

        $maintenance->load('project');
        if ($maintenance->project) {
            app(ProjectNotificationService::class)->notify(
                $maintenance->project, $user, 'maintenance_replied',
                $maintenance->title,
                route('maintenances.show', $maintenance),
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', '답글이 등록되었습니다.');
    }

    public function destroy(ProjectMaintenanceReply $reply)
    {
        $user = auth()->user();

        if ($reply->user_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        $maintenance = $reply->maintenance;
        $reply->delete();

        return back()->with('success', '답글이 삭제되었습니다.');
    }
}
