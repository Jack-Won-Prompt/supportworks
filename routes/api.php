<?php

use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminChatController;
use App\Http\Controllers\Api\Admin\AdminInviteController;
use App\Http\Controllers\Api\Admin\AdminManageController;
use App\Http\Controllers\Api\Admin\AdminPusherController;
use App\Http\Controllers\Api\Admin\AdminUserSearchController;
use App\Http\Controllers\Api\Admin\AppVersionController;
use App\Http\Controllers\Api\Admin\CompanyGroupController;
use App\Http\Controllers\Api\Desktop\AgentController;
use App\Http\Controllers\Api\Desktop\AuthController;
use App\Http\Controllers\Api\Desktop\ChatController;
use App\Http\Controllers\Api\Desktop\EventController;
use App\Http\Controllers\Api\Mobile\ActionItemController as MobileActionItemController;
use App\Http\Controllers\Api\Mobile\AuthController as MobileAuthController;
use App\Http\Controllers\Api\Mobile\CalendarController as MobileCalendarController;
use App\Http\Controllers\Api\Mobile\CommunityController as MobileCommunityController;
use App\Http\Controllers\Api\Mobile\DashboardController as MobileDashboardController;
use App\Http\Controllers\Api\Mobile\FileController as MobileFileController;
use App\Http\Controllers\Api\Mobile\InquiryController as MobileInquiryController;
use App\Http\Controllers\Api\Mobile\IssueController as MobileIssueController;
use App\Http\Controllers\Api\Mobile\LeaveController as MobileLeaveController;
use App\Http\Controllers\Api\Mobile\MeetingMinuteController as MobileMeetingMinuteController;
use App\Http\Controllers\Api\Mobile\MeetingRecordingController as MobileMeetingRecordingController;
use App\Http\Controllers\Api\Mobile\MyWorkController as MobileMyWorkController;
use App\Http\Controllers\Api\Mobile\MemoController as MobileMemoController;
use App\Http\Controllers\Api\Mobile\MessageController as MobileMessageController;
use App\Http\Controllers\Api\Mobile\ProjectController as MobileProjectController;
use App\Http\Controllers\Api\Mobile\QuestionController as MobileQuestionController;
use App\Http\Controllers\Api\Mobile\ScheduleController as MobileScheduleController;
use App\Http\Controllers\Api\Mobile\TaskController as MobileTaskController;
use App\Http\Controllers\Api\Mobile\TeamController as MobileTeamController;
use App\Http\Controllers\Api\Mobile\WeeklyReportController as MobileWeeklyReportController;
use App\Http\Middleware\DesktopTokenMiddleware;
use App\Http\Middleware\MobileTokenMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public: App version check (no auth)
| GET /api/app-version
|--------------------------------------------------------------------------
*/

Route::get('app-version', [AppVersionController::class, 'publicVersion']);

