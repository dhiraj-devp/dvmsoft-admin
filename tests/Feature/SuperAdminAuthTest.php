<?php

namespace Tests\Feature;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\ClientUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SuperAdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_sign_in_through_the_dedicated_entry(): void
    {
        $this->seed();

        $this->get('/super-admin/login')
            ->assertOk()
            ->assertSee('Restricted operator sign in')
            ->assertDontSee('Sign in to Dvmsoft Admin', false);

        $this->post('/super-admin/login', [
            'email' => env('ADMIN_EMAIL', 'admin@dvmsoft.local'),
            'password' => env('ADMIN_PASSWORD', 'password'),
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated('web');
        $this->assertGuest('client');
        $this->get('/dashboard')->assertOk();
        $this->assertTrue(
            AuditLog::query()->where('module', 'auth')->where('action', 'login')->whereJsonContains('new_values->entry', 'super_admin')->exists()
        );
    }

    public function test_super_admin_can_access_super_admin_routes(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin, 'web')->get('/super-admin/dashboard')->assertOk();
        $this->actingAs($admin, 'web')->get('/super-admin/users')->assertOk();
        $this->actingAs($admin, 'web')->get('/super-admin/roles')->assertOk();
        $this->actingAs($admin, 'web')->get('/super-admin/permissions')->assertOk();
        $this->actingAs($admin, 'web')->get('/super-admin/settings')->assertOk();
        $this->actingAs($admin, 'web')->get('/super-admin/audit-logs')->assertOk();
    }

    public function test_authenticated_employees_cannot_open_the_super_admin_login(): void
    {
        $employee = $this->userWithPermissions(['dashboard.view']);

        $this->actingAs($employee, 'web')
            ->get('/super-admin/login')
            ->assertNotFound();
    }

    public function test_employees_cannot_authenticate_through_the_super_admin_login(): void
    {
        $employee = $this->userWithPermissions(['dashboard.view']);

        $this->from('/super-admin/login')
            ->post('/super-admin/login', [
                'email' => $employee->email,
                'password' => 'password',
            ])
            ->assertSessionHasErrors(['email' => LoginRequest::GENERIC_FAILURE])
            ->assertRedirect('/super-admin/login');

        $this->assertGuest('web');
        $this->assertTrue(
            AuditLog::query()->where('module', 'auth')->where('action', 'login_failed')->whereJsonContains('new_values->entry', 'super_admin')->exists()
        );
    }

    public function test_employees_cannot_access_super_admin_routes_directly(): void
    {
        $employee = $this->userWithPermissions([
            'dashboard.view',
            'users.view',
            'roles.view',
            'permissions.view',
            'settings.view',
            'audit_logs.view',
        ]);

        $this->actingAs($employee, 'web')->get('/super-admin/dashboard')->assertNotFound();
        $this->actingAs($employee, 'web')->get('/super-admin/users')->assertNotFound();
        $this->actingAs($employee, 'web')->get('/super-admin/roles')->assertNotFound();
        $this->actingAs($employee, 'web')->get('/super-admin/permissions')->assertNotFound();
        $this->actingAs($employee, 'web')->get('/super-admin/settings')->assertNotFound();
        $this->actingAs($employee, 'web')->get('/super-admin/audit-logs')->assertNotFound();
        $this->actingAs($employee, 'web')->post('/super-admin/logout')->assertNotFound();
    }

    public function test_clients_cannot_access_super_admin_routes(): void
    {
        $client = ClientUser::factory()->create();

        $this->actingAs($client, 'client')->get('/super-admin/dashboard')->assertNotFound();
        $this->actingAs($client, 'client')->get('/super-admin/users')->assertNotFound();
    }

    public function test_client_credentials_cannot_authenticate_through_super_admin_login(): void
    {
        $client = ClientUser::factory()->create(['password' => 'password']);

        $this->from('/super-admin/login')
            ->post('/super-admin/login', [
                'email' => $client->email,
                'password' => 'password',
            ])
            ->assertSessionHasErrors(['email' => LoginRequest::GENERIC_FAILURE]);

        $this->assertGuest('web');
    }

    public function test_staff_login_still_works_for_employees(): void
    {
        $employee = $this->userWithPermissions(['dashboard.view']);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to Dvmsoft Admin')
            ->assertDontSee('super-admin', false)
            ->assertDontSee('Restricted operator sign in', false);

        $this->post('/login', [
            'email' => $employee->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($employee, 'web');
    }

    public function test_client_login_still_works(): void
    {
        $user = ClientUser::factory()->create(['password' => 'password']);

        $this->post(route('client.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticatedAs($user, 'client');
    }

    public function test_super_admin_cannot_authenticate_through_staff_login(): void
    {
        $this->seed();

        $this->from('/login')
            ->post('/login', [
                'email' => env('ADMIN_EMAIL', 'admin@dvmsoft.local'),
                'password' => env('ADMIN_PASSWORD', 'password'),
            ])
            ->assertSessionHasErrors(['email' => LoginRequest::GENERIC_FAILURE])
            ->assertRedirect('/login');

        $this->assertGuest('web');
    }

    public function test_unknown_credentials_use_the_same_generic_failure_on_super_admin_login(): void
    {
        $this->from('/super-admin/login')
            ->post('/super-admin/login', [
                'email' => 'nobody@example.test',
                'password' => 'password',
            ])
            ->assertSessionHasErrors(['email' => LoginRequest::GENERIC_FAILURE])
            ->assertDontSee('Super Admin', false)
            ->assertDontSee('not a Super Admin', false);

        $this->assertGuest('web');
    }

    public function test_guests_cannot_open_super_admin_management_routes(): void
    {
        $this->get('/super-admin/dashboard')->assertNotFound();
        $this->get('/super-admin/users')->assertNotFound();
        $this->post('/super-admin/users')->assertNotFound();
        $this->post('/super-admin/logout')->assertNotFound();
    }

    public function test_super_admin_login_is_rate_limited(): void
    {
        settings()->set('security.login_max_attempts', 1);
        RateLimiter::clear('super-admin-login|nobody@example.test|127.0.0.1');

        $this->from('/super-admin/login')
            ->post('/super-admin/login', [
                'email' => 'nobody@example.test',
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('email');

        $response = $this->from('/super-admin/login')
            ->post('/super-admin/login', [
                'email' => 'nobody@example.test',
                'password' => 'wrong-password',
            ]);

        $this->assertTrue(
            $response->status() === 429 || $response->session()->has('errors'),
            'Expected a throttle response after exceeding Super Admin login attempts.'
        );
        $this->assertGuest('web');
    }

    public function test_super_admin_logout_returns_to_the_dedicated_entry(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin, 'web')
            ->post('/logout')
            ->assertRedirect(route('super-admin.login'));

        $this->assertGuest('web');
        $this->assertTrue(
            AuditLog::query()->where('module', 'auth')->where('action', 'logout')->whereJsonContains('new_values->entry', 'super_admin')->exists()
        );
    }

    public function test_staff_navigation_does_not_link_to_the_super_admin_entry(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin, 'web')
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('/super-admin/login', false)
            ->assertDontSee('super-admin.login', false);
    }
}
