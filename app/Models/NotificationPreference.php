<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationPreference extends Model
{
    use HasUlids;

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'in_app_enabled',
        'email_enabled',
    ];

    protected function casts(): array
    {
        return [
            'in_app_enabled' => 'boolean',
            'email_enabled' => 'boolean',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function for(object $notifiable): self
    {
        return static::query()->firstOrNew([
            'notifiable_type' => $notifiable::class,
            'notifiable_id' => $notifiable->getKey(),
        ], [
            'in_app_enabled' => true,
            'email_enabled' => true,
        ]);
    }
}
