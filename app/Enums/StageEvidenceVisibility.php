<?php

namespace App\Enums;

enum StageEvidenceVisibility: string
{
    case Client = 'client';
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Visible to client',
            self::Internal => 'Internal only',
        };
    }
}
