<?php

namespace Tests\Feature\Office;

use App\Livewire\Office\Calendar;
use App\Models\OfficeHoliday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OfficeCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_open_the_office_calendar(): void
    {
        $staff = $this->userWithPermissions(['work.my.view', 'work.my.manage']);

        $this->actingAs($staff)->get(route('office.calendar'))->assertForbidden();
        Livewire::actingAs($staff)->test(Calendar::class)->assertForbidden();
    }

    public function test_super_admin_can_mark_a_holiday_day_and_week(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('office.calendar'))->assertOk()->assertSee('Office calendar');

        Livewire::actingAs($admin)
            ->test(Calendar::class)
            ->call('toggleDay', '2026-10-08')
            ->assertHasNoErrors();

        $this->assertTrue(OfficeHoliday::query()->whereDate('holiday_date', '2026-10-08')->exists());

        Livewire::actingAs($admin)
            ->test(Calendar::class)
            ->call('markWeekOff', '2026-10-05')
            ->assertHasNoErrors();

        $this->assertSame(5, OfficeHoliday::query()->count());
    }
}
