<?php

namespace App\Models;

use App\Enums\WorkPlanItemStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkDailyPlanItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'work_daily_plan_id',
        'work_goal_id',
        'task_id',
        'sort_order',
        'title',
        'status',
        'completion_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkPlanItemStatus::class,
            'sort_order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(WorkDailyPlan::class, 'work_daily_plan_id');
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(WorkGoal::class, 'work_goal_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
