<?php

namespace App\Livewire\Ai;

use App\Ai\Exceptions\AiConfigurationException;
use App\Ai\Exceptions\AiDisabledException;
use App\Ai\Exceptions\AiException;
use App\Ai\Exceptions\AiTimeoutException;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

abstract class AiPanel extends Component
{
    public bool $generating = false;

    public bool $generated = false;

    public bool $applied = false;

    public ?string $error = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $result = null;

    abstract protected function feature(): string;

    abstract protected function permission(): string;

    public function copyNotice(string $label = 'Copied'): void
    {
        $this->dispatch('notify', type: 'success', message: $label);
    }

    /**
     * @param  callable(): array<string, mixed>  $callback
     */
    protected function generateWith(callable $callback, ?Model $record = null): void
    {
        $this->authorize($this->permission());

        if ($record) {
            $this->authorize('view', $record);
        }

        $this->error = null;
        $this->generated = false;
        $this->applied = false;
        $this->generating = true;

        try {
            $key = 'ai:'.Auth::id();
            $limit = (int) config('ai.rate_limit_per_minute', 20);

            if (RateLimiter::tooManyAttempts($key, $limit)) {
                $this->error = 'Too many AI requests. Please wait a minute and try again.';

                return;
            }

            RateLimiter::hit($key, 60);
            $this->result = $callback();
            $this->generated = true;

            app(AuditLogger::class)->record(
                action: 'generated',
                module: 'ai',
                auditable: $record,
                newValues: [
                    'feature' => $this->feature(),
                    'success' => true,
                ],
            );
        } catch (AiDisabledException) {
            $this->failGeneration($record, 'AI assistance is turned off in Settings.');
        } catch (AiTimeoutException) {
            $this->failGeneration($record, 'The AI provider timed out. Try again.');
        } catch (AiConfigurationException) {
            $this->failGeneration($record, 'AI is not configured. An administrator must set AI_API_KEY in the environment.');
        } catch (AiException) {
            $this->failGeneration($record, 'AI assistance is unavailable right now.');
        } finally {
            $this->generating = false;
        }
    }

    protected function failGeneration(?Model $record, string $message): void
    {
        $this->error = $message;
        $this->result = null;

        app(AuditLogger::class)->record(
            action: 'failed',
            module: 'ai',
            auditable: $record,
            newValues: [
                'feature' => $this->feature(),
                'success' => false,
            ],
        );
    }

    /**
     * @return list<string>
     */
    protected function list(string $key): array
    {
        $value = $this->result[$key] ?? [];

        if (! is_array($value)) {
            return array_filter([(string) $value]);
        }

        return array_values(array_filter(array_map('strval', $value)));
    }
}
