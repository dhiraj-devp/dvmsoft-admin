<?php

namespace App\Automations;

use App\Enums\AutomationRunStatus;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutomationEngine
{
    public function __construct(protected AuditLogger $audit) {}

    /**
     * @return list<string>
     */
    public function scheduledKeys(): array
    {
        return collect(config('automations.definitions', []))
            ->filter(fn (array $definition) => ($definition['trigger_type'] ?? '') === 'schedule')
            ->keys()
            ->all();
    }

    public function globallyEnabled(): bool
    {
        return (bool) settings('automations.enabled', true);
    }

    public function syncCatalog(): void
    {
        foreach (config('automations.definitions', []) as $key => $definition) {
            $automation = Automation::query()->firstOrNew(['key' => $key]);
            $automation->fill([
                'name' => $definition['name'],
                'description' => $definition['description'] ?? null,
                'module' => $definition['module'],
                'trigger_type' => $definition['trigger_type'],
                'schedule' => $definition['schedule'] ?? null,
            ]);

            if (! $automation->exists) {
                $automation->channels = ['database', 'mail'];
                $automation->enabled = true;
            }

            $automation->save();
        }
    }

    public function record(string $key): Automation
    {
        $this->syncCatalog();

        $automation = Automation::query()->where('key', $key)->first();

        if (! $automation) {
            throw new \InvalidArgumentException('Unknown automation ['.$key.'].');
        }

        return $automation;
    }

    public function isEnabled(string $key): bool
    {
        if (! $this->globallyEnabled()) {
            return false;
        }

        return $this->record($key)->enabled;
    }

    /**
     * @param  list<string>|null  $keys
     * @return list<AutomationRun>
     */
    public function runDue(?array $keys = null, bool $force = false): array
    {
        $this->syncCatalog();

        $query = Automation::query()->where('trigger_type', 'schedule');

        if ($keys) {
            $query->whereIn('key', $keys);
        }

        $runs = [];

        foreach ($query->orderBy('module')->orderBy('name')->get() as $automation) {
            if (! $force && ! $this->isDue($automation)) {
                continue;
            }

            $runs[] = $this->run($automation->key, $force);
        }

        return $runs;
    }

    public function run(string $key, bool $force = false): AutomationRun
    {
        $automation = $this->record($key);
        $started = now();

        if (! $this->globallyEnabled() || ! $automation->enabled) {
            $run = $automation->runs()->create([
                'status' => AutomationRunStatus::Skipped,
                'processed_count' => 0,
                'notified_count' => 0,
                'error' => 'Automation is disabled.',
                'started_at' => $started,
                'finished_at' => now(),
            ]);

            $automation->forceFill([
                'last_status' => AutomationRunStatus::Skipped,
                'last_error' => 'Automation is disabled.',
            ])->saveQuietly();

            return $run;
        }

        if ($automation->isEvent() && ! $force) {
            return $automation->runs()->create([
                'status' => AutomationRunStatus::Skipped,
                'processed_count' => 0,
                'notified_count' => 0,
                'error' => 'Event automations run from workflow hooks, not the scheduler.',
                'started_at' => $started,
                'finished_at' => now(),
            ]);
        }

        try {
            $handler = $this->handler($automation);
            $result = $handler->handle($automation);

            $run = $automation->runs()->create([
                'status' => AutomationRunStatus::Success,
                'processed_count' => $result->processed,
                'notified_count' => $result->notified,
                'started_at' => $started,
                'finished_at' => now(),
            ]);

            $automation->forceFill([
                'last_ran_at' => now(),
                'last_status' => AutomationRunStatus::Success,
                'last_error' => null,
                'last_notified_count' => $result->notified,
            ])->saveQuietly();

            return $run;
        } catch (Throwable $exception) {
            Log::warning('Automation failed', [
                'key' => $key,
                'message' => $exception->getMessage(),
            ]);

            $run = $automation->runs()->create([
                'status' => AutomationRunStatus::Failed,
                'processed_count' => 0,
                'notified_count' => 0,
                'error' => mb_substr($exception->getMessage(), 0, 2000),
                'started_at' => $started,
                'finished_at' => now(),
            ]);

            $automation->forceFill([
                'last_ran_at' => now(),
                'last_status' => AutomationRunStatus::Failed,
                'last_error' => mb_substr($exception->getMessage(), 0, 2000),
            ])->saveQuietly();

            $this->audit->record(
                action: 'failed',
                module: 'automations',
                auditable: $automation,
                newValues: [
                    'key' => $automation->key,
                    'error' => mb_substr($exception->getMessage(), 0, 240),
                ],
            );

            return $run;
        }
    }

    public function isDue(Automation $automation): bool
    {
        if (! $automation->enabled || ! $automation->isScheduled()) {
            return false;
        }

        if (! $automation->last_ran_at) {
            return true;
        }

        $minutes = match ($automation->schedule) {
            'every_fifteen_minutes' => 14,
            'hourly' => 55,
            'daily' => 23 * 60,
            default => 55,
        };

        return $automation->last_ran_at->lte(now()->subMinutes($minutes));
    }

    protected function handler(Automation $automation): Contracts\AutomationHandler
    {
        $class = $automation->definition()['handler'] ?? null;

        if (! is_string($class) || ! is_subclass_of($class, Contracts\AutomationHandler::class)) {
            throw new \RuntimeException('Automation handler is not configured for '.$automation->key);
        }

        return app($class);
    }
}
