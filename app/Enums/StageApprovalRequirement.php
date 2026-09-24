<?php

namespace App\Enums;

enum StageApprovalRequirement: string
{
    case Inherit = 'inherit';
    case Required = 'required';
    case NotRequired = 'not_required';

    public function label(): string
    {
        return match ($this) {
            self::Inherit => 'Inherit project setting',
            self::Required => 'Approval required',
            self::NotRequired => 'Approval not required',
        };
    }
}
