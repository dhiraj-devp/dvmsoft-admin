<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\LeadConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Lead::class);

        return view('leads.index');
    }

    public function show(Lead $lead): View
    {
        $this->authorize('view', $lead);

        $lead->load(['assignedUser', 'convertedClient', 'activities.user']);

        return view('leads.show', compact('lead'));
    }

    public function convert(Lead $lead, LeadConversionService $converter): RedirectResponse
    {
        $this->authorize('convert', $lead);

        $client = $converter->convert($lead, request()->user());

        return redirect()
            ->route('clients.show', $client)
            ->with('status', 'Lead converted without duplicating existing client data.');
    }
}
