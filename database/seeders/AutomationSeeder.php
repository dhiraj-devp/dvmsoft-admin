<?php

namespace Database\Seeders;

use App\Automations\AutomationEngine;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AutomationSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(AutomationEngine $engine): void
    {
        $engine->syncCatalog();
    }
}
