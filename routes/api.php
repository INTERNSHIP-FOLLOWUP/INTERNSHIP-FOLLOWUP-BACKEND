<?php

// use App\Http\Controllers\AuthController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanyDashboardController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorklogController;
// use App\Http\Controllers\AuthController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Authentication Routes (Guest Access)
|--------------------------------------------------------------------------
|
| Root-level routes match the frontend axios calls (e.g. /api/register).
| The /auth/ prefix routes are kept for backward compatibility.
*/
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

/*
|--------------------------------------------------------------------------
| Protected Authentication Routes (Requires Sanctum Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/profile/update', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| Worklog Routes (Requires Sanctum Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('worklogs')->name('worklogs.')->group(function () {
    Route::get('/', [WorklogController::class, 'index'])->name('index');
    Route::post('/', [WorklogController::class, 'store'])->name('store');
    Route::get('/{worklog}', [WorklogController::class, 'show'])->name('show');
    Route::put('/{worklog}', [WorklogController::class, 'update'])->name('update');
    Route::delete('/{worklog}', [WorklogController::class, 'destroy'])->name('destroy');
    Route::delete('/{worklog}/attachments/{attachment}', [WorklogController::class, 'destroyAttachment'])->name('attachments.destroy');
    Route::put('/{worklog}/status', [WorklogController::class, 'updateStatus'])->name('status.update');
});

/*
|--------------------------------------------------------------------------
| Company Dashboard Routes (Requires Sanctum Token & Company Role)
|--------------------------------------------------------------------------
*/
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

Route::middleware(['auth:sanctum', 'role:tutor,student'])->prefix('issues')->name('issues.')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\IssueController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\Api\IssueController::class, 'store'])->name('store');
    Route::get('/{issue}', [App\Http\Controllers\Api\IssueController::class, 'show'])->name('show');
    Route::put('/{issue}', [App\Http\Controllers\Api\IssueController::class, 'update'])->name('update');
    Route::delete('/{issue}', [App\Http\Controllers\Api\IssueController::class, 'destroy'])->name('destroy');
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

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::put('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::put('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    // Batch Management Routes
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

    Route::apiResource('students', \App\Http\Controllers\Api\StudentController::class);

    // Assignment Management Routes
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

    // Report Routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
});
