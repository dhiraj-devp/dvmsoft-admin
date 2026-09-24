<?php

namespace App\Services\ClientPortal;

use App\Enums\ChangeRequestStatus;
use App\Models\ChangeRequest;
use App\Models\ClientUser;
use App\Models\Project;
use App\Services\CrmActivityLogger;
use App\Services\SequentialNumberGenerator;
use Illuminate\Validation\ValidationException;

class ClientChangeRequestService
{
    public function __construct(
        protected SequentialNumberGenerator $numbers,
        protected CrmActivityLogger $activities,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(ClientUser $actor, array $attributes): ChangeRequest
    {
        $project = Project::query()->find($attributes['project_id'] ?? null);

        if (! $project || $project->client_id !== $actor->client_id) {
            throw ValidationException::withMessages([
                'project_id' => 'The project must belong to your account.',
            ]);
        }

        $changeRequest = ChangeRequest::query()->create([
            'number' => $this->numbers->nextChangeRequest(),
            'project_id' => $project->id,
            'requested_by_id' => null,
            'client_user_id' => $actor->id,
            'title' => $attributes['title'],
            'description' => $attributes['description'] ?? null,
            'impact_on_cost' => null,
            'impact_on_timeline_days' => $attributes['impact_on_timeline_days'] ?: null,
            'status' => ChangeRequestStatus::Pending,
            'notes' => null,
        ]);

        $this->activities->log(
            $project,
            'change_request',
            'Change request submitted from client portal',
            $changeRequest->number.' · '.$changeRequest->title,
            ['change_request_id' => $changeRequest->id, 'client_user_id' => $actor->id],
        );

        return $changeRequest->fresh('project');
    }
}
