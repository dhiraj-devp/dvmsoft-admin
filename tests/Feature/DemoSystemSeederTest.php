<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\OfficeHoliday;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WorkDailyUpdate;
use Database\Seeders\DemoSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSystemSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_system_demo_data_is_seeded(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(4, Client::query()->count());
        $this->assertGreaterThanOrEqual(7, Lead::query()->count());
        $this->assertGreaterThanOrEqual(4, Quotation::query()->count());
        $this->assertGreaterThanOrEqual(2, Project::query()->count());
        $this->assertGreaterThanOrEqual(3, Invoice::query()->count());
        $this->assertGreaterThanOrEqual(3, Ticket::query()->count());
        $this->assertGreaterThanOrEqual(5, OfficeHoliday::query()->count());
        $karthik = User::query()->where('email', 'karthik.reddy@dvmsoft.local')->firstOrFail();
        $this->assertTrue(WorkDailyUpdate::query()->where('user_id', $karthik->id)->exists());

        $nimbus = Client::query()->where('email', 'accounts@nimbusretail.in')->first();
        $this->assertNotNull($nimbus);
        $this->assertSame('27AABCN1234A1Z5', $nimbus->gst_number);
        $this->assertTrue($nimbus->projects()->where('number', 'PRJ-2025-0001')->exists());
        $this->assertTrue(ClientUser::query()->where('email', 'ravi.kulkarni@nimbusretail.in')->exists());
    }

    public function test_client_portal_demo_user_can_sign_in(): void
    {
        $this->seed();

        $this->post(route('client.login.store'), [
            'email' => 'ravi.kulkarni@nimbusretail.in',
            'password' => DemoSystemSeeder::PORTAL_PASSWORD,
        ])->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticated('client');
    }
}
