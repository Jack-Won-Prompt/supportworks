<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminLoginLog;
use App\Models\MeetingMinute;
use App\Models\SystemErrorLog;
use App\Models\UserLoginLog;
use App\Models\UserPageLog;
use App\Models\WeeklyReport;
use Illuminate\Support\Facades\DB;

class AdminDataResetController extends Controller
{
    private function assertSuperAdmin(): void
    {
        if (!auth('admin')->user()?->isSuperAdmin()) {
            abort(403, 'Super Admin 권한이 필요합니다.');
        }
    }

    public function resetInquiries()
    {
        $this->assertSuperAdmin();

        $ids   = DB::table('conversations')->where('type', 'inquiry')->pluck('id');
        $count = $ids->count();

        DB::table('messages')->whereIn('conversation_id', $ids)->delete();
        DB::table('conversation_user')->whereIn('conversation_id', $ids)->delete();
        DB::table('conversations')->whereIn('id', $ids)->delete();

        return back()->with('success', "문의 데이터 {$count}건을 초기화했습니다.");
    }

    public function resetActivityLogs()
    {
        $this->assertSuperAdmin();

        $count = ActivityLog::count();
        ActivityLog::truncate();

        return back()->with('success', "사용자 활동 로그 {$count}건을 초기화했습니다.");
    }

    public function resetLoginLogs()
    {
        $this->assertSuperAdmin();

        $count = AdminLoginLog::count();
        AdminLoginLog::truncate();

        return back()->with('success', "로그인 로그 {$count}건을 초기화했습니다.");
    }

    public function resetUserLoginLogs()
    {
        $this->assertSuperAdmin();

        $count = UserLoginLog::count();
        UserLoginLog::truncate();

        return back()->with('success', "사용자 로그인 로그 {$count}건을 초기화했습니다.");
    }

    public function resetUserPageLogs()
    {
        $this->assertSuperAdmin();

        $count = UserPageLog::count();
        UserPageLog::truncate();

        return back()->with('success', "화면 접근 로그 {$count}건을 초기화했습니다.");
    }

    public function resetMeetingMinutes()
    {
        $this->assertSuperAdmin();

        $count = MeetingMinute::count();
        DB::table('meeting_action_items')->delete();
        DB::table('meeting_memos')->delete();
        DB::table('meeting_attendees')->delete();
        DB::table('meeting_minutes')->delete();

        return back()->with('success', "회의록 {$count}건을 초기화했습니다.");
    }

    public function resetWeeklyReports()
    {
        $this->assertSuperAdmin();

        $count = WeeklyReport::count();
        DB::table('weekly_report_tasks')->delete();
        DB::table('weekly_ai_summaries')->delete();
        DB::table('weekly_reports')->delete();

        return back()->with('success', "위클리 리포트 {$count}건을 초기화했습니다.");
    }

    public function resetSystemErrors()
    {
        $this->assertSuperAdmin();

        $count = SystemErrorLog::count();
        SystemErrorLog::truncate();

        return redirect()->route('admin.system-errors.index')
            ->with('success', "시스템 에러 로그 {$count}건을 초기화했습니다.");
    }
}
