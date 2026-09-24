<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Converted = 'converted';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Sent => 'brand',
            self::Viewed => 'brand',
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::Converted => 'success',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
