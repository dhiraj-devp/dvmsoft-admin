<?php

namespace App\Enums;

enum WorkPlanItemStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case NotCompleted = 'not_completed';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::NotCompleted => 'Not completed',
            self::Blocked => 'Blocked',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Planned => 'neutral',
            self::InProgress => 'brand',
            self::Completed => 'success',
            self::NotCompleted => 'warning',
            self::Blocked => 'danger',
        };
    }
}
