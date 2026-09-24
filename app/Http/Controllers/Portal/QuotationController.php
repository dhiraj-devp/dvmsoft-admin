<?php

namespace App\Http\Controllers\Portal;

use App\Enums\QuotationStatus;
use App\Services\AuditLogger;
use App\Services\QuotationWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QuotationController extends PortalController
{
    public function index(Request $request): View
    {
        $user = $this->portalUser();
        $status = $request->string('status')->toString();

        $quotations = $this->access()->quotations($user)
            ->with('items')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('portal.quotations.index', compact('quotations', 'status'));
    }

    public function show(string $quotation, QuotationWorkflowService $workflow): View
    {
        $user = $this->portalUser();
        $quotation = $this->access()->quotation($user, $quotation)->load('items');

        $workflow->markViewed($quotation);

        return view('portal.quotations.show', [
            'quotation' => $quotation->fresh('items'),
            'canDecide' => in_array($quotation->fresh()->status, [QuotationStatus::Sent, QuotationStatus::Viewed], true),
        ]);
    }

    public function pdf(string $quotation, QuotationWorkflowService $workflow): Response
    {
        $user = $this->portalUser();
        $quotation = $this->access()->quotation($user, $quotation);

        $workflow->markViewed($quotation);
        $quotation->load(['client.contacts', 'items']);

        $pdf = Pdf::loadView('quotations.pdf', [
            'quotation' => $quotation->fresh(['client.contacts', 'items']),
        ])->setPaper('a4');

        return $pdf->download($quotation->number.'.pdf');
    }

    public function accept(string $quotation, QuotationWorkflowService $workflow, AuditLogger $audit): RedirectResponse
    {
        $user = $this->portalUser();
        $quotation = $this->access()->quotation($user, $quotation);

        $workflow->accept($quotation);
        $quotation = $quotation->fresh();

        $audit->record(
            action: 'accepted',
            module: 'client_portal',
            auditable: $quotation,
            newValues: array_merge($user->auditActorValues(), [
                'quotation_id' => $quotation->id,
                'status' => $quotation->status->value,
            ]),
            user: $user,
        );

        return back()->with('status', 'Quotation accepted.');
    }

    public function reject(string $quotation, QuotationWorkflowService $workflow, AuditLogger $audit): RedirectResponse
    {
        $user = $this->portalUser();
        $quotation = $this->access()->quotation($user, $quotation);

        $workflow->reject($quotation);
        $quotation = $quotation->fresh();

        $audit->record(
            action: 'rejected',
            module: 'client_portal',
            auditable: $quotation,
            newValues: array_merge($user->auditActorValues(), [
                'quotation_id' => $quotation->id,
                'status' => $quotation->status->value,
            ]),
            user: $user,
        );

        return back()->with('status', 'Quotation declined.');
    }
}
