<?php

namespace Database\Seeders;

use App\Services\SettingsService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(SettingsService $settings): void
    {
        foreach (config('settings.definitions', []) as $key => $definition) {
            $settings->set($key, $definition['default'] ?? null, $definition);
        }
    }
}
