<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'contacts';

    protected $fillable = [
        'client_id',
        'name',
        'email',
        'phone',
        'job_title',
        'is_primary',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Contact $contact): void {
            if ($contact->is_primary && $contact->client_id) {
                static::query()
                    ->where('client_id', $contact->client_id)
                    ->when($contact->id, fn ($query) => $query->where('id', '!=', $contact->id))
                    ->update(['is_primary' => false]);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
