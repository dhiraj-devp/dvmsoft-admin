<?php

namespace App\Support;

class WorkGrowthSnapshot
{
    /**
     * @param  list<array{date: string, day: string, weekday: bool, weeklyOff: bool, holiday: bool, future: bool, status: string|null, id: string|null}>  $calendar
     * @param  list<array{date: string, label: string, note: string}>  $growthNotes
     */
    public function __construct(
        public int $weekWritten,
        public int $weekExpected,
        public int $weekDone,
        public int $streak,
        public int $monthWritten,
        public int $monthExpected,
        public int $monthDone,
        public bool $slipping,
        public array $calendar,
        public array $growthNotes,
    ) {}
}
