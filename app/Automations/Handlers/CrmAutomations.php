<?php

namespace App\Automations\Handlers;

use App\Automations\AutomationResult;
use App\Automations\Contracts\AutomationHandler;
use App\Automations\StaffNotifier;
use App\Enums\QuotationStatus;
use App\Models\Automation;
use App\Models\Lead;
use App\Models\Quotation;
use App\Services\FollowUpReminderService;

class CrmAutomations implements AutomationHandler
{
    public function __construct(
        protected FollowUpReminderService $followUps,
        protected StaffNotifier $staff,
    ) {}

    public function handle(Automation $automation): AutomationResult
    {
        return match ($automation->key) {
            'crm.follow_up_due' => $this->followUpDue(),
            'crm.lead_follow_up_upcoming' => $this->leadUpcoming($automation),
            'crm.lead_follow_up_overdue' => $this->leadOverdue($automation),
            'crm.quotation_follow_up' => $this->quotationFollowUp($automation),
            default => new AutomationResult,
        };
    }

    protected function followUpDue(): AutomationResult
    {
        $result = $this->followUps->sendDueReminders();

        return new AutomationResult($result['processed'], $result['notified']);
    }

    protected function leadUpcoming(Automation $automation): AutomationResult
    {
        $days = max(1, (int) settings('automations.lead_follow_up_upcoming_days', 1));
        $result = new AutomationResult;
        $format = settings('company.date_format', 'd M Y');

        $leads = Lead::query()
            ->with('assignedUser')
            ->open()
            ->whereNotNull('next_follow_up_at')
            ->whereBetween('next_follow_up_at', [now(), now()->addDays($days)->endOfDay()])
            ->get();

        foreach ($leads as $lead) {
            $result->processed++;
            $users = collect([$lead->assignedUser])->filter();

            if ($users->isEmpty()) {
                $users = $this->staff->withPermission('leads.view');
            }

            $result->notified += $this->staff->send(
                $automation->key,
                $users,
                'Upcoming lead follow-up',
                $lead->name.' follow-up is due on '.$lead->next_follow_up_at->format($format).'.',
                url(route('leads.show', $lead, false)),
                ['lead_id' => $lead->id],
                $lead,
                $lead->next_follow_up_at->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }

    protected function leadOverdue(Automation $automation): AutomationResult
    {
        $result = new AutomationResult;
        $format = settings('company.date_format', 'd M Y');

        $leads = Lead::query()
            ->with('assignedUser')
            ->open()
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', now())
            ->get();

        foreach ($leads as $lead) {
            $result->processed++;
            $users = collect([$lead->assignedUser])->filter();

            if ($users->isEmpty()) {
                $users = $this->staff->withPermission('leads.view');
            }

            $result->notified += $this->staff->send(
                $automation->key,
                $users,
                'Overdue lead follow-up',
                $lead->name.' follow-up was due on '.$lead->next_follow_up_at->format($format).'.',
                url(route('leads.show', $lead, false)),
                ['lead_id' => $lead->id],
                $lead,
                now()->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }

    protected function quotationFollowUp(Automation $automation): AutomationResult
    {
        $days = max(1, (int) settings('automations.quotation_follow_up_days', 3));
        $result = new AutomationResult;

        $quotations = Quotation::query()
            ->with(['createdBy', 'client'])
            ->whereIn('status', [QuotationStatus::Sent->value, QuotationStatus::Viewed->value])
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', now()->subDays($days))
            ->get();

        foreach ($quotations as $quotation) {
            $result->processed++;
            $users = collect([$quotation->createdBy])->filter();

            if ($users->isEmpty()) {
                $users = $this->staff->withPermission('quotations.view');
            }

            $result->notified += $this->staff->send(
                $automation->key,
                $users,
                'Quotation follow-up',
                $quotation->number.' is still awaiting a decision.',
                url(route('quotations.show', $quotation, false)),
                ['quotation_id' => $quotation->id],
                $quotation,
                now()->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }
}
