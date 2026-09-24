<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use Database\Factories\FollowUpFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FollowUp extends Model
{
    /** @use HasFactory<FollowUpFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'follow_ups';

    protected $fillable = [
        'followable_type',
        'followable_id',
        'assigned_user_id',
        'scheduled_at',
        'type',
        'notes',
        'status',
        'reminder_at',
        'reminded_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'reminder_at' => 'datetime',
            'reminded_at' => 'datetime',
            'completed_at' => 'datetime',
            'type' => FollowUpType::class,
            'status' => FollowUpStatus::class,
        ];
    }

    public function followable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', FollowUpStatus::Pending->value);
    }

    public function isOverdue(): bool
    {
        return $this->status === FollowUpStatus::Pending
            && $this->scheduled_at !== null
            && $this->scheduled_at->isPast();
    }

    public function subjectName(): string
    {
        $followable = $this->followable;

        if ($followable instanceof Lead || $followable instanceof Client) {
            return $followable->name;
        }

        return 'Record';
    }
}
