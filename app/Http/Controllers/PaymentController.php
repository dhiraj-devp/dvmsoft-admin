<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Payment::class);

        return view('payments.index');
    }

    public function pdf(Payment $payment): Response
    {
        $this->authorize('view', $payment);

        $payment->load(['invoice.items', 'invoice.client', 'client']);

        $pdf = Pdf::loadView('payments.receipt-pdf', [
            'payment' => $payment,
            'logoPath' => company_logo_path(),
        ])->setPaper('a4');

        return $pdf->download('receipt-'.$payment->invoice->number.'.pdf');
    }

    public function receipt(Payment $payment): StreamedResponse
    {
        $this->authorize('view', $payment);

        abort_unless($payment->receipt_path, 404);

        return Storage::disk($payment->receipt_disk ?: 'local')->download($payment->receipt_path, 'payment-receipt');
    }
}
