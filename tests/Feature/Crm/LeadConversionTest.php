<?php

namespace Tests\Feature\Crm;

use App\Enums\ClientType;
use App\Enums\LeadStatus;
use App\Livewire\Leads\Index;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_lead_can_be_created_and_is_audited(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('create')
            ->set('form.name', 'Priya Shah')
            ->set('form.company', 'Northwind Labs')
            ->set('form.email', 'priya@northwind.test')
            ->set('form.phone', '9876543210')
            ->set('form.source', 'website')
            ->set('form.requirement', 'Corporate website')
            ->set('form.estimated_value', '150000')
            ->set('form.status', LeadStatus::New->value)
            ->set('form.priority', 'high')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('leads', [
            'email' => 'priya@northwind.test',
            'company' => 'Northwind Labs',
        ]);

        $this->assertTrue(
            AuditLog::query()->where('module', 'leads')->where('action', 'created')->exists()
        );
    }

    public function test_a_won_lead_converts_into_a_client_without_duplicating_data(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $lead = Lead::factory()->create([
            'name' => 'Amit Rao',
            'company' => 'Rao Media',
            'email' => 'amit@raomedia.test',
            'phone' => '9000000001',
            'notes' => 'Needs a storefront',
            'status' => LeadStatus::Won,
            'assigned_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('leads.convert', $lead))
            ->assertRedirect();

        $lead->refresh();
        $client = $lead->convertedClient;

        $this->assertNotNull($client);
        $this->assertSame('Rao Media', $client->name);
        $this->assertSame(ClientType::Company, $client->type);
        $this->assertSame('amit@raomedia.test', $client->email);
        $this->assertSame($admin->id, $client->account_manager_id);
        $this->assertSame($lead->id, $client->converted_from_lead_id);
        $this->assertSame(LeadStatus::Won, $lead->status);
        $this->assertSame(1, $client->contacts()->count());
        $this->assertSame('Amit Rao', $client->contacts()->first()->name);
        $this->assertSame(1, Client::query()->count());
    }

    public function test_conversion_reuses_an_existing_client_with_the_same_email(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $client = Client::factory()->create([
            'email' => 'shared@client.test',
            'name' => 'Existing Co',
        ]);
        $lead = Lead::factory()->create([
            'email' => 'Shared@client.test',
            'company' => 'Duplicate Risk',
            'status' => LeadStatus::Qualified,
        ]);

        $converted = app(LeadConversionService::class)->convert($lead, $admin);

        $this->assertSame($client->id, $converted->id);
        $this->assertSame(1, Client::query()->count());
        $this->assertSame($client->id, $lead->fresh()->converted_client_id);
    }

    public function test_a_lost_lead_cannot_be_converted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $lead = Lead::factory()->lost()->create();

        $this->actingAs($admin)
            ->from(route('leads.show', $lead))
            ->post(route('leads.convert', $lead))
            ->assertSessionHasErrors('status');

        $this->assertSame(0, Client::query()->count());
        $this->assertNull($lead->fresh()->converted_client_id);
    }

    public function test_users_without_convert_permission_cannot_convert_leads(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->won()->create();

        $this->actingAs($user)
            ->post(route('leads.convert', $lead))
            ->assertForbidden();
    }
}
