<?php

namespace App\Services;

use App\Models\OfficeHoliday;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OfficeCalendar
{
    /**
     * @var list<int>|null
     */
    protected ?array $weeklyOffs = null;

    /**
     * @var Collection<string, OfficeHoliday>|null
     */
    protected ?Collection $holidayIndex = null;

    /**
     * @return list<int>
     */
    public function weeklyOffs(): array
    {
        if ($this->weeklyOffs !== null) {
            return $this->weeklyOffs;
        }

        $days = collect(settings()->get('office.weekly_offs', [6, 7]))
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $this->weeklyOffs = $days === [] ? [6, 7] : $days;
    }

    /**
     * @param  list<int|string>  $days
     */
    public function saveWeeklyOffs(array $days): void
    {
        $normalized = collect($days)
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($normalized === [] || count($normalized) >= 7) {
            $normalized = [6, 7];
        }

        settings()->set('office.weekly_offs', $normalized, [
            'type' => 'json',
            'group' => 'office',
            'description' => 'ISO weekdays that are always off (1=Mon … 7=Sun)',
        ]);

        $this->weeklyOffs = $normalized === [] ? [6, 7] : $normalized;
    }

    public function rememberRange(CarbonInterface $from, CarbonInterface $to): static
    {
        $this->holidayIndex = $this->holidaysBetween($from, $to);

        return $this;
    }

    /**
     * @return Collection<string, OfficeHoliday>
     */
    public function holidaysBetween(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return OfficeHoliday::query()
            ->whereDate('holiday_date', '>=', Carbon::parse($from)->toDateString())
            ->whereDate('holiday_date', '<=', Carbon::parse($to)->toDateString())
            ->get()
            ->keyBy(fn (OfficeHoliday $holiday) => $holiday->holiday_date->toDateString());
    }

    public function isWeeklyOff(CarbonInterface $day): bool
    {
        return in_array((int) Carbon::parse($day)->dayOfWeekIso, $this->weeklyOffs(), true);
    }

    public function isHoliday(CarbonInterface $day): bool
    {
        $key = Carbon::parse($day)->toDateString();

        if ($this->holidayIndex !== null) {
            return $this->holidayIndex->has($key);
        }

        return OfficeHoliday::query()->whereDate('holiday_date', $key)->exists();
    }

    public function isWorkingDay(CarbonInterface $day): bool
    {
        return ! $this->isWeeklyOff($day) && ! $this->isHoliday($day);
    }

    public function toggleDay(string $date, ?string $userId = null): bool
    {
        $day = Carbon::parse($date)->startOfDay();

        if ($this->isWeeklyOff($day)) {
            return false;
        }

        $existing = OfficeHoliday::query()->whereDate('holiday_date', $day->toDateString())->first();

        if ($existing) {
            $existing->delete();
            $this->forgetCachedDay($day->toDateString());

            return false;
        }

        $holiday = OfficeHoliday::query()->create([
            'holiday_date' => $day->toDateString(),
            'title' => 'Holiday',
            'created_by' => $userId,
        ]);

        if ($this->holidayIndex !== null) {
            $this->holidayIndex->put($day->toDateString(), $holiday);
        }

        return true;
    }

    public function markWeekOff(string $date, ?string $userId = null): void
    {
        $monday = Carbon::parse($date)->startOfWeek(Carbon::MONDAY);

        foreach (range(0, 6) as $offset) {
            $day = $monday->copy()->addDays($offset);

            if ($this->isWeeklyOff($day)) {
                continue;
            }

            $exists = OfficeHoliday::query()->whereDate('holiday_date', $day->toDateString())->exists();

            if ($exists) {
                continue;
            }

            OfficeHoliday::query()->create([
                'holiday_date' => $day->toDateString(),
                'title' => 'Week off',
                'created_by' => $userId,
            ]);
        }

        $this->holidayIndex = null;
    }

    public function clearWeekOff(string $date): void
    {
        $monday = Carbon::parse($date)->startOfWeek(Carbon::MONDAY);

        OfficeHoliday::query()
            ->whereDate('holiday_date', '>=', $monday->toDateString())
            ->whereDate('holiday_date', '<=', $monday->copy()->addDays(6)->toDateString())
            ->delete();

        $this->holidayIndex = null;
    }

    protected function forgetCachedDay(string $date): void
    {
        $this->holidayIndex?->forget($date);
    }
}
