<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use App\Models\TicketSlaRule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupportSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (config('support.categories', []) as $index => $category) {
            TicketCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        foreach (config('support.sla', []) as $priority => $rule) {
            TicketSlaRule::query()->updateOrCreate(
                ['priority' => $priority],
                [
                    'hours' => $rule['hours'],
                    'warning_hours' => $rule['warning_hours'],
                ]
            );
        }
    }
}
