<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\User;
use App\Notifications\ResetClientPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DomainSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_named_routes_target_the_configured_admin_and_client_hosts(): void
    {
        $this->assertSame('admin.dvmsoft.test', parse_url((string) config('domains.admin_url'), PHP_URL_HOST));
        $this->assertSame('client.dvmsoft.test', parse_url((string) config('domains.client_url'), PHP_URL_HOST));

        $this->assertSame('http://admin.dvmsoft.test/login', route('login'));
        $this->assertSame('http://admin.dvmsoft.test/dashboard', route('dashboard'));
        $this->assertSame('http://client.dvmsoft.test/login', route('client.login'));
        $this->assertSame('http://client.dvmsoft.test/dashboard', route('client.dashboard'));
        $this->assertSame('http://client.dvmsoft.test/projects', route('client.projects.index'));
    }

    public function test_super_admin_can_sign_in_on_the_admin_host(): void
    {
        $this->seed();

        $this->get('http://admin.dvmsoft.test/super-admin/login')
            ->assertOk()
            ->assertSee('Restricted operator sign in')
            ->assertDontSee('Dvmsoft Client Portal', false);

        $this->post('http://admin.dvmsoft.test/super-admin/login', [
            'email' => env('ADMIN_EMAIL', 'admin@dvmsoft.local'),
            'password' => env('ADMIN_PASSWORD', 'password'),
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated('web');
        $this->assertGuest('client');
        $this->get('http://admin.dvmsoft.test/dashboard')->assertOk();
    }

    public function test_employees_can_sign_in_on_the_admin_host(): void
    {
        $user = $this->userWithPermissions(['dashboard.view']);

        $this->post('http://admin.dvmsoft.test/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('client');
        $this->get('http://admin.dvmsoft.test/dashboard')->assertOk();
    }

    public function test_clients_sign_in_on_the_client_host_not_the_admin_host(): void
    {
        $user = ClientUser::factory()->create(['password' => 'password']);

        $this->get('http://client.dvmsoft.test/login')
            ->assertOk()
            ->assertSee('Dvmsoft Client Portal')
            ->assertDontSee('Sign in to Dvmsoft Admin', false);

        $this->post('http://client.dvmsoft.test/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticatedAs($user, 'client');
        $this->assertGuest('web');
        $this->get('http://client.dvmsoft.test/dashboard')->assertOk();
    }

    public function test_client_users_cannot_open_staff_routes_on_the_admin_host(): void
    {
        $user = ClientUser::factory()->create();

        $this->actingAs($user, 'client')
            ->get('http://admin.dvmsoft.test/dashboard')
            ->assertRedirect(route('login'));

        $this->actingAs($user, 'client')
            ->get('http://admin.dvmsoft.test/users')
            ->assertRedirect(route('login'));
    }

    public function test_staff_cannot_open_client_routes_without_client_authentication(): void
    {
        $staff = User::factory()->superAdmin()->create();

        $this->actingAs($staff, 'web')
            ->get('http://client.dvmsoft.test/dashboard')
            ->assertRedirect(route('client.login'));

        $this->actingAs($staff, 'web')
            ->get('http://client.dvmsoft.test/projects')
            ->assertRedirect(route('client.login'));
    }

    public function test_super_admin_routes_are_not_served_on_the_client_host(): void
    {
        $this->get('http://client.dvmsoft.test/super-admin/login')->assertNotFound();
        $this->get('http://client.dvmsoft.test/super-admin/users')->assertNotFound();
    }

    public function test_admin_host_uses_the_staff_session_cookie(): void
    {
        $this->get('http://admin.dvmsoft.test/login')
            ->assertCookie(config('domains.admin_session_cookie'))
            ->assertCookieMissing(config('domains.client_session_cookie'));
    }

    public function test_client_host_uses_the_client_session_cookie(): void
    {
        $this->get('http://client.dvmsoft.test/login')
            ->assertCookie(config('domains.client_session_cookie'))
            ->assertCookieMissing(config('domains.admin_session_cookie'));
    }

    public function test_staff_logout_on_the_admin_host_returns_to_staff_login(): void
    {
        $user = $this->userWithPermissions(['dashboard.view']);

        $this->actingAs($user, 'web')
            ->post('http://admin.dvmsoft.test/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

    public function test_client_logout_on_the_client_host_returns_to_client_login(): void
    {
        $user = ClientUser::factory()->create();

        $this->actingAs($user, 'client')
            ->post('http://client.dvmsoft.test/logout')
            ->assertRedirect(route('client.login'));

        $this->assertGuest('client');
    }

    public function test_legacy_client_prefix_on_the_admin_host_redirects_to_the_client_origin(): void
    {
        $this->get('http://admin.dvmsoft.test/client/login')
            ->assertRedirect(route('client.login'));

        $this->get('http://admin.dvmsoft.test/client/dashboard')
            ->assertRedirect(route('client.dashboard'));

        $this->get('http://admin.dvmsoft.test/client/projects')
            ->assertRedirect(route('client.projects.index'));
    }

    public function test_loopback_hosts_serve_the_staff_application(): void
    {
        $this->get('http://127.0.0.1/login')
            ->assertOk()
            ->assertSee('Dvmsoft Admin');

        $this->get('http://localhost/login')
            ->assertOk()
            ->assertSee('Dvmsoft Admin');
    }

    public function test_unknown_hosts_are_redirected_to_the_admin_origin(): void
    {
        $this->get('http://untrusted.example.test/login')
            ->assertRedirect('http://admin.dvmsoft.test/login');
    }

    public function test_client_password_reset_links_use_the_client_host(): void
    {
        Notification::fake();
        $user = ClientUser::factory()->create(['email' => 'reset-host@client.test']);

        $this->post(route('client.password.email'), ['email' => $user->email])->assertRedirect();

        Notification::assertSentTo($user, ResetClientPassword::class, function (ResetClientPassword $notification) use ($user) {
            $url = route('client.password.reset', [
                'token' => $notification->token,
                'email' => $user->email,
            ]);

            $this->assertStringStartsWith('http://client.dvmsoft.test/reset-password/', $url);
            $this->assertStringNotContainsString('admin.dvmsoft.test', $url);

            return true;
        });
    }

    public function test_client_password_reset_completes_on_the_client_host(): void
    {
        Notification::fake();
        $user = ClientUser::factory()->create(['email' => 'reset-complete@client.test']);

        $this->post('http://client.dvmsoft.test/forgot-password', ['email' => $user->email])->assertRedirect();

        $token = null;
        Notification::assertSentTo($user, ResetClientPassword::class, function (ResetClientPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->assertNotNull($token);

        $this->get('http://client.dvmsoft.test/reset-password/'.$token.'?email='.urlencode($user->email))
            ->assertOk();

        $this->post('http://client.dvmsoft.test/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('client.login'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
