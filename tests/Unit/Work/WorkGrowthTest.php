<?php

namespace Tests\Unit\Work;

use App\Enums\WorkDailyUpdateStatus;
use App\Models\OfficeHoliday;
use App\Models\User;
use App\Models\WorkDailyUpdate;
use App\Services\Work\WorkGrowth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WorkGrowthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_weekday_streak_ignores_weekends(): void
    {
        Carbon::setTestNow('2026-10-10 12:00:00');
        $user = User::factory()->create();

        WorkDailyUpdate::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-08',
            'notes' => null,
        ]);
        WorkDailyUpdate::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-09',
            'notes' => null,
        ]);

        $snapshot = app(WorkGrowth::class)->forUser($user);

        $this->assertSame(2, $snapshot->streak);
        $this->assertFalse($snapshot->slipping);
    }

    public function test_three_weekdays_in_a_row_count_as_a_streak_of_three(): void
    {
        Carbon::setTestNow('2026-10-08 12:00:00');
        $user = User::factory()->create();

        foreach (['2026-10-06', '2026-10-07', '2026-10-08'] as $date) {
            WorkDailyUpdate::factory()->create([
                'user_id' => $user->id,
                'work_date' => $date,
                'notes' => null,
            ]);
        }

        $snapshot = app(WorkGrowth::class)->forUser($user);

        $this->assertSame(3, $snapshot->streak);
        $this->assertSame(3, $snapshot->weekWritten);
        $this->assertSame(4, $snapshot->weekExpected);
        $this->assertFalse($snapshot->slipping);
        $this->assertCount(28, $snapshot->calendar);
    }

    public function test_two_missed_working_days_mark_the_person_as_slipping(): void
    {
        Carbon::setTestNow('2026-10-08 12:00:00');
        $user = User::factory()->create();

        $snapshot = app(WorkGrowth::class)->forUser($user);

        $this->assertSame(0, $snapshot->streak);
        $this->assertTrue($snapshot->slipping);
    }

    public function test_month_card_lists_growth_notes(): void
    {
        Carbon::setTestNow('2026-10-08 12:00:00');
        $user = User::factory()->create();

        WorkDailyUpdate::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-08',
            'notes' => 'More confident on Git.',
        ]);
        WorkDailyUpdate::factory()->done()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-07',
            'notes' => null,
            'status' => WorkDailyUpdateStatus::Done,
        ]);

        $snapshot = app(WorkGrowth::class)->forUser($user);

        $this->assertSame(2, $snapshot->monthWritten);
        $this->assertSame(1, $snapshot->monthDone);
        $this->assertSame(['More confident on Git.'], collect($snapshot->growthNotes)->pluck('note')->all());
    }

    public function test_office_holidays_are_not_expected_working_days(): void
    {
        Carbon::setTestNow('2026-10-08 12:00:00');
        $user = User::factory()->create();

        OfficeHoliday::factory()->create([
            'holiday_date' => '2026-10-07',
            'title' => 'Diwali',
        ]);

        WorkDailyUpdate::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-06',
            'notes' => null,
        ]);
        WorkDailyUpdate::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-08',
            'notes' => null,
        ]);

        $snapshot = app(WorkGrowth::class)->forUser($user);

        $this->assertSame(3, $snapshot->weekExpected);
        $this->assertSame(2, $snapshot->weekWritten);
        $this->assertSame(2, $snapshot->streak);
        $this->assertFalse($snapshot->slipping);
    }
}
