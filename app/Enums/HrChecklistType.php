<?php

namespace App\Enums;

enum HrChecklistType: string
{
    case Onboarding = 'onboarding';
    case Offboarding = 'offboarding';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function items(): array
    {
        return match ($this) {
            self::Onboarding => config('hr.onboarding_items', []),
            self::Offboarding => config('hr.offboarding_items', []),
        };
    }
}
