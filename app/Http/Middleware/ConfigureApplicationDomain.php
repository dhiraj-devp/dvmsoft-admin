<?php

namespace App\Http\Middleware;

use App\Support\ApplicationDomains;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ConfigureApplicationDomain
{
    public function __construct(protected ApplicationDomains $domains) {}

    public function handle(Request $request, Closure $next): Response
    {
        $client = $this->domains->isClientContext($request);

        config([
            'session.domain' => null,
            'session.cookie' => $client
                ? $this->domains->clientSessionCookie()
                : $this->domains->adminSessionCookie(),
        ]);

        $this->refreshSessionDriver();

        $origin = $this->domains->originForRequest($request);
        $scheme = parse_url($origin, PHP_URL_SCHEME) ?: 'http';

        if ($client) {
            Auth::shouldUse('client');
        } else {
            Auth::shouldUse('web');
        }

        URL::useOrigin($origin);
        URL::forceScheme($scheme);

        return $next($request);
    }

    protected function refreshSessionDriver(): void
    {
        if (app()->bound('session')) {
            $manager = app('session');

            if (method_exists($manager, 'forgetDrivers')) {
                $manager->forgetDrivers();
            }
        }

        if (app()->bound('session.store')) {
            app()->forgetInstance('session.store');
        }
    }
}
