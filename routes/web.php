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
use App\Http\Controllers\MessageActionItemController;
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
use App\Http\Controllers\UrsController;
use App\Http\Controllers\DeliverableController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\MessageAnalyzeController;
use App\Http\Controllers\MessageImageCommentController;
use App\Http\Controllers\MessageImageAnnotationController;
use App\Http\Controllers\TranslateController;
use App\Http\Controllers\AiAgentController;
use App\Http\Controllers\AiAgentApprovalController;
use App\Http\Controllers\AiAgent\Sessions\AgentSessionDashboardController;
use App\Http\Controllers\AiAgent\Sessions\AgentSessionController;
use App\Http\Controllers\AiAgent\Sessions\AgentSourceController;
use App\Http\Controllers\AiAgent\Sessions\AgentAnalysisController;
use App\Http\Controllers\AiAgent\Sessions\AgentOutputController;
use App\Http\Controllers\AiAgent\Sessions\AgentFeedbackController;
use App\Http\Controllers\AiAgent\Sessions\AgentConflictController;
use App\Http\Controllers\AiAgent\Sessions\AgentConfirmedOutputController;
use App\Http\Controllers\AiAgent\Sessions\AgentSettingsController;
use App\Http\Controllers\AiStreamController;
use App\Http\Controllers\AiVersionController;
use App\Http\Controllers\AiTraceabilityController;
use App\Http\Controllers\AiDashboardController;
use App\Http\Controllers\AiProjectConfigController;
use App\Http\Controllers\AiPlanningScreenController;
use App\Http\Controllers\AsIsAnalysisController;
use App\Http\Controllers\ToBeAnalysisController;
use App\Http\Controllers\GapAnalysisController;
use App\Http\Controllers\PlanningDocumentController;
use App\Http\Controllers\IaDiagramController;
use App\Http\Controllers\ScreenPromptController;
use App\Http\Controllers\MockupController;
use App\Http\Controllers\DesignTokenController;
use App\Http\Controllers\ComponentSpecController;
use App\Http\Controllers\LayoutSpecController;
use App\Http\Controllers\ScreenMappingController;
use App\Http\Controllers\DesignReviewController;
use App\Http\Controllers\FigmaSettingsController;
use App\Http\Controllers\PlanningApprovalController;
use App\Http\Controllers\WorksPromptController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\TaskGroupController;
use App\Http\Controllers\SubTaskController;
use App\Http\Controllers\IssueController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) return redirect()->route('dashboard');
    return view('welcome');
});

// 헬스체크 — 인증 불필요. 배포 후 운영 상태 확인용. 외부 모니터/배포 스크립트가 호출.
Route::get('/healthz', function () {
    $checks = [];
    $allOk  = true;

    // 1) DB 연결
    try {
        \Illuminate\Support\Facades\DB::select('SELECT 1');
        $checks['db'] = 'ok';
    } catch (\Throwable $e) {
        $checks['db'] = 'fail';
        $allOk = false;
    }

    // 2) 캐시 (선택적 — 캐시가 죽어도 앱은 동작하므로 fail이어도 200)
    try {
        \Illuminate\Support\Facades\Cache::put('healthz_ping', 1, 5);
        $checks['cache'] = \Illuminate\Support\Facades\Cache::get('healthz_ping') === 1 ? 'ok' : 'degraded';
    } catch (\Throwable) {
        $checks['cache'] = 'degraded';
    }

    // 3) 배포된 git 커밋 (있으면)
    $commit = null;
    try {
        $headFile = base_path('.git/HEAD');
        if (is_file($headFile)) {
            $head = trim(@file_get_contents($headFile));
            if (str_starts_with($head, 'ref: ')) {
                $refPath = base_path('.git/' . substr($head, 5));
                if (is_file($refPath)) {
                    $commit = substr(trim(@file_get_contents($refPath)), 0, 12);
                }
            } else {
                $commit = substr($head, 0, 12);
            }
        }
    } catch (\Throwable) {}

    return response()->json([
        'status' => $allOk ? 'ok' : 'fail',
        'checks' => $checks,
        'commit' => $commit,
        'time'   => now()->toIso8601String(),
    ], $allOk ? 200 : 503);
});

// 브라우저(또는 모바일) 클라이언트 JS/Dart 에러 수집 — 인증 없음 (anonymous 도 보낼 수 있어야).
// CSRF 면제는 bootstrap/app.php 의 validateCsrfTokens(except:) 에서 처리.
// throttle: IP 당 분당 60회 (브라우저 1대가 도배할 수 있는 합리적 상한).
Route::post('/client-errors', [\App\Http\Controllers\ClientErrorController::class, 'store'])
    ->middleware('throttle:60,1');

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

// 방문자 상담 챗 (welcome 페이지, 인증 불필요)
Route::prefix('guest-chat')->name('guest-chat.')->group(function () {
    Route::post('/start',                       [\App\Http\Controllers\GuestChatController::class, 'start'])->name('start');
    Route::get ('/{conversation}/messages',     [\App\Http\Controllers\GuestChatController::class, 'poll'])->name('poll');
    Route::post('/{conversation}/send',         [\App\Http\Controllers\GuestChatController::class, 'send'])->name('send');
});

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
// 기획서 외부 공유 — 로그인 불필요
Route::get('/share/planning/{token}',       [\App\Http\Controllers\PublicPlanningShareController::class, 'show'])->name('planning.public-share');
Route::get('/share/planning/{token}/print', [\App\Http\Controllers\PublicPlanningShareController::class, 'printPdf'])->name('planning.public-print');

// 외부 공유 파일 — 로그인 불필요
Route::get ('/share/file/{token}',             [\App\Http\Controllers\PublicFileShareController::class, 'show'])->name('files.public-share');
Route::get ('/share/serve/{token}',            [\App\Http\Controllers\PublicFileShareController::class, 'serve'])->name('files.public-serve');
Route::get ('/share/serve-pdf/{token}',        [\App\Http\Controllers\PublicFileShareController::class, 'servePdf'])->name('files.public-serve-pdf');
Route::get ('/share/file/{token}/comments',    [\App\Http\Controllers\PublicFileShareController::class, 'getComments'])->name('files.public-comments.index');
Route::post('/share/file/{token}/comments',    [\App\Http\Controllers\PublicFileShareController::class, 'storeComment'])->name('files.public-comments.store');
Route::get ('/share/file/{token}/annotations', [\App\Http\Controllers\PublicFileShareController::class, 'getAnnotations'])->name('files.public-annotations.index');
Route::post ('/share/file/{token}/annotations',              [\App\Http\Controllers\PublicFileShareController::class, 'storeAnnotation'])->name('files.public-annotations.store');
Route::patch ('/share/file/{token}/annotations/{annotation}', [\App\Http\Controllers\PublicFileShareController::class, 'updateAnnotation'])->name('files.public-annotations.update');
Route::delete('/share/file/{token}/annotations/{annotation}', [\App\Http\Controllers\PublicFileShareController::class, 'destroyAnnotation'])->name('files.public-annotations.destroy');

// 외부 공유 페이지에서 SupportWorks 회원가입 (공유한 사람의 회사로 자동 소속)
Route::get ('/share/file/{token}/signup', [\App\Http\Controllers\PublicFileShareController::class, 'signupForm'])->name('files.public-share.signup');
Route::post('/share/file/{token}/signup', [\App\Http\Controllers\PublicFileShareController::class, 'signup'])->name('files.public-share.signup.post');

// 산출물 공개 링크 공유 — 로그인 불필요
Route::get('/share/deliverable/{token}', [\App\Http\Controllers\DeliverableController::class, 'publicShare'])->name('deliverables.public-share');

