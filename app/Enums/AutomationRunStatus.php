<?php

namespace App\Enums;

enum AutomationRunStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Success => 'success',
            self::Failed => 'danger',
            self::Skipped => 'neutral',
        };
    }
}
