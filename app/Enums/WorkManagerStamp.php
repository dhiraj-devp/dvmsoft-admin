<?php

namespace App\Enums;

enum WorkManagerStamp: string
{
    case Good = 'good';
    case NeedsFollowUp = 'needs_follow_up';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Good',
            self::NeedsFollowUp => 'Needs follow-up',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Good => 'success',
            self::NeedsFollowUp => 'warning',
        };
    }
}
