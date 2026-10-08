<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\WorkDailyUpdateStatus;
use App\Enums\WorkManagerStamp;
use Database\Factories\WorkDailyUpdateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WorkDailyUpdate extends Model
{
    /** @use HasFactory<WorkDailyUpdateFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'work_daily_updates';

    protected $fillable = [
        'user_id',
        'work_daily_plan_id',
        'work_date',
        'accomplished',
        'pending',
        'blocked',
        'learned',
        'notes',
        'submitted_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'manager_stamp',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'submitted_at' => 'datetime',
            'status' => WorkDailyUpdateStatus::class,
            'reviewed_at' => 'datetime',
            'manager_stamp' => WorkManagerStamp::class,
        ];
    }

    public function preview(): string
    {
        return Str::limit(trim((string) $this->accomplished), 90);
    }

    public function scopeInReview(Builder $query): Builder
    {
        return $query->where('status', WorkDailyUpdateStatus::InReview);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(WorkDailyPlan::class, 'work_daily_plan_id');
    }

    public function learnings(): HasMany
    {
        return $this->hasMany(WorkDailyUpdateLearning::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(WorkDailyUpdateEvidence::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(WorkReview::class);
    }
}
