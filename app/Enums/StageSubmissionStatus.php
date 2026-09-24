<?php

namespace App\Enums;

enum StageSubmissionStatus: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case ChangesRequested = 'changes_requested';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::ChangesRequested => 'Changes requested',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Submitted => 'warning',
            self::Approved => 'success',
            self::ChangesRequested => 'danger',
        };
    }
}
