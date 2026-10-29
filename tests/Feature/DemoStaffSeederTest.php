<?php

namespace Tests\Feature;

use App\Enums\EmploymentStatus;
use App\Models\User;
use Database\Seeders\DemoStaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoStaffSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_staff_are_indian_people_with_roles_and_managers(): void
    {
        $this->seed();

        $priya = User::query()->where('email', 'priya.sharma@dvmsoft.local')->first();

        $this->assertNotNull($priya);
        $this->assertSame('Priya Sharma', $priya->name);
        $this->assertStringStartsWith('+91', (string) $priya->phone);
        $this->assertTrue($priya->roles()->where('slug', 'sales-manager')->exists());
        $this->assertSame('office@dvmsoft.local', $priya->manager?->email);
        $this->assertStringContainsString('Gurugram', (string) $priya->employee?->address);

        $arjun = User::query()->where('email', 'arjun.mehta@dvmsoft.local')->firstOrFail();
        $this->assertSame($priya->id, $arjun->manager_id);

        $divya = User::query()->where('email', 'divya.krishnan@dvmsoft.local')->firstOrFail();
        $this->assertSame(EmploymentStatus::Probation, $divya->employee?->employment_status);
        $this->assertStringContainsString('Chennai', (string) $divya->employee?->address);

        $this->assertSame(12, User::query()->where('employee_code', 'like', 'DVM-01%')->count());
    }

    public function test_demo_staff_can_sign_in_with_the_seeded_password(): void
    {
        $this->seed();

        $this->post('/login', [
            'email' => 'priya.sharma@dvmsoft.local',
            'password' => DemoStaffSeeder::PASSWORD,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated('web');
    }

    public function test_reseed_does_not_overwrite_an_existing_demo_password(): void
    {
        $this->seed();

        $priya = User::query()->where('email', 'priya.sharma@dvmsoft.local')->firstOrFail();
        $priya->update(['password' => 'ChangedPassword12']);

        $this->seed(DemoStaffSeeder::class);

        $this->assertTrue(Hash::check('ChangedPassword12', $priya->fresh()->password));
    }
}
