<?php

namespace App\Http\Controllers\Portal;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends PortalController
{
    public function index(Request $request): View
    {
        $user = $this->portalUser();
        $status = $request->string('status')->toString();

        $invoices = $this->access()->invoices($user)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('invoice_date')
            ->paginate(15)
            ->withQueryString();

        return view('portal.invoices.index', compact('invoices', 'status'));
    }

    public function show(string $invoice): View
    {
        $user = $this->portalUser();
        $invoice = $this->access()->invoice($user, $invoice)->load(['items', 'payments', 'project']);

        return view('portal.invoices.show', compact('invoice'));
    }

    public function pdf(string $invoice): Response
    {
        $user = $this->portalUser();
        $invoice = $this->access()->invoice($user, $invoice)->load(['client', 'project', 'quotation', 'items']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'logoPath' => company_logo_path(),
        ])->setPaper('a4');

        return $pdf->download($invoice->number.'.pdf');
    }
}
