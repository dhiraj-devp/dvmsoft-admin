<?php

namespace App\Services;

use App\Models\ChangeRequest;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;

class SequentialNumberGenerator
{
    public function next(string $prefix, string $modelClass, string $column = 'number'): string
    {
        $year = now()->year;
        $fullPrefix = $prefix.'-'.$year.'-';

        $latest = $modelClass::withTrashed()
            ->where($column, 'like', $fullPrefix.'%')
            ->orderByDesc($column)
            ->value($column);

        $sequence = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return $fullPrefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function nextProject(): string
    {
        return $this->next('PRJ', Project::class);
    }

    public function nextChangeRequest(): string
    {
        return $this->next('CR', ChangeRequest::class);
    }

    public function nextInvoice(): string
    {
        $prefix = strtoupper((string) settings('finance.invoice_prefix', 'INV')) ?: 'INV';

        return $this->next($prefix, Invoice::class);
    }

    public function nextExpense(): string
    {
        $prefix = strtoupper((string) settings('finance.expense_prefix', 'EXP')) ?: 'EXP';

        return $this->next($prefix, Expense::class);
    }

    public function nextEmployee(): string
    {
        $prefix = strtoupper((string) settings('hr.employee_prefix', 'EMP')) ?: 'EMP';

        return $this->next($prefix, User::class, 'employee_code');
    }

    public function nextTicket(): string
    {
        $prefix = strtoupper((string) settings('support.ticket_prefix', 'TKT')) ?: 'TKT';

        return $this->next($prefix, Ticket::class);
    }

    public function nextDocument(): string
    {
        $prefix = strtoupper((string) settings('documents.prefix', 'DOC')) ?: 'DOC';

        return $this->next($prefix, Document::class);
    }
}
