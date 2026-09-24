<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\ClientStatus;
use App\Enums\ClientType;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'clients';

    protected $fillable = [
        'account_manager_id',
        'converted_from_lead_id',
        'type',
        'name',
        'email',
        'phone',
        'website',
        'address',
        'gst_number',
        'pan_number',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => ClientType::class,
            'status' => ClientStatus::class,
        ];
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function convertedFromLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'converted_from_lead_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function companyDocuments(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function portalUsers(): HasMany
    {
        return $this->hasMany(ClientUser::class);
    }

    public function followUps(): MorphMany
    {
        return $this->morphMany(FollowUp::class, 'followable');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'subject')->latest('created_at');
    }

    public function primaryContact(): ?Contact
    {
        return $this->contacts->firstWhere('is_primary', true) ?? $this->contacts->first();
    }
}
