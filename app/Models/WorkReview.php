<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\WorkReviewFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkReview extends Model
{
    /** @use HasFactory<WorkReviewFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'work_reviews';

    protected $fillable = [
        'user_id',
        'reviewer_id',
        'work_daily_update_id',
        'quality_score',
        'feedback',
        'action_items',
        'reviewed_on',
    ];

    protected function casts(): array
    {
        return [
            'quality_score' => 'integer',
            'reviewed_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function dailyUpdate(): BelongsTo
    {
        return $this->belongsTo(WorkDailyUpdate::class, 'work_daily_update_id');
    }
}
