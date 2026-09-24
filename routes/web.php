<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SuperAdminAuthenticatedSessionController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeePhotoController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinanceDashboardController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\HrDashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OffboardingController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProjectAttachmentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDashboardController;
use App\Http\Controllers\ProjectStageFileController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesDashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupportDashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketCategoryController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketSlaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth('web')->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/login', [SuperAdminAuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [SuperAdminAuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:super-admin-login')
        ->name('login.store');

    Route::middleware(['super-admin', 'active'])->group(function () {
        Route::post('/logout', [SuperAdminAuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/settings/{section}', [SettingsController::class, 'show'])
            ->where('section', 'company|branding|localization|security|email|notifications|system|finance|hr|documents|ai|automations')
            ->name('settings.section');
    });
});

Route::middleware(['auth:web', 'active'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/theme', [ThemeController::class, 'update'])->name('theme.update');

    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('users.index');

    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view')
        ->name('roles.index');

    Route::get('/permissions', [PermissionController::class, 'index'])
        ->middleware('permission:permissions.view')
        ->name('permissions.index');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('permission:audit_logs.view')
        ->name('audit-logs.index');

    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware('permission:settings.view')
        ->name('settings.index');

    Route::get('/settings/{section}', [SettingsController::class, 'show'])
        ->middleware('permission:settings.view')
        ->where('section', 'company|branding|localization|security|email|notifications|system|finance|hr|documents|ai|automations')
        ->name('settings.section');

    Route::get('/sales', SalesDashboardController::class)
        ->middleware('permission:sales.view')
        ->name('sales.dashboard');

    Route::get('/leads', [LeadController::class, 'index'])->middleware('permission:leads.view')->name('leads.index');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->middleware('permission:leads.view')->name('leads.show');
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->middleware('permission:leads.convert')->name('leads.convert');

    Route::get('/clients', [ClientController::class, 'index'])->middleware('permission:clients.view')->name('clients.index');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->middleware('permission:clients.view')->name('clients.show');

    Route::get('/contacts', [ContactController::class, 'index'])->middleware('permission:contacts.view')->name('contacts.index');

    Route::get('/follow-ups', [FollowUpController::class, 'index'])->middleware('permission:follow_ups.view')->name('follow-ups.index');

    Route::get('/quotations', [QuotationController::class, 'index'])->middleware('permission:quotations.view')->name('quotations.index');
    Route::get('/quotations/create', [QuotationController::class, 'create'])->middleware('permission:quotations.create')->name('quotations.create');
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->middleware('permission:quotations.view')->name('quotations.show');
    Route::get('/quotations/{quotation}/edit', [QuotationController::class, 'edit'])->middleware('permission:quotations.edit')->name('quotations.edit');
    Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])->middleware('permission:quotations.view')->name('quotations.pdf');
    Route::post('/quotations/{quotation}/send', [QuotationController::class, 'send'])->middleware('permission:quotations.send')->name('quotations.send');
    Route::post('/quotations/{quotation}/accept', [QuotationController::class, 'accept'])->middleware('permission:quotations.approve')->name('quotations.accept');
    Route::post('/quotations/{quotation}/reject', [QuotationController::class, 'reject'])->middleware('permission:quotations.approve')->name('quotations.reject');
    Route::post('/quotations/{quotation}/convert', [QuotationController::class, 'convert'])->middleware('permission:quotations.convert')->name('quotations.convert');
    Route::post('/quotations/{quotation}/project', [QuotationController::class, 'createProject'])->middleware('permission:projects.create')->name('quotations.project');

    Route::get('/projects/overview', ProjectDashboardController::class)->middleware('permission:projects.view')->name('projects.dashboard');
    Route::get('/projects', [ProjectController::class, 'index'])->middleware('permission:projects.view')->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->middleware('permission:projects.create')->name('projects.create');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->middleware('permission:projects.view')->name('projects.show');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->middleware('permission:projects.edit')->name('projects.edit');
    Route::get('/projects/{project}/files/{attachment}', [ProjectAttachmentController::class, 'download'])->middleware('permission:projects.view')->name('projects.files.download');
    Route::get('/projects/{project}/stages/{stage}/evidence/{evidence}', [ProjectStageFileController::class, 'evidence'])->middleware('permission:projects.stage_evidence.view')->name('projects.stages.evidence.download');
    Route::get('/projects/{project}/stages/{stage}/messages/{message}/attachments/{attachment}', [ProjectStageFileController::class, 'attachment'])->middleware('permission:projects.stage_discussion.view')->name('projects.stages.attachments.download');
    Route::post('/projects/{project}/invoice', [InvoiceController::class, 'fromProject'])->middleware('permission:invoices.create')->name('projects.invoice');

    Route::get('/tasks', [TaskController::class, 'index'])->middleware('permission:tasks.view')->name('tasks.index');
    Route::get('/milestones', [MilestoneController::class, 'index'])->middleware('permission:milestones.view')->name('milestones.index');

    Route::get('/finance', FinanceDashboardController::class)->middleware('permission:finance.dashboard.view')->name('finance.dashboard');
    Route::get('/finance/outstanding', [InvoiceController::class, 'outstanding'])->middleware('permission:invoices.view')->name('finance.outstanding');
    Route::get('/finance/reports', [InvoiceController::class, 'reports'])->middleware('permission:finance.reports.view')->name('finance.reports');

    Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('permission:invoices.view')->name('invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->middleware('permission:invoices.create')->name('invoices.create');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:invoices.view')->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->middleware('permission:invoices.edit')->name('invoices.edit');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->middleware('permission:invoices.view')->name('invoices.pdf');
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->middleware('permission:invoices.send')->name('invoices.send');
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->middleware('permission:invoices.edit')->name('invoices.cancel');

    Route::get('/payments', [PaymentController::class, 'index'])->middleware('permission:payments.view')->name('payments.index');
    Route::get('/payments/{payment}/pdf', [PaymentController::class, 'pdf'])->middleware('permission:payments.view')->name('payments.pdf');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->middleware('permission:payments.view')->name('payments.receipt');

    Route::get('/expenses', [ExpenseController::class, 'index'])->middleware('permission:expenses.view')->name('expenses.index');
    Route::get('/expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])->middleware('permission:expenses.view')->name('expenses.receipt');

    Route::get('/hr', HrDashboardController::class)->middleware('permission:hr.dashboard.view')->name('hr.dashboard');
    Route::get('/employees', [EmployeeController::class, 'index'])->middleware('permission:employees.view')->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->middleware('permission:employees.create')->name('employees.create');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->middleware('permission:employees.view')->name('employees.show');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->middleware('permission:employees.edit')->name('employees.edit');
    Route::get('/employees/{employee}/photo', EmployeePhotoController::class)->middleware('permission:employees.view')->name('employees.photo');
    Route::get('/employees/{employee}/documents/{document}/download', [EmployeeDocumentController::class, 'download'])->middleware('permission:employee_documents.view')->name('employees.documents.download');
    Route::get('/leave', [LeaveController::class, 'index'])->middleware('permission:leave.view')->name('leave.index');
    Route::get('/assets', [AssetController::class, 'index'])->middleware('permission:assets.view')->name('assets.index');
    Route::get('/onboarding', [OnboardingController::class, 'index'])->middleware('permission:onboarding.view')->name('onboarding.index');
    Route::get('/offboarding', [OffboardingController::class, 'index'])->middleware('permission:offboarding.view')->name('offboarding.index');

    Route::get('/support', SupportDashboardController::class)->middleware('permission:support.dashboard.view')->name('support.dashboard');
    Route::get('/tickets', [TicketController::class, 'index'])->middleware('permission:tickets.view')->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->middleware('permission:tickets.create')->name('tickets.create');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->middleware('permission:tickets.view')->name('tickets.show');
    Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->middleware('permission:tickets.edit')->name('tickets.edit');
    Route::get('/tickets/{ticket}/messages/{message}/attachments/{attachment}/download', [TicketAttachmentController::class, 'download'])->middleware('permission:tickets.view')->name('tickets.attachments.download');
    Route::get('/ticket-categories', [TicketCategoryController::class, 'index'])->middleware('permission:tickets.manage_categories')->name('ticket-categories.index');
    Route::get('/ticket-sla', [TicketSlaController::class, 'index'])->middleware('permission:tickets.manage_sla')->name('ticket-sla.index');

    Route::get('/documents/overview', [DocumentController::class, 'dashboard'])->middleware('permission:documents.view')->name('documents.dashboard');
    Route::get('/documents', [DocumentController::class, 'index'])->middleware('permission:documents.view')->name('documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->middleware('permission:documents.create')->name('documents.create');
    Route::get('/documents/expiring', [DocumentController::class, 'expiring'])->middleware('permission:documents.view')->name('documents.expiring');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->middleware('permission:documents.view')->name('documents.show');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->middleware('permission:documents.edit')->name('documents.edit');
    Route::get('/documents/{document}/versions/{version}/download', [DocumentVersionController::class, 'download'])->middleware('permission:documents.download')->name('documents.versions.download');
    Route::get('/document-types', [DocumentTypeController::class, 'index'])->middleware('permission:documents.manage_types')->name('document-types.index');

    Route::get('/reports', [ReportController::class, 'overview'])->middleware('permission:reports.view')->name('reports.overview');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->middleware('permission:reports.sales.view')->name('reports.sales');
    Route::get('/reports/projects', [ReportController::class, 'projects'])->middleware('permission:reports.projects.view')->name('reports.projects');
    Route::get('/reports/finance', [ReportController::class, 'finance'])->middleware('permission:reports.finance.view')->name('reports.finance');
    Route::get('/reports/hr', [ReportController::class, 'hr'])->middleware('permission:reports.hr.view')->name('reports.hr');
    Route::get('/reports/support', [ReportController::class, 'support'])->middleware('permission:reports.support.view')->name('reports.support');
    Route::get('/reports/{section}/export', [ReportController::class, 'export'])->middleware('permission:reports.export')->name('reports.export');

    Route::get('/ai/overview', [AiController::class, 'overview'])->middleware('permission:ai.overview.view')->name('ai.overview');
    Route::get('/ai/finance', [AiController::class, 'finance'])->middleware('permission:ai.finance.use')->name('ai.finance');

    Route::get('/automations', [AutomationController::class, 'index'])->middleware('permission:automations.view')->name('automations.index');
    Route::get('/automations/{automation}', [AutomationController::class, 'show'])->middleware('permission:automations.view')->name('automations.show');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/preferences', [NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::put('/notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');
});
