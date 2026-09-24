<?php

namespace App\Services;

use App\Enums\StageDeliverableStatus;
use App\Enums\StageEvidenceType;
use App\Enums\StageEvidenceVisibility;
use App\Enums\StageStatus;
use App\Enums\StageSubmissionStatus;
use App\Models\ClientUser;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\ProjectStageDeliverable;
use App\Models\ProjectStageEvidence;
use App\Models\ProjectStageMessage;
use App\Models\ProjectStageSubmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectStageWorkflowService
{
    public function __construct(
        protected CrmActivityLogger $activities,
        protected ProjectStageNotifier $notifier,
        protected AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Project $project, array $payload, User $actor): ProjectStage
    {
        $sequence = $payload['sequence'] ?? ((int) $project->stages()->max('sequence') + 1);

        $stage = $project->stages()->create([
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'sequence' => $sequence,
            'status' => StageStatus::NotStarted,
            'start_date' => $payload['start_date'] ?? null,
            'due_date' => $payload['due_date'] ?? null,
            'owner_id' => $payload['owner_id'] ?? null,
            'client_review_enabled' => $payload['client_review_enabled'] ?? true,
            'approval_requirement' => $payload['approval_requirement'] ?? 'inherit',
            'requires_evidence' => $payload['requires_evidence'] ?? false,
            'notes' => $payload['notes'] ?? null,
        ]);

        $this->syncMembers($stage, $payload['member_ids'] ?? []);
        $this->log($stage, $actor, 'created', 'Stage created', $stage->name);

        if ($stage->owner_id) {
            $this->notifier->staff($stage, 'stage_assigned', 'Stage assigned', $stage->name.' was assigned on '.$project->name.'.', $stage, 'assigned-'.$stage->id.'-'.$stage->owner_id);
        }

        return $stage->fresh(['owner', 'members']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(ProjectStage $stage, array $payload, User $actor): ProjectStage
    {
        $previousOwner = $stage->owner_id;

        $stage->update([
            'name' => $payload['name'] ?? $stage->name,
            'description' => array_key_exists('description', $payload) ? $payload['description'] : $stage->description,
            'start_date' => array_key_exists('start_date', $payload) ? $payload['start_date'] : $stage->start_date,
            'due_date' => array_key_exists('due_date', $payload) ? $payload['due_date'] : $stage->due_date,
            'owner_id' => array_key_exists('owner_id', $payload) ? $payload['owner_id'] : $stage->owner_id,
            'client_review_enabled' => $payload['client_review_enabled'] ?? $stage->client_review_enabled,
            'approval_requirement' => $payload['approval_requirement'] ?? $stage->approval_requirement,
            'requires_evidence' => $payload['requires_evidence'] ?? $stage->requires_evidence,
            'notes' => array_key_exists('notes', $payload) ? $payload['notes'] : $stage->notes,
        ]);

        if (array_key_exists('member_ids', $payload)) {
            $this->syncMembers($stage, $payload['member_ids'] ?? []);
        }

        $this->log($stage, $actor, 'updated', 'Stage updated', $stage->name);

        if (($payload['owner_id'] ?? $previousOwner) && $payload['owner_id'] !== $previousOwner && $stage->owner_id) {
            $this->notifier->staff($stage, 'stage_assigned', 'Stage assigned', $stage->name.' was assigned on '.$stage->project->name.'.', $stage, 'assigned-'.$stage->id.'-'.$stage->owner_id);
        }

        return $stage->fresh(['owner', 'members']);
    }

    public function delete(ProjectStage $stage, User $actor): void
    {
        $this->log($stage, $actor, 'deleted', 'Stage deleted', $stage->name);
        $stage->delete();
    }

    /**
     * @param  list<string>  $orderedIds
     */
    public function reorder(Project $project, array $orderedIds, User $actor): void
    {
        $stages = $project->stages()->get()->keyBy('id');

        foreach (array_values($orderedIds) as $index => $id) {
            $stage = $stages->get($id);
            if (! $stage) {
                throw ValidationException::withMessages(['stages' => 'A stage in the order list does not belong to this project.']);
            }
            $stage->forceFill(['sequence' => $index + 1])->save();
        }

        $this->audit->record(
            action: 'reordered',
            module: 'project_stages',
            auditable: $project,
            newValues: ['stage_ids' => $orderedIds],
            user: $actor,
        );
    }

    public function start(ProjectStage $stage, User $actor): ProjectStage
    {
        $this->assertCanActivate($stage);

        if (! in_array($stage->status, [StageStatus::NotStarted, StageStatus::Blocked], true)) {
            throw ValidationException::withMessages(['status' => 'This stage cannot be started from its current status.']);
        }

        $stage->update([
            'status' => StageStatus::InProgress,
            'start_date' => $stage->start_date ?? now()->toDateString(),
        ]);
        $stage->refreshProgress();

        $this->log($stage, $actor, 'started', 'Stage started', $stage->name);
        $this->notifier->staff($stage, 'stage_started', 'Stage started', $stage->name.' on '.$stage->project->name.' is now in progress.', $stage, 'started-'.$stage->id);

        if ($stage->client_review_enabled) {
            $this->notifier->portal($stage, 'stage_started', 'Project update', $stage->name.' on '.$stage->project->name.' is now in progress.', $stage, 'started-'.$stage->id);
        }

        return $stage->fresh();
    }

    public function resume(ProjectStage $stage, User $actor): ProjectStage
    {
        if ($stage->status !== StageStatus::ChangesRequested) {
            throw ValidationException::withMessages(['status' => 'Only stages with requested changes can be resumed.']);
        }

        $stage->update(['status' => StageStatus::InProgress]);
        $this->log($stage, $actor, 'resumed', 'Stage resumed after changes', $stage->name);

        return $stage->fresh();
    }

    public function submitForReview(ProjectStage $stage, User $actor, ?string $comment = null, bool $override = false, ?string $overrideReason = null): ProjectStageSubmission
    {
        if (! in_array($stage->status, [StageStatus::InProgress, StageStatus::ChangesRequested], true)) {
            throw ValidationException::withMessages(['status' => 'Only in-progress stages can be submitted for review.']);
        }

        $incompleteDeliverables = $stage->incompleteRequiredDeliverables()->count();
        $incompleteTasks = $stage->incompleteTasks()->count();
        $missingEvidence = $stage->requires_evidence && $stage->clientVisibleEvidence()->count() === 0;

        if (($incompleteDeliverables > 0 || $incompleteTasks > 0 || $missingEvidence) && ! $override) {
            throw ValidationException::withMessages([
                'status' => 'Complete required tasks, deliverables, and evidence before submitting, or record an authorized override.',
            ]);
        }

        if ($override && blank($overrideReason)) {
            throw ValidationException::withMessages(['override_reason' => 'An override reason is required.']);
        }

        return DB::transaction(function () use ($stage, $actor, $comment, $override, $overrideReason, $incompleteDeliverables, $incompleteTasks, $missingEvidence) {
            $submission = $stage->submissions()->create([
                'version' => $stage->nextVersion(),
                'status' => StageSubmissionStatus::Submitted,
                'submitted_by_id' => $actor->id,
                'submitted_at' => now(),
                'comment' => $comment,
                'override_incomplete' => $override,
                'override_by_id' => $override ? $actor->id : null,
                'override_reason' => $override ? $overrideReason : null,
            ]);

            $stage->update(['status' => StageStatus::ReadyForReview]);
            $stage->refreshProgress();

            $this->log($stage, $actor, 'submitted', 'Stage submitted for review', $stage->name.' · submission #'.$submission->version);

            if ($override) {
                $this->audit->record(
                    action: 'override',
                    module: 'project_stages',
                    auditable: $stage,
                    newValues: [
                        'reason' => $overrideReason,
                        'incomplete_deliverables' => $incompleteDeliverables,
                        'incomplete_tasks' => $incompleteTasks,
                        'missing_evidence' => $missingEvidence,
                    ],
                    user: $actor,
                );
            }

            $this->notifier->staff($stage, 'stage_ready_for_review', 'Stage ready for review', $stage->name.' on '.$stage->project->name.' is ready for review.', $submission, 'ready-'.$submission->id);

            if ($stage->client_review_enabled) {
                $this->notifier->portal($stage, 'stage_ready_for_review', 'Ready for your review', $stage->name.' on '.$stage->project->name.' is ready for your review.', $submission, 'ready-'.$submission->id);
            }

            return $submission->fresh();
        });
    }

    public function approve(ProjectStage $stage, ClientUser|User $actor, ?string $comment = null): ProjectStageSubmission
    {
        if ($stage->status !== StageStatus::ReadyForReview) {
            throw ValidationException::withMessages(['status' => 'Only stages ready for review can be approved.']);
        }

        $submission = $stage->latestSubmission();
        if (! $submission || ! $submission->isPending()) {
            throw ValidationException::withMessages(['status' => 'There is no pending submission to approve.']);
        }

        $submission->update([
            'status' => StageSubmissionStatus::Approved,
            'decided_by_id' => $actor instanceof User ? $actor->id : null,
            'decided_by_client_user_id' => $actor instanceof ClientUser ? $actor->id : null,
            'decided_at' => now(),
            'decision_comment' => $comment,
        ]);

        $stage->update(['status' => StageStatus::Approved]);
        $stage->refreshProgress();

        $this->audit->record(
            action: 'approved',
            module: 'project_stages',
            auditable: $stage,
            newValues: ['submission_id' => $submission->id, 'version' => $submission->version],
            user: $actor,
        );

        $this->activities->log($stage->project, 'stage_approved', 'Stage approved', $stage->name.' · submission #'.$submission->version, ['stage_id' => $stage->id], $actor instanceof User ? $actor : null);

        $this->notifier->staff($stage, 'stage_approved', 'Stage approved', $stage->name.' on '.$stage->project->name.' was approved.', $submission, 'approved-'.$submission->id);

        return $submission->fresh();
    }

    public function requestChanges(ProjectStage $stage, ClientUser|User $actor, string $reason): ProjectStageSubmission
    {
        if ($stage->status !== StageStatus::ReadyForReview) {
            throw ValidationException::withMessages(['status' => 'Only stages ready for review can receive change requests.']);
        }

        if (blank($reason)) {
            throw ValidationException::withMessages(['decision_comment' => 'A reason is required when requesting changes.']);
        }

        $submission = $stage->latestSubmission();
        if (! $submission || ! $submission->isPending()) {
            throw ValidationException::withMessages(['status' => 'There is no pending submission.']);
        }

        $submission->update([
            'status' => StageSubmissionStatus::ChangesRequested,
            'decided_by_id' => $actor instanceof User ? $actor->id : null,
            'decided_by_client_user_id' => $actor instanceof ClientUser ? $actor->id : null,
            'decided_at' => now(),
            'decision_comment' => $reason,
        ]);

        $stage->update(['status' => StageStatus::ChangesRequested]);

        $this->audit->record(
            action: 'changes_requested',
            module: 'project_stages',
            auditable: $stage,
            newValues: ['submission_id' => $submission->id, 'version' => $submission->version],
            user: $actor,
        );

        $this->activities->log($stage->project, 'stage_changes', 'Changes requested', $stage->name.' · submission #'.$submission->version, ['stage_id' => $stage->id], $actor instanceof User ? $actor : null);

        $this->notifier->staff($stage, 'stage_changes_requested', 'Changes requested', 'Changes were requested on '.$stage->name.' for '.$stage->project->name.'.', $submission, 'changes-'.$submission->id);

        return $submission->fresh();
    }

    public function complete(ProjectStage $stage, User $actor, bool $override = false, ?string $overrideReason = null): ProjectStage
    {
        if ($stage->status === StageStatus::Completed) {
            return $stage;
        }

        $allowed = [StageStatus::Approved, StageStatus::InProgress, StageStatus::ReadyForReview];
        if (! in_array($stage->status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'This stage cannot be completed from its current status.']);
        }

        if ($stage->approvalIsRequired() && $stage->status !== StageStatus::Approved) {
            if (! $override) {
                throw ValidationException::withMessages(['status' => 'Client approval is required before this stage can be completed.']);
            }
            if (blank($overrideReason)) {
                throw ValidationException::withMessages(['override_reason' => 'An override reason is required.']);
            }
        }

        $stage->update([
            'status' => StageStatus::Completed,
            'completed_at' => now(),
            'completion_percentage' => 100,
        ]);

        $this->log($stage, $actor, 'completed', 'Stage completed', $stage->name);

        if ($override) {
            $this->audit->record(
                action: 'override',
                module: 'project_stages',
                auditable: $stage,
                newValues: ['reason' => $overrideReason, 'type' => 'complete_without_approval'],
                user: $actor,
            );
        }

        $this->notifier->staff($stage, 'stage_completed', 'Stage completed', $stage->name.' on '.$stage->project->name.' is completed.', $stage, 'completed-'.$stage->id);

        if ($stage->client_review_enabled) {
            $this->notifier->portal($stage, 'stage_completed', 'Stage completed', $stage->name.' on '.$stage->project->name.' is completed.', $stage, 'completed-'.$stage->id);
        }

        return $stage->fresh();
    }

    public function block(ProjectStage $stage, User $actor): ProjectStage
    {
        if ($stage->status === StageStatus::Completed) {
            throw ValidationException::withMessages(['status' => 'A completed stage cannot be blocked.']);
        }

        $stage->update(['status' => StageStatus::Blocked]);
        $this->log($stage, $actor, 'blocked', 'Stage blocked', $stage->name);

        return $stage->fresh();
    }

    public function unblock(ProjectStage $stage, User $actor): ProjectStage
    {
        if ($stage->status !== StageStatus::Blocked) {
            throw ValidationException::withMessages(['status' => 'Only blocked stages can be unblocked.']);
        }

        $this->assertCanActivate($stage);
        $stage->update(['status' => StageStatus::InProgress]);
        $this->log($stage, $actor, 'unblocked', 'Stage unblocked', $stage->name);

        return $stage->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveDeliverable(ProjectStage $stage, array $payload, User $actor, ?ProjectStageDeliverable $deliverable = null): ProjectStageDeliverable
    {
        $data = [
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'is_required' => $payload['is_required'] ?? true,
            'evidence_id' => $payload['evidence_id'] ?? null,
            'sequence' => $payload['sequence'] ?? (($deliverable?->sequence) ?: ((int) $stage->deliverables()->max('sequence') + 1)),
        ];

        if ($deliverable) {
            $deliverable->update($data);
        } else {
            $deliverable = $stage->deliverables()->create($data);
        }

        $stage->refreshProgress();
        $this->log($stage, $actor, 'deliverable', 'Deliverable saved', $deliverable->name);

        return $deliverable->fresh();
    }

    public function toggleDeliverable(ProjectStageDeliverable $deliverable, User $actor): ProjectStageDeliverable
    {
        if ($deliverable->isCompleted()) {
            $deliverable->update([
                'status' => StageDeliverableStatus::Pending,
                'completed_by_id' => null,
                'completed_at' => null,
            ]);
        } else {
            $deliverable->update([
                'status' => StageDeliverableStatus::Completed,
                'completed_by_id' => $actor->id,
                'completed_at' => now(),
            ]);
        }

        $deliverable->stage->refreshProgress();
        $this->log($deliverable->stage, $actor, 'deliverable', 'Deliverable updated', $deliverable->name);

        return $deliverable->fresh();
    }

    public function deleteDeliverable(ProjectStageDeliverable $deliverable, User $actor): void
    {
        $stage = $deliverable->stage;
        $this->log($stage, $actor, 'deliverable', 'Deliverable deleted', $deliverable->name);
        $deliverable->delete();
        $stage->refreshProgress();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function addEvidence(ProjectStage $stage, array $payload, User $actor, ?UploadedFile $file = null): ProjectStageEvidence
    {
        $type = $payload['type'] instanceof StageEvidenceType ? $payload['type'] : StageEvidenceType::from($payload['type']);

        $path = null;
        $disk = null;
        $original = null;
        $mime = null;
        $size = null;

        if ($type->requiresFile()) {
            if (! $file) {
                throw ValidationException::withMessages(['file' => 'A file is required for this evidence type.']);
            }
            $disk = 'stages';
            $path = $file->store('projects/'.$stage->project_id.'/stages/'.$stage->id, $disk);
            $original = $file->getClientOriginalName();
            $mime = $file->getMimeType();
            $size = $file->getSize();
        }

        if ($type->requiresUrl() && blank($payload['url'] ?? null)) {
            throw ValidationException::withMessages(['url' => 'An external URL is required.']);
        }

        $evidence = $stage->evidence()->create([
            'uploaded_by_id' => $actor->id,
            'type' => $type,
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'url' => $payload['url'] ?? null,
            'path' => $path,
            'disk' => $disk,
            'original_name' => $original,
            'mime_type' => $mime,
            'size' => $size,
            'version' => $payload['version'] ?? (string) $stage->nextVersion(),
            'visibility' => $payload['visibility'] ?? StageEvidenceVisibility::Client->value,
        ]);

        $this->log($stage, $actor, 'evidence', 'Work evidence added', $evidence->title);
        $this->notifier->staff($stage, 'stage_evidence', 'New work evidence', $evidence->title.' was added to '.$stage->name.'.', $evidence, 'evidence-'.$evidence->id, $actor);

        return $evidence;
    }

    public function deleteEvidence(ProjectStageEvidence $evidence, User $actor): void
    {
        $stage = $evidence->stage;
        $this->log($stage, $actor, 'evidence', 'Work evidence removed', $evidence->title);
        $evidence->delete();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function postMessage(ProjectStage $stage, array $payload, User|ClientUser $actor, ?UploadedFile $file = null): ProjectStageMessage
    {
        $isClient = $actor instanceof ClientUser;
        $isInternal = $isClient ? false : (bool) ($payload['is_internal'] ?? false);

        if ($isClient && $isInternal) {
            throw ValidationException::withMessages(['is_internal' => 'Client messages cannot be internal.']);
        }

        $parentId = $payload['parent_id'] ?? null;
        if ($parentId) {
            $parent = $stage->allMessages()->find($parentId);
            if (! $parent || ($isClient && $parent->is_internal)) {
                throw ValidationException::withMessages(['parent_id' => 'That reply target is not available.']);
            }
        }

        $message = $stage->allMessages()->create([
            'parent_id' => $parentId,
            'author_id' => $actor instanceof User ? $actor->id : null,
            'client_user_id' => $isClient ? $actor->id : null,
            'body' => $payload['body'],
            'is_internal' => $isInternal,
        ]);

        if ($file) {
            $disk = 'stages';
            $path = $file->store('projects/'.$stage->project_id.'/stages/'.$stage->id.'/discussion', $disk);
            $message->attachments()->create([
                'uploaded_by_id' => $actor instanceof User ? $actor->id : null,
                'uploaded_by_client_user_id' => $isClient ? $actor->id : null,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'disk' => $disk,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->audit->record(
            action: 'discussed',
            module: 'project_stages',
            auditable: $stage,
            newValues: ['message_id' => $message->id, 'internal' => $isInternal],
            user: $actor,
        );

        if ($isInternal) {
            $this->notifier->staff(
                $stage,
                'stage_discussion',
                'Internal stage discussion',
                'A new internal comment was added on '.$stage->name.'.',
                $message,
                'discussion-'.$message->id,
                $actor instanceof User ? $actor : null,
            );
        } else {
            $this->notifier->staff(
                $stage,
                'stage_discussion',
                'Stage discussion',
                'A new comment was added on '.$stage->name.'.',
                $message,
                'discussion-'.$message->id,
                $actor instanceof User ? $actor : null,
            );
            $this->notifier->portal(
                $stage,
                'stage_discussion',
                'New project comment',
                'There is a new comment on '.$stage->name.' for '.$stage->project->name.'.',
                $message,
                'discussion-'.$message->id,
                $isClient ? $actor : null,
            );
        }

        return $message->fresh('attachments');
    }

    public function assertCanActivate(ProjectStage $stage): void
    {
        $previous = $stage->project->stages()
            ->where('sequence', '<', $stage->sequence)
            ->ordered()
            ->get();

        foreach ($previous as $prior) {
            if ($prior->approvalIsRequired() && ! in_array($prior->status, [StageStatus::Completed, StageStatus::Approved], true)) {
                throw ValidationException::withMessages([
                    'status' => 'The previous stage “'.$prior->name.'” must be approved and completed before this stage can start.',
                ]);
            }
        }
    }

    /**
     * @param  list<string>  $memberIds
     */
    protected function syncMembers(ProjectStage $stage, array $memberIds): void
    {
        $ids = collect($memberIds)->filter()->unique()->values()->all();
        $stage->members()->sync($ids);
    }

    protected function log(ProjectStage $stage, User $actor, string $type, string $title, string $body): void
    {
        $stage->loadMissing('project');
        $this->activities->log(
            $stage->project,
            $type,
            $title,
            $body,
            ['stage_id' => $stage->id],
            $actor,
        );
    }
}
