<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\WorkLearningStatus;
use Database\Factories\WorkLearningGoalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkLearningGoal extends Model
{
    /** @use HasFactory<WorkLearningGoalFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'work_learning_goals';

    protected $fillable = [
        'assigned_user_id',
        'created_by',
        'title',
        'description',
        'target_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'status' => WorkLearningStatus::class,
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
