<?php

namespace Tests\Unit;

use App\Support\ApplicationDomains;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApplicationDomainsTest extends TestCase
{
    public function test_separated_hosts_are_detected_from_configuration(): void
    {
        $domains = app(ApplicationDomains::class);

        $this->assertTrue($domains->hostsAreSeparated());
        $this->assertSame('admin.dvmsoft.test', $domains->adminHost());
        $this->assertSame('client.dvmsoft.test', $domains->clientHost());
        $this->assertSame('http://admin.dvmsoft.test', $domains->adminOrigin());
        $this->assertSame('http://client.dvmsoft.test', $domains->clientOrigin());
        $this->assertSame('http://client.dvmsoft.test/login', $domains->clientUrl('/login'));
    }

    public function test_client_context_uses_host_not_request_input(): void
    {
        $domains = app(ApplicationDomains::class);

        $admin = Request::create('http://admin.dvmsoft.test/dashboard', 'GET', ['host' => 'client.dvmsoft.test']);
        $client = Request::create('http://client.dvmsoft.test/dashboard', 'GET', ['host' => 'admin.dvmsoft.test']);

        $this->assertFalse($domains->isClientContext($admin));
        $this->assertTrue($domains->isClientContext($client));
        $this->assertSame($domains->adminSessionCookie(), $domains->sessionCookieFor($admin));
        $this->assertSame($domains->clientSessionCookie(), $domains->sessionCookieFor($client));
    }

    public function test_same_host_configuration_falls_back_to_path_prefixes(): void
    {
        config([
            'domains.admin_url' => 'http://localhost:8000',
            'domains.client_url' => 'http://localhost:8000',
        ]);

        $domains = new ApplicationDomains;

        $this->assertFalse($domains->hostsAreSeparated());

        $staff = Request::create('http://localhost:8000/login');
        $portal = Request::create('http://localhost:8000/client/login');

        $this->assertFalse($domains->isClientContext($staff));
        $this->assertTrue($domains->isClientContext($portal));
    }

    public function test_loopback_hosts_are_treated_as_staff_hosts(): void
    {
        $domains = app(ApplicationDomains::class);

        $loopback = Request::create('http://127.0.0.1/login');
        $localhost = Request::create('http://localhost/login');

        $this->assertTrue($domains->isLoopbackHost($loopback));
        $this->assertTrue($domains->isAdminHost($loopback));
        $this->assertTrue($domains->isAdminHost($localhost));
        $this->assertFalse($domains->isClientContext($loopback));
        $this->assertContains('127.0.0.1', $domains->staffHosts());
    }
}