// 논의 의견 외부 공유 — 로그인 불필요
Route::get('/share/discussion-comment/{token}', [\App\Http\Controllers\DiscussionController::class, 'publicShowComment'])->name('discussions.public-comment');

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

    // 프로젝트 현황
    Route::get('projects', [\App\Http\Controllers\Admin\AdminProjectController::class, 'index'])->name('projects.index');

    // AI 에이전트 사용량
    Route::get('ai-usage', [\App\Http\Controllers\Admin\AdminAiUsageController::class, 'index'])->name('ai-usage.index');

    // 공지사항 관리
    Route::get   ('announcements',                        [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'index'])  ->name('announcements.index');
    Route::post  ('announcements',                        [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'store'])  ->name('announcements.store');
    Route::put   ('announcements/{announcement}',         [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'update']) ->name('announcements.update');
    Route::delete('announcements/{announcement}',         [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'destroy'])->name('announcements.destroy');
    Route::patch ('announcements/{announcement}/toggle',  [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'toggleActive'])->name('announcements.toggle');

    // 통합 로그
    Route::get('/logs', [\App\Http\Controllers\Admin\AdminLogsController::class, 'index'])->name('logs.index');

    // 하위 호환 리다이렉트 (기존 북마크/링크 지원)
    Route::get('activity-logs',    fn() => redirect()->route('admin.logs.index', ['tab' => 'activity']))->name('activity-logs.index');

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

    // 하위 호환 리다이렉트
    Route::get('/login-logs',      fn() => redirect()->route('admin.logs.index', ['tab' => 'login']))->name('login-logs.index');
    Route::get('/user-login-logs', fn() => redirect()->route('admin.logs.index', ['tab' => 'user-login']))->name('user-login-logs.index');
    Route::get('/user-page-logs',  fn() => redirect()->route('admin.logs.index', ['tab' => 'page']))->name('user-page-logs.index');

    // 앱 버전 관리
    Route::get   ('app-versions',                      [\App\Http\Controllers\Admin\AppVersionWebController::class, 'index'])   ->name('app-versions.index');
    Route::post  ('app-versions',                      [\App\Http\Controllers\Admin\AppVersionWebController::class, 'store'])   ->name('app-versions.store');
    Route::put   ('app-versions/{appVersion}',         [\App\Http\Controllers\Admin\AppVersionWebController::class, 'update'])  ->name('app-versions.update');
    Route::delete('app-versions/{appVersion}',         [\App\Http\Controllers\Admin\AppVersionWebController::class, 'destroy']) ->name('app-versions.destroy');
    Route::patch ('app-versions/{appVersion}/activate',[\App\Http\Controllers\Admin\AppVersionWebController::class, 'activate'])->name('app-versions.activate');

    // AI API 키 설정
    Route::get('ai-settings',     [\App\Http\Controllers\Admin\AiSettingController::class, 'index']) ->name('ai-settings.index');
    Route::put('ai-settings',     [\App\Http\Controllers\Admin\AiSettingController::class, 'update'])->name('ai-settings.update');

    // 시스템 유지보수 모드
    Route::get  ('system-maintenance', [\App\Http\Controllers\Admin\SystemMaintenanceController::class, 'index']) ->name('system-maintenance.index');
    Route::patch('system-maintenance', [\App\Http\Controllers\Admin\SystemMaintenanceController::class, 'update'])->name('system-maintenance.update');

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

    // AI Fix Job 관리 (관리자 승인 큐)
    Route::get ('ai-fix-jobs',                       [\App\Http\Controllers\Admin\AdminAiFixJobController::class, 'index'])  ->name('ai-fix-jobs.index');
    Route::get ('ai-fix-jobs/{aiFixJob}',            [\App\Http\Controllers\Admin\AdminAiFixJobController::class, 'show'])   ->name('ai-fix-jobs.show');
    Route::get ('ai-fix-jobs/{aiFixJob}/modal',      [\App\Http\Controllers\Admin\AdminAiFixJobController::class, 'modal'])  ->name('ai-fix-jobs.modal');
    Route::post('ai-fix-jobs/{aiFixJob}/approve',    [\App\Http\Controllers\Admin\AdminAiFixJobController::class, 'approve'])->name('ai-fix-jobs.approve');
    Route::post('ai-fix-jobs/{aiFixJob}/reject',     [\App\Http\Controllers\Admin\AdminAiFixJobController::class, 'reject']) ->name('ai-fix-jobs.reject');


    // Super Admin 데이터 초기화
    Route::delete('reset/inquiries',     [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetInquiries'])    ->name('reset.inquiries');
    Route::delete('reset/activity-logs', [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetActivityLogs']) ->name('reset.activity-logs');
    Route::delete('reset/login-logs',       [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetLoginLogs'])      ->name('reset.login-logs');
    Route::delete('reset/user-login-logs',  [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetUserLoginLogs'])  ->name('reset.user-login-logs');
    Route::delete('reset/system-errors',    [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetSystemErrors'])   ->name('reset.system-errors');
    Route::delete('reset/user-page-logs',    [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetUserPageLogs'])    ->name('reset.user-page-logs');
    Route::delete('reset/meeting-minutes',   [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetMeetingMinutes'])   ->name('reset.meeting-minutes');
    Route::delete('reset/weekly-reports',    [\App\Http\Controllers\Admin\AdminDataResetController::class, 'resetWeeklyReports'])    ->name('reset.weekly-reports');

});


Route::middleware(['auth'])->group(function () {
    Route::post('/upload/sr-image', [\App\Http\Controllers\SrImageUploadController::class, 'store'])->name('upload.sr-image');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');

    // 상단바 이메일 보내기 (팝오버)
    Route::get ('/email-compose/recipients',   [\App\Http\Controllers\EmailComposeController::class, 'recipients'])->name('email-compose.recipients');
    Route::post('/email-compose',              [\App\Http\Controllers\EmailComposeController::class, 'send'])->name('email-compose.send');
    Route::post('/email-compose/upload-image', [\App\Http\Controllers\EmailComposeController::class, 'uploadImage'])->name('email-compose.upload-image');

    // Mailbox (사용자간 메일 시스템)
    Route::prefix('mailbox')->name('mailbox.')->group(function () {
        Route::get('/',                          [\App\Http\Controllers\Mailbox\MailboxController::class, 'inbox'])->name('inbox');
        Route::get('/sent',                      [\App\Http\Controllers\Mailbox\MailboxController::class, 'sent'])->name('sent');
        Route::get('/trash',                     [\App\Http\Controllers\Mailbox\MailboxController::class, 'trash'])->name('trash');
        Route::get('/compose',                   [\App\Http\Controllers\Mailbox\MailboxController::class, 'create'])->name('compose');
        Route::post('/send',                     [\App\Http\Controllers\Mailbox\MailboxController::class, 'send'])->name('send');
        Route::get('/recipients',                [\App\Http\Controllers\Mailbox\MailboxController::class, 'recipients'])->name('recipients');
        Route::get('/project-files',             [\App\Http\Controllers\Mailbox\MailboxController::class, 'projectFiles'])->name('project-files');
        Route::get('/messages/{message}',        [\App\Http\Controllers\Mailbox\MailboxController::class, 'show'])->name('show');
        Route::post('/messages/{message}/read',  [\App\Http\Controllers\Mailbox\MailboxController::class, 'markRead'])->name('read');
        Route::post('/trash/move',               [\App\Http\Controllers\Mailbox\MailboxController::class, 'trashMove'])->name('trash.move');
        Route::post('/trash/restore',            [\App\Http\Controllers\Mailbox\MailboxController::class, 'trashRestore'])->name('trash.restore');
        Route::post('/destroy-forever',          [\App\Http\Controllers\Mailbox\MailboxController::class, 'destroyForever'])->name('destroy-forever');
        Route::get('/attachments/{attachment}',  [\App\Http\Controllers\Mailbox\MailboxController::class, 'downloadAttachment'])->name('attachment');
    });

    // 투어 visited 기록 (온보딩)
    Route::post('/tour/{key}/visited', [\App\Http\Controllers\TourController::class, 'markVisited'])->name('tour.visited');

    // 메시지
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/group', [MessageController::class, 'storeGroup'])->name('messages.group');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    Route::delete('/messages/{conversation}/leave', [MessageController::class, 'leave'])->name('messages.leave');
    Route::post('/messages/{conversation}/invite', [MessageController::class, 'invite'])->name('messages.invite');
    Route::post('/messages/{message}/email-file', [MessageController::class, 'emailFile'])->name('messages.email-file');
    Route::post('/messages/{message}/action-items', [MessageActionItemController::class, 'store'])->name('messages.action-items.store');
    Route::post('/messages/analyze', [MessageAnalyzeController::class, 'analyze'])->name('messages.analyze');
    Route::post('/translate', [TranslateController::class, 'translate'])->name('translate');
    Route::get ('/messages/{message}/image-comments',             [MessageImageCommentController::class, 'index'])->name('messages.image-comments.index');
    Route::post('/messages/{message}/image-comments',             [MessageImageCommentController::class, 'store'])->name('messages.image-comments.store');
    Route::delete('/messages/{message}/image-comments/{comment}', [MessageImageCommentController::class, 'destroy'])->name('messages.image-comments.destroy');
    Route::get   ('/messages/{message}/annotations',                          [MessageImageAnnotationController::class, 'index'])->name('messages.annotations.index');
    Route::post  ('/messages/{message}/annotations',                          [MessageImageAnnotationController::class, 'store'])->name('messages.annotations.store');
    Route::patch ('/messages/{message}/annotations/{annotation}',             [MessageImageAnnotationController::class, 'update'])->name('messages.annotations.update');
    Route::delete('/messages/{message}/annotations/{annotation}',             [MessageImageAnnotationController::class, 'destroy'])->name('messages.annotations.destroy');

    // 메시지 화면 우측 워크스페이스 팝업용 — 내가 멤버인 프로젝트 + 권한 허용 메뉴
    Route::get('/messages/workspace/projects', [MessageController::class, 'workspaceProjects'])
        ->name('messages.workspace.projects');

    // 디자인 시스템 컴포넌트 가이드 (Phase 2)
    Route::view('/dev/ds-components', 'dev.ds-components')->name('dev.ds-components');

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
        Route::post  ('/upload-image',            [\App\Http\Controllers\WeeklyReportController::class, 'uploadImage'])    ->name('upload-image');
        Route::get   ('/manager-summary',          [\App\Http\Controllers\ManagerWeeklySummaryController::class, 'index'])   ->name('manager-summary');
        Route::post  ('/manager-summary/download', [\App\Http\Controllers\ManagerWeeklySummaryController::class, 'download']) ->name('manager-summary.download');
        Route::get   ('/ai-summary',               [\App\Http\Controllers\WeeklyAiSummaryController::class, 'show'])          ->name('ai-summary');
        Route::post  ('/ai-summary/generate',      [\App\Http\Controllers\WeeklyAiSummaryController::class, 'generate'])      ->name('ai-summary.generate');
        Route::get   ('/ai-summary/download',      [\App\Http\Controllers\WeeklyAiSummaryController::class, 'download'])      ->name('ai-summary.download');
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

    // 일정 (legacy schedules — data now in sub_tasks)
    Route::resource('projects.schedules', ScheduleController::class)->shallow();
    Route::get ('projects/{project}/gantt',                     [ScheduleController::class, 'gantt'])        ->name('projects.gantt');
    Route::patch('projects/{project}/gantt/{subTask}',          [ScheduleController::class, 'ganttUpdate'])  ->name('projects.gantt.update');
    Route::post ('projects/{project}/gantt/reorder',            [ScheduleController::class, 'ganttReorder']) ->name('projects.gantt.reorder');

    // 마일스톤
    Route::prefix('projects/{project}/milestones')->name('projects.milestones.')->group(function () {
        Route::get   ('/',              [MilestoneController::class, 'index'])  ->name('index');
        Route::post  ('/',              [MilestoneController::class, 'store'])  ->name('store');
        Route::patch ('/{milestone}',   [MilestoneController::class, 'update']) ->name('update');
        Route::delete('/{milestone}',   [MilestoneController::class, 'destroy'])->name('destroy');
        Route::post  ('/reorder',       [MilestoneController::class, 'reorder'])->name('reorder');
    });

    // 태스크 그룹
    Route::prefix('projects/{project}/task-groups')->name('projects.task-groups.')->group(function () {
        Route::post  ('/',              [TaskGroupController::class, 'store'])  ->name('store');
        Route::patch ('/{taskGroup}',   [TaskGroupController::class, 'update']) ->name('update');
        Route::delete('/{taskGroup}',   [TaskGroupController::class, 'destroy'])->name('destroy');
        Route::post  ('/{taskGroup}/move', [TaskGroupController::class, 'move'])->name('move');
        Route::post  ('/reorder',       [TaskGroupController::class, 'reorder'])->name('reorder');
    });

    // 서브태스크
    Route::prefix('projects/{project}/sub-tasks')->name('projects.sub-tasks.')->group(function () {
        Route::get   ('/',              [SubTaskController::class, 'index'])  ->name('index');
        Route::post  ('/',              [SubTaskController::class, 'store'])  ->name('store');
        Route::get   ('/{subTask}',     [SubTaskController::class, 'show'])   ->name('show');
        Route::patch ('/{subTask}',     [SubTaskController::class, 'update']) ->name('update');
        Route::delete('/{subTask}',     [SubTaskController::class, 'destroy'])->name('destroy');
        Route::post  ('/{subTask}/move',[SubTaskController::class, 'move'])   ->name('move');
        Route::post  ('/reorder',       [SubTaskController::class, 'reorder'])->name('reorder');
    });

    // 일정 트리 (Milestone → TaskGroup → SubTask)
    Route::get('projects/{project}/schedule-tree', function (\App\Models\Project $project) {
        $service = app(\App\Services\Schedule\ScheduleService::class);
        return response()->json($service->getTree($project->id));
    })->name('projects.schedule-tree');

    // 요구사항
    Route::prefix('projects/{project}/requirements')->name('projects.requirements.')->group(function () {
        Route::get   ('/',                              [\App\Http\Controllers\RequirementController::class, 'index'])         ->name('index');
        Route::post  ('/',                              [\App\Http\Controllers\RequirementController::class, 'store'])         ->name('store');
        Route::post  ('/validate',                      [\App\Http\Controllers\RequirementController::class, 'validateBeforeStore'])->name('validate');
        Route::get   ('/export',                        [\App\Http\Controllers\RequirementController::class, 'export'])        ->name('export');
        Route::post  ('/bulk-destroy',                  [\App\Http\Controllers\RequirementController::class, 'bulkDestroy'])   ->name('bulk-destroy');
        Route::get   ('/{requirement}',                 [\App\Http\Controllers\RequirementController::class, 'show'])          ->name('show');
        Route::patch ('/{requirement}',                 [\App\Http\Controllers\RequirementController::class, 'update'])        ->name('update');
        Route::delete('/{requirement}',                 [\App\Http\Controllers\RequirementController::class, 'destroy'])       ->name('destroy');
        Route::post  ('/{requirement}/approve',         [\App\Http\Controllers\RequirementController::class, 'approve'])       ->name('approve');
        Route::post  ('/{requirement}/comments',        [\App\Http\Controllers\RequirementController::class, 'storeComment'])  ->name('comments.store');
        Route::post  ('/{requirement}/watch',           [\App\Http\Controllers\RequirementController::class, 'toggleWatcher']) ->name('watch');
        Route::post  ('/analyze-attachment',            [\App\Http\Controllers\RequirementController::class, 'analyzeAttachment'])->name('attachments.analyze');
        Route::get   ('/ai-context',                    [\App\Http\Controllers\RequirementController::class, 'aiContext'])          ->name('ai-context');
        Route::get   ('/{requirement}/attachments/{attachment}', [\App\Http\Controllers\RequirementController::class, 'downloadAttachment'])->name('attachments.download');

        // AI 분석 세션
        Route::prefix('analysis')->name('analysis.')->group(function () {
            Route::get   ('/',                          [\App\Http\Controllers\AnalysisSessionController::class, 'index'])   ->name('index');
            Route::get   ('/new',                       [\App\Http\Controllers\AnalysisSessionController::class, 'create'])  ->name('create');
            Route::post  ('/',                          [\App\Http\Controllers\AnalysisSessionController::class, 'store'])   ->name('store');
            Route::get   ('/{session}',                 [\App\Http\Controllers\AnalysisSessionController::class, 'show'])    ->name('show');
            Route::post  ('/{session}/approve',         [\App\Http\Controllers\AnalysisSessionController::class, 'approve']) ->name('approve');
            Route::post  ('/{session}/reject',          [\App\Http\Controllers\AnalysisSessionController::class, 'reject'])  ->name('reject');
            Route::post  ('/{session}/retry',           [\App\Http\Controllers\AnalysisSessionController::class, 'retry'])   ->name('retry');
        });
    });

    // 기획서
    // 논의사항
    Route::prefix('projects/{project}/discussions')->name('projects.discussions.')->group(function () {
        Route::get ('/',                                          [\App\Http\Controllers\DiscussionController::class, 'index'])       ->name('index');
        Route::post('/',                                          [\App\Http\Controllers\DiscussionController::class, 'store'])       ->name('store');
        Route::post('/refine',                                    [\App\Http\Controllers\DiscussionController::class, 'refine'])      ->name('refine');
        Route::post('/upload-image',                              [\App\Http\Controllers\DiscussionController::class, 'uploadProjectInlineImage'])->name('upload-image');
        Route::get ('/inline-image/{filename}',                   [\App\Http\Controllers\DiscussionController::class, 'serveProjectInlineImage'])->name('inline-image')->where('filename', '[A-Za-z0-9._-]+');
        Route::get ('/{discussion}/download-word',                [\App\Http\Controllers\DiscussionController::class, 'downloadWord'])->name('download-word');
        Route::get ('/{discussion}',                              [\App\Http\Controllers\DiscussionController::class, 'show'])        ->name('show');
        Route::patch('/{discussion}',                             [\App\Http\Controllers\DiscussionController::class, 'update'])      ->name('update');
        Route::delete('/{discussion}',                            [\App\Http\Controllers\DiscussionController::class, 'destroy'])     ->name('destroy');
        Route::post('/{discussion}/share',                        [\App\Http\Controllers\DiscussionController::class, 'share'])       ->name('share');
        Route::get ('/{discussion}/reflect-targets',              [\App\Http\Controllers\DiscussionController::class, 'reflectTargets'])->name('reflect-targets');
        Route::post('/{discussion}/reflect-to-planning',          [\App\Http\Controllers\DiscussionController::class, 'reflectToPlanning'])->name('reflect');
        Route::post('/{discussion}/reject-reflection',            [\App\Http\Controllers\DiscussionController::class, 'rejectReflection'])->name('reject-reflection');
        Route::post('/{discussion}/comments',                     [\App\Http\Controllers\DiscussionController::class, 'storeComment'])->name('comments.store');
        Route::post('/{discussion}/comments/refine',              [\App\Http\Controllers\DiscussionController::class, 'refineComment'])->name('comments.refine');
        Route::post('/{discussion}/comments/summarize',           [\App\Http\Controllers\DiscussionController::class, 'summarizeComments'])->name('comments.summarize');
        Route::post('/{discussion}/comments/upload-image',        [\App\Http\Controllers\DiscussionController::class, 'uploadInlineImage'])->name('comments.upload-image');
        Route::post('/{discussion}/comments/{comment}/toggle-share', [\App\Http\Controllers\DiscussionController::class, 'toggleCommentShare'])->name('comments.toggle-share');
        Route::delete('/{discussion}/comments/{comment}',         [\App\Http\Controllers\DiscussionController::class, 'destroyComment'])->name('comments.destroy');
        Route::get ('/{discussion}/attachments/{attachment}',     [\App\Http\Controllers\DiscussionController::class, 'downloadAttachment'])->name('attachments.download');
    });

    // Plan-Do-Act
    Route::get   ('/plan-do-acts',              [\App\Http\Controllers\PlanDoActController::class, 'globalIndex'])->name('plan-do-acts.index');
    Route::post  ('/plan-do-acts',              [\App\Http\Controllers\PlanDoActController::class, 'store'])      ->name('plan-do-acts.store');
    Route::get   ('/plan-do-acts/{planDoAct}',  [\App\Http\Controllers\PlanDoActController::class, 'show'])       ->name('plan-do-acts.show');
    Route::patch ('/plan-do-acts/{planDoAct}',  [\App\Http\Controllers\PlanDoActController::class, 'update'])     ->name('plan-do-acts.update');
    Route::delete('/plan-do-acts/{planDoAct}',  [\App\Http\Controllers\PlanDoActController::class, 'destroy'])    ->name('plan-do-acts.destroy');

    // 공유폴더 (회사 단위)
    Route::prefix('shared-folder')->name('shared-folder.')->group(function () {
        Route::get   ('/',                                [\App\Http\Controllers\SharedFileController::class, 'index'])          ->name('index');
        Route::post  ('/',                                [\App\Http\Controllers\SharedFileController::class, 'store'])          ->name('store');
        Route::get   ('/files/{sharedFile}/download',     [\App\Http\Controllers\SharedFileController::class, 'download'])       ->name('download');
        Route::delete('/files/{sharedFile}',              [\App\Http\Controllers\SharedFileController::class, 'destroy'])        ->name('destroy');
        Route::patch ('/files/{sharedFile}/category',     [\App\Http\Controllers\SharedFileController::class, 'moveCategory'])   ->name('move-category');
        Route::post  ('/categories',                      [\App\Http\Controllers\SharedFileController::class, 'storeCategory'])  ->name('categories.store');
        Route::delete('/categories/{sharedFileCategory}', [\App\Http\Controllers\SharedFileController::class, 'destroyCategory'])->name('categories.destroy');
    });

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
        Route::post('/{doc}/ai-write-stream',         [PlanningDocController::class, 'aiWriteStream'])        ->name('aiWriteStream');
        Route::post('/{doc}/ai-cleanup',              [PlanningDocController::class, 'aiCleanup'])            ->name('aiCleanup');
        Route::post('/{doc}/send-email',              [PlanningDocController::class, 'sendEmail'])             ->name('sendEmail');
        Route::post('/{doc}/suggest-features',        [PlanningDocController::class, 'suggestFeatures'])        ->name('suggestFeatures');
        Route::post('/{doc}/suggest-features-stream',[PlanningDocController::class, 'suggestFeaturesStream']) ->name('suggestFeaturesStream');
        Route::post('/{doc}/feature/{suggestion}/apply', [PlanningDocController::class, 'applyFeatureSuggestion'])->name('applyFeatureSuggestion');
        Route::delete('/feature/{suggestion}',        [PlanningDocController::class, 'deleteFeatureSuggestion'])->name('deleteFeatureSuggestion');
        // 요구사항 적용
        Route::get ('/{doc}/applied-requirements',    [\App\Http\Controllers\PlanApplicationController::class, 'listByPlan'])  ->name('applied-requirements');
        Route::post('/{doc}/apply-requirements',      [\App\Http\Controllers\PlanApplicationController::class, 'apply'])       ->name('apply-requirements');
        // 외부 공유 링크 토글
        Route::post('/{doc}/toggle-share',            [PlanningDocController::class, 'toggleShare'])                           ->name('toggleShare');
        // 기획서 전체 리셋
        Route::post('/{doc}/reset',                   [PlanningDocController::class, 'reset'])                                 ->name('reset');
    });

    // URS (User Requirements Specification)
    Route::prefix('projects/{project}/urs')->name('projects.urs.')->group(function () {
        Route::get ('/',                    [UrsController::class, 'index'])       ->name('index');
        Route::post('/',                    [UrsController::class, 'store'])       ->name('store');
        Route::get ('/{urs}',               [UrsController::class, 'show'])        ->name('show');
        Route::put ('/{urs}',               [UrsController::class, 'update'])      ->name('update');
        Route::post('/{urs}/qa/start',      [UrsController::class, 'startQA'])     ->name('qa.start');
        Route::post('/{urs}/qa/answer',     [UrsController::class, 'answerQA'])    ->name('qa.answer');
        Route::post('/{urs}/generate',      [UrsController::class, 'generateURS']) ->name('generate');
        Route::post('/{urs}/reset',         [UrsController::class, 'reset'])       ->name('reset');
        Route::get ('/{urs}/download/word',   [UrsController::class, 'downloadWord'])     ->name('download.word');
        Route::get ('/{urs}/download/pdf',    [UrsController::class, 'downloadPdf'])      ->name('download.pdf');
        Route::post('/{urs}/translate-en',    [UrsController::class, 'translateToEnglish'])->name('translate-en');
    });

    // 기획서 적용 관련 (프로젝트 레벨)
    Route::prefix('projects/{project}/plan-applications')->name('projects.plan-applications.')->group(function () {
        Route::get  ('/plans',                      [\App\Http\Controllers\PlanApplicationController::class, 'plans'])         ->name('plans');
        Route::post ('/preview',                    [\App\Http\Controllers\PlanApplicationController::class, 'preview'])       ->name('preview');
        Route::post ('/{application}/complete',     [\App\Http\Controllers\PlanApplicationController::class, 'toggleComplete'])->name('complete');
        Route::delete('/{application}',             [\App\Http\Controllers\PlanApplicationController::class, 'revert'])        ->name('revert');
    });

    // 요구사항별 기획서 적용 이력
    Route::get('projects/{project}/requirements/{requirement}/plan-applications',
        [\App\Http\Controllers\PlanApplicationController::class, 'listByRequirement'])
        ->name('projects.requirements.plan-applications');

    // 이슈
    Route::prefix('projects/{project}/issues')->name('projects.issues.')->group(function () {
        Route::get   ('/',                        [\App\Http\Controllers\IssueController::class, 'index'])             ->name('index');
        Route::post  ('/',                        [\App\Http\Controllers\IssueController::class, 'store'])             ->name('store');
        Route::get   ('/{issue}',                 [\App\Http\Controllers\IssueController::class, 'show'])              ->name('show');
        Route::patch ('/{issue}',                 [\App\Http\Controllers\IssueController::class, 'update'])            ->name('update');
        Route::delete('/{issue}',                 [\App\Http\Controllers\IssueController::class, 'destroy'])           ->name('destroy');
        Route::post  ('/{issue}/resolve',         [\App\Http\Controllers\IssueController::class, 'resolve'])           ->name('resolve');
        Route::post  ('/{issue}/link-requirement',[\App\Http\Controllers\IssueController::class, 'linkRequirement'])   ->name('link-requirement');
        Route::delete('/{issue}/link-requirement',[\App\Http\Controllers\IssueController::class, 'unlinkRequirement']) ->name('unlink-requirement');
        Route::post  ('/{issue}/comments',        [\App\Http\Controllers\IssueController::class, 'storeComment'])      ->name('comments.store');
        Route::post  ('/{issue}/watch',           [\App\Http\Controllers\IssueController::class, 'toggleWatcher'])     ->name('watch');
        Route::get   ('/export/csv',              [\App\Http\Controllers\IssueController::class, 'export'])            ->name('export');
        Route::get   ('/stats/summary',           [\App\Http\Controllers\IssueController::class, 'stats'])             ->name('stats');
        Route::post  ('/convert-from-question',   [\App\Http\Controllers\IssueController::class, 'convertFromQuestion'])->name('convert-from-question');
    });

    // Q&A
    Route::resource('projects.questions', QuestionController::class)->shallow();
    Route::post('questions/{question}/answers', [AnswerController::class, 'store'])->name('answers.store');
    Route::delete('answers/{answer}', [AnswerController::class, 'destroy'])->name('answers.destroy');
    Route::patch('answers/{answer}/accept', [AnswerController::class, 'accept'])->name('answers.accept');

    // 유지보수 요청 (콜로/위드웍스 — 프로젝트와 무관한 독립 시스템)
    Route::resource('maint-requests', \App\Http\Controllers\MaintRequestController::class)
        ->except(['edit'])
        ->parameters(['maint-requests' => 'maintRequest']);
    Route::post  ('maint-requests/{maintRequest}/notes',              [\App\Http\Controllers\MaintRequestController::class, 'storeNote'])  ->name('maint-requests.notes.store');
    Route::delete('maint-requests/{maintRequest}/notes/{note}',        [\App\Http\Controllers\MaintRequestController::class, 'destroyNote'])->name('maint-requests.notes.destroy');
    Route::post  ('maint-requests/works-summary',                      [\App\Http\Controllers\MaintRequestController::class, 'worksSummary'])->name('maint-requests.works-summary');
    Route::post  ('maint-requests/upload-image',                       [\App\Http\Controllers\MaintRequestController::class, 'uploadImage'])->name('maint-requests.upload-image');
    Route::get   ('maint-requests/export/excel',                       [\App\Http\Controllers\MaintRequestController::class, 'exportExcel'])->name('maint-requests.export-excel');
    Route::post  ('maint-requests/{maintRequest}/send-to-manager',     [\App\Http\Controllers\MaintRequestController::class, 'sendToManager'])->name('maint-requests.send-to-manager');

    // SR 이미지 주석 + 댓글
    Route::get   ('maint-requests/{maintRequest}/image-annotations',                       [\App\Http\Controllers\MaintRequestImageAnnotationController::class, 'index'])  ->name('maint-requests.image-annotations.index');
    Route::post  ('maint-requests/{maintRequest}/image-annotations',                       [\App\Http\Controllers\MaintRequestImageAnnotationController::class, 'store'])  ->name('maint-requests.image-annotations.store');
    Route::patch ('maint-requests/{maintRequest}/image-annotations/{annotation}',          [\App\Http\Controllers\MaintRequestImageAnnotationController::class, 'update']) ->name('maint-requests.image-annotations.update');
    Route::delete('maint-requests/{maintRequest}/image-annotations/{annotation}',          [\App\Http\Controllers\MaintRequestImageAnnotationController::class, 'destroy'])->name('maint-requests.image-annotations.destroy');

    Route::get   ('maint-requests/{maintRequest}/image-comments',                          [\App\Http\Controllers\MaintRequestImageCommentController::class, 'index'])  ->name('maint-requests.image-comments.index');
    Route::post  ('maint-requests/{maintRequest}/image-comments',                          [\App\Http\Controllers\MaintRequestImageCommentController::class, 'store'])  ->name('maint-requests.image-comments.store');
    Route::delete('maint-requests/{maintRequest}/image-comments/{comment}',                [\App\Http\Controllers\MaintRequestImageCommentController::class, 'destroy'])->name('maint-requests.image-comments.destroy');
    Route::post  ('maint-requests/import',                             [\App\Http\Controllers\MaintRequestController::class, 'import'])->name('maint-requests.import');
    Route::patch ('maint-requests/{maintRequest}/quick',               [\App\Http\Controllers\MaintRequestController::class, 'quickUpdate'])->name('maint-requests.quick');
    Route::get   ('maint-requests/embed/closed',                       [\App\Http\Controllers\MaintRequestController::class, 'embedClosed'])->name('maint-requests.embed.closed');
    Route::get   ('maint-requests/{maintRequest}/embed',               [\App\Http\Controllers\MaintRequestController::class, 'embed'])->name('maint-requests.embed');

    // 파일 카테고리
    Route::get   ('projects/{project}/file-categories',                   [\App\Http\Controllers\ProjectFileCategoryController::class, 'index'])->name('projects.file-categories.index');
    Route::post  ('projects/{project}/file-categories',                   [\App\Http\Controllers\ProjectFileCategoryController::class, 'store'])->name('projects.file-categories.store');
    Route::post  ('projects/{project}/file-categories/reorder',           [\App\Http\Controllers\ProjectFileCategoryController::class, 'reorder'])->name('projects.file-categories.reorder');
    Route::patch ('projects/{project}/file-categories/{category}',        [\App\Http\Controllers\ProjectFileCategoryController::class, 'update'])->name('projects.file-categories.update');
    Route::delete('projects/{project}/file-categories/{category}',        [\App\Http\Controllers\ProjectFileCategoryController::class, 'destroy'])->name('projects.file-categories.destroy');

    // 파일
    Route::prefix('projects/{project}/files')->name('projects.files.')->group(function () {
        Route::get('/', [ProjectFileController::class, 'index'])->name('index');
        Route::post('/', [ProjectFileController::class, 'store'])->name('store');
        Route::get('/{file}/preview', [ProjectFileController::class, 'preview'])->name('preview');
        Route::get('/{file}/preview-data', [FileCommentController::class, 'previewData'])->name('preview-data');
        Route::get('/{file}/viewer-embed', [ProjectFileController::class, 'viewerEmbed'])->name('viewer-embed');
        Route::get('/{file}/comments', [FileCommentController::class, 'index'])->name('comments.index');
        Route::post('/{file}/comments', [FileCommentController::class, 'store'])->name('comments.store');
        Route::delete('/{file}/comments/{comment}', [FileCommentController::class, 'destroy'])->name('comments.destroy');
        Route::post('/{file}/comments/{comment}/convert-to-discussion', [FileCommentController::class, 'convertToDiscussion'])->name('comments.convert-to-discussion');
        Route::get ('/{file}/comments/download',                       [FileCommentController::class, 'downloadCommentsReport'])->name('comments.download');
        Route::post('/{file}/upload-version', [ProjectFileController::class, 'uploadVersion'])->name('upload-version');
        Route::get('/{file}/versions', [ProjectFileController::class, 'versionList'])->name('versions');
        Route::delete('/{file}/versions/{fileVersion}', [ProjectFileController::class, 'deleteVersion'])->name('versions.destroy');
        Route::get('/{file}/download', [ProjectFileController::class, 'download'])->name('download');
        Route::get('/{file}/url-view', [ProjectFileController::class, 'urlViewer'])->name('url-view');
        Route::patch('/{file}/category', [ProjectFileController::class, 'updateCategory'])->name('update-category');
        Route::patch('/{file}', [ProjectFileController::class, 'update'])->name('update');
        Route::post('/{file}/share', [ProjectFileController::class, 'toggleShare'])->name('toggle-share');
        Route::delete('/{file}', [ProjectFileController::class, 'destroy'])->name('destroy');
        Route::post('/{file}/review-request', [ProjectFileController::class, 'requestReview'])->name('review-request');
        Route::post('/{file}/review-complete', [ProjectFileController::class, 'completeReview'])->name('review-complete');
        Route::post('/{file}/copy', [ProjectFileController::class, 'copy'])->name('copy');
        Route::get('/{file}/action-logs', [ProjectFileController::class, 'actionLogs'])->name('action-logs');
        Route::post('/{file}/log-action', [ProjectFileController::class, 'logAction'])->name('log-action');
        Route::get('/{file}/annotations', [FileCommentController::class, 'getAnnotations'])->name('annotations.index');
        Route::post('/{file}/annotations', [FileCommentController::class, 'storeAnnotation'])->name('annotations.store');
        Route::patch('/{file}/annotations/{annotation}', [FileCommentController::class, 'updateAnnotation'])->name('annotations.update');
        Route::delete('/{file}/annotations/{annotation}', [FileCommentController::class, 'destroyAnnotation'])->name('annotations.destroy');
    });

    // 공유폴더 파일 ↔ 프로젝트 링크 (link only, 원본은 공유폴더에 그대로 남음)
    Route::prefix('projects/{project}/shared-files')->name('projects.shared-files.')->group(function () {
        Route::post  ('/',             [\App\Http\Controllers\ProjectSharedFileController::class, 'store'])  ->name('store');
        Route::delete('/{sharedFile}', [\App\Http\Controllers\ProjectSharedFileController::class, 'destroy'])->name('destroy');
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
        // 대시보드 (T15)
        Route::get ('/',        [AiDashboardController::class,    'index'])->name('dashboard');
        Route::post('projects', [AiProjectConfigController::class, 'store'])->name('projects.store');

        // T27: Figma 설정 (사용자별, 프로젝트 독립)
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get   ('figma',          [FigmaSettingsController::class, 'index'])->name('figma');
            Route::post  ('figma/save',     [FigmaSettingsController::class, 'save'])->name('figma.save');
            Route::post  ('figma/validate', [FigmaSettingsController::class, 'validateToken'])->name('figma.validate');
            Route::delete('figma',          [FigmaSettingsController::class, 'delete'])->name('figma.delete');
        });

        // 스트리밍 데모 + 공용 취소 엔드포인트 (T13)
        Route::get ('stream/demo',                    [AiStreamController::class, 'demo'])->name('stream.demo');
        Route::get ('stream/demo-sse/{scenario}',     [AiStreamController::class, 'demoSse'])->name('stream.demo-sse');
        Route::post('stream/{sessionId}/cancel',      [AiStreamController::class, 'cancel'])->name('stream.cancel');

        // 버전 이력 · 추적성 데모 (T14)
        Route::get('demo/version-traceability',                    [AiVersionController::class,       'demo'])->name('demo.version-traceability');
        Route::get('demo/artifact/{artifactId}/versions',          [AiVersionController::class,       'demoHistory'])->name('demo.artifact.versions');
        Route::get('demo/artifact/{artifactId}/versions/{version}',[AiVersionController::class,       'demoVersionDetail'])->name('demo.artifact.version');
        Route::get('demo/traceability/{type}/{id}/links',          [AiTraceabilityController::class,  'demoLinks'])->name('demo.traceability.links');
        Route::get('demo/traceability/{type}/{id}/impact',         [AiTraceabilityController::class,  'demoImpact'])->name('demo.traceability.impact');

        Route::prefix('projects/{project}')->name('projects.')->group(function () {
            // 프로젝트 홈 (T15)
            Route::get('/', [AiDashboardController::class, 'show'])->name('home');

            // 산출물 (Deliverables)
            Route::prefix('deliverables')->name('deliverables.')->group(function () {
                Route::get ('/',                          [DeliverableController::class, 'index'])   ->name('index');
                Route::get ('/{typeId}',                  [DeliverableController::class, 'show'])    ->name('show');
                Route::post('/{typeId}/save-step',        [DeliverableController::class, 'saveStep'])    ->name('save-step');
                Route::post('/{typeId}/save-translation', [DeliverableController::class, 'saveTranslation'])->name('save-translation');
                Route::get ('/{typeId}/all-step-fields',  [DeliverableController::class, 'allStepFields'])  ->name('all-step-fields');
                Route::post('/{typeId}/save-tool',        [DeliverableController::class, 'saveTool'])    ->name('save-tool');
                Route::post('/{typeId}/generate-draft',        [DeliverableController::class, 'generateDraft'])      ->name('generate-draft');
                Route::get ('/{typeId}/generate-draft-stream', [DeliverableController::class, 'generateDraftStream'])->name('generate-draft-stream');
                Route::post('/{typeId}/analyze',               [DeliverableController::class, 'analyzeStep'])         ->name('analyze-step');
                Route::delete('/{typeId}',                [DeliverableController::class, 'destroy'])      ->name('destroy');
                // 승인 워크플로
                Route::post('/{typeId}/approval-request', [DeliverableController::class, 'approvalRequest'])->name('approval-request');
                Route::post('/{typeId}/approval-respond',  [DeliverableController::class, 'approvalRespond'])->name('approval-respond');
                // 링크 공유 토큰
                Route::post('/{typeId}/toggle-share',      [DeliverableController::class, 'toggleShare'])->name('toggle-share');
                // Word 내보내기
                Route::get ('/{typeId}/export-word',       [DeliverableController::class, 'exportWord'])->name('export-word');
                // STEP 본문 이미지 paste 업로드
                Route::post('/{typeId}/upload-image',      [DeliverableController::class, 'uploadImage'])->name('upload-image');
                // STEP 버전 이력
                Route::get ('/{typeId}/versions',                       [DeliverableController::class, 'versionIndex'])  ->name('versions.index');
                Route::get ('/{typeId}/versions/{versionId}',           [DeliverableController::class, 'versionShow'])   ->name('versions.show');
                Route::post('/{typeId}/versions/{versionId}/restore',   [DeliverableController::class, 'versionRestore'])->name('versions.restore');
                // 산출물 → 파일 등록 (file_versions 자동 증가)
                Route::get ('/{typeId}/registerable-files', [DeliverableController::class, 'registerableFiles'])->name('registerable-files');
                Route::get   ('/{typeId}/file-registrations',                  [DeliverableController::class, 'fileRegistrations'])      ->name('file-registrations');
                Route::delete('/{typeId}/file-registrations/{registration}',   [DeliverableController::class, 'destroyFileRegistration'])->name('file-registrations.destroy');
                Route::post  ('/{typeId}/register-as-file',                    [DeliverableController::class, 'registerAsFile'])         ->name('register-as-file');
                // 뷰어 의견
                Route::get   ('/{typeId}/viewer-comments',           [DeliverableController::class, 'viewerCommentsIndex'])  ->name('viewer-comments.index');
                Route::post  ('/{typeId}/viewer-comments',           [DeliverableController::class, 'viewerCommentsStore'])  ->name('viewer-comments.store');
                Route::delete('/{typeId}/viewer-comments/{comment}', [DeliverableController::class, 'viewerCommentsDestroy'])->name('viewer-comments.destroy');
                // 등록 파일 의견 (모든 STEP 팝오버) — 버전별 확인 + 반영 처리
                Route::get ('/{typeId}/file-comments',                    [DeliverableController::class, 'registeredFileComments'])->name('file-comments.index');
                Route::post('/{typeId}/file-comments/{comment}/reflect',  [DeliverableController::class, 'reflectFileComment'])    ->name('file-comments.reflect');
                Route::post('/{typeId}/file-comments/{comment}/apply',    [DeliverableController::class, 'applyCommentToStep'])  ->name('file-comments.apply');
                Route::post('/{typeId}/file-comments/{comment}/analyze',  [DeliverableController::class, 'analyzeCommentStepOnly'])->name('file-comments.analyze');
                // 팝오버에서 프로젝트 파일+버전을 직접 매핑
                Route::get ('/{typeId}/project-files/{file}/versions',    [DeliverableController::class, 'projectFileVersions'])->name('project-file-versions');
                Route::post('/{typeId}/file-comments/link',               [DeliverableController::class, 'linkProjectFile'])    ->name('file-comments.link');
            });

            // 기획 단계
            Route::prefix('planning')->name('planning.')->group(function () {
                // T16: 화면(작업 항목) 목록 — 기획 진입점
                Route::get ('/',                         [AiPlanningScreenController::class, 'index'])->name('index');
                Route::post('screens',                   [AiPlanningScreenController::class, 'store'])->name('screens.store');
                Route::get ('screens/{screen}',          [AiPlanningScreenController::class, 'show'])->name('screens.show');
                Route::put ('screens/{screen}',          [AiPlanningScreenController::class, 'update'])->name('screens.update');
                Route::post('screens/{screen}/archive',  [AiPlanningScreenController::class, 'archive'])->name('screens.archive');
                Route::post('screens/{screen}/restore',  [AiPlanningScreenController::class, 'restore'])->name('screens.restore');
                Route::get ('sync-gantt',                [AiPlanningScreenController::class, 'ganttPreview'])->name('sync-gantt.preview');
                Route::post('sync-gantt',                [AiPlanningScreenController::class, 'syncFromGantt'])->name('sync-gantt');

                // T17/T18: AS-IS 분석 (프로젝트 스코프)
                Route::get   ('as-is',                                  [AsIsAnalysisController::class, 'projectIndex'])->name('as-is');
                Route::post  ('as-is/files',                            [AsIsAnalysisController::class, 'projectUpload'])->name('as-is.upload');
                Route::get   ('as-is/status',                           [AsIsAnalysisController::class, 'projectStatus'])->name('as-is.status');
                Route::delete('as-is/files/{file}',                     [AsIsAnalysisController::class, 'projectDeleteFile'])->name('as-is.file.delete');
                Route::post  ('as-is/analyze/start',                    [AsIsAnalysisController::class, 'projectAnalyzeStart'])->name('as-is.analyze.start');
                Route::get   ('as-is/analyze/{sessionId}/sse',          [AsIsAnalysisController::class, 'projectAnalyzeSse'])->name('as-is.analyze.sse');
                Route::post  ('as-is/save',                             [AsIsAnalysisController::class, 'projectSave'])->name('as-is.save');
                Route::get   ('as-is/export',                           [AsIsAnalysisController::class, 'projectExport'])->name('as-is.export');

                // T17/T18: AS-IS 분석 (화면 스코프)
                Route::get   ('screens/{screen}/as-is',                             [AsIsAnalysisController::class, 'screenIndex'])->name('screens.as-is');
                Route::post  ('screens/{screen}/as-is/files',                       [AsIsAnalysisController::class, 'screenUpload'])->name('screens.as-is.upload');
                Route::get   ('screens/{screen}/as-is/status',                      [AsIsAnalysisController::class, 'screenStatus'])->name('screens.as-is.status');
                Route::delete('screens/{screen}/as-is/files/{file}',                [AsIsAnalysisController::class, 'screenDeleteFile'])->name('screens.as-is.file.delete');
                Route::post  ('screens/{screen}/as-is/analyze/start',               [AsIsAnalysisController::class, 'screenAnalyzeStart'])->name('screens.as-is.analyze.start');
                Route::get   ('screens/{screen}/as-is/analyze/{sessionId}/sse',     [AsIsAnalysisController::class, 'screenAnalyzeSse'])->name('screens.as-is.analyze.sse');
                Route::post  ('screens/{screen}/as-is/save',                        [AsIsAnalysisController::class, 'screenSave'])->name('screens.as-is.save');
                Route::get   ('screens/{screen}/as-is/export',                      [AsIsAnalysisController::class, 'screenExport'])->name('screens.as-is.export');
                // T19: TO-BE 요구사항 분석 (프로젝트 스코프)
                Route::get   ('to-be',                                    [ToBeAnalysisController::class, 'projectIndex'])->name('to-be');
                Route::post  ('to-be/files',                              [ToBeAnalysisController::class, 'projectUpload'])->name('to-be.upload');
                Route::get   ('to-be/status',                             [ToBeAnalysisController::class, 'projectStatus'])->name('to-be.status');
                Route::delete('to-be/files/{file}',                       [ToBeAnalysisController::class, 'projectDeleteFile'])->name('to-be.file.delete');
                Route::post  ('to-be/analyze/start',                      [ToBeAnalysisController::class, 'projectAnalyzeStart'])->name('to-be.analyze.start');
                Route::get   ('to-be/analyze/{sessionId}/sse',            [ToBeAnalysisController::class, 'projectAnalyzeSse'])->name('to-be.analyze.sse');
                Route::post  ('to-be/save',                               [ToBeAnalysisController::class, 'projectSave'])->name('to-be.save');
                Route::get   ('to-be/export',                             [ToBeAnalysisController::class, 'projectExport'])->name('to-be.export');
                Route::post  ('to-be/requirements',                       [ToBeAnalysisController::class, 'requirementStore'])->name('to-be.req.store');
                Route::patch ('to-be/requirements/{requirement}',         [ToBeAnalysisController::class, 'requirementUpdate'])->name('to-be.req.update');
                Route::delete('to-be/requirements/{requirement}',         [ToBeAnalysisController::class, 'requirementDestroy'])->name('to-be.req.destroy');

                // T20: Gap 분석
                Route::get   ('gap',                                      [GapAnalysisController::class, 'projectIndex'])->name('gap');
                Route::get   ('gap/prerequisites',                        [GapAnalysisController::class, 'prerequisites'])->name('gap.prerequisites');
                Route::post  ('gap/analyze/start',                        [GapAnalysisController::class, 'analyzeStart'])->name('gap.analyze.start');
                Route::get   ('gap/analyze/{sessionId}/sse',              [GapAnalysisController::class, 'analyzeSse'])->name('gap.analyze.sse');
                Route::post  ('gap/save',                                 [GapAnalysisController::class, 'save'])->name('gap.save');
                Route::get   ('gap/export',                               [GapAnalysisController::class, 'export'])->name('gap.export');
                Route::post  ('gap/items',                                [GapAnalysisController::class, 'gapStore'])->name('gap.items.store');
                Route::patch ('gap/items/{gap}',                          [GapAnalysisController::class, 'gapUpdate'])->name('gap.items.update');
                Route::delete('gap/items/{gap}',                          [GapAnalysisController::class, 'gapDestroy'])->name('gap.items.destroy');
                // T21/T22: AI 기획서
                Route::get  ('document',                                [PlanningDocumentController::class, 'index'])->name('document');
                Route::get  ('document/template',                       [PlanningDocumentController::class, 'templatePreview'])->name('document.template');
                Route::get  ('document/data-status',                    [PlanningDocumentController::class, 'dataStatus'])->name('document.data-status');
                Route::post ('document/generate/start',                 [PlanningDocumentController::class, 'generateStart'])->name('document.generate.start');
                Route::get  ('document/generate/{sessionId}/sse',       [PlanningDocumentController::class, 'generateSse'])->name('document.generate.sse');
                Route::post ('document/save',                           [PlanningDocumentController::class, 'save'])->name('document.save');
                Route::get  ('document/export',                         [PlanningDocumentController::class, 'export'])->name('document.export');
                Route::post ('document/regenerate-section',             [PlanningDocumentController::class, 'regenerateSection'])->name('document.regenerate');
                // T23: IA / 화면 흐름도
                Route::get  ('ia',                              [IaDiagramController::class, 'index'])->name('ia');
                Route::post ('ia/generate/start',               [IaDiagramController::class, 'generateStart'])->name('ia.generate.start');
                Route::get  ('ia/generate/{sessionId}/sse',     [IaDiagramController::class, 'generateSse'])->name('ia.generate.sse');
                Route::post ('ia/save',                         [IaDiagramController::class, 'save'])->name('ia.save');
                Route::get  ('ia/export',                       [IaDiagramController::class, 'export'])->name('ia.export');
                Route::post ('ia/regenerate',                   [IaDiagramController::class, 'regenerateDiagram'])->name('ia.regenerate');
                // T24: 화면 생성 프롬프트
                Route::get  ('screen-prompts',                           [ScreenPromptController::class, 'index'])->name('prompts');
                Route::post ('screen-prompts/batch/start',               [ScreenPromptController::class, 'batchStart'])->name('prompts.batch.start');
                Route::get  ('screen-prompts/batch/{sessionId}/sse',     [ScreenPromptController::class, 'batchSse'])->name('prompts.batch.sse');
                Route::get  ('screen-prompts/{screen}',                  [ScreenPromptController::class, 'show'])->name('prompts.show');
                Route::post ('screen-prompts/{screen}/generate',         [ScreenPromptController::class, 'generateOne'])->name('prompts.generate');
                Route::patch('screen-prompts/{screen}',                  [ScreenPromptController::class, 'update'])->name('prompts.update');
                Route::delete('screen-prompts/{screen}/prompt',          [ScreenPromptController::class, 'destroy'])->name('prompts.destroy');
                // T25: AI 샘플 화면(목업) 생성
                Route::get  ('mockups',                                        [MockupController::class, 'index'])->name('mockups');
                Route::post ('mockups/batch/start',                            [MockupController::class, 'batchStart'])->name('mockups.batch.start');
                Route::get  ('mockups/batch/{sessionId}/sse',                  [MockupController::class, 'batchSse'])->name('mockups.batch.sse');
                Route::get  ('mockups/{screen}',                               [MockupController::class, 'show'])->name('mockups.show');
                Route::post ('mockups/{screen}/generate',                      [MockupController::class, 'generateOne'])->name('mockups.generate');
                Route::patch('mockups/{screen}',                               [MockupController::class, 'update'])->name('mockups.update');
                Route::delete('mockups/{screen}',                              [MockupController::class, 'destroy'])->name('mockups.destroy');
                Route::get  ('mockups/{screen}/preview',                       [MockupController::class, 'preview'])->name('mockups.preview');
                Route::get  ('mockups/{screen}/preview/standalone',            [MockupController::class, 'previewStandalone'])->name('mockups.preview.standalone');
                Route::get  ('mockups/{screen}/download',                      [MockupController::class, 'download'])->name('mockups.download');
                // T26: 기획 단계 승인 게이트
                Route::get('approval',           [PlanningApprovalController::class, 'index'])->name('approval');
                Route::get('approval/diagnosis', [PlanningApprovalController::class, 'diagnosis'])->name('approval.diagnosis');
            });

            // 디자인 단계
            Route::prefix('design')->name('design.')->group(function () {
                Route::get('/',          [AiAgentController::class, 'designIndex'])->name('index');
                // T28: Design Tokens
                Route::get   ('tokens',                [DesignTokenController::class, 'index'])->name('tokens');
                Route::post  ('tokens/extract',        [DesignTokenController::class, 'extract'])->name('tokens.extract');
                Route::get   ('tokens/preview',        [DesignTokenController::class, 'preview'])->name('tokens.preview');
                Route::get   ('tokens/export',         [DesignTokenController::class, 'export'])->name('tokens.export');
                Route::patch ('tokens',                [DesignTokenController::class, 'update'])->name('tokens.update');
                // T29: Component 명세서
                Route::get   ('components',                [ComponentSpecController::class, 'index'])->name('components');
                Route::post  ('components/extract',        [ComponentSpecController::class, 'extract'])->name('components.extract');
                Route::get   ('components/export',         [ComponentSpecController::class, 'export'])->name('components.export');
                Route::get   ('components/{component}',    [ComponentSpecController::class, 'show'])->name('components.show');
                Route::patch ('components/{component}',    [ComponentSpecController::class, 'update'])->name('components.update');
                // T30: 표준 Layout
                Route::get   ('layout',                  [LayoutSpecController::class, 'index'])->name('layout');
                Route::post  ('layout/analyze',          [LayoutSpecController::class, 'analyze'])->name('layout.analyze');
                Route::get   ('layout/preview',          [LayoutSpecController::class, 'preview'])->name('layout.preview');
                Route::get   ('layout/export',           [LayoutSpecController::class, 'export'])->name('layout.export');
                Route::patch ('layout/{layoutKey}',      [LayoutSpecController::class, 'update'])->name('layout.update');
                // T31: 화면 매핑 (SCR-XXX ↔ Figma)
                Route::get   ('screens',             [ScreenMappingController::class, 'index'])->name('screens');
                Route::post  ('screens/load-figma',  [ScreenMappingController::class, 'loadFigma'])->name('screens.load-figma');
                Route::get   ('screens/suggestions', [ScreenMappingController::class, 'suggestions'])->name('screens.suggestions');
                Route::post  ('screens/apply',       [ScreenMappingController::class, 'apply'])->name('screens.apply');
                Route::post  ('screens/apply-batch', [ScreenMappingController::class, 'applyBatch'])->name('screens.apply-batch');
                Route::delete('screens/{screen}',    [ScreenMappingController::class, 'unmap'])->name('screens.unmap');
                Route::get   ('screens/export',      [ScreenMappingController::class, 'export'])->name('screens.export');
                // T32: 디자인 일관성 검수
                Route::get   ('review',                              [DesignReviewController::class, 'index'])->name('validation');
                Route::post  ('review/start',                        [DesignReviewController::class, 'start'])->name('review.start');
                Route::get   ('review/sse/{session}',                [DesignReviewController::class, 'sse'])->name('review.sse');
                Route::get   ('review/screens/{screen}',             [DesignReviewController::class, 'screenShow'])->name('review.screen');
                Route::post  ('review/save',                         [DesignReviewController::class, 'save'])->name('review.save');
                Route::get   ('review/export',                       [DesignReviewController::class, 'export'])->name('review.export');
                Route::post  ('review/screens/{screen}/regenerate',  [DesignReviewController::class, 'regenerate'])->name('review.regenerate');
                // T33: 디자인 시스템 문서
                Route::get  ('system',         [\App\Http\Controllers\DesignSystemController::class, 'index'])  ->name('system');
                Route::post ('system/generate',[\App\Http\Controllers\DesignSystemController::class, 'generate'])->name('system.generate');
                Route::post ('system/enrich',  [\App\Http\Controllers\DesignSystemController::class, 'enrich']) ->name('system.enrich');
                Route::get  ('system/export',  [\App\Http\Controllers\DesignSystemController::class, 'export']) ->name('system.export');
                Route::patch('system',         [\App\Http\Controllers\DesignSystemController::class, 'update']) ->name('system.update');
                Route::get  ('system/preview', [\App\Http\Controllers\DesignSystemController::class, 'preview'])->name('system.preview');
                // T34: 개발 핸드오프 (Figma Dev URL)
                Route::get ('figma-dev',         [\App\Http\Controllers\DevHandoffController::class, 'index'])   ->name('figma-dev');
                Route::post('figma-dev/validate', [\App\Http\Controllers\DevHandoffController::class, 'validate'])->name('figma-dev.validate');
                Route::post('figma-dev/generate', [\App\Http\Controllers\DevHandoffController::class, 'generate'])->name('figma-dev.generate');
                Route::get ('figma-dev/export',   [\App\Http\Controllers\DevHandoffController::class, 'export'])  ->name('figma-dev.export');
                Route::get ('figma-dev/package',  [\App\Http\Controllers\DevHandoffController::class, 'package']) ->name('figma-dev.package');
                // T35: 디자인 단계 승인 게이트
                Route::get('approval',           [\App\Http\Controllers\DesignApprovalController::class, 'index'])    ->name('approval');
                Route::get('approval/diagnosis', [\App\Http\Controllers\DesignApprovalController::class, 'diagnosis'])->name('approval.diagnosis');
            });

            // 개발 준비 단계
            Route::prefix('pre-dev')->name('pre-dev.')->group(function () {
                Route::get('/',          [AiAgentController::class, 'preDevIndex'])->name('index');
                // T36: ERD 자동 생성
                Route::get  ('erd',                   [\App\Http\Controllers\ErdController::class, 'index'])        ->name('erd');
                Route::post ('erd/generate/start',    [\App\Http\Controllers\ErdController::class, 'generateStart'])->name('erd.generate.start');
                Route::get  ('erd/generate/sse/{session}', [\App\Http\Controllers\ErdController::class, 'generateSse'])->name('erd.generate.sse');
                Route::post ('erd/save',              [\App\Http\Controllers\ErdController::class, 'save'])         ->name('erd.save');
                Route::get  ('erd/export',            [\App\Http\Controllers\ErdController::class, 'export'])       ->name('erd.export');
                Route::post ('erd/regenerate',        [\App\Http\Controllers\ErdController::class, 'regenerate'])   ->name('erd.regenerate');
                // T37: API 명세서 자동 생성
                Route::get  ('api-spec',                          [\App\Http\Controllers\ApiSpecController::class, 'index'])        ->name('api-spec');
                Route::post ('api-spec/generate/start',           [\App\Http\Controllers\ApiSpecController::class, 'generateStart'])->name('api-spec.generate.start');
                Route::get  ('api-spec/generate/sse/{sessionId}', [\App\Http\Controllers\ApiSpecController::class, 'generateSse']) ->name('api-spec.generate.sse');
                Route::post ('api-spec/save',                     [\App\Http\Controllers\ApiSpecController::class, 'save'])         ->name('api-spec.save');
                Route::get  ('api-spec/export',                   [\App\Http\Controllers\ApiSpecController::class, 'export'])       ->name('api-spec.export');
                Route::post ('api-spec/regenerate',               [\App\Http\Controllers\ApiSpecController::class, 'regenerate'])   ->name('api-spec.regenerate');
                // T38: RBAC 권한 모델
                Route::get    ('rbac',                          [\App\Http\Controllers\RbacController::class, 'index'])            ->name('rbac');
                Route::post   ('rbac/generate/start',           [\App\Http\Controllers\RbacController::class, 'generateStart'])    ->name('rbac.generate.start');
                Route::get    ('rbac/generate/sse/{sessionId}', [\App\Http\Controllers\RbacController::class, 'generateSse'])      ->name('rbac.generate.sse');
                Route::post   ('rbac/save',                     [\App\Http\Controllers\RbacController::class, 'save'])             ->name('rbac.save');
                Route::get    ('rbac/export',                   [\App\Http\Controllers\RbacController::class, 'export'])           ->name('rbac.export');
                Route::post   ('rbac/regenerate',               [\App\Http\Controllers\RbacController::class, 'regenerate'])       ->name('rbac.regenerate');
                Route::post   ('rbac/roles',                    [\App\Http\Controllers\RbacController::class, 'storeRole'])        ->name('rbac.roles.store');
                Route::patch  ('rbac/roles/{roleKey}',          [\App\Http\Controllers\RbacController::class, 'updateRole'])       ->name('rbac.roles.update');
                Route::delete ('rbac/roles/{roleKey}',          [\App\Http\Controllers\RbacController::class, 'destroyRole'])      ->name('rbac.roles.destroy');
                Route::post   ('rbac/permissions',              [\App\Http\Controllers\RbacController::class, 'storePermission'])  ->name('rbac.permissions.store');
                Route::patch  ('rbac/matrix',                   [\App\Http\Controllers\RbacController::class, 'updateMatrix'])     ->name('rbac.matrix.update');
                // T39: 코드 생성 프롬프트
                Route::get    ('code-prompts',                       [\App\Http\Controllers\CodeGenPromptController::class, 'index'])            ->name('code-prompts');
                Route::post   ('code-prompts/batch/start',           [\App\Http\Controllers\CodeGenPromptController::class, 'batchStart'])       ->name('code-prompts.batch.start');
                Route::get    ('code-prompts/batch/sse/{sessionId}', [\App\Http\Controllers\CodeGenPromptController::class, 'batchSse'])         ->name('code-prompts.batch.sse');
                Route::get    ('code-prompts/{screen}',              [\App\Http\Controllers\CodeGenPromptController::class, 'show'])             ->name('code-prompts.show');
                Route::post   ('code-prompts/{screen}/generate',     [\App\Http\Controllers\CodeGenPromptController::class, 'generateForScreen'])->name('code-prompts.screen.generate');
                Route::patch  ('code-prompts/{screen}',              [\App\Http\Controllers\CodeGenPromptController::class, 'update'])           ->name('code-prompts.screen.update');
                Route::delete ('code-prompts/{screen}',              [\App\Http\Controllers\CodeGenPromptController::class, 'destroy'])          ->name('code-prompts.screen.destroy');
                Route::get('ai-output',  [AiAgentController::class, 'aiOutput'])->name('ai-output');
                Route::get('validation', [AiAgentController::class, 'preDevValidation'])->name('validation');
                // T42: 개발 준비 단계 승인 게이트
                Route::get('approval',           [\App\Http\Controllers\DevPrepApprovalController::class, 'index'])    ->name('approval');
                Route::get('approval/diagnosis', [\App\Http\Controllers\DevPrepApprovalController::class, 'diagnosis'])->name('approval.diagnosis');
            });

            // 개발 단계
            Route::prefix('dev')->name('dev.')->group(function () {
                Route::get('/',           [AiAgentController::class, 'devIndex'])->name('index');
                // T40: Frontend 코드 생성
                Route::get    ('frontend-code',                         [\App\Http\Controllers\FrontendCodeController::class, 'index'])            ->name('frontend-code');
                Route::post   ('frontend-code/batch/start',             [\App\Http\Controllers\FrontendCodeController::class, 'batchStart'])       ->name('frontend-code.batch.start');
                Route::get    ('frontend-code/batch/sse/{sessionId}',   [\App\Http\Controllers\FrontendCodeController::class, 'batchSse'])         ->name('frontend-code.batch.sse');
                Route::get    ('frontend-code/download-all',            [\App\Http\Controllers\FrontendCodeController::class, 'downloadAll'])      ->name('frontend-code.download-all');
                Route::get    ('frontend-code/{screen}/preview',        [\App\Http\Controllers\FrontendCodeController::class, 'preview'])          ->name('frontend-code.screen.preview');
                Route::get    ('frontend-code/{screen}/download',       [\App\Http\Controllers\FrontendCodeController::class, 'download'])         ->name('frontend-code.screen.download');
                Route::get    ('frontend-code/{screen}',                [\App\Http\Controllers\FrontendCodeController::class, 'show'])             ->name('frontend-code.show');
                Route::post   ('frontend-code/{screen}/generate',       [\App\Http\Controllers\FrontendCodeController::class, 'generateForScreen'])->name('frontend-code.screen.generate');
                Route::patch  ('frontend-code/{screen}/files',          [\App\Http\Controllers\FrontendCodeController::class, 'updateFile'])       ->name('frontend-code.screen.files.update');
                Route::delete ('frontend-code/{screen}',                [\App\Http\Controllers\FrontendCodeController::class, 'destroy'])          ->name('frontend-code.screen.destroy');
                // T41: Output 검증
                Route::get    ('code-validation',                              [\App\Http\Controllers\CodeValidationController::class, 'index'])          ->name('code-validation');
                Route::post   ('code-validation/batch/start',                 [\App\Http\Controllers\CodeValidationController::class, 'batchStart'])      ->name('code-validation.batch.start');
                Route::get    ('code-validation/batch/sse/{sessionId}',       [\App\Http\Controllers\CodeValidationController::class, 'batchSse'])         ->name('code-validation.batch.sse');
                Route::get    ('code-validation/export',                      [\App\Http\Controllers\CodeValidationController::class, 'export'])           ->name('code-validation.export');
                Route::get    ('code-validation/{screen}',                    [\App\Http\Controllers\CodeValidationController::class, 'show'])             ->name('code-validation.show');
                Route::post   ('code-validation/{screen}/validate',           [\App\Http\Controllers\CodeValidationController::class, 'validateScreen'])   ->name('code-validation.screen.validate');
                Route::post   ('code-validation/{screen}/auto-fix',           [\App\Http\Controllers\CodeValidationController::class, 'autoFix'])          ->name('code-validation.screen.auto-fix');
                Route::post   ('code-validation/{screen}/ignore/{violationId}',[\App\Http\Controllers\CodeValidationController::class, 'ignore'])          ->name('code-validation.screen.ignore');
                Route::delete ('code-validation/{screen}',                    [\App\Http\Controllers\CodeValidationController::class, 'destroy'])          ->name('code-validation.screen.destroy');
                // T43: Backend 코드 생성
                Route::get    ('backend',                         [\App\Http\Controllers\BackendCodeController::class, 'index'])              ->name('backend');
                Route::post   ('backend/batch/start',             [\App\Http\Controllers\BackendCodeController::class, 'batchStart'])         ->name('backend.batch.start');
                Route::get    ('backend/batch/sse/{sessionId}',   [\App\Http\Controllers\BackendCodeController::class, 'batchSse'])           ->name('backend.batch.sse');
                Route::get    ('backend/download-all',            [\App\Http\Controllers\BackendCodeController::class, 'downloadAll'])        ->name('backend.download-all');
                Route::get    ('backend/{resource}/download',     [\App\Http\Controllers\BackendCodeController::class, 'download'])           ->name('backend.resource.download');
                Route::get    ('backend/{resource}',              [\App\Http\Controllers\BackendCodeController::class, 'show'])               ->name('backend.show');
                Route::post   ('backend/{resource}/generate',     [\App\Http\Controllers\BackendCodeController::class, 'generateForResource'])->name('backend.resource.generate');
                Route::patch  ('backend/{resource}/files',        [\App\Http\Controllers\BackendCodeController::class, 'updateFile'])         ->name('backend.resource.files.update');
                Route::delete ('backend/{resource}',              [\App\Http\Controllers\BackendCodeController::class, 'destroy'])            ->name('backend.resource.destroy');
                // T44: API 연계
                Route::get  ('api-connect',                  [\App\Http\Controllers\ApiIntegrationController::class, 'index'])    ->name('api-connect');
                Route::post ('api-connect/analyze',          [\App\Http\Controllers\ApiIntegrationController::class, 'analyze'])  ->name('api-connect.analyze');
                Route::get  ('api-connect/preview',          [\App\Http\Controllers\ApiIntegrationController::class, 'preview'])  ->name('api-connect.preview');
                Route::post ('api-connect/regen-files',      [\App\Http\Controllers\ApiIntegrationController::class, 'regenFiles'])->name('api-connect.regen-files');
                Route::get  ('api-connect/export',           [\App\Http\Controllers\ApiIntegrationController::class, 'export'])   ->name('api-connect.export');
                // T45: AI 코드 리뷰
                Route::get    ('code-review',                                  [\App\Http\Controllers\CodeReviewController::class, 'index'])     ->name('code-review');
                Route::post   ('code-review/start',                            [\App\Http\Controllers\CodeReviewController::class, 'batchStart']) ->name('code-review.start');
                Route::get    ('code-review/sse/{sessionId}',                  [\App\Http\Controllers\CodeReviewController::class, 'batchSse'])   ->name('code-review.sse');
                Route::get    ('code-review/system',                           [\App\Http\Controllers\CodeReviewController::class, 'system'])     ->name('code-review.system');
                Route::get    ('code-review/export',                           [\App\Http\Controllers\CodeReviewController::class, 'export'])     ->name('code-review.export');
                Route::get    ('code-review/screens/{screen}',                 [\App\Http\Controllers\CodeReviewController::class, 'show'])       ->name('code-review.screen.show');
                Route::post   ('code-review/screens/{screen}/regenerate',      [\App\Http\Controllers\CodeReviewController::class, 'regenerate']) ->name('code-review.screen.regenerate');
                Route::post   ('code-review/screens/{screen}/auto-fix',        [\App\Http\Controllers\CodeReviewController::class, 'autoFix'])    ->name('code-review.screen.auto-fix');
                Route::post   ('code-review/screens/{screen}/ignore/{findingId}',[\App\Http\Controllers\CodeReviewController::class, 'ignore'])   ->name('code-review.screen.ignore');
                // T46: AI 추가 수정
                Route::get  ('additional-fix',                           [\App\Http\Controllers\AdditionalFixController::class, 'index'])      ->name('additional-fix');
                Route::get  ('additional-fix/groups',                    [\App\Http\Controllers\AdditionalFixController::class, 'groups'])     ->name('additional-fix.groups');
                Route::post ('additional-fix/groups/{key}/fix',          [\App\Http\Controllers\AdditionalFixController::class, 'fixGroup'])   ->name('additional-fix.group.fix');
                Route::post ('additional-fix/groups/{key}/ignore',       [\App\Http\Controllers\AdditionalFixController::class, 'ignoreGroup'])->name('additional-fix.group.ignore');
                Route::post ('additional-fix/groups/{key}/manual',       [\App\Http\Controllers\AdditionalFixController::class, 'manualFixed'])->name('additional-fix.group.manual');
                Route::post ('additional-fix/batch/start',               [\App\Http\Controllers\AdditionalFixController::class, 'batchStart']) ->name('additional-fix.batch.start');
                Route::get  ('additional-fix/batch/sse/{sessionId}',     [\App\Http\Controllers\AdditionalFixController::class, 'batchSse'])   ->name('additional-fix.batch.sse');
                Route::post ('additional-fix/reverify',                  [\App\Http\Controllers\AdditionalFixController::class, 'reverify'])   ->name('additional-fix.reverify');
                Route::get  ('additional-fix/export',                    [\App\Http\Controllers\AdditionalFixController::class, 'export'])     ->name('additional-fix.export');
                // T47: 개발 단계 승인 게이트
                Route::get('approval',           [\App\Http\Controllers\DevApprovalController::class, 'index'])    ->name('approval');
                Route::get('approval/diagnosis', [\App\Http\Controllers\DevApprovalController::class, 'diagnosis'])->name('approval.diagnosis');
            });

            // 릴리즈
            Route::get('release', [AiAgentController::class, 'release'])->name('release');
            // T48: 통합 릴리즈 패키지
            Route::prefix('release/package')->name('release.package.')->group(function () {
                Route::get  ('/',         [\App\Http\Controllers\ReleasePackageController::class, 'index'])    ->name('index');
                Route::post ('/generate', [\App\Http\Controllers\ReleasePackageController::class, 'generate']) ->name('generate');
                Route::get  ('/download', [\App\Http\Controllers\ReleasePackageController::class, 'download']) ->name('download');
                Route::get  ('/manifest', [\App\Http\Controllers\ReleasePackageController::class, 'manifest']) ->name('manifest');
                Route::get  ('/preview',  [\App\Http\Controllers\ReleasePackageController::class, 'preview'])  ->name('preview');
                Route::delete('/',        [\App\Http\Controllers\ReleasePackageController::class, 'destroy'])  ->name('destroy');
            });

            // T51: 마이그레이션 가이드
            Route::prefix('release/migration-guide')->name('release.migration-guide.')->group(function () {
                Route::get   ('/',        [\App\Http\Controllers\MigrationGuideController::class, 'index'])    ->name('index');
                Route::post  ('/generate',[\App\Http\Controllers\MigrationGuideController::class, 'generate'])->name('generate');
                Route::get   ('/preview', [\App\Http\Controllers\MigrationGuideController::class, 'preview']) ->name('preview');
                Route::get   ('/export',  [\App\Http\Controllers\MigrationGuideController::class, 'export'])  ->name('export');
                Route::patch ('/',        [\App\Http\Controllers\MigrationGuideController::class, 'update'])  ->name('update');
            });

            // T50: 사용자 매뉴얼
            Route::prefix('release/user-manual')->name('release.user-manual.')->group(function () {
                Route::get   ('/',        [\App\Http\Controllers\UserManualController::class, 'index'])    ->name('index');
                Route::post  ('/generate',[\App\Http\Controllers\UserManualController::class, 'generate'])->name('generate');
                Route::get   ('/preview', [\App\Http\Controllers\UserManualController::class, 'preview']) ->name('preview');
                Route::get   ('/export',  [\App\Http\Controllers\UserManualController::class, 'export'])  ->name('export');
                Route::patch ('/',        [\App\Http\Controllers\UserManualController::class, 'update'])  ->name('update');
            });

            // T49: 배포 가이드
            Route::prefix('release/deploy-guide')->name('release.deploy-guide.')->group(function () {
                Route::get   ('/',       [\App\Http\Controllers\DeployGuideController::class, 'index'])    ->name('index');
                Route::post  ('/generate',[\App\Http\Controllers\DeployGuideController::class, 'generate'])->name('generate');
                Route::get   ('/preview',[\App\Http\Controllers\DeployGuideController::class, 'preview']) ->name('preview');
                Route::get   ('/export', [\App\Http\Controllers\DeployGuideController::class, 'export'])  ->name('export');
                Route::patch ('/',       [\App\Http\Controllers\DeployGuideController::class, 'update'])  ->name('update');
            });

            // T52: 릴리즈 단계 승인 게이트
            Route::prefix('release/approval')->name('release.approval.')->group(function () {
                Route::get('/',          [\App\Http\Controllers\ReleaseApprovalController::class, 'index'])    ->name('index');
                Route::get('/diagnosis', [\App\Http\Controllers\ReleaseApprovalController::class, 'diagnosis'])->name('diagnosis');
                Route::get('/summary',   [\App\Http\Controllers\ReleaseApprovalController::class, 'summary'])  ->name('summary');
            });

            // 공통 기능
            Route::prefix('common')->name('common.')->group(function () {
                Route::get('traceability', [AiAgentController::class, 'traceability'])->name('traceability');
                Route::get('versions',     [AiAgentController::class, 'versions'])->name('versions');
                Route::get('prompts',      [AiAgentController::class, 'commonPrompts'])->name('prompts');
                Route::get('usage',        [AiAgentController::class, 'usage'])->name('usage');
                Route::get('permissions',  [AiAgentController::class, 'permissions'])->name('permissions');
            });

            // 스트리밍 세션 (T13)
            Route::prefix('stream')->name('stream.')->group(function () {
                Route::post('start',              [AiStreamController::class, 'start'])->name('start');
                Route::get ('sse/{sessionId}',    [AiStreamController::class, 'sse'])->name('sse');
                Route::get ('{sessionId}/status', [AiStreamController::class, 'status'])->name('status');
            });

            // 버전 이력 (T14)
            Route::prefix('artifacts/{artifact}')->name('artifact.')->group(function () {
                Route::get ('versions',                    [AiVersionController::class, 'history'])->name('versions');
                Route::get ('versions/{version}',          [AiVersionController::class, 'show'])->name('version');
                Route::post('versions/{version}/restore',  [AiVersionController::class, 'restore'])->name('restore');
            });

            // 추적성 (T14)
            Route::prefix('traceability/{type}/{id}')->name('traceability.')->group(function () {
                Route::get('links',  [AiTraceabilityController::class, 'links'])->name('links');
                Route::get('impact', [AiTraceabilityController::class, 'impact'])->name('impact');
            });

            // 승인 게이트 (T12)
            Route::prefix('approvals')->name('approvals.')->group(function () {
                Route::get ('demo',           [AiAgentApprovalController::class, 'demo'])->name('demo');
                Route::post('request',        [AiAgentApprovalController::class, 'store'])->name('request');
                Route::post('{gate}/approve', [AiAgentApprovalController::class, 'approve'])->name('approve');
                Route::post('{gate}/reject',  [AiAgentApprovalController::class, 'reject'])->name('reject');
                Route::post('{gate}/cancel',  [AiAgentApprovalController::class, 'cancel'])->name('cancel');
            });

            // AI Agent 세션 (디자인 → Output 워크플로) — Phase 3 스켈레톤
            Route::prefix('agent-sessions')->name('agent-sessions.')->group(function () {
                // 작업 대시보드
                Route::get ('/',          [AgentSessionDashboardController::class, 'index'])->name('index');

                // 새 작업
                Route::get ('create',     [AgentSessionController::class, 'create'])->name('create');
                Route::post('/',          [AgentSessionController::class, 'store'])->name('store');

                // 확정 산출물 목록
                Route::get ('confirmed',  [AgentConfirmedOutputController::class, 'index'])->name('confirmed.index');

                // Agent 세션 설정
                Route::get ('settings',   [AgentSettingsController::class, 'show'])->name('settings');

                // 세션 상세 + 하위 액션
                Route::prefix('{session}')->group(function () {
                    Route::get    ('/',         [AgentSessionController::class,  'show'])->name('show');
                    Route::delete ('/',         [AgentSessionController::class,  'destroy'])->name('destroy');

                    Route::get    ('source',    [AgentSourceController::class,   'show'])->name('source');
                    Route::get    ('analysis',  [AgentAnalysisController::class, 'show'])->name('analysis');

                    Route::get    ('outputs',   [AgentOutputController::class,   'index'])->name('outputs.index');
                    Route::get    ('outputs/{output}',          [AgentOutputController::class,   'show'])->name('outputs.show');
                    Route::get    ('outputs/{output}/feedback', [AgentFeedbackController::class, 'show'])->name('outputs.feedback');

                    Route::get    ('conflicts', [AgentConflictController::class, 'index'])->name('conflicts.index');
                });
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
    Route::patch('/team/members/{member}/projects', [TeamController::class, 'updateMemberProjects'])->name('team.member.projects');
    Route::patch('/team/members/{member}/company',  [TeamController::class, 'updateMemberCompany'])->name('team.member.company');
    Route::get  ('/team/companies',                  [TeamController::class, 'listCompanies'])->name('team.companies.index');
    Route::get  ('/team/companies/search',           [TeamController::class, 'searchCompanies'])->name('team.companies.search');
    Route::post ('/team/companies',                  [TeamController::class, 'storeCompany'])->name('team.companies.store');

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
    Route::get('/memos/members', [MemoController::class, 'members'])->name('memos.members');
    Route::patch('/memos/{memo}', [MemoController::class, 'update'])->name('memos.update');
    Route::patch('/memos/{memo}/pin', [MemoController::class, 'togglePin'])->name('memos.pin');
    Route::delete('/memos/{memo}', [MemoController::class, 'destroy'])->name('memos.destroy');
    Route::post('/memos/{memo}/share', [MemoController::class, 'share'])->name('memos.share');
    Route::delete('/memos/{memo}/share', [MemoController::class, 'unshare'])->name('memos.unshare');
    Route::patch('/memo-shares/{share}/pin', [MemoController::class, 'toggleSharedPin'])->name('memo-shares.pin');

    // 빠른 프롬프트 변환
    Route::get   ('/quick-prompts',                [\App\Http\Controllers\QuickPromptController::class, 'index'])->name('quick-prompts.index');
    Route::post  ('/quick-prompts',                [\App\Http\Controllers\QuickPromptController::class, 'store'])->name('quick-prompts.store');
    Route::delete('/quick-prompts/{quickPrompt}',  [\App\Http\Controllers\QuickPromptController::class, 'destroy'])->name('quick-prompts.destroy');
    Route::patch ('/quick-prompts/{quickPrompt}/toggle-suffix', [\App\Http\Controllers\QuickPromptController::class, 'toggleSuffix'])->name('quick-prompts.toggle-suffix');

    // 프롬프트 추가 문구(접미사) 라이브러리
    Route::get   ('/prompt-suffixes',                  [\App\Http\Controllers\PromptSuffixController::class, 'index'])->name('prompt-suffixes.index');
    Route::post  ('/prompt-suffixes',                  [\App\Http\Controllers\PromptSuffixController::class, 'store'])->name('prompt-suffixes.store');
    Route::patch ('/prompt-suffixes/{promptSuffix}',   [\App\Http\Controllers\PromptSuffixController::class, 'update'])->name('prompt-suffixes.update');
    Route::delete('/prompt-suffixes/{promptSuffix}',   [\App\Http\Controllers\PromptSuffixController::class, 'destroy'])->name('prompt-suffixes.destroy');

    // 내업무 통합 대시보드
    Route::get('/my-work', [\App\Http\Controllers\MyWorkController::class, 'index'])->name('my-work.index');

    // Action 아이템
    Route::get('/action-items', [ActionItemController::class, 'index'])->name('action-items.index');
    Route::post('/action-items', [ActionItemController::class, 'store'])->name('action-items.store');
    Route::patch('/action-items/{actionItem}/toggle', [ActionItemController::class, 'toggle'])->name('action-items.toggle');
    Route::delete('/action-items/{actionItem}', [ActionItemController::class, 'destroy'])->name('action-items.destroy');

    // 회의록
    // 나의 위클리 (전체 프로젝트)
    Route::get('/my-weekly', [\App\Http\Controllers\MyWeeklyReportController::class, 'index'])->name('my-weekly.index');

    Route::prefix('meeting-minutes')->name('meeting-minutes.')->group(function () {
        Route::get('/',                      [MeetingMinuteController::class, 'index'])->name('index');
        Route::post('/',                     [MeetingMinuteController::class, 'store'])->name('store');
        Route::post('/schedule',             [MeetingMinuteController::class, 'storeSchedule'])->name('schedule.store');
        Route::post('/refine',               [MeetingMinuteController::class, 'refine'])->name('refine');
        Route::get('/{meetingMinute}',            [MeetingMinuteController::class, 'show'])->name('show');
        Route::get('/{meetingMinute}/popup',      [MeetingMinuteController::class, 'showPopup'])->name('popup');
        Route::get('/{meetingMinute}/download',   [MeetingMinuteController::class, 'downloadDocx'])->name('download');
        Route::get('/{meetingMinute}/json',       [MeetingMinuteController::class, 'getJson'])->name('json');
        Route::patch('/{meetingMinute}',     [MeetingMinuteController::class, 'update'])->name('update');
        Route::delete('/{meetingMinute}',    [MeetingMinuteController::class, 'destroy'])->name('destroy');

        Route::post('/{meetingMinute}/memos',            [MeetingMemoController::class, 'store'])->name('memos.store');
        Route::delete('/memos/{meetingMemo}',            [MeetingMemoController::class, 'destroy'])->name('memos.destroy');

        Route::post('/{meetingMinute}/action-items',              [MeetingActionItemController::class, 'store'])->name('action-items.store');
        Route::patch('/action-items/{meetingActionItem}/status',  [MeetingActionItemController::class, 'updateStatus'])->name('action-items.status');
        Route::delete('/action-items/{meetingActionItem}',        [MeetingActionItemController::class, 'destroy'])->name('action-items.destroy');

        Route::get('/{meetingMinute}/recordings/{recording}/audio',    [MeetingMinuteController::class, 'recordingAudio'])->name('recordings.audio');
        Route::get('/{meetingMinute}/recordings/{recording}/download', [MeetingMinuteController::class, 'recordingDownload'])->name('recordings.download');
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

    // Works Builder (소스 빌더)
    require __DIR__.'/works-builder.php';

    // 웍스 프롬프트
    Route::prefix('works-prompt')->name('works-prompt.')->middleware('throttle:20,1')->group(function () {
        Route::get   ('/',                              [WorksPromptController::class, 'index'])           ->name('index');
        Route::post  ('/refine',                        [WorksPromptController::class, 'refine'])          ->name('refine');
        Route::get   ('/projects/{projectId}/plan',     [WorksPromptController::class, 'projectPlan'])     ->name('project.plan');
        Route::get   ('/history',                       [WorksPromptController::class, 'history'])         ->name('history');
        Route::get   ('/history/{id}',                  [WorksPromptController::class, 'historyShow'])     ->name('history.show');
        Route::delete('/history/{id}',                  [WorksPromptController::class, 'historyDestroy'])  ->name('history.destroy');
    });

    // (관리자 패널은 별도 admin.web 미들웨어로 분리됨)
});

require __DIR__.'/auth.php';
