<?php

namespace App\Services;

use App\Models\Quotation;

class QuotationNumberGenerator
{
    public function next(): string
    {
        $year = now()->year;
        $prefix = 'QT-'.$year.'-';

        $latest = Quotation::withTrashed()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
