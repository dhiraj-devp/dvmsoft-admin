<?php

namespace App\Enums;

enum WorkGoalStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::InProgress => 'In progress',
            self::Blocked => 'Blocked',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::NotStarted => 'neutral',
            self::InProgress => 'brand',
            self::Blocked => 'danger',
            self::Completed => 'success',
            self::Cancelled => 'neutral',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::NotStarted, self::InProgress, self::Blocked], true);
    }
}
