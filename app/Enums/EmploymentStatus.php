<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case Probation = 'probation';
    case OnLeave = 'on_leave';
    case Exited = 'exited';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Probation => 'On probation',
            self::OnLeave => 'On leave',
            self::Exited => 'Exited',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Probation => 'warning',
            self::OnLeave => 'brand',
            self::Exited => 'danger',
        };
    }
}
