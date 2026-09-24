<?php

namespace App\Enums;

enum ClientApprovalMode: string
{
    case Strict = 'strict';
    case Flexible = 'flexible';

    public function label(): string
    {
        return match ($this) {
            self::Strict => 'Strict',
            self::Flexible => 'Flexible',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Strict => 'Stages that require approval cannot complete, and the next stage cannot start, until the client approves.',
            self::Flexible => 'Clients can still review work, but approval does not block the team from moving on unless a stage explicitly requires it.',
        };
    }
}
