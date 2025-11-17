<?php

use Illuminate\Support\Facades\Route;
use App\Jobs\SendOtpJob;
use App\Http\Controllers\AdminAuthController;
use App\Http\Middleware\{RedirectIfAuthenticated, Check2FAUserSession};
use App\Constants\AppConstants;
use Carbon\Carbon;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FailedOperationsController;
use App\Http\Controllers\LogController;


// Route::get('/test', function() {
//     return Carbon::createFromFormat('Y-m-d', '2024-02-25')->format('Y-m-d');
// });
Route::get('/', function () {
    return redirect()->route('index'); // Use route() to redirect to a named route
});

// Group for guest users (login & forgot password)
Route::prefix('admin')->middleware('guest')->group(function () {
    Route::view('/login', 'pages.auth-login-basic')->name('login');
    Route::view('/auth-forgot-password-basic', 'pages.auth-forgot-password-basic')->name('auth-forgot-password-basic');
    Route::post('/send-password-reset-link', [AdminAuthController::class, 'sendPasswordResetLink'])->name('admin.send-password-reset-link');
    Route::get('password/reset/{token}', [AdminAuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('password/reset', [AdminAuthController::class, 'reset'])->name('password.update');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
    Route::get('/2fa', [AdminAuthController::class, 'show2faForm'])->name('admin.2fa.verify')->middleware([Check2FAUserSession::class]);
    Route::post('/2fa', [AdminAuthController::class, 'verify2fa'])->name('admin.2fa.verify.post');
    Route::post('/2fa/resend', [AdminAuthController::class, 'resend2faCode'])->name('admin.2fa.resend');
})->middleware([RedirectIfAuthenticated::class]);

Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Grouped admin routes with 'auth' middleware
Route::prefix('admin')->middleware('auth')->group(function () {
    // Home Page
    Route::get('/', function () {
        return view('pages.index');
    })->name('index');

    Route::get('/profile', [AdminAuthController::class, 'getProfilePage'])->name('admin.profile');

    Route::post('/generate-password-reset-token', [AdminAuthController::class, 'generatePasswordResetToken'])->name('admin.generate-password-update-token');
    Route::post('/update-admin-password', [AdminAuthController::class, 'updateAdminPassword'])->name('admin.update-admin-password');
    Route::post('/admin/profile/update', [AdminAuthController::class, 'updateProfile'])->name('admin.profile.update');
    Route::post('/admin/profile/update-two-factor-authentication', [AdminAuthController::class, 'updateAdminTwoFactor'])->name('admin.profile.update-two-factor-authentication');


    // Route::get('/leads', function () {
    //     return view('pages.leads');
    // })->name('leads');

    Route::get('/leads', [LeadsController::class, 'index'])->name('leads.index');
    Route::get('/leads/{id}', [LeadsController::class, 'show'])->name('leads.show');
    Route::post('/leads-report', [LeadsController::class, 'leadsReport'])->name('leads.report');
    Route::post('/leads/schedule-export', [ExportController::class, 'scheduleLeadExport'])->name('leads.export');
    Route::post('/save-lead-field-setting', [LeadsController::class, 'saveLeadFieldSetting'])->name('save.lead.field.setting');
    Route::get('/reset-lead-field-setting', [LeadsController::class, 'resetLeadFieldSetting'])->name('reset.lead.field.setting');

    Route::get('/exports-listing', [ExportController::class, 'exportsListing'])->name('leads.export.exports-listing');
    Route::get('/exports-files-listing', [ExportController::class, 'exportsFilesListing'])->name('leads.export.exports-files-listing');
    Route::get('/export-schedule-details', [ExportController::class, 'exportScheduleDetails'])->name('export.schedule.details');
    Route::post('/export-schedule-status-update', [ExportController::class, 'exportScheduleStatusUpdate'])->name('export.schedule.status-update');
    Route::post('/delete-export-schedule', [ExportController::class, 'deleteExportSchedule'])->name('delete.export.schedule');
    Route::get('/download-export-file/{exportFileId}', [ExportController::class, 'downloadExportFile'])
    ->name('export.download.file');
    Route::delete('/exports-files/delete/{id}', [ExportController::class, 'deleteExportFile'])->name('export.export-files.delete');
    Route::get('/export-schedule-options-data', [ExportController::class, 'exportScheduleOptionData'])->name('export.schedule.options-data');

    Route::get('/failed-operations/{type}', [FailedOperationsController::class, 'getFailedOperationsList'])
    ->whereIn('type', ['failed-jobs', 'system-logs', 'failed-dispatch', 'system-failed-logs', 'export-logs'])
    ->name('failed-operations.list');

    Route::get('/failed-jobs/list-data', [FailedOperationsController::class, 'fetchFailedJobs'])->name('failed-operations.failed-jobs.list-data');
    Route::post('/failed-jobs/data', [FailedOperationsController::class, 'fetchFailedJobDataById'])->name('failed-operations.failed-job.get-data-by-id');
    Route::post('/failed-jobs/retry', [FailedOperationsController::class, 'retryFailedJob'])->name('failed-operations.failed-jobs.retry');
    Route::post('/failed-jobs/delete', [FailedOperationsController::class, 'deleteFailedJob'])->name('failed-operations.failed-jobs.delete');

    Route::get('/failed-logs/list', [FailedOperationsController::class, 'fetchSystemLogs'])->name('failed-operations.failed-logs.list');
    Route::get('/system-failed-logs/list', [FailedOperationsController::class, 'fetchSystemFailedLogs'])->name('failed-operations.system-failed-logs.list');
    Route::get('/export-logs/list', [FailedOperationsController::class, 'fetchExportLogs'])->name('failed-operations.export-logs.list');

    Route::get('/failed-dispatch/list-data', [FailedOperationsController::class, 'fetchFailedDispatchLeads'])->name('failed-operations.failed-dispatch.list');
    Route::post('/failed-dispatch/data', [FailedOperationsController::class, 'fetchFailedDispatchLeadDataById'])->name('failed-operations.failed-dispatch.get-data');
    Route::post('/failed-dispatch/retry', [FailedOperationsController::class, 'retryFailedDispatchLeadDataById'])->name('failed-operations.failed-dispatch.retry');

    Route::get('/logs/{type}/download/{filename}', [FailedOperationsController::class, 'downloadLogFile'])->name('failed-operations.logs.download');
    Route::get('/logs/{type}/delete/{filename}', [FailedOperationsController::class, 'deleteLogFile'])->name('failed-operations.logs.delete');

    // Authentication Pages
    Route::get('/auth-register-basic', function () {
        return view('pages.auth-register-basic');
    })->name('auth-register-basic');

    Route::get('/view/logs', [LogController::class, 'viewAdminLogs'])->name('view.admin.logs');
});

Route::view('error', 'pages.pages-misc-error');
Route::view('maintainance', 'pages.maintenance');

Route::get('/generate-token', [LogController::class, 'generateToken'])->name('generate.token');
Route::get('/logs/{token}', [LogController::class, 'viewLogs'])->name('view.logs');

// Route::fallback(function () {
//     return redirect()->route('login');  // Redirect to login if no route matches
// });