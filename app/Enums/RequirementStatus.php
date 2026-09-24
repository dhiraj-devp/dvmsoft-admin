<?php

namespace App\Enums;

enum RequirementStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InProgress => 'In progress',
            self::Done => 'Done',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::InProgress => 'brand',
            self::Done => 'success',
            self::Cancelled => 'danger',
        };
    }
}
