<?php

namespace App\Services\ClientPortal;

use App\Enums\ChangeRequestStatus;
use App\Enums\DocumentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\QuotationStatus;
use App\Enums\RequirementApprovalStatus;
use App\Enums\StageEvidenceVisibility;
use App\Enums\StageStatus;
use App\Enums\TicketStatus;
use App\Models\ChangeRequest;
use App\Models\ClientUser;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\ProjectStage;
use App\Models\ProjectStageEvidence;
use App\Models\ProjectStageMessage;
use App\Models\ProjectStageMessageAttachment;
use App\Models\ProjectStageSubmission;
use App\Models\Quotation;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Database\Eloquent\Builder;

class ClientAccess
{
    public function clientId(ClientUser $user): string
    {
        return $user->client_id;
    }

    public function projects(ClientUser $user): Builder
    {
        return Project::query()->where('client_id', $this->clientId($user));
    }

    public function project(ClientUser $user, string $id): Project
    {
        return $this->projects($user)->findOrFail($id);
    }

    public function quotations(ClientUser $user): Builder
    {
        return Quotation::query()
            ->where('client_id', $this->clientId($user))
            ->where('status', '!=', QuotationStatus::Draft->value);
    }

    public function quotation(ClientUser $user, string $id): Quotation
    {
        return $this->quotations($user)->findOrFail($id);
    }

    public function invoices(ClientUser $user): Builder
    {
        return Invoice::query()
            ->where('client_id', $this->clientId($user))
            ->whereNotIn('status', [
                InvoiceStatus::Draft->value,
                InvoiceStatus::Cancelled->value,
            ]);
    }

    public function invoice(ClientUser $user, string $id): Invoice
    {
        return $this->invoices($user)->findOrFail($id);
    }

    public function payments(ClientUser $user): Builder
    {
        return Payment::query()->where('client_id', $this->clientId($user));
    }

    public function payment(ClientUser $user, string $id): Payment
    {
        return $this->payments($user)
            ->whereHas('invoice', function (Builder $query) use ($user): void {
                $query->where('client_id', $this->clientId($user))
                    ->whereNotIn('status', [
                        InvoiceStatus::Draft->value,
                        InvoiceStatus::Cancelled->value,
                    ]);
            })
            ->findOrFail($id);
    }

    public function tickets(ClientUser $user): Builder
    {
        return Ticket::query()->where('client_id', $this->clientId($user));
    }

    public function ticket(ClientUser $user, string $id): Ticket
    {
        return $this->tickets($user)->findOrFail($id);
    }

    public function documents(ClientUser $user): Builder
    {
        return Document::query()
            ->where('client_id', $this->clientId($user))
            ->whereIn('status', [
                DocumentStatus::Approved->value,
                DocumentStatus::Sent->value,
                DocumentStatus::Signed->value,
                DocumentStatus::Archived->value,
            ]);
    }

    public function document(ClientUser $user, string $id): Document
    {
        return $this->documents($user)->findOrFail($id);
    }

    public function changeRequests(ClientUser $user): Builder
    {
        return ChangeRequest::query()->whereHas('project', function (Builder $query) use ($user): void {
            $query->where('client_id', $this->clientId($user));
        });
    }

    public function changeRequest(ClientUser $user, string $id): ChangeRequest
    {
        return $this->changeRequests($user)->findOrFail($id);
    }

    public function projectFiles(ClientUser $user, Project $project): Builder
    {
        abort_unless($project->client_id === $this->clientId($user), 404);

        return ProjectAttachment::query()
            ->where('project_id', $project->id)
            ->whereHas('requirement', function (Builder $query): void {
                $query->where('client_approval_status', RequirementApprovalStatus::Approved->value);
            });
    }

    public function projectFile(ClientUser $user, Project $project, string $attachmentId): ProjectAttachment
    {
        return $this->projectFiles($user, $project)->findOrFail($attachmentId);
    }

    public function stages(ClientUser $user, Project $project): Builder
    {
        abort_unless($project->client_id === $this->clientId($user), 404);

        return ProjectStage::query()
            ->where('project_id', $project->id)
            ->orderBy('sequence');
    }

    public function stage(ClientUser $user, Project $project, string $stageId): ProjectStage
    {
        return $this->stages($user, $project)->findOrFail($stageId);
    }

    public function stageEvidence(ClientUser $user, Project $project, ProjectStage $stage): Builder
    {
        abort_unless($stage->project_id === $project->id, 404);
        abort_unless($project->client_id === $this->clientId($user), 404);

        return ProjectStageEvidence::query()
            ->where('stage_id', $stage->id)
            ->where('visibility', StageEvidenceVisibility::Client->value);
    }

    public function stageEvidenceItem(ClientUser $user, Project $project, ProjectStage $stage, string $evidenceId): ProjectStageEvidence
    {
        return $this->stageEvidence($user, $project, $stage)->findOrFail($evidenceId);
    }

    public function stageMessages(ClientUser $user, Project $project, ProjectStage $stage): Builder
    {
        abort_unless($stage->project_id === $project->id, 404);
        abort_unless($project->client_id === $this->clientId($user), 404);

        return ProjectStageMessage::query()
            ->where('stage_id', $stage->id)
            ->visibleToClient();
    }

    public function stageMessage(ClientUser $user, Project $project, ProjectStage $stage, string $messageId): ProjectStageMessage
    {
        return $this->stageMessages($user, $project, $stage)->findOrFail($messageId);
    }

    public function stageMessageAttachment(ClientUser $user, Project $project, ProjectStage $stage, string $messageId, string $attachmentId): ProjectStageMessageAttachment
    {
        $message = $this->stageMessage($user, $project, $stage, $messageId);

        return ProjectStageMessageAttachment::query()
            ->where('message_id', $message->id)
            ->findOrFail($attachmentId);
    }

    public function stageSubmission(ClientUser $user, Project $project, ProjectStage $stage, string $submissionId): ProjectStageSubmission
    {
        abort_unless($stage->project_id === $project->id, 404);
        abort_unless($project->client_id === $this->clientId($user), 404);

        return ProjectStageSubmission::query()
            ->where('stage_id', $stage->id)
            ->findOrFail($submissionId);
    }

    public function stagesAwaitingReview(ClientUser $user): Builder
    {
        return ProjectStage::query()
            ->where('status', StageStatus::ReadyForReview->value)
            ->where('client_review_enabled', true)
            ->whereHas('project', fn (Builder $query) => $query->where('client_id', $this->clientId($user)));
    }

    public function ticketAttachment(ClientUser $user, Ticket $ticket, string $messageId, string $attachmentId): TicketAttachment
    {
        abort_unless($ticket->client_id === $this->clientId($user), 404);

        return TicketAttachment::query()
            ->where('id', $attachmentId)
            ->whereHas('message', function (Builder $query) use ($ticket, $messageId): void {
                $query->where('id', $messageId)
                    ->where('ticket_id', $ticket->id)
                    ->where('is_internal', false);
            })
            ->findOrFail($attachmentId);
    }

    /**
     * @return list<string>
     */
    public function pendingQuotationStatuses(): array
    {
        return [QuotationStatus::Sent->value, QuotationStatus::Viewed->value];
    }

    /**
     * @return list<string>
     */
    public function openTicketStatuses(): array
    {
        return [
            TicketStatus::Open->value,
            TicketStatus::InProgress->value,
            TicketStatus::WaitingForClient->value,
        ];
    }

    /**
     * @return list<string>
     */
    public function pendingChangeRequestStatuses(): array
    {
        return [ChangeRequestStatus::Pending->value];
    }
}
