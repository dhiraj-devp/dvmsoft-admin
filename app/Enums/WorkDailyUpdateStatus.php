<?php

namespace App\Enums;

enum WorkDailyUpdateStatus: string
{
    case InReview = 'in_review';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::InReview => 'In review',
            self::Done => 'Done',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::InReview => 'warning',
            self::Done => 'success',
        };
    }

    public function isDone(): bool
    {
        return $this === self::Done;
    }
}
