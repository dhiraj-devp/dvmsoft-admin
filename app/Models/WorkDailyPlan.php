<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\WorkPlanItemStatus;
use Database\Factories\WorkDailyPlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkDailyPlan extends Model
{
    /** @use HasFactory<WorkDailyPlanFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'work_daily_plans';

    protected $fillable = [
        'user_id',
        'work_date',
        'focus',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkDailyPlanItem::class)->orderBy('sort_order');
    }

    public function updateEntry(): HasOne
    {
        return $this->hasOne(WorkDailyUpdate::class);
    }

    public function completionRatio(): ?float
    {
        $total = $this->items->count();

        if ($total === 0) {
            return null;
        }

        $done = $this->items->where('status', WorkPlanItemStatus::Completed)->count();

        return $done / $total;
    }
}
