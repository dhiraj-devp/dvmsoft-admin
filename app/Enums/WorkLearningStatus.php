<?php

namespace App\Enums;

enum WorkLearningStatus: string
{
    case NotStarted = 'not_started';
    case Learning = 'learning';
    case Practiced = 'practiced';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::Learning => 'Learning',
            self::Practiced => 'Practiced',
            self::Completed => 'Completed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::NotStarted => 'neutral',
            self::Learning => 'brand',
            self::Practiced => 'warning',
            self::Completed => 'success',
        };
    }
}
