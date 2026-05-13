<?php

namespace App\Http\Controllers;

use App\Events\LeaveNotificationEvent;
use App\Mail\ProjectLeaveMail;
use App\Models\Project;
use App\Models\ProjectLeave;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ProjectLeaveController extends Controller
{
    private function canManageAll(Project $project): bool
    {
        $user = auth()->user();
        if ($user->isAdmin()) return true;
        return $project->getMemberRole($user) === 'manager';
    }

    /** 해당 leave의 결재자이거나 매니저이면 승인/반려 가능 */
    private function canDecide(Project $project, ProjectLeave $leave): bool
    {
        if ($this->canManageAll($project)) return true;
        return $leave->approver_id === auth()->id();
    }

    public function index(Project $project, Request $request)
    {
        $myId  = auth()->id();
        $year  = (int) $request->get('year',  now()->year);
        $month = (int) $request->get('month', now()->month);

        $isManager = $this->canManageAll($project);

        // 이달 휴무 목록
        $leaves = $project->leaves()
            ->with(['user:id,name', 'approver:id,name'])
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('start_date', $year)->whereMonth('start_date', $month)
                  ->orWhere(function ($e) use ($year, $month) {
                      $e->whereYear('end_date', $year)->whereMonth('end_date', $month);
                  });
            })
            ->orderBy('start_date')
            ->get();

        // 결재 대기 건: 매니저는 전체 미결, 일반 멤버는 본인 지정 건
        if ($isManager) {
            $pendingForMe = $project->leaves()
                ->with(['user:id,name', 'approver:id,name'])
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->get();
        } else {
            $pendingForMe = $project->leaves()
                ->with(['user:id,name', 'approver:id,name'])
                ->where('approver_id', $myId)
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->get();
        }

        // 달력용: 날짜 → 휴무 목록 매핑
        $calendarLeaves = [];
        foreach ($leaves as $leave) {
            $cursor = $leave->start_date->copy();
            while ($cursor->lte($leave->end_date)) {
                if ($cursor->year === $year && $cursor->month === $month) {
                    $calendarLeaves[$cursor->format('Y-m-d')][] = $leave;
                }
                $cursor->addDay();
            }
        }

        $members = $project->projectMembers()->with('user:id,name')->get();

        $todayStart = now()->startOfDay();

        // 나의 연간/월간 승인 휴가 사용 통계
        $myYearApproved = $project->leaves()
            ->where('user_id', $myId)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->get();
        $yearUsed      = $this->calcUsage($myYearApproved->filter(fn ($lv) => $lv->start_date->lte($todayStart)));
        $yearUpcoming  = $this->calcUsage($myYearApproved->filter(fn ($lv) => $lv->start_date->gt($todayStart)));

        $myMonthAll    = $myYearApproved->filter(fn ($lv) => (int) $lv->start_date->month === $month);
        $monthUsed     = $this->calcUsage($myMonthAll->filter(fn ($lv) => $lv->start_date->lte($todayStart)));
        $monthUpcoming = $this->calcUsage($myMonthAll->filter(fn ($lv) => $lv->start_date->gt($todayStart)));

        // 팀 전체 연간 사용 통계 (매니저용)
        $teamStats = collect();
        if ($isManager) {
            $allMemberIds   = $members->pluck('user_id');
            $teamYearLeaves = $project->leaves()
                ->with('user:id,name')
                ->where('status', 'approved')
                ->whereYear('start_date', $year)
                ->whereIn('user_id', $allMemberIds)
                ->get();

            $teamStats = $members->map(function ($member) use ($teamYearLeaves, $todayStart) {
                $ml  = $teamYearLeaves->where('user_id', $member->user_id);
                return [
                    'user_id'   => $member->user_id,
                    'user_name' => $member->user->name,
                    'used'      => $this->calcUsage($ml->filter(fn ($lv) => $lv->start_date->lte($todayStart))),
                    'upcoming'  => $this->calcUsage($ml->filter(fn ($lv) => $lv->start_date->gt($todayStart))),
                ];
            });
        }

        return view('leaves.index', compact(
            'project', 'leaves', 'calendarLeaves', 'pendingForMe',
            'year', 'month', 'isManager', 'members', 'myId',
            'yearUsed', 'yearUpcoming', 'monthUsed', 'monthUpcoming',
            'teamStats'
        ));
    }

    private function calcUsage($leaves): array
    {
        $halfCount = $leaves->whereIn('leave_type', ['half_day_am', 'half_day_pm'])->count();
        $annual    = (float) $leaves->where('leave_type', 'annual')->sum('days_count');
        $sick      = (float) $leaves->where('leave_type', 'sick')->sum('days_count');
        $other     = (float) $leaves->where('leave_type', 'other')->sum('days_count');

        return [
            'annual' => $annual,
            'half'   => $halfCount,
            'sick'   => $sick,
            'other'  => $other,
            'total'  => $annual + ($halfCount * 0.5) + $sick + $other,
        ];
    }

    public function store(Request $request, Project $project)
    {
        $user      = auth()->user();
        $isManager = $this->canManageAll($project);

        $validated = $request->validate([
            'user_id'     => 'required|integer|exists:users,id',
            'approver_id' => 'nullable|integer|exists:users,id',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'leave_type'  => 'required|in:annual,half_day_am,half_day_pm,sick,other',
            'reason'      => 'nullable|string|max:500',
        ]);

        if (in_array($validated['leave_type'], ['half_day_am', 'half_day_pm'])) {
            if ($validated['start_date'] !== $validated['end_date']) {
                return response()->json(['ok' => false, 'message' => '반차는 하루만 선택 가능합니다.'], 422);
            }
        }

        if ((int) $validated['user_id'] !== $user->id && !$isManager) {
            return response()->json(['ok' => false, 'message' => '권한이 없습니다.'], 403);
        }

        $approverId = $validated['approver_id'] ?? null;
        $status     = $isManager ? 'approved' : 'pending';

        $leave = $project->leaves()->create([
            'user_id'     => $validated['user_id'],
            'approver_id' => $approverId,
            'start_date'  => $validated['start_date'],
            'end_date'    => $validated['end_date'],
            'leave_type'  => $validated['leave_type'],
            'reason'      => $validated['reason'] ?? null,
            'status'      => $status,
            'created_by'  => $user->id,
        ]);

        $leave->load(['user:id,name', 'approver:id,name,email']);

        if ($approverId && $leave->approver && $leave->approver->email) {
            $this->sendApprovalRequest($project, $leave, $user);
        }

        if ($approverId) {
            $dateRange = $leave->start_date->eq($leave->end_date)
                ? $leave->start_date->format('Y.m.d')
                : $leave->start_date->format('Y.m.d') . ' ~ ' . $leave->end_date->format('Y.m.d');
            event(new LeaveNotificationEvent(
                targetUserId: $approverId,
                type: 'leave_requested',
                actorName: $user->name,
                leaveLabel: $leave->leave_type_label,
                dateRange: $dateRange,
                url: route('projects.leaves.index', $project),
            ));
        }

        return response()->json(['ok' => true, 'leave' => $this->leaveRow($leave)]);
    }

    private function sendApprovalRequest(Project $project, ProjectLeave $leave, $actor): void
    {
        $leaveUrl = route('projects.leaves.index', $project);
        $mailOk = false;
        try {
            Mail::to($leave->approver->email)->send(
                new ProjectLeaveMail($project, $leave, $actor, $leaveUrl, 'approval_request')
            );
            $mailOk = true;
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
        }

        if ($mailOk && $leave->approver && $leave->approver->phone) {
            $range = $leave->start_date->eq($leave->end_date)
                ? $leave->start_date->format('Y.m.d')
                : $leave->start_date->format('Y.m.d') . '~' . $leave->end_date->format('Y.m.d');
            $smsPhone = $leave->approver->phone;
            $smsName  = $leave->approver->name;
            $smsMsg   = "[SupportWorks] {$actor->name}님이 휴가({$leave->leave_type_label}, {$range}) 승인을 요청했습니다.";
            app()->terminating(static function () use ($smsPhone, $smsName, $smsMsg) {
                set_time_limit(0);
                try { SmsService::send($smsPhone, $smsMsg, $smsName); } catch (\Throwable) {}
            });
        }
    }

    public function update(Request $request, Project $project, ProjectLeave $leave)
    {
        abort_if($leave->project_id !== $project->id, 404);

        $user      = auth()->user();
        $isManager = $this->canManageAll($project);
        $canDecide = $this->canDecide($project, $leave);

        // 내용 수정은 본인·매니저만
        if (!$request->has('status') && $leave->user_id !== $user->id && !$isManager) {
            return response()->json(['ok' => false, 'message' => '권한이 없습니다.'], 403);
        }

        // 상태 변경은 지정된 결재자·매니저만
        if ($request->has('status') && !$canDecide) {
            return response()->json(['ok' => false, 'message' => '결재 권한이 없습니다.'], 403);
        }

        $validated = $request->validate([
            'approver_id' => 'nullable|integer|exists:users,id',
            'start_date'  => 'sometimes|date',
            'end_date'    => 'sometimes|date|after_or_equal:start_date',
            'leave_type'  => 'sometimes|in:annual,half_day_am,half_day_pm,sick,other',
            'reason'      => 'nullable|string|max:500',
            'status'      => 'sometimes|in:pending,approved,rejected',
        ]);

        $leave->update($validated);
        $leave->load(['user:id,name,email', 'approver:id,name']);

        // 승인/반려 시 신청자에게 이메일 + 실시간 토스트
        if ($request->has('status') && in_array($validated['status'] ?? '', ['approved', 'rejected'])) {
            $this->sendStatusNotification($project, $leave, $user);

            if ($leave->user_id !== $user->id) {
                $dateRange = $leave->start_date->eq($leave->end_date)
                    ? $leave->start_date->format('Y.m.d')
                    : $leave->start_date->format('Y.m.d') . ' ~ ' . $leave->end_date->format('Y.m.d');
                event(new LeaveNotificationEvent(
                    targetUserId: $leave->user_id,
                    type: $leave->status === 'approved' ? 'leave_approved' : 'leave_rejected',
                    actorName: $user->name,
                    leaveLabel: $leave->leave_type_label,
                    dateRange: $dateRange,
                    url: route('projects.leaves.index', $project),
                ));
            }
        }

        return response()->json(['ok' => true, 'leave' => $this->leaveRow($leave)]);
    }

    private function sendStatusNotification(Project $project, ProjectLeave $leave, $actor): void
    {
        $leave->loadMissing('user:id,name,email,phone');
        if (!$leave->user || !$leave->user->email) return;

        $type     = $leave->status === 'approved' ? 'approved' : 'rejected';
        $leaveUrl = route('projects.leaves.index', $project);

        $mailOk = false;
        try {
            Mail::to($leave->user->email)->send(
                new ProjectLeaveMail($project, $leave, $actor, $leaveUrl, $type)
            );
            $mailOk = true;
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
        }

        if ($mailOk && $leave->user->phone) {
            $label = $type === 'approved' ? '승인' : '반려';
            $range = $leave->start_date->eq($leave->end_date)
                ? $leave->start_date->format('Y.m.d')
                : $leave->start_date->format('Y.m.d') . '~' . $leave->end_date->format('Y.m.d');
            $smsPhone = $leave->user->phone;
            $smsName  = $leave->user->name;
            $smsMsg   = "[SupportWorks] {$actor->name}님이 휴가({$leave->leave_type_label}, {$range})를 {$label}했습니다.";
            app()->terminating(static function () use ($smsPhone, $smsName, $smsMsg) {
                set_time_limit(0);
                try { SmsService::send($smsPhone, $smsMsg, $smsName); } catch (\Throwable) {}
            });
        }
    }

    public function destroy(Project $project, ProjectLeave $leave)
    {
        abort_if($leave->project_id !== $project->id, 404);

        $user      = auth()->user();
        $isManager = $this->canManageAll($project);

        if ($leave->user_id !== $user->id && !$isManager) {
            return response()->json(['ok' => false, 'message' => '권한이 없습니다.'], 403);
        }

        $leave->delete();
        return response()->json(['ok' => true]);
    }

    private function leaveRow(ProjectLeave $leave): array
    {
        return [
            'id'               => $leave->id,
            'user_id'          => $leave->user_id,
            'user_name'        => $leave->user->name,
            'approver_id'      => $leave->approver_id,
            'approver_name'    => $leave->approver?->name,
            'start_date'       => $leave->start_date->format('Y-m-d'),
            'end_date'         => $leave->end_date->format('Y-m-d'),
            'leave_type'       => $leave->leave_type,
            'leave_type_label' => $leave->leave_type_label,
            'leave_type_color' => $leave->leave_type_color,
            'leave_type_bg'    => $leave->leave_type_bg,
            'reason'           => $leave->reason,
            'status'           => $leave->status,
            'status_label'     => $leave->status_label,
            'status_color'     => $leave->status_color,
            'status_bg'        => $leave->status_bg,
            'days_count'       => $leave->days_count,
        ];
    }
}
