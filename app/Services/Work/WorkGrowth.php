<?php

namespace App\Services\Work;

use App\Enums\WorkDailyUpdateStatus;
use App\Models\User;
use App\Models\WorkDailyUpdate;
use App\Services\OfficeCalendar;
use App\Support\WorkGrowthSnapshot;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WorkGrowth
{
    public function __construct(protected OfficeCalendar $office) {}

    public function forUser(User $user, ?CarbonInterface $asOf = null): WorkGrowthSnapshot
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $this->prepare($asOf);
        $updates = $this->updatesFor($user->id, $this->historyStart($asOf), $asOf);

        return $this->snapshot($updates, $asOf);
    }

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<string, WorkGrowthSnapshot>
     */
    public function forUsers(Collection $users, ?CarbonInterface $asOf = null): Collection
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $this->prepare($asOf);
        $ids = $users->pluck('id');
        $grouped = WorkDailyUpdate::query()
            ->whereIn('user_id', $ids)
            ->whereDate('work_date', '>=', $this->historyStart($asOf)->toDateString())
            ->whereDate('work_date', '<=', $asOf->toDateString())
            ->get()
            ->groupBy('user_id');

        return $users->mapWithKeys(function (User $user) use ($grouped, $asOf) {
            $updates = $grouped->get($user->id, collect());

            return [$user->id => $this->snapshot($updates, $asOf)];
        });
    }

    /**
     * @param  Collection<int, WorkDailyUpdate>  $updates
     */
    public function snapshot(Collection $updates, ?CarbonInterface $asOf = null): WorkGrowthSnapshot
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $byDate = $updates->keyBy(fn (WorkDailyUpdate $update) => $update->work_date->toDateString());

        $weekDays = $this->workingDays($asOf->copy()->startOfWeek(Carbon::MONDAY), $asOf);
        $monthDays = $this->workingDays($asOf->copy()->startOfMonth(), $asOf);

        return new WorkGrowthSnapshot(
            weekWritten: $this->writtenCount($weekDays, $byDate),
            weekExpected: $weekDays->count(),
            weekDone: $this->doneCount($weekDays, $byDate),
            streak: $this->streak($byDate, $asOf),
            monthWritten: $this->writtenCount($monthDays, $byDate),
            monthExpected: $monthDays->count(),
            monthDone: $this->doneCount($monthDays, $byDate),
            slipping: $this->isSlipping($byDate, $asOf),
            calendar: $this->calendar($byDate, $asOf),
            growthNotes: $this->growthNotes($byDate, $asOf),
        );
    }

    /**
     * @return Collection<int, Carbon>
     */
    public function workingDays(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $days = collect();
        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();

        while ($cursor->lte($end)) {
            if ($this->isWorkingDay($cursor)) {
                $days->push($cursor->copy());
            }
            $cursor->addDay();
        }

        return $days;
    }

    public function isWorkingDay(CarbonInterface $day): bool
    {
        return $this->office->isWorkingDay($day);
    }

    /**
     * @param  Collection<string, WorkDailyUpdate>  $byDate
     * @return list<array{date: string, day: string, weekday: bool, status: string|null, id: string|null}>
     */
    protected function calendar(Collection $byDate, CarbonInterface $asOf): array
    {
        $start = $this->calendarStart($asOf);
        $weeks = (int) config('work.growth.calendar_weeks', 4);
        $days = [];

        foreach (range(0, ($weeks * 7) - 1) as $offset) {
            $day = $start->copy()->addDays($offset);
            $key = $day->toDateString();
            $update = $byDate->get($key);
            $working = $this->isWorkingDay($day);

            $days[] = [
                'date' => $key,
                'day' => $day->format('j'),
                'weekday' => $working,
                'weeklyOff' => $this->office->isWeeklyOff($day),
                'holiday' => $this->office->isHoliday($day) && ! $this->office->isWeeklyOff($day),
                'future' => $day->gt($asOf),
                'status' => $update?->status?->value,
                'id' => $update?->id,
            ];
        }

        return $days;
    }

    /**
     * @param  Collection<string, WorkDailyUpdate>  $byDate
     * @return list<array{date: string, label: string, note: string}>
     */
    protected function growthNotes(Collection $byDate, CarbonInterface $asOf): array
    {
        $start = $asOf->copy()->startOfMonth()->toDateString();
        $end = $asOf->toDateString();

        return $byDate
            ->filter(function (WorkDailyUpdate $update) use ($start, $end) {
                $date = $update->work_date->toDateString();

                return $date >= $start && $date <= $end && filled($update->notes);
            })
            ->sortBy(fn (WorkDailyUpdate $update) => $update->work_date->toDateString())
            ->values()
            ->map(fn (WorkDailyUpdate $update) => [
                'date' => $update->work_date->toDateString(),
                'label' => $update->work_date->format('d M'),
                'note' => $update->notes,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, Carbon>  $days
     * @param  Collection<string, WorkDailyUpdate>  $byDate
     */
    protected function writtenCount(Collection $days, Collection $byDate): int
    {
        return $days->filter(fn (Carbon $day) => $byDate->has($day->toDateString()))->count();
    }

    /**
     * @param  Collection<int, Carbon>  $days
     * @param  Collection<string, WorkDailyUpdate>  $byDate
     */
    protected function doneCount(Collection $days, Collection $byDate): int
    {
        return $days->filter(function (Carbon $day) use ($byDate) {
            $update = $byDate->get($day->toDateString());

            return $update?->status === WorkDailyUpdateStatus::Done;
        })->count();
    }

    /**
     * @param  Collection<string, WorkDailyUpdate>  $byDate
     */
    protected function streak(Collection $byDate, CarbonInterface $asOf): int
    {
        $cursor = $this->latestWorkingDayOnOrBefore($asOf);

        if (! $byDate->has($cursor->toDateString())) {
            $cursor = $this->previousWorkingDay($cursor);
        }

        $streak = 0;
        $guard = 0;

        while ($guard < 366) {
            $guard++;

            if (! $this->isWorkingDay($cursor)) {
                $cursor->subDay();

                continue;
            }

            if (! $byDate->has($cursor->toDateString())) {
                break;
            }

            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    /**
     * @param  Collection<string, WorkDailyUpdate>  $byDate
     */
    protected function isSlipping(Collection $byDate, CarbonInterface $asOf): bool
    {
        $needed = (int) config('work.growth.slipping_missed_days', 2);
        $cursor = $this->latestWorkingDayOnOrBefore($asOf);
        $missed = 0;
        $checked = 0;

        while ($checked < $needed) {
            if (! $byDate->has($cursor->toDateString())) {
                $missed++;
            } else {
                break;
            }

            $checked++;
            $cursor = $this->previousWorkingDay($cursor);
        }

        return $missed >= $needed;
    }

    protected function latestWorkingDayOnOrBefore(CarbonInterface $day): Carbon
    {
        $cursor = Carbon::parse($day)->startOfDay();
        $guard = 0;

        while (! $this->isWorkingDay($cursor) && $guard < 366) {
            $cursor->subDay();
            $guard++;
        }

        return $cursor;
    }

    protected function previousWorkingDay(CarbonInterface $day): Carbon
    {
        $cursor = Carbon::parse($day)->startOfDay()->subDay();
        $guard = 0;

        while (! $this->isWorkingDay($cursor) && $guard < 366) {
            $cursor->subDay();
            $guard++;
        }

        return $cursor;
    }

    protected function prepare(CarbonInterface $asOf): void
    {
        $this->office->rememberRange($asOf->copy()->subYear(), $asOf);
    }

    protected function calendarStart(CarbonInterface $asOf): Carbon
    {
        $weeks = (int) config('work.growth.calendar_weeks', 4);

        return Carbon::parse($asOf)->startOfWeek(Carbon::MONDAY)->subWeeks($weeks - 1);
    }

    protected function historyStart(CarbonInterface $asOf): Carbon
    {
        $month = Carbon::parse($asOf)->startOfMonth();
        $calendar = $this->calendarStart($asOf);

        return $calendar->lt($month) ? $calendar : $month;
    }

    /**
     * @return Collection<int, WorkDailyUpdate>
     */
    protected function updatesFor(string $userId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return WorkDailyUpdate::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->get();
    }
}
