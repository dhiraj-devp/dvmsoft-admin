<?php

use App\Http\Controllers\Portal\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Portal\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Portal\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Portal\Auth\NewPasswordController;
use App\Http\Controllers\Portal\Auth\PasswordResetLinkController;
use App\Http\Controllers\Portal\Auth\VerifyEmailController;
use App\Http\Controllers\Portal\ChangeRequestController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\DocumentController;
use App\Http\Controllers\Portal\InvoiceController;
use App\Http\Controllers\Portal\NotificationController;
use App\Http\Controllers\Portal\PaymentController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\ProjectController;
use App\Http\Controllers\Portal\QuotationController;
use App\Http\Controllers\Portal\ThemeController;
use App\Http\Controllers\Portal\TicketController;
use App\Http\Middleware\EnsureClientEmailIsVerified;
use App\Http\Middleware\EnsureClientUserIsActive;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth('client')->check()
        ? redirect()->route('client.dashboard')
        : redirect()->route('client.login');
});

Route::middleware('guest:client')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::middleware(['auth:client', EnsureClientUserIsActive::class])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/theme', [ThemeController::class, 'update'])->name('theme.update');

    Route::get('/email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware(EnsureClientEmailIsVerified::class)->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::get('/projects/{project}/files/{attachment}', [ProjectController::class, 'downloadFile'])->name('projects.files.download');
        Route::get('/projects/{project}/stages/{stage}/evidence/{evidence}', [ProjectController::class, 'downloadEvidence'])->name('projects.stages.evidence.download');
        Route::get('/projects/{project}/stages/{stage}/messages/{message}/attachments/{attachment}', [ProjectController::class, 'downloadAttachment'])->name('projects.stages.attachments.download');

        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

        Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
        Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])->name('quotations.pdf');
        Route::post('/quotations/{quotation}/accept', [QuotationController::class, 'accept'])
            ->middleware('throttle:10,1')
            ->name('quotations.accept');
        Route::post('/quotations/{quotation}/reject', [QuotationController::class, 'reject'])
            ->middleware('throttle:10,1')
            ->name('quotations.reject');

        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}/pdf', [PaymentController::class, 'pdf'])->name('payments.pdf');

        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [TicketController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('tickets.store');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/replies', [TicketController::class, 'reply'])
            ->middleware('throttle:30,1')
            ->name('tickets.reply');
        Route::get('/tickets/{ticket}/messages/{message}/attachments/{attachment}', [TicketController::class, 'downloadAttachment'])
            ->name('tickets.attachments.download');

        Route::get('/change-requests', [ChangeRequestController::class, 'index'])->name('change-requests.index');
        Route::get('/change-requests/create', [ChangeRequestController::class, 'create'])->name('change-requests.create');
        Route::post('/change-requests', [ChangeRequestController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('change-requests.store');
        Route::get('/change-requests/{changeRequest}', [ChangeRequestController::class, 'show'])->name('change-requests.show');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
            ->middleware('throttle:10,1')
            ->name('profile.password');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/preferences', [NotificationController::class, 'preferences'])->name('notifications.preferences');
        Route::put('/notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');
    });
});
