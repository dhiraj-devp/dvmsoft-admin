<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_helper_reads_seeded_company_values(): void
    {
        $this->seed();

        $this->assertSame('Dvmsoft', settings('company.name'));
        $this->assertSame('INR', settings('company.currency'));
        $this->assertSame('INV', settings('finance.invoice_prefix'));
        $this->assertSame('EMP', settings('hr.employee_prefix'));
        $this->assertSame('TKT', settings('support.ticket_prefix'));
        $this->assertSame('DOC', settings('documents.prefix'));
        $this->assertSame('flexible', settings('projects.default_client_approval_mode'));
        $this->assertFalse((bool) settings('ai.enabled'));
        $this->assertSame('openai', settings('ai.provider'));
        $this->assertTrue((bool) settings('notifications.in_app_enabled'));
        $this->assertTrue((bool) settings('automations.enabled'));
    }

    public function test_settings_can_be_updated_through_the_service(): void
    {
        $this->seed();

        settings()->set('company.name', 'Dvmsoft Technologies');

        $this->assertSame('Dvmsoft Technologies', settings('company.name'));
    }
}
