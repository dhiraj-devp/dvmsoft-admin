<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\AssetCondition;
use Database\Factories\EmployeeAssetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAsset extends Model
{
    /** @use HasFactory<EmployeeAssetFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'assets';

    protected $fillable = [
        'employee_id',
        'assigned_by_id',
        'name',
        'serial_number',
        'assigned_date',
        'return_date',
        'condition',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'return_date' => 'date',
            'condition' => AssetCondition::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function scopeAssigned(Builder $query): Builder
    {
        return $query->whereNull('return_date');
    }

    public function isReturned(): bool
    {
        return $this->return_date !== null;
    }
}
