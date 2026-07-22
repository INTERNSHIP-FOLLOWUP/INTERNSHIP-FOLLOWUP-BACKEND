<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanyDashboardController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorklogController;
use App\Http\Controllers\Api\FollowupController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\StudentDashboardController;
// use App\Http\Controllers\AuthController;

use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Generic profile endpoints for any authenticated user
    Route::get('/profile', [AuthController::class, 'user']);
    Route::post('/profile/avatar', [AuthController::class, 'uploadAvatar']);
    Route::delete('/profile/avatar', [AuthController::class, 'removeAvatar']);
});

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('show');
    Route::match(['put', 'post'], '/update', [ProfileController::class, 'update'])->name('update');
    Route::put('/password', [ProfileController::class, 'changePassword'])->name('password');
    Route::put('/theme', [ProfileController::class, 'updateTheme'])->name('theme');
});

Route::middleware('auth:sanctum')->prefix('worklogs')->name('worklogs.')->group(function () {
    Route::get('/', [WorklogController::class, 'index'])->name('index');
    Route::post('/', [WorklogController::class, 'store'])->name('store');
    Route::get('/{worklog}', [WorklogController::class, 'show'])->name('show');
    Route::put('/{worklog}', [WorklogController::class, 'update'])->name('update');
    Route::delete('/{worklog}', [WorklogController::class, 'destroy'])->name('destroy');
    Route::delete('/{worklog}/attachments/{attachment}', [WorklogController::class, 'destroyAttachment'])->name('attachments.destroy');
    Route::put('/{worklog}/status', [WorklogController::class, 'updateStatus'])->name('status.update');
});

Route::middleware(['auth:sanctum', 'role:company'])->prefix('company')->name('company.')->group(function () {
    Route::get('/profile', [CompanyDashboardController::class, 'profile'])->name('profile');
    Route::put('/profile', [CompanyDashboardController::class, 'updateProfile'])->name('profile.update');
});

Route::middleware(['auth:sanctum', 'role:company'])->prefix('evaluations')->name('evaluations.')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\EvaluationController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\Api\EvaluationController::class, 'store'])->name('store');
    Route::get('/{evaluation}', [App\Http\Controllers\Api\EvaluationController::class, 'show'])->name('show');
    Route::put('/{evaluation}', [App\Http\Controllers\Api\EvaluationController::class, 'update'])->name('update');
    Route::delete('/{evaluation}', [App\Http\Controllers\Api\EvaluationController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth:sanctum', 'role:tutor,student,admin'])->prefix('issues')->name('issues.')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\IssueController::class, 'index'])->name('index');
    Route::get('/stats', [App\Http\Controllers\Api\IssueController::class, 'stats'])->name('stats');
    Route::post('/', [App\Http\Controllers\Api\IssueController::class, 'store'])->name('store');
    Route::get('/{issue}', [App\Http\Controllers\Api\IssueController::class, 'show'])->name('show');
    Route::put('/{issue}', [App\Http\Controllers\Api\IssueController::class, 'update'])->name('update');
    Route::delete('/{issue}', [App\Http\Controllers\Api\IssueController::class, 'destroy'])->name('destroy');
    Route::patch('/{issue}/assign', [App\Http\Controllers\Api\IssueController::class, 'assign'])->name('assign');
    Route::patch('/{issue}/resolve', [App\Http\Controllers\Api\IssueController::class, 'resolve'])->name('resolve');
});

