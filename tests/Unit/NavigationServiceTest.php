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
        $this->assertContains('AI', $labels);
        $this->assertStringNotContainsString('super-admin', json_encode($items));
    }
}
