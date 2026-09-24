<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\AutomationRunStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Automation extends Model
{
    use Auditable, HasUlids;

    protected string $auditModule = 'automations';

    protected $fillable = [
        'key',
        'name',
        'description',
        'module',
        'trigger_type',
        'schedule',
        'channels',
        'enabled',
        'last_ran_at',
        'last_status',
        'last_error',
        'last_notified_count',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'enabled' => 'boolean',
            'last_ran_at' => 'datetime',
            'last_status' => AutomationRunStatus::class,
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class)->latest();
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function isScheduled(): bool
    {
        return $this->trigger_type === 'schedule';
    }

    public function isEvent(): bool
    {
        return $this->trigger_type === 'event';
    }

    public function triggerLabel(): string
    {
        if ($this->isEvent()) {
            return 'Event';
        }

        return match ($this->schedule) {
            'every_fifteen_minutes' => 'Every 15 minutes',
            'hourly' => 'Hourly',
            'daily' => 'Daily',
            default => ucfirst((string) $this->schedule),
        };
    }

    public function channelLabel(): string
    {
        $channels = collect($this->channels ?? ['database', 'mail'])
            ->map(fn (string $channel) => $channel === 'database' ? 'In-app' : 'Email');

        return $channels->implode(' + ') ?: 'In-app';
    }

    public function moduleLabel(): string
    {
        return match ($this->module) {
            'crm' => 'CRM',
            'projects' => 'Projects',
            'finance' => 'Finance',
            'hr' => 'HR',
            'support' => 'Support',
            'documents' => 'Documents',
            'portal' => 'Client Portal',
            default => ucfirst($this->module),
        };
    }

    public function definition(): array
    {
        return config('automations.definitions')[$this->key] ?? [];
    }
}
