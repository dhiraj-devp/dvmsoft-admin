<?php

namespace App\Http\Controllers\Portal;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PaymentController extends PortalController
{
    public function index(Request $request): View
    {
        $user = $this->portalUser();
        $search = $request->string('q')->toString();

        $payments = $this->access()->payments($user)
            ->with('invoice')
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search): void {
                $inner->where('reference', 'like', '%'.$search.'%')
                    ->orWhereHas('invoice', fn ($invoice) => $invoice->where('number', 'like', '%'.$search.'%'));
            }))
            ->latest('paid_on')
            ->paginate(15)
            ->withQueryString();

        return view('portal.payments.index', compact('payments', 'search'));
    }

    public function pdf(string $payment): Response
    {
        $user = $this->portalUser();
        $payment = $this->access()->payment($user, $payment)->load(['invoice.items', 'invoice.client', 'client']);

        $pdf = Pdf::loadView('payments.receipt-pdf', [
            'payment' => $payment,
            'logoPath' => company_logo_path(),
        ])->setPaper('a4');

        return $pdf->download('receipt-'.$payment->invoice->number.'.pdf');
    }
}
