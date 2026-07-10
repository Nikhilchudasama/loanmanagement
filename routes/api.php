<?php

use App\Domains\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:login');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:login');
Route::post('/payments/webhook', [\App\Domains\Payment\Controllers\PaymentController::class, 'webhook'])->middleware('throttle:payments');

// Signed payment link (no auth — validated via signed URL instead)
Route::middleware(['signed'])->group(function () {
    Route::get('/pay/{emiSchedule}', [\App\Domains\Payment\Controllers\PaymentController::class, 'securePay'])
        ->name('payments.secure-pay');
});

// Protected routes
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Tenant management
    Route::apiResource('tenants', \App\Domains\Tenant\Controllers\TenantController::class)
        ->middleware('role:super-admin');

    // Users (super-admin or admin only)
    Route::apiResource('users', \App\Domains\Auth\Controllers\UserController::class)
        ->middleware('permission:manage-tenants|approve-loans');

    // Borrowers
    Route::apiResource('borrowers', \App\Domains\Borrower\Controllers\BorrowerController::class)
        ->middleware('permission:view-borrowers|create-borrowers|edit-borrowers|delete-borrowers');

    // Loans
    Route::apiResource('loans', \App\Domains\Loan\Controllers\LoanController::class)
        ->middleware('permission:view-loans|create-loans|edit-loans');
    Route::post('loans/{loan}/approve', [\App\Domains\Loan\Controllers\LoanController::class, 'approve'])
        ->middleware('permission:approve-loans');
    Route::post('loans/{loan}/foreclose', [\App\Domains\Loan\Controllers\LoanController::class, 'foreclose'])
        ->middleware('permission:foreclose-loans');

    // EMI Schedules
    Route::get('loans/{loan}/emi-schedules', [\App\Domains\Loan\Controllers\EmiScheduleController::class, 'index'])
        ->middleware('permission:view-loans');

    // Payments
    Route::apiResource('payments', \App\Domains\Payment\Controllers\PaymentController::class)->only([
        'index', 'show',
    ])->middleware('permission:view-payments');
    Route::post('payments/initiate', [\App\Domains\Payment\Controllers\PaymentController::class, 'initiate'])
        ->middleware('permission:process-payments');

    // Dashboard
    Route::get('/dashboard', [\App\Domains\Dashboard\Controllers\DashboardController::class, 'index']);

    // Reports
    Route::get('/reports/loans', [\App\Domains\Report\Controllers\ReportController::class, 'index'])
        ->middleware('permission:view-reports');
    Route::get('/reports/loans/export', [\App\Domains\Report\Controllers\ReportController::class, 'export'])
        ->middleware('permission:export-reports');
    Route::get('/reports/loans/export-pdf', [\App\Domains\Report\Controllers\ReportController::class, 'exportPdf'])
        ->middleware('permission:export-reports');

    // Activity Logs
    Route::get('/activity-logs', [\App\Domains\ActivityLog\Controllers\ActivityLogController::class, 'index']);

    // Notifications
    Route::get('/notifications', [\App\Domains\Notification\Controllers\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [\App\Domains\Notification\Controllers\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [\App\Domains\Notification\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Domains\Notification\Controllers\NotificationController::class, 'markAllAsRead']);
});
