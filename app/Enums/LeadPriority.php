<?php

namespace App\Enums;

enum LeadPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Low => 'neutral',
            self::Medium => 'brand',
            self::High => 'warning',
            self::Urgent => 'danger',
        };
    }
}
