<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use Auditable, HasUlids;

    protected string $auditModule = 'leave_balances';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'entitled',
        'used',
        'pending',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'entitled' => 'decimal:2',
            'used' => 'decimal:2',
            'pending' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function available(): float
    {
        return round(max(0, (float) $this->entitled - (float) $this->used - (float) $this->pending), 2);
    }
}
