<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::PartiallyPaid => 'Partially paid',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Sent => 'brand',
            self::PartiallyPaid => 'warning',
            self::Paid => 'success',
            self::Overdue => 'danger',
            self::Cancelled => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Sent, self::PartiallyPaid, self::Overdue], true);
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function acceptsPayments(): bool
    {
        return in_array($this, [self::Sent, self::PartiallyPaid, self::Overdue], true);
    }
}
