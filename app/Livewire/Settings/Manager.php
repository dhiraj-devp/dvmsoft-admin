<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Manager extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public string $section = 'company';

    public string $company_name = '';

    public string $company_email = '';

    public string $company_phone = '';

    public string $company_website = '';

    public string $company_address = '';

    public string $company_gst_number = '';

    public string $company_pan_number = '';

    public $logo = null;

    public $favicon = null;

    public string $company_currency = 'INR';

    public string $company_timezone = 'Asia/Kolkata';

    public string $company_date_format = 'd M Y';

    public string $email_from_name = '';

    public string $email_from_address = '';

    public bool $notifications_email_enabled = true;

    public bool $notifications_in_app_enabled = true;

    public int $security_session_timeout = 120;

    public int $security_login_max_attempts = 5;

    public bool $security_require_strong_passwords = true;

    public string $system_maintenance_message = '';

    public string $finance_invoice_prefix = 'INV';

    public string $finance_expense_prefix = 'EXP';

    public string $finance_default_tax_percent = '18';

    public int $finance_invoice_due_days = 15;

    public string $finance_bank_name = '';

    public string $finance_bank_account_name = '';

    public string $finance_bank_account_number = '';

    public string $finance_bank_ifsc = '';

    public string $finance_upi_id = '';

    public string $finance_payment_instructions = '';

    public int $finance_reminder_days_before_due = 3;

    public int $finance_reminder_days_after_due = 1;

    public string $hr_employee_prefix = 'EMP';

    public int $hr_probation_days = 90;

    public int $hr_annual_leave_days = 18;

    public string $documents_prefix = 'DOC';

    public int $documents_expiry_warning_days = 30;

    public string $documents_version_format = 'v{n}';

    public bool $ai_enabled = false;

    public string $ai_provider = 'openai';

    public string $ai_model = 'gpt-4o-mini';

    public int $ai_timeout = 20;

    public int $ai_max_tokens = 1200;

    public bool $automations_enabled = true;

    public int $automations_lead_follow_up_upcoming_days = 1;

    public int $automations_quotation_follow_up_days = 3;

    public int $automations_milestone_upcoming_days = 3;

    public int $automations_project_deadline_days = 7;

    public int $automations_hr_joining_days = 7;

    public int $automations_hr_exit_days = 7;

    public int $automations_hr_document_expiry_days = 30;

    public string $projects_default_client_approval_mode = 'flexible';

    public function mount(string $section = 'company'): void
    {
        $this->authorize('viewAny', Setting::class);
        $this->section = $section;
        $this->fillFromSettings();
    }

    public function save(SettingsService $settings, AuditLogger $audit): void
    {
        $this->authorize('update', Setting::class);

        $rules = $this->rulesForSection();
        $this->validate($rules);

        $before = $settings->all();

        match ($this->section) {
            'company' => $settings->put([
                'company.name' => $this->company_name,
                'company.email' => $this->company_email,
                'company.phone' => $this->company_phone,
                'company.website' => $this->company_website,
                'company.address' => $this->company_address,
                'company.gst_number' => $this->company_gst_number,
                'company.pan_number' => $this->company_pan_number,
            ]),
            'branding' => $this->saveBranding($settings),
            'localization' => $settings->put([
                'company.currency' => strtoupper($this->company_currency),
                'company.timezone' => $this->company_timezone,
                'company.date_format' => $this->company_date_format,
            ]),
            'email' => $settings->put([
                'email.from_name' => $this->email_from_name,
                'email.from_address' => $this->email_from_address,
            ]),
            'notifications' => $settings->put([
                'notifications.email_enabled' => $this->notifications_email_enabled,
                'notifications.in_app_enabled' => $this->notifications_in_app_enabled,
            ]),
            'security' => $settings->put([
                'security.session_timeout' => $this->security_session_timeout,
                'security.login_max_attempts' => $this->security_login_max_attempts,
                'security.require_strong_passwords' => $this->security_require_strong_passwords,
            ]),
            'system' => $settings->put([
                'system.maintenance_message' => $this->system_maintenance_message,
            ]),
            'finance' => $settings->put([
                'finance.invoice_prefix' => strtoupper($this->finance_invoice_prefix),
                'finance.expense_prefix' => strtoupper($this->finance_expense_prefix),
                'finance.default_tax_percent' => $this->finance_default_tax_percent,
                'finance.invoice_due_days' => $this->finance_invoice_due_days,
                'finance.bank_name' => $this->finance_bank_name,
                'finance.bank_account_name' => $this->finance_bank_account_name,
                'finance.bank_account_number' => $this->finance_bank_account_number,
                'finance.bank_ifsc' => $this->finance_bank_ifsc,
                'finance.upi_id' => $this->finance_upi_id,
                'finance.payment_instructions' => $this->finance_payment_instructions,
                'finance.reminder_days_before_due' => $this->finance_reminder_days_before_due,
                'finance.reminder_days_after_due' => $this->finance_reminder_days_after_due,
            ]),
            'hr' => $settings->put([
                'hr.employee_prefix' => strtoupper($this->hr_employee_prefix),
                'hr.probation_days' => $this->hr_probation_days,
                'hr.annual_leave_days' => $this->hr_annual_leave_days,
            ]),
            'documents' => $settings->put([
                'documents.prefix' => strtoupper($this->documents_prefix),
                'documents.expiry_warning_days' => $this->documents_expiry_warning_days,
                'documents.version_format' => $this->documents_version_format,
            ]),
            'ai' => $settings->put([
                'ai.enabled' => $this->ai_enabled,
                'ai.provider' => $this->ai_provider,
                'ai.model' => $this->ai_model,
                'ai.timeout' => $this->ai_timeout,
                'ai.max_tokens' => $this->ai_max_tokens,
            ]),
            'automations' => $settings->put([
                'automations.enabled' => $this->automations_enabled,
                'automations.lead_follow_up_upcoming_days' => $this->automations_lead_follow_up_upcoming_days,
                'automations.quotation_follow_up_days' => $this->automations_quotation_follow_up_days,
                'automations.milestone_upcoming_days' => $this->automations_milestone_upcoming_days,
                'automations.project_deadline_days' => $this->automations_project_deadline_days,
                'automations.hr_joining_days' => $this->automations_hr_joining_days,
                'automations.hr_exit_days' => $this->automations_hr_exit_days,
                'automations.hr_document_expiry_days' => $this->automations_hr_document_expiry_days,
            ]),
            'projects' => $settings->put([
                'projects.default_client_approval_mode' => $this->projects_default_client_approval_mode,
            ]),
            default => null,
        };

        $audit->record(
            action: 'updated',
            module: 'settings',
            oldValues: ['section' => $this->section],
            newValues: ['section' => $this->section, 'keys' => array_keys($rules)],
        );

        $this->dispatch('notify', type: 'success', message: 'Settings saved.');
    }

    public function removeFile(string $key, SettingsService $settings): void
    {
        $this->authorize('update', Setting::class);

        $path = $settings->get($key);
        if (is_string($path) && $path !== '') {
            Storage::disk(config('settings.branding_disk'))->delete($path);
        }

        $settings->set($key, '');
        $this->dispatch('notify', type: 'success', message: 'File removed.');
    }

    public function render(): View
    {
        return view('livewire.settings.manager', [
            'tabs' => [
                'company' => 'Company',
                'branding' => 'Branding',
                'localization' => 'Localization',
                'email' => 'Email',
                'notifications' => 'Notifications',
                'security' => 'Security',
                'system' => 'System',
                'finance' => 'Finance',
                'hr' => 'HR',
                'documents' => 'Documents',
                'ai' => 'AI',
                'automations' => 'Automations',
                'projects' => 'Projects',
            ],
            'logoUrl' => settings()->fileUrl('company.logo'),
            'faviconUrl' => settings()->fileUrl('company.favicon'),
            'timezones' => \DateTimeZone::listIdentifiers(),
            'aiKeyConfigured' => filled(config('ai.api_key')),
        ]);
    }

    protected function fillFromSettings(): void
    {
        $this->company_name = (string) settings('company.name', '');
        $this->company_email = (string) settings('company.email', '');
        $this->company_phone = (string) settings('company.phone', '');
        $this->company_website = (string) settings('company.website', '');
        $this->company_address = (string) settings('company.address', '');
        $this->company_gst_number = (string) settings('company.gst_number', '');
        $this->company_pan_number = (string) settings('company.pan_number', '');
        $this->company_currency = (string) settings('company.currency', 'INR');
        $this->company_timezone = (string) settings('company.timezone', 'Asia/Kolkata');
        $this->company_date_format = (string) settings('company.date_format', 'd M Y');
        $this->email_from_name = (string) settings('email.from_name', '');
        $this->email_from_address = (string) settings('email.from_address', '');
        $this->notifications_email_enabled = (bool) settings('notifications.email_enabled', true);
        $this->notifications_in_app_enabled = (bool) settings('notifications.in_app_enabled', true);
        $this->security_session_timeout = (int) settings('security.session_timeout', 120);
        $this->security_login_max_attempts = (int) settings('security.login_max_attempts', 5);
        $this->security_require_strong_passwords = (bool) settings('security.require_strong_passwords', true);
        $this->system_maintenance_message = (string) settings('system.maintenance_message', '');
        $this->finance_invoice_prefix = (string) settings('finance.invoice_prefix', 'INV');
        $this->finance_expense_prefix = (string) settings('finance.expense_prefix', 'EXP');
        $this->finance_default_tax_percent = (string) settings('finance.default_tax_percent', '18');
        $this->finance_invoice_due_days = (int) settings('finance.invoice_due_days', 15);
        $this->finance_bank_name = (string) settings('finance.bank_name', '');
        $this->finance_bank_account_name = (string) settings('finance.bank_account_name', '');
        $this->finance_bank_account_number = (string) settings('finance.bank_account_number', '');
        $this->finance_bank_ifsc = (string) settings('finance.bank_ifsc', '');
        $this->finance_upi_id = (string) settings('finance.upi_id', '');
        $this->finance_payment_instructions = (string) settings('finance.payment_instructions', '');
        $this->finance_reminder_days_before_due = (int) settings('finance.reminder_days_before_due', 3);
        $this->finance_reminder_days_after_due = (int) settings('finance.reminder_days_after_due', 1);
        $this->hr_employee_prefix = (string) settings('hr.employee_prefix', 'EMP');
        $this->hr_probation_days = (int) settings('hr.probation_days', 90);
        $this->hr_annual_leave_days = (int) settings('hr.annual_leave_days', 18);
        $this->documents_prefix = (string) settings('documents.prefix', 'DOC');
        $this->documents_expiry_warning_days = (int) settings('documents.expiry_warning_days', 30);
        $this->documents_version_format = (string) settings('documents.version_format', 'v{n}');
        $this->ai_enabled = (bool) settings('ai.enabled', false);
        $this->ai_provider = (string) settings('ai.provider', config('ai.provider', 'openai'));
        $this->ai_model = (string) settings('ai.model', config('ai.model', 'gpt-4o-mini'));
        $this->ai_timeout = (int) settings('ai.timeout', config('ai.timeout', 20));
        $this->ai_max_tokens = (int) settings('ai.max_tokens', config('ai.max_tokens', 1200));
        $this->automations_enabled = (bool) settings('automations.enabled', true);
        $this->automations_lead_follow_up_upcoming_days = (int) settings('automations.lead_follow_up_upcoming_days', 1);
        $this->automations_quotation_follow_up_days = (int) settings('automations.quotation_follow_up_days', 3);
        $this->automations_milestone_upcoming_days = (int) settings('automations.milestone_upcoming_days', 3);
        $this->automations_project_deadline_days = (int) settings('automations.project_deadline_days', 7);
        $this->automations_hr_joining_days = (int) settings('automations.hr_joining_days', 7);
        $this->automations_hr_exit_days = (int) settings('automations.hr_exit_days', 7);
        $this->automations_hr_document_expiry_days = (int) settings('automations.hr_document_expiry_days', 30);
        $this->projects_default_client_approval_mode = (string) settings('projects.default_client_approval_mode', 'flexible');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesForSection(): array
    {
        return match ($this->section) {
            'company' => [
                'company_name' => ['required', 'string', 'max:255'],
                'company_email' => ['required', 'email', 'max:255'],
                'company_phone' => ['nullable', 'string', 'max:50'],
                'company_website' => ['nullable', 'url', 'max:255'],
                'company_address' => ['nullable', 'string', 'max:1000'],
                'company_gst_number' => ['nullable', 'string', 'max:50'],
                'company_pan_number' => ['nullable', 'string', 'max:20'],
            ],
            'branding' => [
                'logo' => ['nullable', 'image', 'max:2048'],
                'favicon' => ['nullable', 'file', 'max:512', 'mimes:png,ico,jpg,jpeg,webp,svg'],
            ],
            'localization' => [
                'company_currency' => ['required', 'string', 'size:3'],
                'company_timezone' => ['required', 'timezone'],
                'company_date_format' => ['required', Rule::in(['d M Y', 'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y'])],
            ],
            'email' => [
                'email_from_name' => ['required', 'string', 'max:255'],
                'email_from_address' => ['required', 'email', 'max:255'],
            ],
            'notifications' => [
                'notifications_email_enabled' => ['boolean'],
                'notifications_in_app_enabled' => ['boolean'],
            ],
            'security' => [
                'security_session_timeout' => ['required', 'integer', 'min:15', 'max:1440'],
                'security_login_max_attempts' => ['required', 'integer', 'min:3', 'max:20'],
                'security_require_strong_passwords' => ['boolean'],
            ],
            'system' => [
                'system_maintenance_message' => ['nullable', 'string', 'max:500'],
            ],
            'finance' => [
                'finance_invoice_prefix' => ['required', 'string', 'max:10'],
                'finance_expense_prefix' => ['required', 'string', 'max:10'],
                'finance_default_tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
                'finance_invoice_due_days' => ['required', 'integer', 'min:0', 'max:365'],
                'finance_bank_name' => ['nullable', 'string', 'max:255'],
                'finance_bank_account_name' => ['nullable', 'string', 'max:255'],
                'finance_bank_account_number' => ['nullable', 'string', 'max:50'],
                'finance_bank_ifsc' => ['nullable', 'string', 'max:50'],
                'finance_upi_id' => ['nullable', 'string', 'max:100'],
                'finance_payment_instructions' => ['nullable', 'string', 'max:2000'],
                'finance_reminder_days_before_due' => ['required', 'integer', 'min:0', 'max:60'],
                'finance_reminder_days_after_due' => ['required', 'integer', 'min:1', 'max:60'],
            ],
            'hr' => [
                'hr_employee_prefix' => ['required', 'string', 'max:10'],
                'hr_probation_days' => ['required', 'integer', 'min:0', 'max:365'],
                'hr_annual_leave_days' => ['required', 'integer', 'min:0', 'max:365'],
            ],
            'documents' => [
                'documents_prefix' => ['required', 'string', 'max:10'],
                'documents_expiry_warning_days' => ['required', 'integer', 'min:1', 'max:365'],
                'documents_version_format' => ['required', 'string', 'max:20'],
            ],
            'ai' => [
                'ai_enabled' => ['boolean'],
                'ai_provider' => ['required', 'in:openai,fake'],
                'ai_model' => ['required', 'string', 'max:100'],
                'ai_timeout' => ['required', 'integer', 'min:5', 'max:120'],
                'ai_max_tokens' => ['required', 'integer', 'min:100', 'max:4000'],
            ],
            'automations' => [
                'automations_enabled' => ['boolean'],
                'automations_lead_follow_up_upcoming_days' => ['required', 'integer', 'min:1', 'max:30'],
                'automations_quotation_follow_up_days' => ['required', 'integer', 'min:1', 'max:60'],
                'automations_milestone_upcoming_days' => ['required', 'integer', 'min:1', 'max:30'],
                'automations_project_deadline_days' => ['required', 'integer', 'min:1', 'max:60'],
                'automations_hr_joining_days' => ['required', 'integer', 'min:1', 'max:60'],
                'automations_hr_exit_days' => ['required', 'integer', 'min:1', 'max:60'],
                'automations_hr_document_expiry_days' => ['required', 'integer', 'min:1', 'max:365'],
            ],
            'projects' => [
                'projects_default_client_approval_mode' => ['required', Rule::in(['strict', 'flexible'])],
            ],
            default => [],
        };
    }

    protected function saveBranding(SettingsService $settings): void
    {
        $disk = config('settings.branding_disk');
        $directory = config('settings.branding_path');

        if ($this->logo) {
            $previous = $settings->get('company.logo');
            $path = $this->logo->store($directory, $disk);
            $settings->set('company.logo', $path);
            if (is_string($previous) && $previous !== '') {
                Storage::disk($disk)->delete($previous);
            }
            $this->logo = null;
        }

        if ($this->favicon) {
            $previous = $settings->get('company.favicon');
            $path = $this->favicon->store($directory, $disk);
            $settings->set('company.favicon', $path);
            if (is_string($previous) && $previous !== '') {
                Storage::disk($disk)->delete($previous);
            }
            $this->favicon = null;
        }
    }
}
