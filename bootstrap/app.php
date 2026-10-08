<?php

use App\Http\Middleware\ConfigureApplicationDomain;
use App\Http\Middleware\EnsureClientEmailIsVerified;
use App\Http\Middleware\EnsureClientUserIsActive;
use App\Http\Middleware\EnsureHasPermission;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetClientGuard;
use App\Support\ApplicationDomains;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            $domains = app(ApplicationDomains::class);

            if ($domains->hostsAreSeparated()) {
                foreach ($domains->staffHosts() as $host) {
                    Route::middleware('web')
                        ->domain($host)
                        ->group(base_path('routes/web.php'));
                }

                Route::middleware(['web', SetClientGuard::class])
                    ->domain($domains->clientHost())
                    ->name('client.')
                    ->group(base_path('routes/client.php'));

                Route::middleware('web')->group(base_path('routes/legacy.php'));

                return;
            }

            Route::middleware('web')->group(base_path('routes/web.php'));

            Route::middleware(['web', SetClientGuard::class])
                ->prefix('client')
                ->name('client.')
                ->group(base_path('routes/client.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => EnsureHasPermission::class,
            'active' => EnsureUserIsActive::class,
            'super-admin' => EnsureSuperAdmin::class,
            'client.guard' => SetClientGuard::class,
            'client.active' => EnsureClientUserIsActive::class,
            'client.verified' => EnsureClientEmailIsVerified::class,
        ]);

        $middleware->web(prepend: [
            ConfigureApplicationDomain::class,
        ]);

        $middleware->trustHosts(
            at: fn () => app(ApplicationDomains::class)->trustedHostPatterns(),
            subdomains: false,
        );

        $middleware->redirectGuestsTo(function (Request $request) {
            return app(ApplicationDomains::class)->isClientContext($request)
                ? route('client.login')
                : route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            if (app(ApplicationDomains::class)->isClientContext($request)) {
                return route('client.dashboard');
            }

            $user = $request->user('web');

            return $user ? route($user->homeRoute()) : route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
