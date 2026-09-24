<div>
    <div class="mb-6 flex flex-wrap gap-2 border-b border-ink-200 pb-3 dark:border-ink-800">
        @foreach ($tabs as $key => $label)
            <a href="{{ $key === 'company' ? route('settings.index') : route('settings.section', $key) }}"
               @class([
                   'rounded-xl px-3 py-2 text-sm font-medium',
                   'bg-brand-50 text-brand-800 dark:bg-brand-950 dark:text-brand-200' => $section === $key,
                   'text-ink-500 hover:bg-ink-50 dark:hover:bg-ink-800' => $section !== $key,
               ])>
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form wire:submit="save" class="card max-w-3xl space-y-5 p-6" enctype="multipart/form-data">
        @if ($section === 'company')
            <div>
                <label class="label">Company name</label>
                <input type="text" wire:model="company_name" class="input">
                @error('company_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Email</label>
                    <input type="email" wire:model="company_email" class="input">
                    @error('company_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Phone</label>
                    <input type="text" wire:model="company_phone" class="input">
                </div>
            </div>
            <div>
                <label class="label">Website</label>
                <input type="url" wire:model="company_website" class="input">
                @error('company_website') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Address</label>
                <textarea wire:model="company_address" rows="3" class="input"></textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">GST number</label>
                    <input type="text" wire:model="company_gst_number" class="input">
                </div>
                <div>
                    <label class="label">PAN number</label>
                    <input type="text" wire:model="company_pan_number" class="input">
                </div>
            </div>
        @endif

        @if ($section === 'branding')
            <div>
                <label class="label">Logo</label>
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo" class="mb-3 h-16 rounded-xl border border-ink-200 object-contain p-2 dark:border-ink-700">
                    @can('manage', App\Models\Setting::class)
                        <button type="button" class="mb-3 text-sm text-red-600" wire:click="removeFile('company.logo')">Remove logo</button>
                    @endcan
                @endif
                <input type="file" wire:model="logo" accept="image/*" class="input">
                <div wire:loading wire:target="logo" class="mt-2 text-xs text-ink-400">Uploading…</div>
                @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Favicon</label>
                @if ($faviconUrl)
                    <img src="{{ $faviconUrl }}" alt="Favicon" class="mb-3 h-10 w-10 rounded-lg border border-ink-200 object-contain p-1 dark:border-ink-700">
                    @can('manage', App\Models\Setting::class)
                        <button type="button" class="mb-3 text-sm text-red-600" wire:click="removeFile('company.favicon')">Remove favicon</button>
                    @endcan
                @endif
                <input type="file" wire:model="favicon" class="input">
                @error('favicon') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        @if ($section === 'localization')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Currency</label>
                    <input type="text" wire:model="company_currency" maxlength="3" class="input uppercase">
                    @error('company_currency') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Date format</label>
                    <select wire:model="company_date_format" class="input">
                        <option value="d M Y">08 Sep 2026</option>
                        <option value="Y-m-d">2026-09-08</option>
                        <option value="d/m/Y">08/09/2026</option>
                        <option value="m/d/Y">09/08/2026</option>
                        <option value="d-m-Y">08-09-2026</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="label">Timezone</label>
                <select wire:model="company_timezone" class="input">
                    @foreach ($timezones as $timezone)
                        <option value="{{ $timezone }}">{{ $timezone }}</option>
                    @endforeach
                </select>
                @error('company_timezone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        @if ($section === 'email')
            <div>
                <label class="label">From name</label>
                <input type="text" wire:model="email_from_name" class="input">
            </div>
            <div>
                <label class="label">From address</label>
                <input type="email" wire:model="email_from_address" class="input">
                @error('email_from_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        @if ($section === 'notifications')
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="notifications_email_enabled" class="rounded border-ink-300 text-brand-700">
                Enable email notifications
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="notifications_in_app_enabled" class="rounded border-ink-300 text-brand-700">
                Enable in-app notifications
            </label>
        @endif

        @if ($section === 'security')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Session timeout (minutes)</label>
                    <input type="number" wire:model="security_session_timeout" class="input">
                    @error('security_session_timeout') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Max login attempts</label>
                    <input type="number" wire:model="security_login_max_attempts" class="input">
                    @error('security_login_max_attempts') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="security_require_strong_passwords" class="rounded border-ink-300 text-brand-700">
                Require strong passwords
            </label>
        @endif

        @if ($section === 'system')
            <div>
                <label class="label">Maintenance message</label>
                <textarea wire:model="system_maintenance_message" rows="4" class="input" placeholder="Optional banner shown to internal users"></textarea>
            </div>
        @endif

        @if ($section === 'finance')
            <p class="text-sm text-ink-500">These values print on invoices and receipts. They are never hardcoded in templates.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Invoice prefix</label>
                    <input type="text" wire:model="finance_invoice_prefix" class="input uppercase">
                    @error('finance_invoice_prefix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Expense prefix</label>
                    <input type="text" wire:model="finance_expense_prefix" class="input uppercase">
                </div>
                <div>
                    <label class="label">Default GST / tax %</label>
                    <input type="number" step="0.01" wire:model="finance_default_tax_percent" class="input">
                    @error('finance_default_tax_percent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Default due days</label>
                    <input type="number" wire:model="finance_invoice_due_days" class="input">
                    @error('finance_invoice_due_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Bank name</label>
                    <input type="text" wire:model="finance_bank_name" class="input">
                </div>
                <div>
                    <label class="label">Account name</label>
                    <input type="text" wire:model="finance_bank_account_name" class="input">
                </div>
                <div>
                    <label class="label">Account number</label>
                    <input type="text" wire:model="finance_bank_account_number" class="input">
                </div>
                <div>
                    <label class="label">IFSC</label>
                    <input type="text" wire:model="finance_bank_ifsc" class="input">
                </div>
            </div>
            <div>
                <label class="label">UPI ID</label>
                <input type="text" wire:model="finance_upi_id" class="input">
            </div>
            <div>
                <label class="label">Payment instructions</label>
                <textarea wire:model="finance_payment_instructions" rows="3" class="input"></textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Remind before due (days)</label>
                    <input type="number" wire:model="finance_reminder_days_before_due" class="input">
                </div>
                <div>
                    <label class="label">Overdue reminder interval (days)</label>
                    <input type="number" wire:model="finance_reminder_days_after_due" class="input">
                </div>
            </div>
        @endif

        @if ($section === 'hr')
            <p class="text-sm text-ink-500">These defaults apply to new employee records. Salary and payroll are not configured here.</p>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="label">Employee code prefix</label>
                    <input type="text" wire:model="hr_employee_prefix" class="input uppercase">
                    @error('hr_employee_prefix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Default probation days</label>
                    <input type="number" wire:model="hr_probation_days" class="input">
                    @error('hr_probation_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Default annual leave days</label>
                    <input type="number" wire:model="hr_annual_leave_days" class="input">
                    @error('hr_annual_leave_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        @if ($section === 'documents')
            <p class="text-sm text-ink-500">These settings apply to the company document registry. HR employee files and project files stay in their own modules.</p>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="label">Document prefix</label>
                    <input type="text" wire:model="documents_prefix" class="input uppercase">
                    @error('documents_prefix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Expiry warning (days)</label>
                    <input type="number" wire:model="documents_expiry_warning_days" class="input">
                    @error('documents_expiry_warning_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Version format</label>
                    <input type="text" wire:model="documents_version_format" class="input">
                    <p class="mt-1 text-xs text-ink-400">Use {n} for the version number, e.g. v{n}</p>
                    @error('documents_version_format') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        @if ($section === 'ai')
            <p class="text-sm text-ink-500">AI is an assistance layer over existing records. It never sends messages, changes statuses, or invents prices on its own. API credentials stay in the environment and are never shown here.</p>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="ai_enabled" class="rounded border-ink-300 text-brand-700">
                Enable AI assistance
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Provider</label>
                    <select wire:model="ai_provider" class="input">
                        <option value="openai">OpenAI</option>
                        <option value="fake">Fake (tests / dry run)</option>
                    </select>
                    @error('ai_provider') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Model</label>
                    <input type="text" wire:model="ai_model" class="input">
                    @error('ai_model') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Timeout (seconds)</label>
                    <input type="number" wire:model="ai_timeout" class="input">
                    @error('ai_timeout') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Max tokens</label>
                    <input type="number" wire:model="ai_max_tokens" class="input">
                    @error('ai_max_tokens') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="rounded-xl bg-ink-50 p-4 text-sm dark:bg-ink-950">
                <p class="font-medium">API key</p>
                <p class="mt-1 text-ink-500">Configured through <code>AI_API_KEY</code> in the environment. {{ $aiKeyConfigured ? 'A key is present.' : 'No key is configured.' }}</p>
            </div>
        @endif

        @if ($section === 'automations')
            <p class="text-sm text-ink-500">Reminder timing for the workflow engine. Invoice due dates still use Finance settings, and company document expiry still uses Documents settings. WhatsApp is not sent.</p>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="automations_enabled" class="rounded border-ink-300 text-brand-700">
                Enable automations
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Upcoming lead follow-up (days)</label>
                    <input type="number" wire:model="automations_lead_follow_up_upcoming_days" class="input">
                    @error('automations_lead_follow_up_upcoming_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Quotation follow-up (days after send)</label>
                    <input type="number" wire:model="automations_quotation_follow_up_days" class="input">
                    @error('automations_quotation_follow_up_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Upcoming milestone (days)</label>
                    <input type="number" wire:model="automations_milestone_upcoming_days" class="input">
                    @error('automations_milestone_upcoming_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Project deadline (days)</label>
                    <input type="number" wire:model="automations_project_deadline_days" class="input">
                    @error('automations_project_deadline_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Employee joining (days)</label>
                    <input type="number" wire:model="automations_hr_joining_days" class="input">
                    @error('automations_hr_joining_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Employee exit (days)</label>
                    <input type="number" wire:model="automations_hr_exit_days" class="input">
                    @error('automations_hr_exit_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">HR document expiry (days)</label>
                    <input type="number" wire:model="automations_hr_document_expiry_days" class="input">
                    @error('automations_hr_document_expiry_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        @if ($section === 'projects')
            <p class="text-sm text-ink-500">Default delivery rules for new projects. Existing projects keep their own approval mode.</p>
            <div>
                <label class="label">Default client approval mode</label>
                <select wire:model="projects_default_client_approval_mode" class="input">
                    <option value="flexible">Flexible — review is available but does not block progression</option>
                    <option value="strict">Strict — required stages cannot complete until the client approves</option>
                </select>
                @error('projects_default_client_approval_mode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        @can('manage', App\Models\Setting::class)
            <div class="flex justify-end">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">Save settings</button>
            </div>
        @else
            <p class="text-sm text-ink-500">You can view these settings, but you do not have permission to change them.</p>
        @endcan
    </form>
</div>
