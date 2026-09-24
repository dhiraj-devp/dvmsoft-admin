<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceFromProjectService;
use App\Services\InvoiceWorkflowService;
use App\Services\ProjectProfitabilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Invoice::class);

        return view('invoices.index');
    }

    public function create(): View
    {
        $this->authorize('create', Invoice::class);

        return view('invoices.create');
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'project', 'quotation', 'createdBy', 'items', 'payments.recordedBy']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('update', $invoice);

        return view('invoices.edit', compact('invoice'));
    }

    public function send(Invoice $invoice, InvoiceWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('send', $invoice);
        $workflow->send($invoice, request()->user());

        return back()->with('status', 'Invoice marked as sent.');
    }

    public function cancel(Invoice $invoice, InvoiceWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('cancel', $invoice);
        $workflow->cancel($invoice, request()->user());

        return back()->with('status', 'Invoice cancelled.');
    }

    public function fromProject(\App\Models\Project $project, InvoiceFromProjectService $invoices): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = $invoices->create($project, request()->user());

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'Draft invoice created from the project.');
    }

    public function pdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'project', 'quotation', 'items']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'logoPath' => company_logo_path(),
        ])->setPaper('a4');

        return $pdf->download($invoice->number.'.pdf');
    }

    public function outstanding(): View
    {
        $this->authorize('viewAny', Invoice::class);

        return view('finance.outstanding');
    }

    public function reports(ProjectProfitabilityService $profitability): View
    {
        $this->authorize('finance.reports.view');

        return view('finance.reports', [
            'rows' => $profitability->rows(),
        ]);
    }
}
