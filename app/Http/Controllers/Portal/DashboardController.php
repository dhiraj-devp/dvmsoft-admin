<?php

namespace App\Http\Controllers\Portal;

use App\Enums\StageStatus;
use App\Models\ChangeRequest;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Quotation;
use App\Models\Ticket;
use Illuminate\View\View;

class DashboardController extends PortalController
{
    public function __invoke(): View
    {
        $user = $this->portalUser();
        $access = $this->access();

        $projects = $access->projects($user)->withCount('milestones')->with('stages')->latest()->get();
        $activeProjects = $projects->filter(fn (Project $project) => $project->status->isOpen());

        $awaitingStages = $access->stagesAwaitingReview($user)->with('project')->orderByDesc('updated_at')->limit(6)->get();
        $awaitingCount = $access->stagesAwaitingReview($user)->count();
        $recentlyCompleted = ProjectStage::query()
            ->where('status', StageStatus::Completed->value)
            ->whereHas('project', fn ($query) => $query->where('client_id', $access->clientId($user)))
            ->orderByDesc('completed_at')
            ->limit(6)
            ->with('project')
            ->get();

        $pendingQuotations = $access->quotations($user)
            ->whereIn('status', $access->pendingQuotationStatuses())
            ->count();

        $outstandingInvoices = $access->invoices($user)->outstanding();

        $openTickets = $access->tickets($user)->open()->count();

        $pendingChangeRequests = $access->changeRequests($user)
            ->where('status', $access->pendingChangeRequestStatuses()[0])
            ->count();

        $recent = collect()
            ->merge($access->projects($user)->latest()->limit(5)->get()->map(fn (Project $item) => [
                'label' => $item->name,
                'meta' => $item->number,
                'status' => $item->status->label(),
                'tone' => $item->status->tone(),
                'at' => $item->updated_at,
                'url' => route('client.projects.show', $item),
            ]))
            ->merge($access->quotations($user)->latest()->limit(5)->get()->map(fn (Quotation $item) => [
                'label' => $item->title,
                'meta' => $item->number,
                'status' => $item->status->label(),
                'tone' => $item->status->tone(),
                'at' => $item->updated_at,
                'url' => route('client.quotations.show', $item),
            ]))
            ->merge($access->invoices($user)->latest()->limit(5)->get()->map(fn (Invoice $item) => [
                'label' => $item->title,
                'meta' => $item->number,
                'status' => $item->status->label(),
                'tone' => $item->status->tone(),
                'at' => $item->updated_at,
                'url' => route('client.invoices.show', $item),
            ]))
            ->merge($access->tickets($user)->latest()->limit(5)->get()->map(fn (Ticket $item) => [
                'label' => $item->subject,
                'meta' => $item->number,
                'status' => $item->status->label(),
                'tone' => $item->status->tone(),
                'at' => $item->updated_at,
                'url' => route('client.tickets.show', $item),
            ]))
            ->merge($access->changeRequests($user)->latest()->limit(5)->get()->map(fn (ChangeRequest $item) => [
                'label' => $item->title,
                'meta' => $item->number,
                'status' => $item->status->label(),
                'tone' => $item->status->tone(),
                'at' => $item->updated_at,
                'url' => route('client.change-requests.show', $item),
            ]))
            ->sortByDesc('at')
            ->take(8)
            ->values();

        $metrics = [
            [
                'label' => 'Active projects',
                'value' => (string) $activeProjects->count(),
                'hint' => $projects->count().' total',
                'icon' => 'briefcase',
            ],
            [
                'label' => 'Pending quotations',
                'value' => (string) $pendingQuotations,
                'hint' => 'Awaiting your response',
                'icon' => 'document',
            ],
            [
                'label' => 'Outstanding invoices',
                'value' => money($outstandingInvoices->sum('balance')),
                'hint' => $outstandingInvoices->count().' open',
                'icon' => 'currency',
            ],
            [
                'label' => 'Open tickets',
                'value' => (string) $openTickets,
                'hint' => $pendingChangeRequests.' pending approvals',
                'icon' => 'lifebuoy',
            ],
            [
                'label' => 'Stages to review',
                'value' => (string) $awaitingCount,
                'hint' => 'Waiting for your approval',
                'icon' => 'document',
            ],
        ];

        return view('portal.dashboard', [
            'user' => $user,
            'metrics' => $metrics,
            'activeProjects' => $activeProjects->take(6),
            'pendingQuotations' => $pendingQuotations,
            'outstandingCount' => $outstandingInvoices->count(),
            'openTickets' => $openTickets,
            'pendingChangeRequests' => $pendingChangeRequests,
            'awaitingStages' => $awaitingStages,
            'recentlyCompletedStages' => $recentlyCompleted,
            'recent' => $recent,
        ]);
    }
}
