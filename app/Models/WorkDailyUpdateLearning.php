<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkDailyUpdateLearning extends Model
{
    use HasUlids;

    protected $fillable = [
        'work_daily_update_id',
        'work_learning_goal_id',
        'topic',
        'description',
        'confidence',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'integer',
        ];
    }

    public function updateEntry(): BelongsTo
    {
        return $this->belongsTo(WorkDailyUpdate::class, 'work_daily_update_id');
    }

    public function learningGoal(): BelongsTo
    {
        return $this->belongsTo(WorkLearningGoal::class, 'work_learning_goal_id');
    }
}
