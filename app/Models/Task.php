<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\ProjectPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'tasks';

    protected $fillable = [
        'project_id',
        'milestone_id',
        'stage_id',
        'assigned_user_id',
        'title',
        'description',
        'priority',
        'status',
        'start_date',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => ProjectPriority::class,
            'status' => TaskStatus::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Task $task): void {
            if ($task->status === TaskStatus::Completed) {
                $task->completed_at ??= now();
            } elseif ($task->isDirty('status')) {
                $task->completed_at = null;
            }
        });

        static::saved(function (Task $task): void {
            if ($task->stage_id) {
                $task->stage?->refreshProgress();
            }
            if ($task->wasChanged('stage_id') && $task->getOriginal('stage_id')) {
                ProjectStage::query()->find($task->getOriginal('stage_id'))?->refreshProgress();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStage::class, 'stage_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', TaskStatus::Completed->value);
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen()
            && $this->due_date !== null
            && $this->due_date->isPast();
    }
}
