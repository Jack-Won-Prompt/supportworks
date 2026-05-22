<?php

use App\Http\Controllers\WorksBuilder\AiCallLogController;
use App\Http\Controllers\WorksBuilder\AiProgressController;
use App\Http\Controllers\WorksBuilder\ChecklistController;
use App\Http\Controllers\WorksBuilder\NgInputController;
use App\Http\Controllers\WorksBuilder\NotificationController;
use App\Http\Controllers\WorksBuilder\OptionController;
use App\Http\Controllers\WorksBuilder\OutputPackageController;
use App\Http\Controllers\WorksBuilder\PreviewController;
use App\Http\Controllers\WorksBuilder\ResultConfirmController;
use App\Http\Controllers\WorksBuilder\ReviewController;
use App\Http\Controllers\WorksBuilder\SpecReviewController;
use App\Http\Controllers\WorksBuilder\TaskCancelController;
use App\Http\Controllers\WorksBuilder\TaskCloneController;
use App\Http\Controllers\WorksBuilder\TaskController;
use App\Http\Controllers\WorksBuilder\TaskReopenController;
use Illuminate\Support\Facades\Route;

Route::prefix('works-builder')->name('wb.')->group(function () {
    Route::get   ('/',                [TaskController::class, 'index'])->name('tasks.index');
    Route::get   ('/tasks/new',       [TaskController::class, 'create'])->name('tasks.create');
    Route::post  ('/tasks',           [TaskController::class, 'start'])->name('tasks.start');
    Route::get   ('/tasks/completed', [TaskController::class, 'completed'])->name('tasks.completed');
    Route::get   ('/tasks/{task}',    [TaskController::class, 'show'])->name('tasks.show');

    Route::post  ('/tasks/{task}/reopen',  [TaskReopenController::class, 'store'])->name('tasks.reopen');
    Route::post  ('/tasks/{task}/clone',   [TaskCloneController::class,  'store'])->name('tasks.clone');
    Route::post  ('/tasks/{task}/cancel',  [TaskCancelController::class, 'store'])->name('tasks.cancel');

    Route::prefix('tasks/{task}/options')->name('tasks.options.')->group(function () {
        Route::get ('/',        [OptionController::class, 'edit'])->name('edit');
        Route::put ('/',        [OptionController::class, 'update'])->name('update');
        Route::post('/preview', [OptionController::class, 'previewJson'])->name('preview');
    });

    Route::get('/tasks/{task}/preview.svg', [PreviewController::class, 'svg'])->name('tasks.preview.svg');

    Route::prefix('tasks/{task}/spec-review')->name('tasks.spec-review.')->group(function () {
        Route::get ('/',        [SpecReviewController::class, 'show'])->name('show');
        Route::post('/confirm', [SpecReviewController::class, 'confirm'])->name('confirm');
    });

    Route::prefix('tasks/{task}/ai-progress')->name('tasks.ai-progress.')->group(function () {
        Route::get  ('/',         [AiProgressController::class, 'show'])->name('show');
        Route::get  ('/status',   [AiProgressController::class, 'status'])->name('status');
        Route::post ('/cancel',   [AiProgressController::class, 'cancel'])->name('cancel');
    });

    Route::prefix('tasks/{task}/result-confirm')->name('tasks.result-confirm.')->group(function () {
        Route::get ('/',                [ResultConfirmController::class, 'show'])->name('show');
        Route::post('/{html}/decide',   [ResultConfirmController::class, 'decide'])->name('decide');
    });

    Route::prefix('tasks/{task}/review')->name('tasks.review.')->group(function () {
        Route::get ('/sessions/{session}', [ReviewController::class, 'show'])->name('show');
        Route::post('/sessions/{session}', [ReviewController::class, 'decide'])->name('decide');
    });

    Route::prefix('tasks/{task}/ng-input/{session}')->name('tasks.ng-input.')->group(function () {
        Route::get ('/', [NgInputController::class, 'create'])->name('create');
        Route::post('/', [NgInputController::class, 'store'])->name('store');
    });

    Route::prefix('tasks/{task}/package')->name('tasks.package.')->group(function () {
        Route::get ('/html',     [OutputPackageController::class, 'downloadHtml'])->name('html');
        Route::get ('/download', [OutputPackageController::class, 'download'])->name('download');
        Route::post('/rebuild',  [OutputPackageController::class, 'rebuild'])->name('rebuild');
    });

    // 사이드바 진입점 — 첫 접근 가능 프로젝트로 자동 redirect
    Route::get('/checklists', [ChecklistController::class, 'entry'])->name('checklists.entry');

    Route::prefix('projects/{project}/checklists')->name('checklists.')->group(function () {
        Route::get   ('/',              [ChecklistController::class, 'index'])->name('index');
        Route::post  ('/',              [ChecklistController::class, 'store'])->name('store');
        Route::patch ('/{item}/toggle', [ChecklistController::class, 'toggle'])->name('toggle');
    });

    Route::get('/ai-call-logs', [AiCallLogController::class, 'index'])->name('ai-call-logs.index');

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get   ('/',                       [NotificationController::class, 'index'])->name('index');
        Route::get   ('/unread-count',           [NotificationController::class, 'unreadCountJson'])->name('unread-count');
        Route::post  ('/{notification}/read',    [NotificationController::class, 'markRead'])->name('read');
        Route::post  ('/mark-all-read',          [NotificationController::class, 'markAllRead'])->name('mark-all-read');
        Route::delete('/{notification}',         [NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/',                       [NotificationController::class, 'destroyAll'])->name('destroy-all');
    });
});
