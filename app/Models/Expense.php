<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'expenses';

    protected $fillable = [
        'number',
        'project_id',
        'recorded_by_id',
        'expense_date',
        'category',
        'vendor',
        'amount',
        'tax_amount',
        'payment_method',
        'status',
        'notes',
        'receipt_path',
        'receipt_disk',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => ExpenseStatus::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function total(): float
    {
        return (float) $this->amount + (float) $this->tax_amount;
    }

    public function categoryLabel(): string
    {
        return config('finance.expense_categories.'.$this->category, $this->category);
    }
}
