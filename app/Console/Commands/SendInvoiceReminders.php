<?php

namespace App\Console\Commands;

use App\Automations\AutomationEngine;
use App\Services\InvoiceBalanceService;
use Illuminate\Console\Command;

class SendInvoiceReminders extends Command
{
    protected $signature = 'finance:send-invoice-reminders';

    protected $description = 'Mark overdue invoices and send internal payment reminders (not WhatsApp)';

    public function handle(InvoiceBalanceService $balances, AutomationEngine $engine): int
    {
        $overdue = $balances->markOverdueInvoices();
        $due = $engine->run('finance.invoice_due', true);
        $overdueRun = $engine->run('finance.invoice_overdue', true);

        $this->info($overdue.' invoice(s) refreshed for overdue status.');
        $this->info(($due->notified_count + $overdueRun->notified_count).' reminder(s) recorded. WhatsApp is not sent.');

        return self::SUCCESS;
    }
}
