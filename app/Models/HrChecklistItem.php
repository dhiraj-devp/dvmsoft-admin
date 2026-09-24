<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrChecklistItem extends Model
{
    use Auditable, HasUlids;

    protected string $auditModule = 'hr_checklists';

    protected $fillable = [
        'hr_checklist_id',
        'key',
        'label',
        'is_completed',
        'completed_at',
        'completed_by_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(HrChecklist::class, 'hr_checklist_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_id');
    }
}