/*
|--------------------------------------------------------------------------
| Admin API Routes
| Base URL: /api/admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {

    // ── 인증 (토큰 불필요) ──────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('login',   [AdminAuthController::class, 'login']);
        Route::post('refresh', [AdminAuthController::class, 'refresh']);
    });

    // ── 인증 필요 ───────────────────────────────────────────────
    Route::middleware('admin.token')->group(function () {

        Route::prefix('auth')->group(function () {
            Route::get ('me',       [AdminAuthController::class, 'me']);
            Route::post('logout',   [AdminAuthController::class, 'logout']);
            Route::put ('password', [AdminAuthController::class, 'changePassword']);
        });

        // 초대 관리 (admin 이상)
        Route::middleware('admin.role:super_admin,admin')->group(function () {
            Route::get   ('invitations',     [AdminInviteController::class, 'index']);
            Route::post  ('invitations',     [AdminInviteController::class, 'send']);
            Route::delete('invitations/{id}',[AdminInviteController::class, 'revoke']);
        });

        // 관리자 계정 관리 (super_admin 이상)
        Route::middleware('admin.role:super_admin')->group(function () {
            Route::get ('admins',                        [AdminManageController::class, 'index']);
            Route::get ('admins/{id}',                   [AdminManageController::class, 'show']);
            Route::put ('admins/{id}/status',            [AdminManageController::class, 'updateStatus']);
            Route::put ('admins/{id}/role',              [AdminManageController::class, 'updateRole']);
            Route::put ('admins/{id}/groups',            [AdminManageController::class, 'updateGroups']);
            Route::post('admins/{id}/reset-password',    [AdminManageController::class, 'resetPassword']);
        });

        // Pusher 채널 인증
        Route::post('pusher/auth', [AdminPusherController::class, 'auth']);

        // 사용자 검색 (모든 admin 역할)
        Route::get ('users/search',                        [AdminUserSearchController::class, 'search']);
        Route::get ('users/{userId}/conversations',        [AdminUserSearchController::class, 'conversations']);
        Route::post('users/{userId}/start-chat',           [AdminUserSearchController::class, 'startChat']);

        // 상담 관리 (모든 admin 역할)
        Route::get ('chats',                    [AdminChatController::class, 'index']);
        Route::get ('chats/{roomId}/messages',  [AdminChatController::class, 'messages']);
        Route::post('chats/{roomId}/messages',  [AdminChatController::class, 'sendMessage']);
        Route::post('chats/{roomId}/files',     [AdminChatController::class, 'sendFile']);
        Route::post('chats/{roomId}/accept',    [AdminChatController::class, 'accept']);
        Route::post('chats/{roomId}/close',     [AdminChatController::class, 'close']);
        Route::post('chats/{roomId}/reopen',    [AdminChatController::class, 'reopen']);

        // 회사 그룹
        Route::get('company-groups',                                      [CompanyGroupController::class, 'index']);
        Route::get('company-groups/{id}/admins',                          [CompanyGroupController::class, 'admins']);
        Route::get('company-groups/{id}/web-users',                       [CompanyGroupController::class, 'webUsers']);
        Route::get('company-groups/{id}/web-users/unassigned',            [CompanyGroupController::class, 'unassignedWebUsers']);
        Route::put('company-groups/{id}/web-users/{userId}',              [CompanyGroupController::class, 'assignWebUser']);
        Route::delete('company-groups/{id}/web-users/{userId}',           [CompanyGroupController::class, 'removeWebUser']);

        Route::middleware('admin.role:super_admin')->group(function () {
            Route::post('company-groups',          [CompanyGroupController::class, 'store']);
            Route::put ('company-groups/{id}',     [CompanyGroupController::class, 'update']);

            // 앱 버전 관리
            Route::post  ('app-versions/upload',       [AppVersionController::class, 'upload']);
            Route::get   ('app-versions',              [AppVersionController::class, 'index']);
            Route::post  ('app-versions',              [AppVersionController::class, 'store']);
            Route::put   ('app-versions/{id}',         [AppVersionController::class, 'update']);
            Route::delete('app-versions/{id}',         [AppVersionController::class, 'destroy']);
            Route::post  ('app-versions/{id}/activate',[AppVersionController::class, 'activate']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Desktop App API Routes
| Base URL: /api/desktop
|--------------------------------------------------------------------------
*/

