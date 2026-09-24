<?php

namespace App\Automations\Handlers;

use App\Automations\AutomationResult;
use App\Automations\Contracts\AutomationHandler;
use App\Models\Automation;
use App\Services\InvoiceBalanceService;
use App\Services\InvoiceReminderService;

class FinanceAutomations implements AutomationHandler
{
    public function __construct(
        protected InvoiceReminderService $reminders,
        protected InvoiceBalanceService $balances,
    ) {}

    public function handle(Automation $automation): AutomationResult
    {
        return match ($automation->key) {
            'finance.invoice_due' => new AutomationResult(
                processed: 0,
                notified: $this->reminders->sendUpcomingReminders(),
            ),
            'finance.invoice_overdue' => $this->overdue(),
            'finance.outstanding' => new AutomationResult(
                processed: 0,
                notified: $this->reminders->sendOutstandingReminders(),
            ),
            default => new AutomationResult,
        };
    }

    protected function overdue(): AutomationResult
    {
        $this->balances->markOverdueInvoices();

        return new AutomationResult(
            processed: 0,
            notified: $this->reminders->sendOverdueReminders(),
        );
    }
}
