<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'leads';

    protected $fillable = [
        'assigned_user_id',
        'converted_client_id',
        'name',
        'company',
        'email',
        'phone',
        'source',
        'requirement',
        'estimated_value',
        'status',
        'priority',
        'next_follow_up_at',
        'notes',
        'lost_reason',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'status' => LeadStatus::class,
            'priority' => LeadPriority::class,
            'next_follow_up_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function convertedClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'converted_client_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function followUps(): MorphMany
    {
        return $this->morphMany(FollowUp::class, 'followable');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'subject')->latest('created_at');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [LeadStatus::Won->value, LeadStatus::Lost->value]);
    }

    public function isConverted(): bool
    {
        return $this->converted_client_id !== null;
    }

    public function displayCompany(): string
    {
        return $this->company ?: $this->name;
    }
}
