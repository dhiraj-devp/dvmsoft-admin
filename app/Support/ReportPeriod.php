<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ReportPeriod
{
    /**
     * @var list<string>
     */
    public const PRESETS = ['today', 'this_week', 'this_month', 'this_quarter', 'this_year', 'custom'];

    public function __construct(
        public readonly string $preset,
        public readonly Carbon $from,
        public readonly Carbon $to,
    ) {}

    public static function resolve(?string $preset = null, ?string $from = null, ?string $to = null): self
    {
        $preset = in_array($preset, self::PRESETS, true) ? $preset : 'this_month';
        $now = now();

        if ($preset === 'custom') {
            $start = $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth();
            $end = $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay();
        } else {
            [$start, $end] = match ($preset) {
                'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
                'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
                default => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            };
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return new self($preset, $start, $end);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function presetOptions(): array
    {
        return [
            ['value' => 'today', 'label' => 'Today'],
            ['value' => 'this_week', 'label' => 'This week'],
            ['value' => 'this_month', 'label' => 'This month'],
            ['value' => 'this_quarter', 'label' => 'This quarter'],
            ['value' => 'this_year', 'label' => 'This year'],
            ['value' => 'custom', 'label' => 'Custom range'],
        ];
    }

    public function apply(Builder $query, string $column): Builder
    {
        return $query->whereBetween($column, [$this->from->toDateTimeString(), $this->to->toDateTimeString()]);
    }

    public function applyDate(Builder $query, string $column): Builder
    {
        return $query->whereBetween($column, [$this->from->toDateString(), $this->to->toDateString()]);
    }

    public function label(): string
    {
        $format = settings('company.date_format', 'd M Y');

        return $this->from->format($format).' – '.$this->to->format($format);
    }

    /**
     * @return list<array{start: Carbon, end: Carbon, label: string}>
     */
    public function months(): array
    {
        $cursor = $this->from->copy()->startOfMonth();
        $end = $this->to->copy()->startOfMonth();
        $months = [];

        while ($cursor->lte($end)) {
            $months[] = [
                'start' => $cursor->copy(),
                'end' => $cursor->copy()->endOfMonth(),
                'label' => $cursor->format('M Y'),
            ];
            $cursor->addMonth();
        }

        return $months;
    }

    /**
     * @return array{preset: string, from: string, to: string}
     */
    public function query(): array
    {
        return [
            'preset' => $this->preset,
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
        ];
    }
}
