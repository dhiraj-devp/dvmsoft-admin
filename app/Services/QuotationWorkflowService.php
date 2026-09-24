<?php

namespace App\Services;

use App\Automations\ClientPortalNotifier;
use App\Enums\LeadStatus;
use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class QuotationWorkflowService
{
    public function __construct(
        protected CrmActivityLogger $activities,
        protected ClientPortalNotifier $portal,
    ) {}

    public function send(Quotation $quotation, User $actor): Quotation
    {
        $this->assertStatus($quotation, [QuotationStatus::Draft, QuotationStatus::Sent]);

        $quotation->update([
            'status' => QuotationStatus::Sent,
            'sent_at' => $quotation->sent_at ?? now(),
        ]);

        $this->log($quotation, $actor, 'sent', 'Quotation sent');
        $this->notifyPortal($quotation, 'sent', 'Quotation ready', $quotation->number.' is ready to review.');

        return $quotation->fresh();
    }

    public function markViewed(Quotation $quotation, ?User $actor = null): Quotation
    {
        if (! in_array($quotation->status, [QuotationStatus::Sent, QuotationStatus::Viewed], true)) {
            return $quotation;
        }

        if ($quotation->status === QuotationStatus::Sent) {
            $quotation->update([
                'status' => QuotationStatus::Viewed,
                'viewed_at' => now(),
            ]);

            $this->log($quotation, $actor, 'viewed', 'Quotation viewed');
        }

        return $quotation->fresh();
    }

    public function accept(Quotation $quotation, ?User $actor = null): Quotation
    {
        $this->assertStatus($quotation, [QuotationStatus::Sent, QuotationStatus::Viewed]);

        $quotation->update([
            'status' => QuotationStatus::Accepted,
            'accepted_at' => now(),
        ]);

        if ($quotation->lead && $quotation->lead->status->isOpen()) {
            $quotation->lead->update(['status' => LeadStatus::Won]);
        }

        $this->log($quotation, $actor, 'accepted', 'Quotation accepted');
        $this->notifyPortal($quotation, 'accepted', 'Quotation accepted', $quotation->number.' was accepted.');

        return $quotation->fresh();
    }

    public function reject(Quotation $quotation, ?User $actor = null): Quotation
    {
        $this->assertStatus($quotation, [QuotationStatus::Sent, QuotationStatus::Viewed]);

        $quotation->update([
            'status' => QuotationStatus::Rejected,
            'rejected_at' => now(),
        ]);

        $this->log($quotation, $actor, 'rejected', 'Quotation rejected');
        $this->notifyPortal($quotation, 'rejected', 'Quotation declined', $quotation->number.' was declined.');

        return $quotation->fresh();
    }

    public function convert(Quotation $quotation, User $actor): Quotation
    {
        $this->assertStatus($quotation, [QuotationStatus::Accepted]);

        $quotation->update([
            'status' => QuotationStatus::Converted,
            'converted_at' => now(),
        ]);

        $this->log($quotation, $actor, 'converted', 'Quotation converted');

        return $quotation->fresh();
    }

    /**
     * @param  list<QuotationStatus>  $allowed
     */
    protected function assertStatus(Quotation $quotation, array $allowed): void
    {
        if (! in_array($quotation->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'This quotation cannot move from '.$quotation->status->label().' with the selected action.',
            ]);
        }
    }

    protected function log(Quotation $quotation, ?User $actor, string $type, string $title): void
    {
        if ($quotation->client) {
            $this->activities->log(
                $quotation->client,
                $type,
                $title,
                $quotation->number.' · '.$quotation->title,
                ['quotation_id' => $quotation->id],
                $actor,
            );
        }
    }

    protected function notifyPortal(Quotation $quotation, string $event, string $title, string $message): void
    {
        $quotation->loadMissing('client');

        $this->portal->notify(
            $quotation->client,
            'portal.quotation_decision',
            $event,
            $title,
            $message,
            $quotation->client ? route('client.quotations.show', $quotation) : null,
            ['quotation_id' => $quotation->id],
            $quotation,
            $event,
        );
    }
}
