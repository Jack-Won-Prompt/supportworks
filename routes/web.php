<?php

use App\Http\Controllers\ActionItemController;
use App\Http\Controllers\MeetingMinuteController;
use App\Http\Controllers\MeetingMemoController;
use App\Http\Controllers\MeetingActionItemController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AdminInviteAcceptController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\MemoController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\ProjectLeaveController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\CollabController;
use App\Http\Controllers\FileCommentController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\PlanningDocController;
use App\Http\Controllers\ProjectMaintenanceController;
use App\Http\Controllers\ProjectMaintenanceReplyController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\MessageAnalyzeController;
use App\Http\Controllers\MessageImageCommentController;
use App\Http\Controllers\MessageImageAnnotationController;
use App\Http\Controllers\TranslateController;
use App\Http\Controllers\AiAgentController;
use App\Http\Controllers\AiAgentApprovalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) return redirect()->route('dashboard');
    return view('welcome');
});

// 언어 전환
Route::post('/locale', function (\Illuminate\Http\Request $request) {
    $locale = $request->input('locale', config('app.locale'));
    $available = config('app.available_locales', ['ko', 'en']);

    if (!in_array($locale, $available)) {
        $locale = config('app.locale');
    }

    // 세션과 평문 쿠키에 모두 저장 (app_locale은 암호화 제외됨)
    $request->session()->put('locale', $locale);
    $request->session()->save();

    return back()->withCookie(cookie('app_locale', $locale, 60 * 24 * 365));
})->name('locale.switch');

// 관리자 사용자 가장(impersonate) 로그인 — 토큰 일회성, 60초 유효
Route::get('/auth/impersonate/{token}', [\App\Http\Controllers\Auth\ImpersonateController::class, 'login'])->name('impersonate.login');

// 정책 페이지 (인증 불필요)
Route::get('/terms',   fn() => view('policy.terms'))->name('policy.terms');
Route::get('/privacy', fn() => view('policy.privacy'))->name('policy.privacy');
Route::get('/cookie',  fn() => view('policy.cookie'))->name('policy.cookie');
Route::get('/youth',   fn() => view('policy.youth'))->name('policy.youth');

// 회원가입 회사명 자동완성 (인증 불필요)
Route::get('/autocomplete/companies', function (\Illuminate\Http\Request $request) {
    $q = trim($request->get('q', ''));
    if (strlen($q) < 1) return response()->json([]);

    $fromUsers = \App\Models\User::query()
        ->whereNotNull('company')
        ->where('company', '!=', '')
        ->where('company', 'like', '%' . $q . '%')
        ->distinct()
        ->pluck('company');

    $fromGroups = \App\Models\CompanyGroup::query()
        ->where('is_active', true)
        ->where('name', 'like', '%' . $q . '%')
        ->pluck('name');

    $results = $fromUsers->merge($fromGroups)
        ->unique()
        ->sortBy(fn($v) => [mb_strpos($v, $q) === 0 ? 0 : 1, $v])
        ->values()
        ->take(10);

    return response()->json($results);
})->name('autocomplete.companies');

// 파일 미리보기 서빙 — 서명된 URL로만 접근 (Office Online Viewer에서 파일을 가져가기 위해 auth 불필요)
Route::get('/files/serve/{file}',         [ProjectFileController::class, 'servePreview'])->name('files.serve');
Route::get('/files/serve-pdf/{file}',     [ProjectFileController::class, 'serveConvertedPdf'])->name('files.serve-pdf');
Route::get('/maintenance-files/serve/{maintenanceFile}',     [\App\Http\Controllers\MaintenanceFileController::class, 'serve'])->name('maintenance-files.serve');
Route::get('/maintenance-files/serve-pdf/{maintenanceFile}', [\App\Http\Controllers\MaintenanceFileController::class, 'servePdf'])->name('maintenance-files.serve-pdf');

// 외부 공유 파일 — 로그인 불필요
Route::get ('/share/file/{token}',             [\App\Http\Controllers\PublicFileShareController::class, 'show'])->name('files.public-share');
Route::get ('/share/serve/{token}',            [\App\Http\Controllers\PublicFileShareController::class, 'serve'])->name('files.public-serve');
Route::get ('/share/file/{token}/comments',    [\App\Http\Controllers\PublicFileShareController::class, 'getComments'])->name('files.public-comments.index');
Route::post('/share/file/{token}/comments',    [\App\Http\Controllers\PublicFileShareController::class, 'storeComment'])->name('files.public-comments.store');
Route::get ('/share/file/{token}/annotations', [\App\Http\Controllers\PublicFileShareController::class, 'getAnnotations'])->name('files.public-annotations.index');
Route::post ('/share/file/{token}/annotations',              [\App\Http\Controllers\PublicFileShareController::class, 'storeAnnotation'])->name('files.public-annotations.store');
Route::patch ('/share/file/{token}/annotations/{annotation}', [\App\Http\Controllers\PublicFileShareController::class, 'updateAnnotation'])->name('files.public-annotations.update');
Route::delete('/share/file/{token}/annotations/{annotation}', [\App\Http\Controllers\PublicFileShareController::class, 'destroyAnnotation'])->name('files.public-annotations.destroy');

// SR 접수 파일 외부 공유 — 로그인 불필요
Route::get ('/share/maintenance-file/{token}',             [\App\Http\Controllers\PublicMaintenanceFileShareController::class, 'show'])->name('maintenance-files.public-share');
Route::get ('/share/maintenance-file/{token}/serve',       [\App\Http\Controllers\PublicMaintenanceFileShareController::class, 'serve'])->name('maintenance-files.public-serve');
Route::get ('/share/maintenance-file/{token}/serve-pdf',   [\App\Http\Controllers\PublicMaintenanceFileShareController::class, 'servePdf'])->name('maintenance-files.public-serve-pdf');
Route::get ('/share/maintenance-file/{token}/comments',    [\App\Http\Controllers\PublicMaintenanceFileShareController::class, 'getComments'])->name('maintenance-files.public-comments.index');
Route::post('/share/maintenance-file/{token}/comments',    [\App\Http\Controllers\PublicMaintenanceFileShareController::class, 'storeComment'])->name('maintenance-files.public-comments.store');
Route::get ('/share/maintenance-file/{token}/annotations', [\App\Http\Controllers\PublicMaintenanceFileShareController::class, 'getAnnotations'])->name('maintenance-files.public-annotations.index');
Route::post  ('/share/maintenance-file/{token}/annotations',              [\App\Http\Controllers\PublicMaintenanceFileShareController::class, 'storeAnnotation'])->name('maintenance-files.public-annotations.store');
Route::patch ('/share/maintenance-file/{token}/annotations/{annotation}', [\App\Http\Controllers\PublicMaintenanceFileShareController::class, 'updateAnnotation'])->name('maintenance-files.public-annotations.update');
Route::delete('/share/maintenance-file/{token}/annotations/{annotation}', [\App\Http\Controllers\PublicMaintenanceFileShareController::class, 'destroyAnnotation'])->name('maintenance-files.public-annotations.destroy');

