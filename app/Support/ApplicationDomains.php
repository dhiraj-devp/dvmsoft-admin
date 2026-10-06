<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class ApplicationDomains
{
    public function adminOrigin(): string
    {
        return $this->originFromUrl((string) config('domains.admin_url', config('app.url')));
    }

    public function clientOrigin(): string
    {
        $configured = trim((string) config('domains.client_url'));

        if ($configured === '') {
            return $this->adminOrigin();
        }

        return $this->originFromUrl($configured);
    }

    public function adminHost(): string
    {
        return $this->hostFromOrigin($this->adminOrigin());
    }

    public function clientHost(): string
    {
        return $this->hostFromOrigin($this->clientOrigin());
    }

    public function adminScheme(): string
    {
        return parse_url($this->adminOrigin(), PHP_URL_SCHEME) ?: 'http';
    }

    public function clientScheme(): string
    {
        return parse_url($this->clientOrigin(), PHP_URL_SCHEME) ?: 'http';
    }

    public function hostsAreSeparated(): bool
    {
        $admin = $this->adminHost();
        $client = $this->clientHost();

        return $admin !== '' && $client !== '' && strcasecmp($admin, $client) !== 0;
    }

    public function isAdminHost(Request $request): bool
    {
        $host = $request->getHost();

        return collect($this->staffHosts())->contains(
            fn (string $staffHost) => $this->hostsMatch($host, $staffHost)
        );
    }

    public function isLoopbackHost(Request $request): bool
    {
        return in_array(strtolower($request->getHost()), $this->loopbackHosts(), true);
    }

    /**
     * @return list<string>
     */
    public function loopbackHosts(): array
    {
        return ['127.0.0.1', 'localhost', '::1'];
    }

    /**
     * Hosts that serve the staff application.
     *
     * @return list<string>
     */
    public function staffHosts(): array
    {
        return collect([
            $this->adminHost(),
            $this->appHost(),
            ...$this->wwwAliases($this->adminHost()),
            ...$this->wwwAliases($this->appHost()),
            ...$this->loopbackHosts(),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function isClientHost(Request $request): bool
    {
        if (! $this->hostsAreSeparated()) {
            return false;
        }

        return $this->hostsMatch($request->getHost(), $this->clientHost());
    }

    public function isLegacyClientPath(Request $request): bool
    {
        return $request->is('client') || $request->is('client/*');
    }

    public function isClientContext(Request $request): bool
    {
        if ($this->hostsAreSeparated()) {
            return $this->isClientHost($request);
        }

        return $this->isLegacyClientPath($request);
    }

    public function adminSessionCookie(): string
    {
        return (string) config('domains.admin_session_cookie', 'dvmsoft-admin-session');
    }

    public function clientSessionCookie(): string
    {
        return (string) config('domains.client_session_cookie', 'dvmsoft-client-session');
    }

    public function sessionCookieFor(Request $request): string
    {
        return $this->isClientContext($request)
            ? $this->clientSessionCookie()
            : $this->adminSessionCookie();
    }

    public function adminUrl(string $path = '/'): string
    {
        return $this->join($this->adminOrigin(), $path);
    }

    public function clientUrl(string $path = '/'): string
    {
        return $this->join($this->clientOrigin(), $path);
    }

    /**
     * Map a legacy `/client/...` path to the canonical client-host path.
     */
    public function canonicalClientPath(?string $legacyPath = null): string
    {
        $path = '/'.ltrim((string) $legacyPath, '/');

        return $path === '/' ? '/' : $path;
    }

    /**
     * @return list<string>
     */
    public function trustedHostPatterns(): array
    {
        return collect([
            $this->adminHost(),
            $this->clientHost(),
            $this->appHost(),
            ...$this->wwwAliases($this->adminHost()),
            ...$this->wwwAliases($this->clientHost()),
            ...$this->wwwAliases($this->appHost()),
            ...$this->loopbackHosts(),
        ])
            ->filter()
            ->unique(fn (string $host) => strtolower($host))
            ->map(fn (string $host) => '^'.preg_quote($host, '/').'$')
            ->values()
            ->all();
    }

    public function originForRequest(Request $request): string
    {
        if ($this->isClientContext($request)) {
            return $this->clientOrigin();
        }

        if ($this->isLoopbackHost($request)) {
            return $request->getSchemeAndHttpHost();
        }

        return $this->adminOrigin();
    }

    public function formatGeneratedRoot(string $root, mixed $route = null): string
    {
        $routeDomain = $route instanceof Route ? $route->getDomain() : null;
        $request = request();

        if ($request instanceof Request && $this->isLoopbackHost($request)) {
            if (is_string($routeDomain) && $this->hostsMatch($routeDomain, $this->clientHost())) {
                return $this->clientOrigin();
            }

            return $request->getSchemeAndHttpHost();
        }

        if (is_string($routeDomain) && $routeDomain !== '') {
            if ($this->hostsMatch($routeDomain, $this->clientHost())) {
                return $this->clientOrigin();
            }

            if ($this->hostsMatch($routeDomain, $this->adminHost())) {
                return $this->adminOrigin();
            }
        }

        $rootHost = parse_url($root, PHP_URL_HOST) ?: '';

        if ($this->hostsMatch($rootHost, $this->clientHost())) {
            return $this->clientOrigin();
        }

        if ($this->hostsMatch($rootHost, $this->adminHost())) {
            return $this->adminOrigin();
        }

        return $root;
    }

    public function originFromUrl(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            return rtrim($url, '/');
        }

        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'];
        $origin = $scheme.'://'.$host;
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $defaultPort = $scheme === 'https' ? 443 : 80;

        if ($port && $port !== $defaultPort) {
            $origin .= ':'.$port;
        }

        return $origin;
    }

    protected function hostFromOrigin(string $origin): string
    {
        return strtolower((string) (parse_url($origin, PHP_URL_HOST) ?: ''));
    }

    protected function appHost(): string
    {
        return strtolower((string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: ''));
    }

    /**
     * @return list<string>
     */
    protected function wwwAliases(string $host): array
    {
        if ($host === '') {
            return [];
        }

        return str_starts_with($host, 'www.')
            ? [substr($host, 4)]
            : ['www.'.$host];
    }

    protected function hostsMatch(string $left, string $right): bool
    {
        return $left !== '' && $right !== '' && strcasecmp($left, $right) === 0;
    }

    protected function join(string $origin, string $path): string
    {
        $path = '/'.ltrim($path, '/');

        if ($path === '/') {
            return $origin;
        }

        return $origin.$path;
    }
}
