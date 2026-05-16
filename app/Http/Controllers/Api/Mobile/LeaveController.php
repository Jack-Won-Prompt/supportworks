<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectLeave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /** GET /leaves - 내 휴가 목록 (연도별) */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = (int) $request->get('year', now()->year);

        $leaves = ProjectLeave::where('user_id', $user->id)
            ->with(['project:id,name', 'approver:id,name'])
            ->whereYear('start_date', $year)
            ->orderByDesc('start_date')
            ->get();

        // 연간 사용 통계 (승인 건)
        $approved = $leaves->where('status', 'approved');
        $summary = [
            'annual' => (float) $approved->where('leave_type', 'annual')->sum('days_count'),
            'half'   => $approved->whereIn('leave_type', ['half_day_am', 'half_day_pm'])->count(),
            'sick'   => (float) $approved->where('leave_type', 'sick')->sum('days_count'),
        ];
        $summary['total'] = $summary['annual'] + ($summary['half'] * 0.5) + $summary['sick'];

        return response()->json([
            'year'     => $year,
            'summary'  => $summary,
            'leaves'   => $leaves->map(fn($l) => $this->resource($l)),
        ]);
    }

    /** GET /leaves/projects - 휴가 신청 가능한 프로젝트 + 멤버(결재자) */
    public function projects(Request $request): JsonResponse
    {
        $user = $request->user();

        $projects = Project::whereHas('projectMembers', fn($q) => $q->where('user_id', $user->id))
            ->with('projectMembers.user:id,name')
            ->orderBy('name')
            ->get();

        return response()->json($projects->map(fn($p) => [
            'id'      => $p->id,
            'name'    => $p->name,
            'members' => $p->projectMembers
                ->filter(fn($m) => $m->user && $m->user->id !== $user->id)
                ->map(fn($m) => ['id' => $m->user->id, 'name' => $m->user->name])
                ->values(),
        ]));
    }

    /** POST /leaves - 휴가 신청 */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'project_id'  => 'required|integer|exists:projects,id',
            'approver_id' => 'nullable|integer|exists:users,id',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'leave_type'  => 'required|in:annual,half_day_am,half_day_pm,sick,other',
            'reason'      => 'nullable|string|max:500',
        ]);

        // 반차는 하루만
        if (in_array($validated['leave_type'], ['half_day_am', 'half_day_pm'])
            && $validated['start_date'] !== $validated['end_date']) {
            return response()->json(['message' => '반차는 하루만 선택할 수 있습니다.'], 422);
        }

        $project = Project::findOrFail($validated['project_id']);

        // 프로젝트 멤버 확인
        $isMember = $project->projectMembers()->where('user_id', $user->id)->exists();
        abort_unless($isMember || $user->isAdmin(), 403, '프로젝트 멤버만 신청할 수 있습니다.');

        $isManager = $user->isAdmin() || $project->getMemberRole($user) === 'manager';

        $leave = ProjectLeave::create([
            'project_id'  => $project->id,
            'user_id'     => $user->id,
            'approver_id' => $validated['approver_id'] ?? null,
            'start_date'  => $validated['start_date'],
            'end_date'    => $validated['end_date'],
            'leave_type'  => $validated['leave_type'],
            'reason'      => $validated['reason'] ?? null,
            'status'      => $isManager ? 'approved' : 'pending',
            'created_by'  => $user->id,
        ]);

        $leave->load(['project:id,name', 'approver:id,name']);

        return response()->json($this->resource($leave), 201);
    }

    /** DELETE /leaves/{leave} - 휴가 취소 */
    public function destroy(Request $request, ProjectLeave $leave): JsonResponse
    {
        abort_if($leave->user_id !== $request->user()->id, 403);
        $leave->delete();
        return response()->json(['message' => '휴가 신청이 취소되었습니다.']);
    }

    private function resource(ProjectLeave $l): array
    {
        return [
            'id'               => $l->id,
            'project'          => $l->project ? ['id' => $l->project->id, 'name' => $l->project->name] : null,
            'approver_name'    => $l->approver?->name,
            'start_date'       => $l->start_date->format('Y-m-d'),
            'end_date'         => $l->end_date->format('Y-m-d'),
            'leave_type'       => $l->leave_type,
            'leave_type_label' => $l->leave_type_label,
            'reason'           => $l->reason,
            'status'           => $l->status,
            'status_label'     => $l->status_label,
            'days_count'       => $l->days_count,
            'is_half'          => in_array($l->leave_type, ['half_day_am', 'half_day_pm']),
            'created_at'       => $l->created_at,
        ];
    }
}