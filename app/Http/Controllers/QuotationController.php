<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Services\ProjectFromQuotationService;
use App\Services\QuotationWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Quotation::class);

        return view('quotations.index');
    }

    public function create(): View
    {
        $this->authorize('create', Quotation::class);

        return view('quotations.create');
    }

    public function show(Quotation $quotation): View
    {
        $this->authorize('view', $quotation);

        $quotation->load(['client.contacts', 'lead', 'createdBy', 'items', 'project']);

        return view('quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation): View
    {
        $this->authorize('update', $quotation);

        return view('quotations.edit', compact('quotation'));
    }

    public function send(Quotation $quotation, QuotationWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('send', $quotation);
        $workflow->send($quotation, request()->user());

        return back()->with('status', 'Quotation marked as sent.');
    }

    public function accept(Quotation $quotation, QuotationWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('approve', $quotation);
        $workflow->accept($quotation, request()->user());

        return back()->with('status', 'Quotation accepted.');
    }

    public function reject(Quotation $quotation, QuotationWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('approve', $quotation);
        $workflow->reject($quotation, request()->user());

        return back()->with('status', 'Quotation rejected.');
    }

    public function convert(Quotation $quotation, QuotationWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('convert', $quotation);
        $workflow->convert($quotation, request()->user());

        return back()->with('status', 'Quotation converted.');
    }

    public function createProject(Quotation $quotation, ProjectFromQuotationService $projects): RedirectResponse
    {
        $this->authorize('createProject', $quotation);

        $project = $projects->create($quotation, request()->user());

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Project created from the converted quotation.');
    }

    public function pdf(Quotation $quotation, QuotationWorkflowService $workflow): Response
    {
        $this->authorize('view', $quotation);

        $workflow->markViewed($quotation, request()->user());
        $quotation->load(['client.contacts', 'items']);

        $pdf = Pdf::loadView('quotations.pdf', [
            'quotation' => $quotation->fresh(['client.contacts', 'items']),
        ])->setPaper('a4');

        return $pdf->download($quotation->number.'.pdf');
    }
}
