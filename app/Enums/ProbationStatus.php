<?php

namespace App\Enums;

enum ProbationStatus: string
{
    case NotApplicable = 'not_applicable';
    case Ongoing = 'ongoing';
    case Confirmed = 'confirmed';
    case Extended = 'extended';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::NotApplicable => 'Not applicable',
            self::Ongoing => 'On probation',
            self::Confirmed => 'Confirmed',
            self::Extended => 'Extended',
            self::Failed => 'Failed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::NotApplicable => 'neutral',
            self::Ongoing => 'warning',
            self::Confirmed => 'success',
            self::Extended => 'brand',
            self::Failed => 'danger',
        };
    }
}
