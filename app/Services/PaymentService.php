<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(protected InvoiceBalanceService $balances) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function record(Invoice $invoice, array $attributes, User $actor, ?UploadedFile $receipt = null): Payment
    {
        $this->assertAcceptsPayment($invoice, (float) $attributes['amount']);

        return DB::transaction(function () use ($invoice, $attributes, $actor, $receipt) {
            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'recorded_by_id' => $actor->id,
                'amount' => $attributes['amount'],
                'paid_on' => $attributes['paid_on'],
                'method' => $attributes['method'],
                'reference' => $attributes['reference'] ?: null,
                'notes' => $attributes['notes'] ?: null,
                'receipt_disk' => 'local',
            ]);

            if ($receipt) {
                $this->storeReceipt($payment, $receipt);
            }

            $this->balances->refresh($invoice->fresh());

            return $payment->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Payment $payment, array $attributes, ?UploadedFile $receipt = null): Payment
    {
        $invoice = $payment->invoice()->firstOrFail();
        $otherPaid = (float) $invoice->payments()->whereKeyNot($payment->id)->sum('amount');
        $remaining = round((float) $invoice->total - $otherPaid, 2);

        if ((float) $attributes['amount'] - $remaining > 0.009) {
            throw ValidationException::withMessages([
                'amount' => 'Payment cannot exceed the remaining invoice balance of '.money($remaining).'.',
            ]);
        }

        return DB::transaction(function () use ($payment, $invoice, $attributes, $receipt) {
            $payment->update([
                'amount' => $attributes['amount'],
                'paid_on' => $attributes['paid_on'],
                'method' => $attributes['method'],
                'reference' => $attributes['reference'] ?: null,
                'notes' => $attributes['notes'] ?: null,
            ]);

            if ($receipt) {
                $this->storeReceipt($payment, $receipt);
            }

            $this->balances->refresh($invoice->fresh());

            return $payment->fresh();
        });
    }

    public function delete(Payment $payment): void
    {
        $invoice = $payment->invoice()->firstOrFail();

        DB::transaction(function () use ($payment, $invoice): void {
            if ($payment->receipt_path) {
                Storage::disk($payment->receipt_disk ?: 'local')->delete($payment->receipt_path);
            }

            $payment->delete();
            $this->balances->refresh($invoice->fresh());
        });
    }

    protected function assertAcceptsPayment(Invoice $invoice, float $amount): void
    {
        if (! $invoice->status->acceptsPayments()) {
            throw ValidationException::withMessages([
                'invoice_id' => 'Payments can only be recorded on sent, partially paid, or overdue invoices.',
            ]);
        }

        $remaining = round((float) $invoice->balance, 2);

        if ($amount - $remaining > 0.009) {
            throw ValidationException::withMessages([
                'amount' => 'Payment cannot exceed the remaining invoice balance of '.money($remaining).'.',
            ]);
        }
    }

    protected function storeReceipt(Payment $payment, UploadedFile $receipt): void
    {
        if ($payment->receipt_path) {
            Storage::disk($payment->receipt_disk ?: 'local')->delete($payment->receipt_path);
        }

        $path = $receipt->store('payments/'.$payment->id, 'local');

        $payment->forceFill([
            'receipt_path' => $path,
            'receipt_disk' => 'local',
        ])->save();
    }
}
