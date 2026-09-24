<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\TicketMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketMessage extends Model
{
    /** @use HasFactory<TicketMessageFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'tickets';

    protected $fillable = [
        'ticket_id',
        'author_id',
        'client_user_id',
        'body',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class, 'client_user_id');
    }

    public function displayAuthorName(): string
    {
        return $this->author?->name
            ?: $this->clientUser?->name
            ?: ($this->is_internal ? 'Staff' : 'Client');
    }

    public function scopeVisibleToClient(Builder $query): Builder
    {
        return $query->where('is_internal', false);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('tickets.internal_notes')) {
            return $query;
        }

        return $query->where('is_internal', false);
    }
}
