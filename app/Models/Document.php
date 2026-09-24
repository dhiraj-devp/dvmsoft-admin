<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\DocumentStatus;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected $table = 'company_documents';

    protected string $auditModule = 'documents';

    protected $fillable = [
        'number',
        'title',
        'document_type_id',
        'description',
        'status',
        'current_version_number',
        'owner_id',
        'created_by_id',
        'approved_by_id',
        'approved_at',
        'approval_notes',
        'expiry_date',
        'expiry_notified_at',
        'notes',
        'client_id',
        'project_id',
        'employee_id',
        'quotation_id',
        'invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'approved_at' => 'datetime',
            'expiry_date' => 'date',
            'expiry_notified_at' => 'datetime',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id')->orderByDesc('version_number');
    }

    public function currentVersion(): ?DocumentVersion
    {
        return $this->versions->firstWhere('version_number', $this->current_version_number)
            ?? $this->versions()->where('version_number', $this->current_version_number)->first();
    }

    public function versionLabel(): string
    {
        $format = (string) settings('documents.version_format', 'v{n}');

        return str_replace('{n}', (string) $this->current_version_number, $format);
    }

    public function isExpiringSoon(?int $days = null): bool
    {
        if (! $this->expiry_date || $this->status === DocumentStatus::Archived) {
            return false;
        }

        $days ??= (int) settings('documents.expiry_warning_days', 30);

        return $this->expiry_date->lte(now()->addDays($days)->startOfDay())
            && $this->expiry_date->gte(now()->startOfDay());
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null
            && $this->status !== DocumentStatus::Archived
            && $this->expiry_date->lt(now()->startOfDay());
    }

    public function scopeExpiring(Builder $query, ?int $days = null): Builder
    {
        $days ??= (int) settings('documents.expiry_warning_days', 30);

        return $query->whereNotNull('expiry_date')
            ->where('status', '!=', DocumentStatus::Archived->value)
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->where('status', '!=', DocumentStatus::Archived->value)
            ->whereDate('expiry_date', '<', now()->toDateString());
    }
}
