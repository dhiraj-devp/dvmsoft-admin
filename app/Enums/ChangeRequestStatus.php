<?php

namespace App\Enums;

enum ChangeRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Implemented = 'implemented';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'brand',
            self::Rejected => 'danger',
            self::Implemented => 'success',
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }
}
