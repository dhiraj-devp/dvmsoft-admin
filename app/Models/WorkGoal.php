<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\WorkGoalStatus;
use App\Enums\WorkPriority;
use Database\Factories\WorkGoalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkGoal extends Model
{
    /** @use HasFactory<WorkGoalFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'work_goals';

    protected $fillable = [
        'assigned_user_id',
        'created_by',
        'project_id',
        'title',
        'description',
        'expected_outcome',
        'start_date',
        'due_date',
        'priority',
        'status',
        'progress',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'priority' => WorkPriority::class,
            'status' => WorkGoalStatus::class,
            'progress' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (WorkGoal $goal): void {
            if ($goal->status === WorkGoalStatus::Completed) {
                $goal->progress = 100;
            }
        });
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function planItems(): HasMany
    {
        return $this->hasMany(WorkDailyPlanItem::class);
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen()
            && $this->due_date !== null
            && $this->due_date->endOfDay()->isPast();
    }
}