Route::prefix('desktop')->group(function () {

    // ── 인증 (토큰 불필요) ────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('login',   [AuthController::class, 'login']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // ── 인증 필요 ─────────────────────────────────────────────
    Route::middleware(DesktopTokenMiddleware::class)->group(function () {

        // 내 정보 / 로그아웃
        Route::get('auth/me',      [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // 상담 목록 / 상태 관리
        Route::get ('chats',                        [ChatController::class, 'index']);
        Route::post('chats/{room_id}/assign',       [ChatController::class, 'assign']);
        Route::post('chats/{room_id}/transfer',     [ChatController::class, 'transfer']);
        Route::post('chats/{room_id}/close',        [ChatController::class, 'close']);

        // 메시지
        Route::get ('chats/{room_id}/messages',     [ChatController::class, 'messages']);
        Route::post('chats/{room_id}/messages',     [ChatController::class, 'sendMessage']);
        Route::post('chats/{room_id}/files',        [ChatController::class, 'sendFile']);

        // 상담원 상태
        Route::post('agents/me/status', [AgentController::class, 'updateStatus']);

        // 누락 이벤트 동기화
        Route::get('events/sync', [EventController::class, 'sync']);
    });
});

/*
|--------------------------------------------------------------------------
| Mobile App API Routes
| Base URL: /api/mobile
|--------------------------------------------------------------------------
*/

Route::prefix('mobile')->group(function () {

    // ── 인증 (토큰 불필요) ────────────────────────────────────────
    Route::post('auth/login',    [MobileAuthController::class, 'login']);
    Route::post('auth/register', [MobileAuthController::class, 'register']);
    Route::post('auth/refresh',  [MobileAuthController::class, 'refresh']);

    // ── 인증 필요 ─────────────────────────────────────────────────
    Route::middleware(MobileTokenMiddleware::class)->group(function () {

        // 인증
        Route::get ('auth/me',              [MobileAuthController::class, 'me']);
        Route::post('auth/logout',          [MobileAuthController::class, 'logout']);
        Route::patch('auth/profile',        [MobileAuthController::class, 'updateProfile']);
        Route::patch('auth/password',       [MobileAuthController::class, 'changePassword']);

        // 대시보드
        Route::get('dashboard', [MobileDashboardController::class, 'index']);

        // 내 업무
        Route::get('my-work', [MobileMyWorkController::class, 'index']);

        // 휴가
        Route::get   ('leaves',          [MobileLeaveController::class, 'index']);
        Route::get   ('leaves/projects', [MobileLeaveController::class, 'projects']);
        Route::post  ('leaves',          [MobileLeaveController::class, 'store']);
        Route::delete('leaves/{leave}',  [MobileLeaveController::class, 'destroy']);

        // 프로젝트
        Route::get   ('projects',          [MobileProjectController::class, 'index']);
        Route::post  ('projects',          [MobileProjectController::class, 'store']);
        Route::get   ('projects/{project}', [MobileProjectController::class, 'show']);
        Route::put   ('projects/{project}', [MobileProjectController::class, 'update']);
        Route::delete('projects/{project}', [MobileProjectController::class, 'destroy']);

        // 일정
        Route::get   ('projects/{project}/schedules',     [MobileScheduleController::class, 'index']);
        Route::post  ('projects/{project}/schedules',     [MobileScheduleController::class, 'store']);
        Route::get   ('schedules/{schedule}',             [MobileScheduleController::class, 'show']);
        Route::put   ('schedules/{schedule}',             [MobileScheduleController::class, 'update']);
        Route::delete('schedules/{schedule}',             [MobileScheduleController::class, 'destroy']);

        // Q&A
        Route::get   ('projects/{project}/questions',          [MobileQuestionController::class, 'index']);
        Route::post  ('projects/{project}/questions',          [MobileQuestionController::class, 'store']);
        Route::get   ('questions/{question}',                  [MobileQuestionController::class, 'show']);
        Route::delete('questions/{question}',                  [MobileQuestionController::class, 'destroy']);
        Route::post  ('questions/{question}/answers',          [MobileQuestionController::class, 'storeAnswer']);
        Route::patch ('answers/{answer}/accept',               [MobileQuestionController::class, 'acceptAnswer']);
        Route::delete('answers/{answer}',                      [MobileQuestionController::class, 'destroyAnswer']);

        // 태스크
        Route::get   ('tasks',               [MobileTaskController::class, 'index']);
        Route::post  ('tasks',               [MobileTaskController::class, 'store']);
        Route::patch ('tasks/{task}/status', [MobileTaskController::class, 'updateStatus']);
        Route::delete('tasks/{task}',        [MobileTaskController::class, 'destroy']);

        // 메모
        Route::get   ('memos',              [MobileMemoController::class, 'index']);
        Route::post  ('memos',              [MobileMemoController::class, 'store']);
        Route::put   ('memos/{memo}',       [MobileMemoController::class, 'update']);
        Route::patch ('memos/{memo}/pin',   [MobileMemoController::class, 'togglePin']);
        Route::delete('memos/{memo}',       [MobileMemoController::class, 'destroy']);

        // 메시지
        Route::get ('messages',                              [MobileMessageController::class, 'index']);
        Route::post('messages',                              [MobileMessageController::class, 'store']);
        Route::get ('messages/users',                        [MobileMessageController::class, 'users']);
        Route::get ('messages/{conversation}',               [MobileMessageController::class, 'show']);
        Route::post('messages/{conversation}/reply',         [MobileMessageController::class, 'reply']);

        // 커뮤니티
        Route::get   ('community',                        [MobileCommunityController::class, 'index']);
        Route::post  ('community',                        [MobileCommunityController::class, 'store']);
        Route::get   ('community/{post}',                 [MobileCommunityController::class, 'show']);
        Route::delete('community/{post}',                 [MobileCommunityController::class, 'destroy']);
        Route::post  ('community/{post}/vote',            [MobileCommunityController::class, 'vote']);
        Route::post  ('community/{post}/comments',        [MobileCommunityController::class, 'storeComment']);
        Route::delete('community/comments/{comment}',     [MobileCommunityController::class, 'destroyComment']);

        // 회의록
        Route::get   ('meeting-minutes',                              [MobileMeetingMinuteController::class, 'index']);
        Route::post  ('meeting-minutes',                              [MobileMeetingMinuteController::class, 'store']);
        Route::get   ('meeting-minutes/{meetingMinute}',              [MobileMeetingMinuteController::class, 'show']);
        Route::patch ('meeting-minutes/{meetingMinute}',              [MobileMeetingMinuteController::class, 'update']);
        Route::delete('meeting-minutes/{meetingMinute}',              [MobileMeetingMinuteController::class, 'destroy']);
        Route::post  ('meeting-minutes/{meetingMinute}/action-items', [MobileMeetingMinuteController::class, 'storeActionItem']);
        Route::patch ('meeting-action-items/{meetingActionItem}/status', [MobileMeetingMinuteController::class, 'updateActionItemStatus']);
        Route::delete('meeting-action-items/{meetingActionItem}',     [MobileMeetingMinuteController::class, 'destroyActionItem']);

        // 액션 아이템
        Route::get   ('action-items',                   [MobileActionItemController::class, 'index']);
        Route::post  ('action-items',                   [MobileActionItemController::class, 'store']);
        Route::patch ('action-items/{actionItem}/toggle',[MobileActionItemController::class, 'toggle']);
        Route::delete('action-items/{actionItem}',      [MobileActionItemController::class, 'destroy']);

        // 문의
        Route::get ('inquiry',                         [MobileInquiryController::class, 'index']);
        Route::post('inquiry',                         [MobileInquiryController::class, 'store']);
        Route::get ('inquiry/{conversation}',          [MobileInquiryController::class, 'show']);
        Route::post('inquiry/{conversation}/reply',    [MobileInquiryController::class, 'reply']);
        Route::post('inquiry/{conversation}/close',    [MobileInquiryController::class, 'close']);

        // 팀원
        Route::get   ('team',                     [MobileTeamController::class, 'index']);
        Route::post  ('team/invite',              [MobileTeamController::class, 'invite']);
        Route::delete('team/invite/{invitation}', [MobileTeamController::class, 'cancelInvite']);

        // 캘린더
        Route::get('calendar', [MobileCalendarController::class, 'index']);

        // 프로젝트 파일
        Route::get   ('projects/{project}/files',                  [MobileFileController::class, 'index']);
        Route::post  ('projects/{project}/files',                  [MobileFileController::class, 'store']);
        Route::get   ('projects/{project}/files/{file}/download',  [MobileFileController::class, 'download']);
        Route::delete('projects/{project}/files/{file}',           [MobileFileController::class, 'destroy']);

        // 이슈
        Route::get   ('projects/{project}/issues',                 [MobileIssueController::class, 'index']);
        Route::post  ('projects/{project}/issues',                 [MobileIssueController::class, 'store']);
        Route::get   ('projects/{project}/issues/{issue}',         [MobileIssueController::class, 'show']);
        Route::put   ('projects/{project}/issues/{issue}',         [MobileIssueController::class, 'update']);
        Route::delete('projects/{project}/issues/{issue}',         [MobileIssueController::class, 'destroy']);
        Route::post  ('projects/{project}/issues/{issue}/resolve', [MobileIssueController::class, 'resolve']);
        Route::post  ('projects/{project}/issues/{issue}/comments',[MobileIssueController::class, 'storeComment']);

        // 회의 녹음
        Route::get   ('meeting-recordings',                       [MobileMeetingRecordingController::class, 'index']);
        Route::post  ('meeting-recordings',                       [MobileMeetingRecordingController::class, 'store']);
        Route::get   ('meeting-recordings/{recording}',           [MobileMeetingRecordingController::class, 'show']);
        Route::patch ('meeting-recordings/{recording}',           [MobileMeetingRecordingController::class, 'update']);
        Route::delete('meeting-recordings/{recording}',           [MobileMeetingRecordingController::class, 'destroy']);
        Route::get   ('meeting-recordings/{recording}/download',  [MobileMeetingRecordingController::class, 'download']);
        Route::post  ('meeting-recordings/{recording}/retry-transcription', [MobileMeetingRecordingController::class, 'retryTranscription']);
        Route::patch ('meeting-recordings/{recording}/content',             [MobileMeetingRecordingController::class, 'updateContent']);
        Route::post  ('meeting-recordings/{recording}/convert-to-minute',   [MobileMeetingRecordingController::class, 'convertToMinute']);

        // 내 주간 보고
        Route::get   ('weekly-reports',           [MobileWeeklyReportController::class, 'index']);
        Route::get   ('weekly-reports/projects',  [MobileWeeklyReportController::class, 'projects']);
        Route::post  ('weekly-reports',           [MobileWeeklyReportController::class, 'store']);
        Route::get   ('weekly-reports/{report}',  [MobileWeeklyReportController::class, 'show']);
        Route::put   ('weekly-reports/{report}',  [MobileWeeklyReportController::class, 'update']);
        Route::delete('weekly-reports/{report}',  [MobileWeeklyReportController::class, 'destroy']);
    });
});
