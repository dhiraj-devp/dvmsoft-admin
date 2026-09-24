<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'DOC-TEST-'.fake()->unique()->numerify('######'),
            'title' => fake()->sentence(4),
            'document_type_id' => DocumentType::factory(),
            'description' => fake()->optional()->sentence(),
            'status' => DocumentStatus::Draft,
            'current_version_number' => 1,
            'owner_id' => User::factory(),
            'created_by_id' => User::factory(),
        ];
    }

    public function review(): static
    {
        return $this->state(fn () => ['status' => DocumentStatus::Review]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => DocumentStatus::Approved,
            'approved_at' => now(),
        ]);
    }
}
