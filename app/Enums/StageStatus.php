<?php

namespace App\Enums;

enum StageStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case ReadyForReview = 'ready_for_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Completed = 'completed';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::InProgress => 'In progress',
            self::ReadyForReview => 'Ready for review',
            self::ChangesRequested => 'Changes requested',
            self::Approved => 'Approved',
            self::Completed => 'Completed',
            self::Blocked => 'Blocked',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::NotStarted => 'neutral',
            self::InProgress => 'brand',
            self::ReadyForReview => 'warning',
            self::ChangesRequested => 'danger',
            self::Approved, self::Completed => 'success',
            self::Blocked => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Completed], true);
    }

    public function isActiveWork(): bool
    {
        return in_array($this, [
            self::InProgress,
            self::ReadyForReview,
            self::ChangesRequested,
            self::Approved,
            self::Blocked,
        ], true);
    }

    public function marker(): string
    {
        return match ($this) {
            self::Completed, self::Approved => '✓',
            self::InProgress, self::ReadyForReview, self::ChangesRequested, self::Blocked => '●',
            self::NotStarted => '○',
        };
    }
}
