<?php

namespace App\Enums;

enum ProjectHealth: string
{
    case Green = 'green';
    case Yellow = 'yellow';
    case Red = 'red';

    public function label(): string
    {
        return match ($this) {
            self::Green => 'Green',
            self::Yellow => 'Yellow',
            self::Red => 'Red',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Green => 'success',
            self::Yellow => 'warning',
            self::Red => 'danger',
        };
    }
}