Route::middleware(['auth:sanctum', 'role:admin,tutor,student'])->prefix('followups')->name('followups.')->group(function () {
    Route::get('/', [FollowupController::class, 'index'])->name('index');
    Route::post('/', [FollowupController::class, 'store'])->name('store');
    Route::get('/{followup}', [FollowupController::class, 'show'])->name('show');
    Route::put('/{followup}', [FollowupController::class, 'update'])->name('update');
    Route::delete('/{followup}', [FollowupController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth:sanctum', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/internship', [AssignmentController::class, 'myInternship'])->name('internship');
    Route::get('/profile', [StudentDashboardController::class, 'profile'])->name('profile');
    Route::put('/profile', [StudentDashboardController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/photo', [StudentDashboardController::class, 'uploadPhoto'])->name('profile.photo');
    Route::put('/profile/password', [StudentDashboardController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware(['auth:sanctum', 'role:admin,tutor,student'])->prefix('worklogs')->name('worklogs.')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\WorklogController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\Api\WorklogController::class, 'store'])->name('store');
    Route::get('/{worklog}', [App\Http\Controllers\Api\WorklogController::class, 'show'])->name('show');
    Route::put('/{worklog}', [App\Http\Controllers\Api\WorklogController::class, 'update'])->name('update');
    Route::delete('/{worklog}', [App\Http\Controllers\Api\WorklogController::class, 'destroy'])->name('destroy');
    Route::post('/{worklog}/attachments', [App\Http\Controllers\Api\WorklogController::class, 'uploadAttachment'])->name('attachments.upload');
    Route::delete('/attachments/{attachment}', [App\Http\Controllers\Api\WorklogController::class, 'deleteAttachment'])->name('attachments.destroy');
});

Route::middleware(['auth:sanctum', 'role:tutor'])->prefix('tutor')->name('tutor.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\TutorDashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [\App\Http\Controllers\Api\TutorProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [\App\Http\Controllers\Api\TutorProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [\App\Http\Controllers\Api\TutorProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::delete('/profile/avatar', [\App\Http\Controllers\Api\TutorProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    Route::put('/profile/password', [\App\Http\Controllers\Api\TutorProfileController::class, 'changePassword'])->name('profile.password');

    // Students
    Route::get('/students', [\App\Http\Controllers\Api\TutorStudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}', [\App\Http\Controllers\Api\TutorStudentController::class, 'show'])->name('students.show');
    Route::put('/students/{student}/status', [\App\Http\Controllers\Api\TutorStudentController::class, 'updateStatus'])->name('students.status.update');

    Route::middleware(['auth:sanctum', 'role:tutor'])->prefix('worklogs')->name('worklogs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\TutorWorklogController::class, 'index'])->name('index');

        // Frontend contract: /api/tutor/worklogs/{id}
        Route::get('/{id}', [\App\Http\Controllers\Api\TutorWorklogController::class, 'show'])->name('show');

        // Frontend contract: POST /api/tutor/worklogs/{id}
        Route::post('/{id}', [\App\Http\Controllers\Api\TutorWorklogController::class, 'review'])->name('review');
    });

    // Follow-ups
    Route::get('/followups', [\App\Http\Controllers\Api\FollowupController::class, 'index'])->name('followups.index');
    Route::post('/followups', [\App\Http\Controllers\Api\FollowupController::class, 'store'])->name('followups.store');
    Route::put('/followups/{followup}', [\App\Http\Controllers\Api\FollowupController::class, 'update'])->name('followups.update');
    Route::delete('/followups/{followup}', [\App\Http\Controllers\Api\FollowupController::class, 'destroy'])->name('followups.destroy');

    // Companies (for follow-up dropdown)
    Route::get('/companies', [\App\Http\Controllers\Api\TutorCompanyController::class, 'index'])->name('companies.index');

    // Issues
    Route::get('/issues/{id}', [\App\Http\Controllers\Api\TutorIssueController::class, 'show'])->name('issues.show');
    Route::put('/issues/{id}', [\App\Http\Controllers\Api\TutorIssueController::class, 'update'])->name('issues.update');
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::delete('/users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulk-delete');
    Route::put('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::put('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
    Route::get('/users/import/template', [UserController::class, 'importTemplate'])->name('users.import-template');
    Route::get('/users/export/excel', [UserController::class, 'exportExcel'])->name('users.export.excel');

    Route::get('/students/{id}/activity', [UserController::class, 'activity'])->name('students.activity');
    Route::get('/tutors/{id}/activity', [UserController::class, 'tutorActivity'])->name('tutors.activity');

    Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
    Route::post('/batches', [BatchController::class, 'store'])->name('batches.store');
    Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
    Route::put('/batches/{batch}', [BatchController::class, 'update'])->name('batches.update');
    Route::delete('/batches/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');
    Route::get('/batches/{batch}/statistics', [BatchController::class, 'statistics'])->name('batches.statistics');
    Route::get('/batches/{batch}/export/pdf', [BatchController::class, 'exportPdf'])->name('batches.export.pdf');
    Route::get('/batches/{batch}/export/excel', [BatchController::class, 'exportExcel'])->name('batches.export.excel');
    Route::post('/batches/seed', [BatchController::class, 'seed'])->name('batches.seed');

    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

    Route::get('/students/import/template', [\App\Http\Controllers\Api\StudentController::class, 'importTemplate'])->name('students.import-template');
    Route::post('/students/import', [\App\Http\Controllers\Api\StudentController::class, 'import'])->name('students.import');
    Route::get('/students/export/pdf', [\App\Http\Controllers\Api\StudentController::class, 'exportPdf'])->name('students.export.pdf');
    Route::get('/students/export/excel', [\App\Http\Controllers\Api\StudentController::class, 'exportExcel'])->name('students.export.excel');
    Route::apiResource('students', \App\Http\Controllers\Api\StudentController::class);

    Route::get('/assignments', [\App\Http\Controllers\Api\AssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/assignments', [\App\Http\Controllers\Api\AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('/assignments/{assignment}', [\App\Http\Controllers\Api\AssignmentController::class, 'show'])->name('assignments.show');
    Route::put('/assignments/{assignment}', [\App\Http\Controllers\Api\AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/assignments/{assignment}', [\App\Http\Controllers\Api\AssignmentController::class, 'destroy'])->name('assignments.destroy');

    // Worklog Management Routes (Admin full access)
    Route::get('/worklogs', [WorklogController::class, 'index'])->name('worklogs.index');
    Route::post('/worklogs', [WorklogController::class, 'store'])->name('worklogs.store');
    Route::get('/worklogs/{worklog}', [WorklogController::class, 'show'])->name('worklogs.show');
    Route::put('/worklogs/{worklog}', [WorklogController::class, 'update'])->name('worklogs.update');
    Route::delete('/worklogs/{worklog}', [WorklogController::class, 'destroy'])->name('worklogs.destroy');
    Route::put('/worklogs/{worklog}/status', [WorklogController::class, 'updateStatus'])->name('worklogs.status.update');
    Route::delete('/worklogs/{worklog}/attachments/{attachment}', [WorklogController::class, 'destroyAttachment'])->name('worklogs.attachments.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
});
