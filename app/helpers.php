<?php

use App\Services\SettingsService;
use App\Support\ApplicationDomains;
use Illuminate\Support\Facades\Storage;

if (! function_exists('domains')) {
    function domains(): ApplicationDomains
    {
        return app(ApplicationDomains::class);
    }
}

if (! function_exists('admin_url')) {
    function admin_url(string $path = '/'): string
    {
        return domains()->adminUrl($path);
    }
}

if (! function_exists('client_url')) {
    function client_url(string $path = '/'): string
    {
        return domains()->clientUrl($path);
    }
}

if (! function_exists('settings')) {
    /**
     * Get a setting value or the settings service.
     */
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $settings = app(SettingsService::class);

        if ($key === null) {
            return $settings;
        }

        return $settings->get($key, $default);
    }
}

if (! function_exists('company_name')) {
    function company_name(): string
    {
        return (string) settings('company.name', config('app.name'));
    }
}

if (! function_exists('money')) {
    function money(mixed $amount, int $decimals = 2): string
    {
        $currency = strtoupper((string) settings('company.currency', 'INR'));
        $formatted = number_format((float) $amount, $decimals);

        return $currency === 'INR' ? '₹'.$formatted : $currency.' '.$formatted;
    }
}

if (! function_exists('company_logo_path')) {
    function company_logo_path(): ?string
    {
        $relative = settings('company.logo');

        if (! is_string($relative) || $relative === '') {
            return null;
        }

        $path = Storage::disk(config('settings.branding_disk'))->path($relative);

        return is_file($path) ? $path : null;
    }
}
