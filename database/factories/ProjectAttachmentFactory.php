<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectAttachment>
 */
class ProjectAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'uploaded_by_id' => User::factory(),
            'original_name' => 'brief.pdf',
            'path' => 'projects/demo/brief.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
