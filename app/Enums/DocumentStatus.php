<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Approved = 'approved';
    case Sent = 'sent';
    case Signed = 'signed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Review => 'Review',
            self::Approved => 'Approved',
            self::Sent => 'Sent',
            self::Signed => 'Signed',
            self::Archived => 'Archived',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Review => 'warning',
            self::Approved => 'success',
            self::Sent => 'brand',
            self::Signed => 'success',
            self::Archived => 'neutral',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Review], true);
    }
}
