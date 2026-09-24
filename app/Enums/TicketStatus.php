<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingForClient = 'waiting_for_client';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::WaitingForClient => 'Waiting for client',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'brand',
            self::InProgress => 'warning',
            self::WaitingForClient => 'neutral',
            self::Resolved => 'success',
            self::Closed => 'neutral',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::InProgress, self::WaitingForClient], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Resolved, self::Closed], true);
    }
}
