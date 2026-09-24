<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'version_number' => 1,
            'version_label' => 'v1',
            'original_name' => 'document.pdf',
            'path' => 'documents/test/document.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by_id' => User::factory(),
            'change_notes' => 'Initial upload',
        ];
    }
}
