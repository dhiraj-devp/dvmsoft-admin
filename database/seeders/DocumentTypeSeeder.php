<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (config('documents.types', []) as $index => $type) {
            DocumentType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }
}
