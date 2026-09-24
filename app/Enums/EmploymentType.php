<?php

namespace App\Enums;

enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Intern = 'intern';

    public function label(): string
    {
        return config('hr.employment_types.'.$this->value, ucfirst(str_replace('_', '-', $this->value)));
    }
}
