<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $resolved = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        if (array_key_exists($key, $settings)) {
            return $settings[$key];
        }

        return $this->definition($key)['default'] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        if (! $this->tableReady()) {
            return $this->defaults();
        }

        $this->resolved = Cache::remember(
            config('settings.cache_key'),
            config('settings.cache_ttl'),
            function (): array {
                $stored = Setting::query()->pluck('value', 'key')->all();
                $values = [];

                foreach (config('settings.definitions', []) as $key => $definition) {
                    $raw = $stored[$key] ?? $definition['default'] ?? null;
                    $values[$key] = $this->cast($raw, $definition['type'] ?? 'string');
                }

                foreach ($stored as $key => $value) {
                    if (! array_key_exists($key, $values)) {
                        $values[$key] = $value;
                    }
                }

                return $values;
            }
        );

        return $this->resolved;
    }

    public function set(string $key, mixed $value, ?array $meta = null): Setting
    {
        $definition = $this->definition($key);

        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $this->serialize($value, $meta['type'] ?? $definition['type'] ?? 'string'),
                'type' => $meta['type'] ?? $definition['type'] ?? 'string',
                'group' => $meta['group'] ?? $definition['group'] ?? 'system',
                'description' => $meta['description'] ?? $definition['description'] ?? null,
                'is_public' => $meta['is_public'] ?? $definition['is_public'] ?? false,
            ]
        );

        $this->flush();

        return $setting;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function put(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function fileUrl(string $key): ?string
    {
        $path = $this->get($key);

        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk(config('settings.branding_disk'))->url($path);
    }

    public function flush(): void
    {
        $this->resolved = null;
        Cache::forget(config('settings.cache_key'));
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(string $key): array
    {
        return config('settings.definitions.'.$key, []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $values = [];

        foreach (config('settings.definitions', []) as $key => $definition) {
            $values[$key] = $this->cast($definition['default'] ?? null, $definition['type'] ?? 'string');
        }

        return $values;
    }

    protected function tableReady(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }

    protected function cast(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return match ($type) {
                'boolean' => false,
                'integer' => 0,
                'json' => [],
                default => $value === '' ? '' : null,
            };
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => is_array($value) ? $value : json_decode((string) $value, true) ?? [],
            default => (string) $value,
        };
    }

    protected function serialize(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'json' => json_encode($value),
            default => (string) $value,
        };
    }
}
