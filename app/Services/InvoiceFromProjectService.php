<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvoiceFromProjectService
{
    public function __construct(
        protected SequentialNumberGenerator $numbers,
        protected QuotationCalculator $calculator,
    ) {}

    public function create(Project $project, User $actor): Invoice
    {
        return DB::transaction(function () use ($project, $actor) {
            $project->loadMissing('quotation.items');

            $items = [];

            if ($project->quotation?->items()->exists()) {
                $items = $project->quotation->items->map(fn ($item) => [
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount_percent' => (float) $item->discount_percent,
                    'tax_percent' => (float) $item->tax_percent,
                ])->all();
                $headerDiscount = (float) $project->quotation->discount_percent;
            } else {
                $items = [[
                    'description' => $project->name,
                    'quantity' => 1,
                    'unit_price' => (float) ($project->budget ?? 0),
                    'discount_percent' => 0,
                    'tax_percent' => (float) settings('finance.default_tax_percent', 18),
                ]];
                $headerDiscount = 0.0;
            }

            $totals = $this->calculator->calculate($items, $headerDiscount);
            $dueDays = (int) settings('finance.invoice_due_days', 15);

            $invoice = Invoice::query()->create([
                'number' => $this->numbers->nextInvoice(),
                'client_id' => $project->client_id,
                'project_id' => $project->id,
                'quotation_id' => $project->quotation_id,
                'created_by_id' => $actor->id,
                'title' => $project->name,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays($dueDays)->toDateString(),
                'status' => InvoiceStatus::Draft,
                'subtotal' => $totals['subtotal'],
                'discount_percent' => $headerDiscount,
                'discount_amount' => $totals['discount_amount'],
                'tax_percent' => (float) settings('finance.default_tax_percent', 18),
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'amount_paid' => 0,
                'balance' => $totals['total'],
                'payment_terms' => $project->quotation?->payment_terms ?? 'net_15',
                'notes' => 'Created from project '.$project->number.'.',
            ]);

            foreach ($totals['items'] as $item) {
                $invoice->items()->create($item);
            }

            return $invoice->fresh(['items', 'client', 'project']);
        });
    }
}
