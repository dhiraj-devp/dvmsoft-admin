<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\User;
use App\Notifications\InvoiceReminderNotification;

class InvoiceReminderService
{
    /**
     * Queue in-app (and optional email) reminders. WhatsApp is intentionally not sent.
     */
    public function sendDueReminders(): int
    {
        return $this->sendUpcomingReminders() + $this->sendOverdueReminders();
    }

    public function sendUpcomingReminders(): int
    {
        $before = max(0, (int) settings('finance.reminder_days_before_due', 3));
        $sent = 0;

        $upcoming = Invoice::query()
            ->with('client')
            ->outstanding()
            ->whereNotNull('due_date')
            ->whereDate('due_date', now()->addDays($before)->toDateString())
            ->get();

        foreach ($upcoming as $invoice) {
            $sent += $this->remind($invoice, 'upcoming');
        }

        return $sent;
    }

    public function sendOverdueReminders(): int
    {
        $after = max(0, (int) settings('finance.reminder_days_after_due', 1));
        $sent = 0;

        $overdue = Invoice::query()
            ->with('client')
            ->where('status', InvoiceStatus::Overdue->value)
            ->where('balance', '>', 0)
            ->where(function ($query) use ($after) {
                $query->whereNull('last_reminded_at')
                    ->orWhere('last_reminded_at', '<=', now()->subDays(max(1, $after)));
            })
            ->get();

        foreach ($overdue as $invoice) {
            $sent += $this->remind($invoice, 'overdue');
        }

        return $sent;
    }

    public function sendOutstandingReminders(): int
    {
        $before = max(0, (int) settings('finance.reminder_days_before_due', 3));
        $sent = 0;

        $outstanding = Invoice::query()
            ->with('client')
            ->outstanding()
            ->where('status', '!=', InvoiceStatus::Overdue->value)
            ->where('balance', '>', 0)
            ->where(function ($query) use ($before) {
                $query->whereNull('due_date')
                    ->orWhereDate('due_date', '!=', now()->addDays($before)->toDateString());
            })
            ->get();

        foreach ($outstanding as $invoice) {
            $sent += $this->remind($invoice, 'outstanding');
        }

        return $sent;
    }

    public function remind(Invoice $invoice, string $type): int
    {
        if ($invoice->reminders()->where('type', $type)->whereDate('sent_at', now()->toDateString())->exists()) {
            return 0;
        }

        $recipients = User::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('is_super_admin', true)
                    ->orWhereHas('roles.permissions', fn ($permissions) => $permissions->where('name', 'finance.dashboard.view'));
            })
            ->get();

        if ($recipients->isEmpty()) {
            $recipients = User::query()->where('is_super_admin', true)->get();
        }

        $channel = settings('notifications.email_enabled') ? 'database+mail' : 'database';

        foreach ($recipients as $user) {
            if (settings('notifications.in_app_enabled') || settings('notifications.email_enabled')) {
                $user->notify(new InvoiceReminderNotification($invoice, $type));
            }
        }

        InvoiceReminder::query()->create([
            'invoice_id' => $invoice->id,
            'type' => $type,
            'channel' => $channel,
            'sent_at' => now(),
            'meta' => [
                'whatsapp' => false,
                'balance' => $invoice->balance,
            ],
        ]);

        $invoice->forceFill(['last_reminded_at' => now()])->saveQuietly();

        return 1;
    }
}
