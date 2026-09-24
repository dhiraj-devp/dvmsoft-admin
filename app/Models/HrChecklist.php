<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\HrChecklistType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrChecklist extends Model
{
    use Auditable, HasUlids;

    protected string $auditModule = 'hr_checklists';

    protected $fillable = [
        'employee_id',
        'type',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => HrChecklistType::class,
            'completed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(HrChecklistItem::class)->orderBy('sort_order');
    }

    public function progressPercent(): int
    {
        $total = $this->items()->count();

        if ($total === 0) {
            return 0;
        }

        $done = $this->items()->where('is_completed', true)->count();

        return (int) round(($done / $total) * 100);
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }
}
