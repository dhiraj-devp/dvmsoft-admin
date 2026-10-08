<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_hides_items_without_permission(): void
    {
        $user = User::factory()->create();
        $items = app(NavigationService::class)->for($user);

        $this->assertSame([], $items);
    }

    public function test_super_admin_sees_enabled_navigation(): void
    {
        $user = User::factory()->superAdmin()->create();
        $items = app(NavigationService::class)->for($user);
        $labels = collect($items)->pluck('label')->all();

        $this->assertContains('Dashboard', $labels);
        $this->assertContains('Administration', $labels);
        $this->assertContains('Sales', $labels);
        $this->assertContains('Projects', $labels);
        $this->assertContains('Finance', $labels);
        $this->assertContains('HR', $labels);
        $this->assertContains('Support', $labels);
        $this->assertContains('Documents', $labels);
        $this->assertContains('Reports', $labels);
        $this->assertContains('Work', $labels);
        $this->assertContains('AI', $labels);
        $this->assertStringNotContainsString('super-admin', json_encode($items));

        $administration = collect($items)->firstWhere('label', 'Administration');
        $this->assertContains('Office calendar', collect($administration['children'])->pluck('label')->all());
    }

    public function test_staff_do_not_see_office_calendar(): void
    {
        $staff = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $items = app(NavigationService::class)->for($staff);
        $labels = collect($items)->pluck('label')->all();
        $this->assertNotContains('Administration', $labels);
        $this->assertStringNotContainsString('Office calendar', json_encode($items));
    }
}
