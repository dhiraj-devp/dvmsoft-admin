<?php

namespace App\Support;

class WorkProgressResult
{
    /**
     * @param  array<string, array{label: string, weight: int, value: int|null}>  $breakdown
     */
    public function __construct(
        public bool $enoughData,
        public ?int $percent,
        public array $breakdown,
    ) {}

    public function label(): string
    {
        return $this->enoughData && $this->percent !== null
            ? $this->percent.'%'
            : 'Not enough data';
    }
}
