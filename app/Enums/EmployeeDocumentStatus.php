<?php

namespace App\Enums;

enum EmployeeDocumentStatus: string
{
    case Pending = 'pending';
    case Uploaded = 'uploaded';
    case Signed = 'signed';
    case Expired = 'expired';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Uploaded => 'brand',
            self::Signed => 'success',
            self::Expired, self::Rejected => 'danger',
        };
    }

    public function isSatisfied(): bool
    {
        return in_array($this, [self::Uploaded, self::Signed], true);
    }
}
