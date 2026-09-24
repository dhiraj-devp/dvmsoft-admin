<?php

namespace App\Http\Controllers;

use App\Enums\FollowUpStatus;
use App\Enums\LeadStatus;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Quotation;
use Illuminate\View\View;

class SalesDashboardController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('sales.view');

        $leads = Lead::query();

        return view('sales.dashboard', [
            'metrics' => [
                ['label' => 'Total leads', 'value' => (clone $leads)->count(), 'hint' => 'All captured demand', 'icon' => 'briefcase'],
                ['label' => 'New leads', 'value' => Lead::query()->where('status', LeadStatus::New)->count(), 'hint' => 'Waiting first contact', 'icon' => 'plus'],
                ['label' => 'Qualified', 'value' => Lead::query()->where('status', LeadStatus::Qualified)->count(), 'hint' => 'Ready for proposal', 'icon' => 'check'],
                ['label' => 'Active opportunities', 'value' => Lead::query()->whereIn('status', [LeadStatus::Qualified, LeadStatus::Proposal, LeadStatus::Negotiation])->count(), 'hint' => 'In the pipeline', 'icon' => 'folder'],
                ['label' => 'Won deals', 'value' => Lead::query()->where('status', LeadStatus::Won)->count(), 'hint' => 'Closed this book', 'icon' => 'check'],
                ['label' => 'Pipeline value', 'value' => money(Lead::query()->whereIn('status', [LeadStatus::Qualified, LeadStatus::Proposal, LeadStatus::Negotiation])->sum('estimated_value')), 'hint' => 'Open opportunity value', 'icon' => 'currency'],
                ['label' => 'Won revenue', 'value' => money(Lead::query()->where('status', LeadStatus::Won)->sum('estimated_value')), 'hint' => 'Won estimated value', 'icon' => 'currency'],
                ['label' => 'Pending follow-ups', 'value' => FollowUp::query()->pending()->count(), 'hint' => 'Still on the calendar', 'icon' => 'clock'],
            ],
            'recentLeads' => Lead::query()->with('assignedUser')->latest()->limit(6)->get(),
            'recentQuotations' => Quotation::query()->with('client')->latest()->limit(6)->get(),
            'upcomingFollowUps' => FollowUp::query()
                ->with(['assignedUser', 'followable'])
                ->where('status', FollowUpStatus::Pending)
                ->orderBy('scheduled_at')
                ->limit(6)
                ->get(),
        ]);
    }
}
