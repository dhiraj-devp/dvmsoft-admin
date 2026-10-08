<?php

namespace App\Providers;

use App\Ai\AiManager;
use App\Contracts\AiServiceInterface;
use App\Models\User;
use App\Services\NavigationService;
use App\Services\OfficeCalendar;
use App\Services\SettingsService;
use App\Support\ApplicationDomains;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(NavigationService::class);
        $this->app->singleton(OfficeCalendar::class);
        $this->app->singleton(ApplicationDomains::class);
        $this->app->singleton(AiManager::class);
        $this->app->bind(AiServiceInterface::class, fn ($app) => $app->make(AiManager::class)->driver());
    }

    public function boot(): void
    {
        $this->registerPermissionGates();
        $this->configureRateLimiting();
        $this->shareViews();
        $this->applyRuntimeSettings();
        $this->configureUrlGeneration();
    }

    protected function registerPermissionGates(): void
    {
        collect(config('permissions', []))
            ->flatMap(fn (array $items) => $items)
            ->pluck('name')
            ->filter()
            ->each(function (string $permission): void {
                Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
            });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $max = (int) settings('security.login_max_attempts', 5);

            return Limit::perMinute($max)->by($request->ip().'|'.$request->input('email'));
        });

        RateLimiter::for('super-admin-login', function (Request $request) {
            $max = (int) settings('security.login_max_attempts', 5);

            return Limit::perMinute($max)->by('super-admin|'.$request->ip().'|'.$request->input('email'));
        });

        RateLimiter::for('ai', function (Request $request) {
            $max = (int) config('ai.rate_limit_per_minute', 20);

            return Limit::perMinute($max)->by($request->user()?->id ?: $request->ip());
        });
    }

    protected function shareViews(): void
    {
        View::composer(['layouts.app', 'layouts.guest'], function ($view): void {
            try {
                $view->with([
                    'navigation' => app(NavigationService::class)->for(auth()->user()),
                    'companyName' => company_name(),
                    'companyLogo' => settings()->fileUrl('company.logo'),
                ]);
            } catch (\Throwable) {
                $view->with([
                    'navigation' => [],
                    'companyName' => config('app.name'),
                    'companyLogo' => null,
                ]);
            }
        });

        View::composer(['layouts.client', 'layouts.client-guest'], function ($view): void {
            try {
                $view->with([
                    'companyName' => company_name(),
                    'companyLogo' => settings()->fileUrl('company.logo'),
                ]);
            } catch (\Throwable) {
                $view->with([
                    'companyName' => config('app.name'),
                    'companyLogo' => null,
                ]);
            }
        });
    }

    protected function applyRuntimeSettings(): void
    {
        try {
            $timezone = settings('company.timezone');
            if (is_string($timezone) && $timezone !== '') {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }

            $fromAddress = settings('email.from_address');
            $fromName = settings('email.from_name');
            if ($fromAddress) {
                config(['mail.from.address' => $fromAddress]);
            }
            if ($fromName) {
                config(['mail.from.name' => $fromName]);
            }

            $timeout = (int) settings('security.session_timeout', 120);
            if ($timeout > 0) {
                config(['session.lifetime' => $timeout]);
            }
        } catch (\Throwable) {
            // Database may not be ready during early install commands.
        }
    }

    protected function configureUrlGeneration(): void
    {
        $domains = $this->app->make(ApplicationDomains::class);

        URL::formatHostUsing(fn (string $root, $route = null) => $domains->formatGeneratedRoot($root, $route));

        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            URL::useOrigin($domains->adminOrigin());
            URL::forceScheme($domains->adminScheme());
        }
    }
}
