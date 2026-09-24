<?php

namespace App\Enums;

enum FollowUpType: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Whatsapp = 'whatsapp';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Call',
            self::Email => 'Email',
            self::Meeting => 'Meeting',
            self::Whatsapp => 'WhatsApp',
            self::Other => 'Other',
        };
    }
}
