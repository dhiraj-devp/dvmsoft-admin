<?php

namespace Database\Factories;

use App\Enums\StageSubmissionStatus;
use App\Models\ProjectStage;
use App\Models\ProjectStageSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectStageSubmission>
 */
class ProjectStageSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stage_id' => ProjectStage::factory(),
            'version' => 1,
            'status' => StageSubmissionStatus::Submitted,
            'submitted_by_id' => User::factory(),
            'submitted_at' => now(),
            'comment' => 'Ready for review',
        ];
    }
}
