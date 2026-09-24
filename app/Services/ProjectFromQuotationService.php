<?php

namespace App\Services;

use App\Enums\ClientApprovalMode;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\QuotationStatus;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectFromQuotationService
{
    public function __construct(
        protected SequentialNumberGenerator $numbers,
        protected CrmActivityLogger $activities,
    ) {}

    public function create(Quotation $quotation, User $actor): Project
    {
        if ($quotation->status !== QuotationStatus::Converted) {
            throw ValidationException::withMessages([
                'status' => 'Only converted quotations can become projects.',
            ]);
        }

        if ($quotation->project) {
            return $quotation->project;
        }

        return DB::transaction(function () use ($quotation, $actor) {
            $quotation->refresh();

            if ($quotation->project) {
                return $quotation->project;
            }

            $project = Project::query()->create([
                'number' => $this->numbers->nextProject(),
                'client_id' => $quotation->client_id,
                'quotation_id' => $quotation->id,
                'manager_id' => $actor->id,
                'name' => $quotation->title,
                'description' => $quotation->notes,
                'start_date' => now()->toDateString(),
                'expected_end_date' => $quotation->valid_until?->toDateString() ?? now()->addDays(30)->toDateString(),
                'budget' => $quotation->total,
                'status' => ProjectStatus::Planning,
                'health' => ProjectHealth::Green,
                'priority' => ProjectPriority::Medium,
                'client_approval_mode' => ClientApprovalMode::from(settings('projects.default_client_approval_mode', ClientApprovalMode::Flexible->value)),
                'notes' => 'Created from quotation '.$quotation->number.'.',
            ]);

            $project->members()->create([
                'user_id' => $actor->id,
                'role' => 'project_manager',
            ]);

            $this->activities->log(
                $project,
                'created',
                'Project created from quotation',
                $quotation->number.' · '.$quotation->title,
                ['quotation_id' => $quotation->id, 'client_id' => $quotation->client_id],
                $actor,
            );

            if ($quotation->client) {
                $this->activities->log(
                    $quotation->client,
                    'project',
                    'Project created from quotation',
                    $project->number.' · '.$project->name,
                    ['project_id' => $project->id, 'quotation_id' => $quotation->id],
                    $actor,
                );
            }

            return $project->fresh(['client', 'quotation', 'members']);
        });
    }
}