// 초대 수락 — auth 미들웨어 없이 접근 가능 (기존 로그인 여부 무관)
Route::get('/team/accept/{token}',  [TeamController::class, 'accept'])->name('team.accept');
Route::post('/team/accept/{token}', [TeamController::class, 'register'])->name('team.register');

// 관리자 초대 수락 (Windows 앱 계정 설정)
Route::get ('/admin/invite/accept/{token}', [AdminInviteAcceptController::class, 'show'])->name('admin.invite.accept');
Route::post('/admin/invite/accept/{token}', [AdminInviteAcceptController::class, 'accept'])->name('admin.invite.register');

// ── 관리자 웹 패널 ──────────────────────────────────────────────────────
Route::get ('/admin/login',  [\App\Http\Controllers\Admin\AdminWebLoginController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login',  [\App\Http\Controllers\Admin\AdminWebLoginController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [\App\Http\Controllers\Admin\AdminWebLoginController::class, 'logout'])->name('admin.logout');

Route::middleware('admin.web')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn() => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');

    // 활동 로그
    Route::get('activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-logs.index');

    // 사용자 관리
    Route::post('users/invite',             [\App\Http\Controllers\Admin\UserController::class, 'invite'])->name('users.invite');
    Route::delete('users/invitations/{invitation}', [\App\Http\Controllers\Admin\UserController::class, 'cancelInvite'])->name('users.invitations.cancel');
    Route::post('users/{user}/impersonate',  [\App\Http\Controllers\Admin\UserController::class, 'impersonate'])->name('users.impersonate');
    Route::patch('users/{user}/group',       [\App\Http\Controllers\Admin\UserController::class, 'updateGroup'])->name('users.update-group');
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->names([
        'index'   => 'users.index',
        'create'  => 'users.create',
        'store'   => 'users.store',
        'edit'    => 'users.edit',
        'update'  => 'users.update',
        'destroy' => 'users.destroy',
    ]);

    // 회사(사용자 그룹) 관리
    Route::resource('company-groups', \App\Http\Controllers\Admin\CompanyGroupWebController::class)
        ->except(['show'])
        ->names([
            'index'   => 'company-groups.index',
            'create'  => 'company-groups.create',
            'store'   => 'company-groups.store',
            'edit'    => 'company-groups.edit',
            'update'  => 'company-groups.update',
            'destroy' => 'company-groups.destroy',
        ]);
    Route::post('company-groups/{companyGroup}/users/assign',            [\App\Http\Controllers\Admin\CompanyGroupWebController::class, 'assignUser'])->name('company-groups.users.assign');
    Route::delete('company-groups/{companyGroup}/users/{user}/remove',   [\App\Http\Controllers\Admin\CompanyGroupWebController::class, 'removeUser'])->name('company-groups.users.remove');
    Route::get('company-groups/{companyGroup}/users/search',             [\App\Http\Controllers\Admin\CompanyGroupWebController::class, 'searchUnassigned'])->name('company-groups.users.search');
    Route::patch('company-groups/{companyGroup}/users/{user}/role',      [\App\Http\Controllers\Admin\CompanyGroupWebController::class, 'updateUserRole'])->name('company-groups.users.role');

    // 관리자 계정 관리
    Route::resource('admins', \App\Http\Controllers\Admin\AdminAccountWebController::class)
        ->except(['show'])
        ->names([
            'index'   => 'admins.index',
            'create'  => 'admins.create',
            'store'   => 'admins.store',
            'edit'    => 'admins.edit',
            'update'  => 'admins.update',
            'destroy' => 'admins.destroy',
        ]);

    // 관리자 관리 (초대 + 프로젝트 배정)
    Route::get ('/management',                              [\App\Http\Controllers\Admin\AdminManagementController::class, 'index'])->name('management.index');
    Route::post('/management/invite',                       [\App\Http\Controllers\Admin\AdminManagementController::class, 'invite'])->name('management.invite');
    Route::delete('/management/invitations/{invitation}',   [\App\Http\Controllers\Admin\AdminManagementController::class, 'cancelInvite'])->name('management.invite.cancel');
    Route::post('/management/admins/{admin}/projects',      [\App\Http\Controllers\Admin\AdminManagementController::class, 'assignProjects'])->name('management.assign-projects');

    // 문의사항
    Route::get ('/inquiries',                         [\App\Http\Controllers\Admin\AdminInquiryController::class, 'index'])->name('inquiries.index');
    Route::get ('/inquiries/users/search',            [\App\Http\Controllers\Admin\AdminInquiryController::class, 'searchUsers'])->name('inquiries.users.search');
    Route::post('/inquiries/send',                    [\App\Http\Controllers\Admin\AdminInquiryController::class, 'sendToUsers'])->name('inquiries.send');
    Route::post('/inquiries/broadcast',               [\App\Http\Controllers\Admin\AdminInquiryController::class, 'broadcastToAll'])->name('inquiries.broadcast');
    Route::post('/inquiries/upload-image',            [\App\Http\Controllers\Admin\AdminInquiryController::class, 'uploadImage'])->name('inquiries.upload-image');
    Route::get ('/inquiries/{conversation}',          [\App\Http\Controllers\Admin\AdminInquiryController::class, 'show'])->name('inquiries.show');
    Route::post('/inquiries/{conversation}/reply',    [\App\Http\Controllers\Admin\AdminInquiryController::class, 'reply'])->name('inquiries.reply');
    Route::post('/inquiries/{conversation}/close',    [\App\Http\Controllers\Admin\AdminInquiryController::class, 'close'])->name('inquiries.close');
    Route::post('/inquiries/{conversation}/reopen',   [\App\Http\Controllers\Admin\AdminInquiryController::class, 'reopen'])->name('inquiries.reopen');
    Route::post('/inquiries/analyze',                 [MessageAnalyzeController::class, 'analyze'])->name('inquiries.analyze');

    // 유지보수 관리
    Route::get ('/maintenances',                                    [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'index'])->name('maintenances.index');
    Route::get ('/maintenances/{maintenance}',                      [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'show'])->name('maintenances.show');
    Route::get ('/maintenances/{maintenance}/detail',               [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'detail'])->name('maintenances.detail');
    Route::patch('/maintenances/{maintenance}/status',              [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'updateStatus'])->name('maintenances.status');
    Route::patch('/maintenances/{maintenance}/schedule',            [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'updateSchedule'])->name('maintenances.schedule');
    Route::post ('/maintenances/{maintenance}/replies',             [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'storeReply'])->name('maintenances.replies.store');
    Route::delete('/maintenance-replies/{reply}',                   [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'destroyReply'])->name('maintenance-replies.destroy');

    // 로그인 로그
    Route::get('/login-logs', [\App\Http\Controllers\Admin\AdminLoginLogController::class, 'index'])->name('login-logs.index');
    Route::get('/user-login-logs', [\App\Http\Controllers\Admin\AdminUserLoginLogController::class, 'index'])->name('user-login-logs.index');
    Route::get('/user-page-logs',  [\App\Http\Controllers\Admin\AdminUserPageLogController::class,  'index'])->name('user-page-logs.index');

    // 앱 버전 관리
    Route::get   ('app-versions',                      [\App\Http\Controllers\Admin\AppVersionWebController::class, 'index'])   ->name('app-versions.index');
    Route::post  ('app-versions',                      [\App\Http\Controllers\Admin\AppVersionWebController::class, 'store'])   ->name('app-versions.store');
    Route::put   ('app-versions/{appVersion}',         [\App\Http\Controllers\Admin\AppVersionWebController::class, 'update'])  ->name('app-versions.update');
    Route::delete('app-versions/{appVersion}',         [\App\Http\Controllers\Admin\AppVersionWebController::class, 'destroy']) ->name('app-versions.destroy');
    Route::patch ('app-versions/{appVersion}/activate',[\App\Http\Controllers\Admin\AppVersionWebController::class, 'activate'])->name('app-versions.activate');

    // AI API 키 설정
    Route::get('ai-settings',     [\App\Http\Controllers\Admin\AiSettingController::class, 'index']) ->name('ai-settings.index');
    Route::put('ai-settings',     [\App\Http\Controllers\Admin\AiSettingController::class, 'update'])->name('ai-settings.update');

    // AI 프롬프트 내역
    Route::get('ai-prompts',           [\App\Http\Controllers\Admin\AdminAiPromptController::class, 'index'])->name('ai-prompts.index');
    Route::get('ai-prompts/{session}', [\App\Http\Controllers\Admin\AdminAiPromptController::class, 'show']) ->name('ai-prompts.show');

    // 시스템 에러 로그
    Route::get   ('system-errors',                      [\App\Http\Controllers\Admin\AdminSystemErrorController::class, 'index'])         ->name('system-errors.index');
    Route::patch ('system-errors/resolve-all',          [\App\Http\Controllers\Admin\AdminSystemErrorController::class, 'resolveAll'])    ->name('system-errors.resolve-all');
    Route::delete('system-errors',                      [\App\Http\Controllers\Admin\AdminSystemErrorController::class, 'destroyResolved'])->name('system-errors.destroy-resolved');
    Route::get   ('system-errors/{systemError}',        [\App\Http\Controllers\Admin\AdminSystemErrorController::class, 'show'])          ->name('system-errors.show');
    Route::patch ('system-errors/{systemError}/resolve',[\App\Http\Controllers\Admin\AdminSystemErrorController::class, 'resolve'])       ->name('system-errors.resolve');
    Route::delete('system-errors/{systemError}',        [\App\Http\Controllers\Admin\AdminSystemErrorController::class, 'destroy'])       ->name('system-errors.destroy');

    // Super Admin 데이터 초기화
    Route::delete('reset/inquiries',     [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetInquiries'])    ->name('reset.inquiries');
    Route::delete('reset/activity-logs', [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetActivityLogs']) ->name('reset.activity-logs');
    Route::delete('reset/login-logs',    [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetLoginLogs'])    ->name('reset.login-logs');
    Route::delete('reset/system-errors',    [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetSystemErrors'])   ->name('reset.system-errors');
    Route::delete('reset/user-page-logs',  [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetUserPageLogs'])  ->name('reset.user-page-logs');

});


Route::middleware(['auth'])->group(function () {
    Route::post('/upload/sr-image', [\App\Http\Controllers\SrImageUploadController::class, 'store'])->name('upload.sr-image');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');

    // 메시지
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/group', [MessageController::class, 'storeGroup'])->name('messages.group');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    Route::delete('/messages/{conversation}/leave', [MessageController::class, 'leave'])->name('messages.leave');
    Route::post('/messages/analyze', [MessageAnalyzeController::class, 'analyze'])->name('messages.analyze');
    Route::post('/translate', [TranslateController::class, 'translate'])->name('translate');
    Route::get ('/messages/{message}/image-comments',             [MessageImageCommentController::class, 'index'])->name('messages.image-comments.index');
    Route::post('/messages/{message}/image-comments',             [MessageImageCommentController::class, 'store'])->name('messages.image-comments.store');
    Route::delete('/messages/{message}/image-comments/{comment}', [MessageImageCommentController::class, 'destroy'])->name('messages.image-comments.destroy');
    Route::get   ('/messages/{message}/annotations',                          [MessageImageAnnotationController::class, 'index'])->name('messages.annotations.index');
    Route::post  ('/messages/{message}/annotations',                          [MessageImageAnnotationController::class, 'store'])->name('messages.annotations.store');
    Route::patch ('/messages/{message}/annotations/{annotation}',             [MessageImageAnnotationController::class, 'update'])->name('messages.annotations.update');
    Route::delete('/messages/{message}/annotations/{annotation}',             [MessageImageAnnotationController::class, 'destroy'])->name('messages.annotations.destroy');

    // 프로젝트
    Route::resource('projects', ProjectController::class);
    Route::post ('projects/{project}/join',  [ProjectController::class, 'join'])->name('projects.join');
    Route::delete('projects/{project}/leave', [ProjectController::class, 'leave'])->name('projects.leave');

    // 주간 업무 보고
    Route::prefix('projects/{project}/weekly-reports')->name('projects.weekly-reports.')->group(function () {
        Route::get   ('/',                        [\App\Http\Controllers\WeeklyReportController::class, 'index'])          ->name('index');
        Route::get   ('/create',                  [\App\Http\Controllers\WeeklyReportController::class, 'create'])         ->name('create');
        Route::post  ('/',                        [\App\Http\Controllers\WeeklyReportController::class, 'store'])          ->name('store');
        Route::post  ('/bulk-download',           [\App\Http\Controllers\WeeklyReportController::class, 'bulkDownload'])   ->name('bulk-download');
        Route::post  ('/analyze',                 [\App\Http\Controllers\WeeklyReportController::class, 'analyze'])        ->name('analyze');
        Route::get   ('/previous-tasks',          [\App\Http\Controllers\WeeklyReportController::class, 'previousTasks'])  ->name('previous-tasks');
        Route::get   ('/check-concurrent',        [\App\Http\Controllers\WeeklyReportController::class, 'checkConcurrent'])->name('check-concurrent');
        Route::get   ('/team-names',              [\App\Http\Controllers\WeeklyReportController::class, 'teamNames'])      ->name('team-names');
        Route::get   ('/{weeklyReport}/edit',     [\App\Http\Controllers\WeeklyReportController::class, 'edit'])           ->name('edit');
        Route::patch ('/{weeklyReport}',          [\App\Http\Controllers\WeeklyReportController::class, 'update'])         ->name('update');
        Route::delete('/{weeklyReport}',          [\App\Http\Controllers\WeeklyReportController::class, 'destroy'])        ->name('destroy');
        Route::get   ('/{weeklyReport}/download', [\App\Http\Controllers\WeeklyReportController::class, 'download'])       ->name('download');
    });

    // 휴무
    Route::resource('projects.leaves', ProjectLeaveController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['leaves' => 'leave']);

    // 프로젝트 멤버
    Route::prefix('projects/{project}/members')->name('projects.members.')->group(function () {
        Route::get('/', [ProjectMemberController::class, 'index'])->name('index');
        Route::get('/json', [ProjectMemberController::class, 'json'])->name('json');
        Route::post('/', [ProjectMemberController::class, 'store'])->name('store');
        Route::post('/bulk', [ProjectMemberController::class, 'bulkStore'])->name('bulk-store');
        Route::delete('/{member}', [ProjectMemberController::class, 'destroy'])->name('destroy');
        Route::patch('/{member}', [ProjectMemberController::class, 'update'])->name('update');
    });

    // 일정
    Route::resource('projects.schedules', ScheduleController::class)->shallow();
    Route::get('projects/{project}/gantt', [ScheduleController::class, 'gantt'])->name('projects.gantt');
    Route::patch('projects/{project}/gantt/{schedule}', [ScheduleController::class, 'ganttUpdate'])->name('projects.gantt.update');
    Route::post('projects/{project}/gantt/reorder', [ScheduleController::class, 'ganttReorder'])->name('projects.gantt.reorder');

    // 기획서
    Route::prefix('projects/{project}/planning')->name('projects.planning.')->group(function () {
        Route::get ('/',                              [PlanningDocController::class, 'index'])       ->name('index');
        Route::post('/',                              [PlanningDocController::class, 'store'])       ->name('store');
        Route::get ('/{doc}',                         [PlanningDocController::class, 'show'])        ->name('show');
        Route::put ('/{doc}',                         [PlanningDocController::class, 'update'])      ->name('update');
        Route::delete('/{doc}',                       [PlanningDocController::class, 'destroy'])     ->name('destroy');
        Route::post('/{doc}/input',                   [PlanningDocController::class, 'addInput'])    ->name('addInput');
        Route::delete('/{doc}/input/{input}',         [PlanningDocController::class, 'deleteInput']) ->name('deleteInput');
        Route::post('/{doc}/ai-integrate',            [PlanningDocController::class, 'aiIntegrate']) ->name('aiIntegrate');
        Route::post('/{doc}/approve',                 [PlanningDocController::class, 'approve'])     ->name('approve');
        Route::post('/{doc}/reject',                  [PlanningDocController::class, 'reject'])      ->name('reject');
        Route::get ('/{doc}/download',                [PlanningDocController::class, 'download'])    ->name('download');
        Route::post('/{doc}/ai-write',                [PlanningDocController::class, 'aiWrite'])              ->name('aiWrite');
        Route::post('/{doc}/ai-cleanup',              [PlanningDocController::class, 'aiCleanup'])            ->name('aiCleanup');
        Route::post('/{doc}/suggest-features',        [PlanningDocController::class, 'suggestFeatures'])      ->name('suggestFeatures');
        Route::post('/{doc}/feature/{suggestion}/apply', [PlanningDocController::class, 'applyFeatureSuggestion'])->name('applyFeatureSuggestion');
        Route::delete('/feature/{suggestion}',        [PlanningDocController::class, 'deleteFeatureSuggestion'])->name('deleteFeatureSuggestion');
    });

    // Q&A
    Route::resource('projects.questions', QuestionController::class)->shallow();
    Route::post('questions/{question}/answers', [AnswerController::class, 'store'])->name('answers.store');
    Route::delete('answers/{answer}', [AnswerController::class, 'destroy'])->name('answers.destroy');
    Route::patch('answers/{answer}/accept', [AnswerController::class, 'accept'])->name('answers.accept');

    // 유지보수
    Route::resource('projects.maintenances', ProjectMaintenanceController::class)->shallow()->except(['edit']);
    Route::get ('maintenances/{maintenance}/detail', [ProjectMaintenanceController::class, 'detail'])->name('maintenances.detail');
    Route::patch('maintenances/{maintenance}/status', [ProjectMaintenanceController::class, 'updateStatus'])->name('maintenances.status');
    Route::patch('maintenances/{maintenance}/dates',  [ProjectMaintenanceController::class, 'updateDates'])->name('maintenances.dates');
    Route::post('maintenances/{maintenance}/replies', [ProjectMaintenanceReplyController::class, 'store'])->name('maintenances.replies.store');
    Route::delete('maintenance-replies/{reply}', [ProjectMaintenanceReplyController::class, 'destroy'])->name('maintenance-replies.destroy');

    // SR 첨부파일 (SR 항목 연결)
    Route::post  ('maintenances/{maintenance}/files',                                               [\App\Http\Controllers\MaintenanceFileController::class, 'store'])->name('maintenances.files.store');
    Route::get   ('maintenances/{maintenance}/files/{maintenanceFile}/download',                   [\App\Http\Controllers\MaintenanceFileController::class, 'download'])->name('maintenances.files.download');
    Route::get   ('maintenances/{maintenance}/files/{maintenanceFile}/preview-data',               [\App\Http\Controllers\MaintenanceFileController::class, 'previewData'])->name('maintenances.files.preview-data');
    Route::delete('maintenances/{maintenance}/files/{maintenanceFile}',                            [\App\Http\Controllers\MaintenanceFileController::class, 'destroy'])->name('maintenances.files.destroy');
    Route::post  ('maintenances/{maintenance}/files/{maintenanceFile}/share',                      [\App\Http\Controllers\MaintenanceFileController::class, 'toggleShare'])->name('maintenances.files.share');
    Route::patch ('maintenances/{maintenance}/files/{maintenanceFile}/category',                   [\App\Http\Controllers\MaintenanceFileController::class, 'updateCategory'])->name('maintenances.files.update-category');

    // SR 첨부파일 (SR 항목 미연결 — 프로젝트 레벨)
    Route::post  ('projects/{project}/maintenance-files',                                              [\App\Http\Controllers\MaintenanceFileController::class, 'storeProject'])->name('projects.maintenance-files.store');
    Route::get   ('projects/{project}/maintenance-files/{maintenanceFile}/download',                  [\App\Http\Controllers\MaintenanceFileController::class, 'downloadProject'])->name('projects.maintenance-files.download');
    Route::get   ('projects/{project}/maintenance-files/{maintenanceFile}/preview-data',              [\App\Http\Controllers\MaintenanceFileController::class, 'previewDataProject'])->name('projects.maintenance-files.preview-data');
    Route::delete('projects/{project}/maintenance-files/{maintenanceFile}',                           [\App\Http\Controllers\MaintenanceFileController::class, 'destroyProject'])->name('projects.maintenance-files.destroy');
    Route::post  ('projects/{project}/maintenance-files/{maintenanceFile}/share',                     [\App\Http\Controllers\MaintenanceFileController::class, 'toggleShareProject'])->name('projects.maintenance-files.share');
    Route::patch ('projects/{project}/maintenance-files/{maintenanceFile}/category',                  [\App\Http\Controllers\MaintenanceFileController::class, 'updateCategoryProject'])->name('projects.maintenance-files.update-category');

    // SR 파일 의견/주석 (인증)
    Route::get   ('maintenance-files/{maintenanceFile}/comments',                          [\App\Http\Controllers\MaintenanceFileController::class, 'getComments'])->name('maintenance-files.comments.index');
    Route::post  ('maintenance-files/{maintenanceFile}/comments',                          [\App\Http\Controllers\MaintenanceFileController::class, 'storeComment'])->name('maintenance-files.comments.store');
    Route::delete('maintenance-files/{maintenanceFile}/comments/{comment}',                [\App\Http\Controllers\MaintenanceFileController::class, 'destroyComment'])->name('maintenance-files.comments.destroy');
    Route::get   ('maintenance-files/{maintenanceFile}/annotations',                       [\App\Http\Controllers\MaintenanceFileController::class, 'getAnnotations'])->name('maintenance-files.annotations.index');
    Route::post  ('maintenance-files/{maintenanceFile}/annotations',                       [\App\Http\Controllers\MaintenanceFileController::class, 'storeAnnotation'])->name('maintenance-files.annotations.store');
    Route::patch ('maintenance-files/{maintenanceFile}/annotations/{annotation}',           [\App\Http\Controllers\MaintenanceFileController::class, 'updateAnnotation'])->name('maintenance-files.annotations.update');
    Route::delete('maintenance-files/{maintenanceFile}/annotations/{annotation}',           [\App\Http\Controllers\MaintenanceFileController::class, 'destroyAnnotation'])->name('maintenance-files.annotations.destroy');

    // SR 파일 카테고리 (프로젝트 파일 카테고리와 별도)
    Route::post  ('projects/{project}/maintenance-file-categories',           [\App\Http\Controllers\MaintenanceFileCategoryController::class, 'store'])->name('projects.maintenance-file-categories.store');
    Route::delete('projects/{project}/maintenance-file-categories/{category}',[\App\Http\Controllers\MaintenanceFileCategoryController::class, 'destroy'])->name('projects.maintenance-file-categories.destroy');

    // 파일 카테고리
    Route::get   ('projects/{project}/file-categories',                   [\App\Http\Controllers\ProjectFileCategoryController::class, 'index'])->name('projects.file-categories.index');
    Route::post  ('projects/{project}/file-categories',                   [\App\Http\Controllers\ProjectFileCategoryController::class, 'store'])->name('projects.file-categories.store');
    Route::patch ('projects/{project}/file-categories/{category}',        [\App\Http\Controllers\ProjectFileCategoryController::class, 'update'])->name('projects.file-categories.update');
    Route::delete('projects/{project}/file-categories/{category}',        [\App\Http\Controllers\ProjectFileCategoryController::class, 'destroy'])->name('projects.file-categories.destroy');

    // 파일
    Route::prefix('projects/{project}/files')->name('projects.files.')->group(function () {
        Route::get('/', [ProjectFileController::class, 'index'])->name('index');
        Route::post('/', [ProjectFileController::class, 'store'])->name('store');
        Route::get('/{file}/preview', [ProjectFileController::class, 'preview'])->name('preview');
        Route::get('/{file}/preview-data', [FileCommentController::class, 'previewData'])->name('preview-data');
        Route::get('/{file}/comments', [FileCommentController::class, 'index'])->name('comments.index');
        Route::post('/{file}/comments', [FileCommentController::class, 'store'])->name('comments.store');
        Route::delete('/{file}/comments/{comment}', [FileCommentController::class, 'destroy'])->name('comments.destroy');
        Route::get('/{file}/download', [ProjectFileController::class, 'download'])->name('download');
        Route::get('/{file}/url-view', [ProjectFileController::class, 'urlViewer'])->name('url-view');
        Route::patch('/{file}/category', [ProjectFileController::class, 'updateCategory'])->name('update-category');
        Route::post('/{file}/share', [ProjectFileController::class, 'toggleShare'])->name('toggle-share');
        Route::delete('/{file}', [ProjectFileController::class, 'destroy'])->name('destroy');
        Route::post('/{file}/review-request', [ProjectFileController::class, 'requestReview'])->name('review-request');
        Route::post('/{file}/copy', [ProjectFileController::class, 'copy'])->name('copy');
        Route::get('/{file}/action-logs', [ProjectFileController::class, 'actionLogs'])->name('action-logs');
        Route::post('/{file}/log-action', [ProjectFileController::class, 'logAction'])->name('log-action');
        Route::get('/{file}/annotations', [FileCommentController::class, 'getAnnotations'])->name('annotations.index');
        Route::post('/{file}/annotations', [FileCommentController::class, 'storeAnnotation'])->name('annotations.store');
        Route::patch('/{file}/annotations/{annotation}', [FileCommentController::class, 'updateAnnotation'])->name('annotations.update');
        Route::delete('/{file}/annotations/{annotation}', [FileCommentController::class, 'destroyAnnotation'])->name('annotations.destroy');
    });

    // AI Agent
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/',                                [AiController::class, 'index'])->name('index');
        Route::post('/settings',                       [AiController::class, 'saveSettings'])->name('settings');
        Route::post('/figma',                          [AiController::class, 'addFigmaFile'])->name('figma.add');
        Route::post('/figma/{file}/sync',              [AiController::class, 'syncFigmaFile'])->name('figma.sync');
        Route::delete('/figma/{file}',                 [AiController::class, 'deleteFigmaFile'])->name('figma.delete');
        Route::post('/sessions',                       [AiController::class, 'createSession'])->name('sessions.create');
        Route::get('/sessions/{session}',              [AiController::class, 'getSession'])->name('sessions.get');
        Route::delete('/sessions/{session}',           [AiController::class, 'deleteSession'])->name('sessions.delete');
        Route::post('/sessions/{session}/messages',        [AiController::class, 'sendMessage'])->name('sessions.message');
        Route::post('/sessions/{session}/generate-prompt', [AiController::class, 'generatePrompt'])->name('sessions.generate-prompt');
        // 프롬프트 정제
        Route::post('/prompts/refine',                 [AiController::class, 'refinePrompt'])->name('prompts.refine');
        // 프롬프트 라이브러리
        Route::get('/prompts',                         [AiController::class, 'promptIndex'])->name('prompts.index');
        Route::post('/prompts',                        [AiController::class, 'storePrompt'])->name('prompts.store');
        Route::put('/prompts/{prompt}',                [AiController::class, 'updatePrompt'])->name('prompts.update');
        Route::delete('/prompts/{prompt}',             [AiController::class, 'destroyPrompt'])->name('prompts.destroy');
        // 카테고리
        Route::post('/categories',                     [AiController::class, 'storeCategory'])->name('categories.store');
        Route::delete('/categories/{category}',        [AiController::class, 'destroyCategory'])->name('categories.destroy');
        // 실행 이력
        Route::get('/executions',                      [AiController::class, 'executionIndex'])->name('executions.index');
        Route::get('/executions/{execution}',          [AiController::class, 'executionShow'])->name('executions.show');
        Route::get('/executions/{execution}/files/{file}/download', [AiController::class, 'fileDownload'])->name('executions.files.download');
        Route::get('/messages/{message}/download',                 [AiController::class, 'downloadCode'])->name('message.download');
        Route::post('/sessions/{session}/share',                   [AiController::class, 'shareSession'])->name('sessions.share');
        Route::post('/sessions/{session}/fork',                    [AiController::class, 'forkSession'])->name('sessions.fork');
        Route::post('/sessions/{session}/minutes',                 [AiController::class, 'exportMinutes'])->name('sessions.minutes');
        Route::get('/minutes/download',                            [AiController::class, 'downloadMinutes'])->name('minutes.download');
        Route::post('/sessions/{session}/pptx',                   [AiController::class, 'exportPptx'])->name('sessions.pptx');
        Route::get('/pptx/download',                              [AiController::class, 'downloadPptx'])->name('pptx.download');
        Route::post('/sessions/{session}/excel',                  [AiController::class, 'exportExcel'])->name('sessions.excel');
        Route::get('/excel/download',                             [AiController::class, 'downloadExcel'])->name('excel.download');
        Route::get('/word/download',                              [AiController::class, 'downloadWordDoc'])->name('word.download');
        Route::get('/projects/{project}/files',                   [AiController::class, 'getProjectFiles'])->name('projects.files');
        Route::post('/sessions/{session}/context-check',           [AiController::class, 'contextCheck'])->name('sessions.contextCheck');
        Route::get('/user-projects',                               [AiController::class, 'getUserProjects'])->name('user.projects');
        Route::post('/messages/{message}/add-to-project',          [AiController::class, 'addDocToProject'])->name('messages.addToProject');
        Route::post('/messages/{message}/add-zip-to-project',      [AiController::class, 'addZipToProject'])->name('messages.addZipToProject');
    });

    // AI Agent 개발 워크플로우
    Route::prefix('ai-agent')->name('ai-agent.')->group(function () {
        Route::get('/', [AiAgentController::class, 'dashboard'])->name('dashboard');

        Route::prefix('projects/{project}')->name('projects.')->group(function () {
            Route::get('/', [AiAgentController::class, 'projectHome'])->name('home');

            // 기획 단계
            Route::prefix('planning')->name('planning.')->group(function () {
                Route::get('/',        [AiAgentController::class, 'planningIndex'])->name('index');
                Route::get('as-is',    [AiAgentController::class, 'asIs'])->name('as-is');
                Route::get('to-be',    [AiAgentController::class, 'toBe'])->name('to-be');
                Route::get('gap',      [AiAgentController::class, 'gap'])->name('gap');
                Route::get('document', [AiAgentController::class, 'document'])->name('document');
                Route::get('ia',       [AiAgentController::class, 'ia'])->name('ia');
                Route::get('prompts',  [AiAgentController::class, 'planningPrompts'])->name('prompts');
                Route::get('mockups',  [AiAgentController::class, 'mockups'])->name('mockups');
                Route::get('approval', [AiAgentController::class, 'planningApproval'])->name('approval');
            });

            // 디자인 단계
            Route::prefix('design')->name('design.')->group(function () {
                Route::get('/',          [AiAgentController::class, 'designIndex'])->name('index');
                Route::get('tokens',     [AiAgentController::class, 'designTokens'])->name('tokens');
                Route::get('components', [AiAgentController::class, 'designComponents'])->name('components');
                Route::get('layout',     [AiAgentController::class, 'designLayout'])->name('layout');
                Route::get('screens',    [AiAgentController::class, 'designScreens'])->name('screens');
                Route::get('validation', [AiAgentController::class, 'designValidation'])->name('validation');
                Route::get('system',     [AiAgentController::class, 'designSystem'])->name('system');
                Route::get('figma-dev',  [AiAgentController::class, 'figmaDev'])->name('figma-dev');
                Route::get('approval',   [AiAgentController::class, 'designApproval'])->name('approval');
            });

            // 개발 준비 단계
            Route::prefix('pre-dev')->name('pre-dev.')->group(function () {
                Route::get('/',          [AiAgentController::class, 'preDevIndex'])->name('index');
                Route::get('erd',        [AiAgentController::class, 'erd'])->name('erd');
                Route::get('api-spec',   [AiAgentController::class, 'apiSpec'])->name('api-spec');
                Route::get('rbac',       [AiAgentController::class, 'rbac'])->name('rbac');
                Route::get('code-prompts', [AiAgentController::class, 'codePrompts'])->name('code-prompts');
                Route::get('ai-output',  [AiAgentController::class, 'aiOutput'])->name('ai-output');
                Route::get('validation', [AiAgentController::class, 'preDevValidation'])->name('validation');
                Route::get('approval',   [AiAgentController::class, 'preDevApproval'])->name('approval');
            });

            // 개발 단계
            Route::prefix('dev')->name('dev.')->group(function () {
                Route::get('/',           [AiAgentController::class, 'devIndex'])->name('index');
                Route::get('backend',     [AiAgentController::class, 'backend'])->name('backend');
                Route::get('api-connect', [AiAgentController::class, 'apiConnect'])->name('api-connect');
                Route::get('code-review', [AiAgentController::class, 'codeReview'])->name('code-review');
                Route::get('ai-tasks',    [AiAgentController::class, 'aiTasks'])->name('ai-tasks');
                Route::get('approval',    [AiAgentController::class, 'devApproval'])->name('approval');
            });

            // 릴리즈
            Route::get('release', [AiAgentController::class, 'release'])->name('release');

            // 공통 기능
            Route::prefix('common')->name('common.')->group(function () {
                Route::get('traceability', [AiAgentController::class, 'traceability'])->name('traceability');
                Route::get('versions',     [AiAgentController::class, 'versions'])->name('versions');
                Route::get('prompts',      [AiAgentController::class, 'commonPrompts'])->name('prompts');
                Route::get('usage',        [AiAgentController::class, 'usage'])->name('usage');
                Route::get('permissions',  [AiAgentController::class, 'permissions'])->name('permissions');
            });

            // 승인 게이트 (T12)
            Route::prefix('approvals')->name('approvals.')->group(function () {
                Route::get ('demo',           [AiAgentApprovalController::class, 'demo'])->name('demo');
                Route::post('request',        [AiAgentApprovalController::class, 'store'])->name('request');
                Route::post('{gate}/approve', [AiAgentApprovalController::class, 'approve'])->name('approve');
                Route::post('{gate}/reject',  [AiAgentApprovalController::class, 'reject'])->name('reject');
                Route::post('{gate}/cancel',  [AiAgentApprovalController::class, 'cancel'])->name('cancel');
            });
        });
    });

    // 화면 유지보수
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::post('/',                                         [MaintenanceController::class, 'store'])->name('store');
        Route::get ('/{screenKey}/info',                         [MaintenanceController::class, 'info'])->name('info');
        Route::delete('/{screenKey}',                            [MaintenanceController::class, 'destroy'])->name('destroy');
        Route::get ('/{screenKey}/files',                        [MaintenanceController::class, 'readFiles'])->name('files');
        Route::post('/{screenKey}/generate-prompt',              [MaintenanceController::class, 'generatePrompt'])->name('generate-prompt');
        Route::post('/{screenKey}/generate-patch',               [MaintenanceController::class, 'generatePatch'])->name('generate-patch');
        Route::post('/{screenKey}/apply',                        [MaintenanceController::class, 'applyPatch'])->name('apply');
        Route::post('/{screenKey}/preview',                      [MaintenanceController::class, 'storePreview'])->name('preview');
        Route::get ('/{screenKey}/preview/{token}',              [MaintenanceController::class, 'renderPreview'])->name('preview.render');
        Route::get ('/{screenKey}/versions',                     [MaintenanceController::class, 'versions'])->name('versions');
        Route::get ('/{screenKey}/versions/{versionId}',         [MaintenanceController::class, 'versionDetail'])->name('versions.detail');
        Route::post('/{screenKey}/versions/{versionId}/rollback',[MaintenanceController::class, 'rollback'])->name('versions.rollback');
    });

    // Teams 연동
    Route::prefix('teams')->name('teams.')->group(function () {
        Route::get('/',                        [TeamsController::class, 'index'])->name('index');
        Route::post('/verify',                 [TeamsController::class, 'verify'])->name('verify');
        Route::get('/api/teams',               [TeamsController::class, 'teams'])->name('api.teams');
        Route::get('/api/teams/{teamId}/channels', [TeamsController::class, 'channels'])->name('api.channels');
        Route::post('/api/message',            [TeamsController::class, 'sendMessage'])->name('api.message');
        Route::post('/api/chat',               [TeamsController::class, 'createChat'])->name('api.chat');
        Route::get('/api/users',               [TeamsController::class, 'searchUsers'])->name('api.users');
        Route::get('/api/sites',               [TeamsController::class, 'sites'])->name('api.sites');
        Route::get('/api/sites/{siteId}/drives', [TeamsController::class, 'drives'])->name('api.drives');
        Route::post('/api/upload',             [TeamsController::class, 'uploadFile'])->name('api.upload');
    });

    // 팀원
    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::post('/team/invite', [TeamController::class, 'invite'])->name('team.invite');
    Route::delete('/team/invite/{invitation}', [TeamController::class, 'cancelInvite'])->name('team.invite.cancel');

    // 문의하기
    Route::prefix('inquiry')->name('inquiry.')->group(function () {
        Route::get('/',                            [InquiryController::class, 'index'])->name('index');
        Route::post('/',                           [InquiryController::class, 'store'])->name('store');
        Route::post('/upload-image',               [InquiryController::class, 'uploadImage'])->name('upload-image');
        Route::get('/{conversation}',              [InquiryController::class, 'show'])->name('show');
        Route::post('/{conversation}/reply',       [InquiryController::class, 'reply'])->name('reply');
        Route::post('/{conversation}/close',       [InquiryController::class, 'close'])->name('close');
    });

    // 커뮤니티
    Route::prefix('community')->name('community.')->group(function () {
        Route::get('/',                                     [CommunityController::class, 'index'])->name('index');
        Route::post('/',                                    [CommunityController::class, 'store'])->name('store');
        Route::get('/{post}',                               [CommunityController::class, 'show'])->name('show');
        Route::get('/{post}/detail',                        [CommunityController::class, 'quickView'])->name('detail');
        Route::delete('/{post}',                            [CommunityController::class, 'destroy'])->name('destroy');
        Route::post('/{post}/vote',                         [CommunityController::class, 'vote'])->name('vote');
        Route::post('/{post}/react',                        [CommunityController::class, 'react'])->name('react');
        Route::post('/{post}/comments',                     [CommunityController::class, 'storeComment'])->name('comments.store');
        Route::delete('/comments/{comment}',                [CommunityController::class, 'destroyComment'])->name('comments.destroy');
        Route::post('/comments/{comment}/vote',             [CommunityController::class, 'voteComment'])->name('comments.vote');
    });

    // 메모
    Route::get('/memos', [MemoController::class, 'index'])->name('memos.index');
    Route::post('/memos', [MemoController::class, 'store'])->name('memos.store');
    Route::patch('/memos/{memo}', [MemoController::class, 'update'])->name('memos.update');
    Route::patch('/memos/{memo}/pin', [MemoController::class, 'togglePin'])->name('memos.pin');
    Route::delete('/memos/{memo}', [MemoController::class, 'destroy'])->name('memos.destroy');

    // Action 아이템
    Route::get('/action-items', [ActionItemController::class, 'index'])->name('action-items.index');
    Route::post('/action-items', [ActionItemController::class, 'store'])->name('action-items.store');
    Route::patch('/action-items/{actionItem}/toggle', [ActionItemController::class, 'toggle'])->name('action-items.toggle');
    Route::delete('/action-items/{actionItem}', [ActionItemController::class, 'destroy'])->name('action-items.destroy');

    // 회의록
    Route::prefix('meeting-minutes')->name('meeting-minutes.')->group(function () {
        Route::get('/',                      [MeetingMinuteController::class, 'index'])->name('index');
        Route::get('/create',                [MeetingMinuteController::class, 'create'])->name('create');
        Route::post('/',                     [MeetingMinuteController::class, 'store'])->name('store');
        Route::get('/{meetingMinute}',            [MeetingMinuteController::class, 'show'])->name('show');
        Route::get('/{meetingMinute}/download',   [MeetingMinuteController::class, 'downloadDocx'])->name('download');
        Route::get('/{meetingMinute}/edit',       [MeetingMinuteController::class, 'edit'])->name('edit');
        Route::patch('/{meetingMinute}',     [MeetingMinuteController::class, 'update'])->name('update');
        Route::delete('/{meetingMinute}',    [MeetingMinuteController::class, 'destroy'])->name('destroy');

        Route::post('/{meetingMinute}/memos',            [MeetingMemoController::class, 'store'])->name('memos.store');
        Route::delete('/memos/{meetingMemo}',            [MeetingMemoController::class, 'destroy'])->name('memos.destroy');

        Route::post('/{meetingMinute}/action-items',              [MeetingActionItemController::class, 'store'])->name('action-items.store');
        Route::patch('/action-items/{meetingActionItem}/status',  [MeetingActionItemController::class, 'updateStatus'])->name('action-items.status');
        Route::delete('/action-items/{meetingActionItem}',        [MeetingActionItemController::class, 'destroy'])->name('action-items.destroy');
    });

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // 프로필
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 실시간 협업
    Route::prefix('collab')->name('collab.')->group(function () {
        Route::get  ('/online',                          [CollabController::class, 'online'])->name('online');
        Route::post ('/heartbeat',                       [CollabController::class, 'heartbeat'])->name('heartbeat');
        Route::post ('/cursor/{sessionKey}',             [CollabController::class, 'cursor'])->name('cursor');
        Route::get  ('/session',                         [CollabController::class, 'getCurrentSession'])->name('session');
        Route::post ('/request',                         [CollabController::class, 'sendRequest'])->name('request');
        Route::post ('/respond/{sessionKey}',            [CollabController::class, 'respond'])->name('respond');
        Route::patch('/navigate/{sessionKey}',           [CollabController::class, 'navigate'])->name('navigate');
        Route::patch('/permission/{sessionKey}',         [CollabController::class, 'changePermission'])->name('permission');
        Route::post ('/scroll/{sessionKey}',             [CollabController::class, 'scroll'])->name('scroll');
        Route::delete('/end/{sessionKey}',               [CollabController::class, 'end'])->name('end');

        // 화면 공유 WebRTC 시그널링
        Route::post  ('/screen/request/{sessionKey}',   [CollabController::class, 'screenRequest'])->name('screen.request');
        Route::post  ('/screen/signal/{sessionKey}',    [CollabController::class, 'screenSignal'])->name('screen.signal');
        Route::delete('/screen/end/{sessionKey}',       [CollabController::class, 'screenEnd'])->name('screen.end');
    });

    // (관리자 패널은 별도 admin.web 미들웨어로 분리됨)
});

require __DIR__.'/auth.php';
