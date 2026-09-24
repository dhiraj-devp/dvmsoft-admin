<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Completed = 'completed';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'To do',
            self::InProgress => 'In progress',
            self::Review => 'Review',
            self::Completed => 'Completed',
            self::Blocked => 'Blocked',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Todo => 'neutral',
            self::InProgress => 'brand',
            self::Review => 'warning',
            self::Completed => 'success',
            self::Blocked => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Completed;
    }
}
