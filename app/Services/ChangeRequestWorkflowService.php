<?php

namespace App\Services;

use App\Automations\ClientPortalNotifier;
use App\Enums\ChangeRequestStatus;
use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ChangeRequestWorkflowService
{
    public function __construct(
        protected CrmActivityLogger $activities,
        protected ClientPortalNotifier $portal,
    ) {}

    public function approve(ChangeRequest $changeRequest, User $actor): ChangeRequest
    {
        $this->assertPending($changeRequest);

        $changeRequest->update([
            'status' => ChangeRequestStatus::Approved,
            'decided_by_id' => $actor->id,
            'decided_at' => now(),
        ]);

        $this->log($changeRequest, $actor, 'approved', 'Change request approved');
        $this->notifyPortal($changeRequest, 'approved', 'Change request approved', $changeRequest->number.' was approved.');

        return $changeRequest->fresh();
    }

    public function reject(ChangeRequest $changeRequest, User $actor): ChangeRequest
    {
        $this->assertPending($changeRequest);

        $changeRequest->update([
            'status' => ChangeRequestStatus::Rejected,
            'decided_by_id' => $actor->id,
            'decided_at' => now(),
        ]);

        $this->log($changeRequest, $actor, 'rejected', 'Change request rejected');
        $this->notifyPortal($changeRequest, 'rejected', 'Change request declined', $changeRequest->number.' was declined.');

        return $changeRequest->fresh();
    }

    public function implement(ChangeRequest $changeRequest, User $actor): ChangeRequest
    {
        if ($changeRequest->status !== ChangeRequestStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => 'Only approved change requests can be marked implemented.',
            ]);
        }

        $changeRequest->update([
            'status' => ChangeRequestStatus::Implemented,
        ]);

        $this->log($changeRequest, $actor, 'implemented', 'Change request implemented');
        $this->notifyPortal($changeRequest, 'implemented', 'Change request implemented', $changeRequest->number.' was marked implemented.');

        return $changeRequest->fresh();
    }

    protected function assertPending(ChangeRequest $changeRequest): void
    {
        if ($changeRequest->status !== ChangeRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'This change request is no longer pending.',
            ]);
        }
    }

    protected function log(ChangeRequest $changeRequest, User $actor, string $type, string $title): void
    {
        $this->activities->log(
            $changeRequest->project,
            $type,
            $title,
            $changeRequest->number.' · '.$changeRequest->title,
            ['change_request_id' => $changeRequest->id],
            $actor,
        );
    }

    protected function notifyPortal(ChangeRequest $changeRequest, string $event, string $title, string $message): void
    {
        $changeRequest->loadMissing('project.client');

        $this->portal->notify(
            $changeRequest->project?->client,
            'portal.change_request',
            $event,
            $title,
            $message,
            route('client.change-requests.show', $changeRequest),
            ['change_request_id' => $changeRequest->id],
            $changeRequest,
            $event,
        );
    }
}
