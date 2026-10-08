<?php

namespace App\Livewire\Office;

use App\Services\OfficeCalendar;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Calendar extends Component
{
    public string $month = '';

    /** @var list<int> */
    public array $weeklyOffs = [];

    public function mount(OfficeCalendar $calendar): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);
        $this->month = now()->format('Y-m');
        $this->weeklyOffs = $calendar->weeklyOffs();
    }

    public function updatedWeeklyOffs(): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);
        $calendar = app(OfficeCalendar::class);
        $calendar->saveWeeklyOffs($this->weeklyOffs);
        $this->weeklyOffs = $calendar->weeklyOffs();
        session()->flash('status', 'Weekly off days saved.');
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::parse($this->month.'-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::parse($this->month.'-01')->addMonth()->format('Y-m');
    }

    public function toggleDay(string $date, OfficeCalendar $calendar): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);
        $on = $calendar->toggleDay($date, auth()->id());
        session()->flash('status', $on ? 'Holiday added.' : 'Holiday removed.');
    }

    public function markWeekOff(string $date, OfficeCalendar $calendar): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);
        $calendar->markWeekOff($date, auth()->id());
        session()->flash('status', 'Week marked as holiday.');
    }

    public function clearWeekOff(string $date, OfficeCalendar $calendar): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);
        $calendar->clearWeekOff($date);
        session()->flash('status', 'Week holidays cleared.');
    }

    public function render(OfficeCalendar $calendar): View
    {
        $start = Carbon::parse($this->month.'-01')->startOfWeek(Carbon::MONDAY);
        $end = Carbon::parse($this->month.'-01')->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $holidays = $calendar->holidaysBetween($start, $end);
        $weeks = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $week = [];
            foreach (range(0, 6) as $offset) {
                $day = $cursor->copy()->addDays($offset);
                $key = $day->toDateString();
                $week[] = [
                    'date' => $key,
                    'day' => $day->format('j'),
                    'inMonth' => $day->format('Y-m') === $this->month,
                    'weeklyOff' => $calendar->isWeeklyOff($day),
                    'holiday' => $holidays->has($key),
                    'title' => $holidays->get($key)?->title,
                ];
            }
            $weeks[] = $week;
            $cursor->addWeek();
        }

        return view('livewire.office.calendar', [
            'label' => Carbon::parse($this->month.'-01')->format('F Y'),
            'weeks' => $weeks,
            'weekdays' => [
                1 => 'Mon',
                2 => 'Tue',
                3 => 'Wed',
                4 => 'Thu',
                5 => 'Fri',
                6 => 'Sat',
                7 => 'Sun',
            ],
        ]);
    }
}
