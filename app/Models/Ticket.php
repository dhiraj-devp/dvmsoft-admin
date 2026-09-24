<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'tickets';

    protected $fillable = [
        'number',
        'client_id',
        'project_id',
        'category_id',
        'assigned_to_id',
        'created_by_id',
        'client_user_id',
        'subject',
        'description',
        'priority',
        'status',
        'sla_due_at',
        'sla_breached_at',
        'sla_notified_approaching_at',
        'sla_notified_breached_at',
        'resolution',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'sla_due_at' => 'datetime',
            'sla_breached_at' => 'datetime',
            'sla_notified_approaching_at' => 'datetime',
            'sla_notified_breached_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class, 'client_user_id');
    }

    public function openerName(): string
    {
        return $this->createdBy?->name
            ?: $this->clientUser?->name
            ?: 'Client';
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->oldest();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TicketStatus::Open->value,
            TicketStatus::InProgress->value,
            TicketStatus::WaitingForClient->value,
        ]);
    }

    public function scopeBreached(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now());
    }

    public function isSlaBreached(): bool
    {
        if (! $this->sla_due_at) {
            return false;
        }

        $cutoff = $this->resolved_at ?? $this->closed_at ?? now();

        return $this->sla_due_at->lt($cutoff);
    }

    public function slaRemainingSeconds(): ?int
    {
        if (! $this->sla_due_at || $this->status->isTerminal()) {
            return null;
        }

        return $this->sla_due_at->getTimestamp() - now()->getTimestamp();
    }

    public function slaLabel(): string
    {
        if (! $this->sla_due_at) {
            return 'No SLA';
        }

        if ($this->status->isTerminal()) {
            return $this->isSlaBreached() ? 'Breached' : 'Met';
        }

        $seconds = $this->slaRemainingSeconds() ?? 0;

        if ($seconds < 0) {
            return 'Breached '.$this->formatDuration(abs($seconds)).' ago';
        }

        return $this->formatDuration($seconds).' remaining';
    }

    public function slaTone(): string
    {
        if ($this->isSlaBreached()) {
            return 'danger';
        }

        $seconds = $this->slaRemainingSeconds();

        if ($seconds !== null && $seconds < 3600) {
            return 'warning';
        }

        return 'success';
    }

    protected function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return $hours.'h '.$minutes.'m';
        }

        return max(1, $minutes).'m';
    }
}
