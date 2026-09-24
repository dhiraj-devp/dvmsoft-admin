<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\ClientStatus;
use App\Notifications\ResetClientPassword;
use App\Notifications\VerifyClientEmail;
use Database\Factories\ClientUserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class ClientUser extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<ClientUserFactory> */
    use Auditable, HasFactory, HasUlids, MustVerifyEmailTrait, Notifiable, SoftDeletes;

    protected string $auditModule = 'client_portal';

    protected static function newFactory(): ClientUserFactory
    {
        return ClientUserFactory::new();
    }

    protected $fillable = [
        'client_id',
        'name',
        'email',
        'password',
        'is_active',
        'theme',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function canAccessPortal(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $status = $this->client?->status;

        return $status instanceof ClientStatus && $status !== ClientStatus::Inactive;
    }

    /**
     * @return array<string, mixed>
     */
    public function auditActorValues(): array
    {
        return [
            'client_user_id' => $this->id,
            'client_user_email' => $this->email,
            'client_id' => $this->client_id,
        ];
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyClientEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetClientPassword($token));
    }
}
