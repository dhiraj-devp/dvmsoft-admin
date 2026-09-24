<?php

namespace App\Livewire\Invoices;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quotation;
use App\Services\QuotationCalculator;
use App\Services\SequentialNumberGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public ?string $invoiceId = null;

    public array $form = [];

    public array $items = [];

    public array $totals = [
        'subtotal' => 0,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => 0,
    ];

    public function mount(?Invoice $invoice = null): void
    {
        if ($invoice?->exists) {
            $this->authorize('update', $invoice);
            $this->invoiceId = $invoice->id;
            $invoice->load('items');
            $this->form = [
                'client_id' => $invoice->client_id,
                'project_id' => $invoice->project_id ?? '',
                'quotation_id' => $invoice->quotation_id ?? '',
                'title' => $invoice->title,
                'invoice_date' => optional($invoice->invoice_date)?->format('Y-m-d') ?? now()->toDateString(),
                'due_date' => optional($invoice->due_date)?->format('Y-m-d') ?? '',
                'discount_percent' => (float) $invoice->discount_percent,
                'tax_percent' => (float) $invoice->tax_percent,
                'payment_terms' => $invoice->payment_terms ?? 'net_15',
                'notes' => $invoice->notes ?? '',
            ];
            $this->items = $invoice->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_percent' => (float) $item->discount_percent,
                'tax_percent' => (float) $item->tax_percent,
            ])->all();
        } else {
            $this->authorize('create', Invoice::class);
            $dueDays = (int) settings('finance.invoice_due_days', 15);
            $this->form = [
                'client_id' => request('client_id', ''),
                'project_id' => request('project_id', ''),
                'quotation_id' => request('quotation_id', ''),
                'title' => '',
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays($dueDays)->format('Y-m-d'),
                'discount_percent' => 0,
                'tax_percent' => (float) settings('finance.default_tax_percent', 18),
                'payment_terms' => 'net_15',
                'notes' => '',
            ];
            $this->items = [$this->blankItem()];
            $this->prefillFromProject();
            $this->prefillFromQuotation();
        }

        $this->recalculate();
    }

    public function updatedFormClientId(): void
    {
        $this->form['project_id'] = '';
        $this->form['quotation_id'] = '';
    }

    public function updatedFormProjectId(): void
    {
        $this->prefillFromProject();
        $this->recalculate();
    }

    public function updatedFormQuotationId(): void
    {
        $this->prefillFromQuotation();
        $this->recalculate();
    }

    public function updatedForm(): void
    {
        $this->recalculate();
    }

    public function updatedItems(): void
    {
        $this->recalculate();
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
        $this->recalculate();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        if ($this->items === []) {
            $this->items[] = $this->blankItem();
        }
        $this->recalculate();
    }

    public function save(QuotationCalculator $calculator, SequentialNumberGenerator $numbers): mixed
    {
        $invoice = $this->invoiceId ? Invoice::query()->findOrFail($this->invoiceId) : null;
        $invoice ? $this->authorize('update', $invoice) : $this->authorize('create', Invoice::class);

        $this->validate([
            'form.client_id' => ['required', 'ulid', 'exists:clients,id'],
            'form.project_id' => ['nullable', 'ulid', 'exists:projects,id'],
            'form.quotation_id' => ['nullable', 'ulid', 'exists:quotations,id'],
            'form.title' => ['required', 'string', 'max:255'],
            'form.invoice_date' => ['required', 'date'],
            'form.due_date' => ['nullable', 'date', 'after_or_equal:form.invoice_date'],
            'form.discount_percent' => ['numeric', 'min:0', 'max:100'],
            'form.tax_percent' => ['numeric', 'min:0', 'max:100'],
            'form.payment_terms' => ['required', Rule::in(array_keys(config('crm.payment_terms')))],
            'form.notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['numeric', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['numeric', 'min:0', 'max:100'],
        ]);

        $this->assertLinkedRecords();

        $totals = $calculator->calculate($this->items, (float) $this->form['discount_percent']);

        $invoice = DB::transaction(function () use ($invoice, $totals, $numbers) {
            $payload = [
                'client_id' => $this->form['client_id'],
                'project_id' => $this->form['project_id'] ?: null,
                'quotation_id' => $this->form['quotation_id'] ?: null,
                'title' => $this->form['title'],
                'invoice_date' => $this->form['invoice_date'],
                'due_date' => $this->form['due_date'] ?: null,
                'discount_percent' => $this->form['discount_percent'],
                'tax_percent' => $this->form['tax_percent'],
                'payment_terms' => $this->form['payment_terms'],
                'notes' => $this->form['notes'] ?: null,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'balance' => $totals['total'],
                'amount_paid' => $invoice?->amount_paid ?? 0,
            ];

            if ($invoice) {
                $payload['balance'] = round(max(0, $totals['total'] - (float) $invoice->amount_paid), 2);
                $invoice->update($payload);
            } else {
                $payload['number'] = $numbers->nextInvoice();
                $payload['created_by_id'] = auth()->id();
                $invoice = Invoice::query()->create($payload);
            }

            $invoice->items()->delete();
            foreach ($totals['items'] as $item) {
                $invoice->items()->create($item);
            }

            return $invoice;
        });

        session()->flash('status', 'Invoice saved.');

        return redirect()->route('invoices.show', $invoice);
    }

    public function render(): View
    {
        $clientId = $this->form['client_id'] ?: null;

        return view('livewire.invoices.form', [
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()
                ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
                ->orderBy('name')
                ->get(['id', 'name', 'number', 'client_id', 'quotation_id']),
            'quotations' => Quotation::query()
                ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
                ->orderByDesc('created_at')
                ->limit(200)
                ->get(['id', 'number', 'title', 'client_id']),
            'paymentTerms' => config('crm.payment_terms'),
        ]);
    }

    protected function recalculate(): void
    {
        $totals = app(QuotationCalculator::class)->calculate($this->items, (float) ($this->form['discount_percent'] ?? 0));
        $this->totals = [
            'subtotal' => $totals['subtotal'],
            'discount_amount' => $totals['discount_amount'],
            'tax_amount' => $totals['tax_amount'],
            'total' => $totals['total'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function blankItem(): array
    {
        return [
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount_percent' => 0,
            'tax_percent' => (float) ($this->form['tax_percent'] ?? settings('finance.default_tax_percent', 18)),
        ];
    }

    protected function prefillFromProject(): void
    {
        if (! $this->form['project_id']) {
            return;
        }

        $project = Project::query()->with('quotation.items')->find($this->form['project_id']);
        if (! $project) {
            return;
        }

        $this->form['client_id'] = $project->client_id;
        if ($project->quotation_id) {
            $this->form['quotation_id'] = $project->quotation_id;
        }
        if ($this->form['title'] === '') {
            $this->form['title'] = $project->name;
        }

        if ($this->invoiceId === null && $project->quotation?->items->isNotEmpty() && $this->itemsAreBlank()) {
            $this->copyQuotationItems($project->quotation);
        }
    }

    protected function prefillFromQuotation(): void
    {
        if (! $this->form['quotation_id']) {
            return;
        }

        $quotation = Quotation::query()->with('items')->find($this->form['quotation_id']);
        if (! $quotation) {
            return;
        }

        $this->form['client_id'] = $quotation->client_id;
        if ($this->form['title'] === '') {
            $this->form['title'] = $quotation->title;
        }
        if (! $this->form['payment_terms']) {
            $this->form['payment_terms'] = $quotation->payment_terms ?? 'net_15';
        }

        if ($this->invoiceId === null && $quotation->items->isNotEmpty() && $this->itemsAreBlank()) {
            $this->copyQuotationItems($quotation);
        }
    }

    protected function copyQuotationItems(Quotation $quotation): void
    {
        $this->form['discount_percent'] = (float) $quotation->discount_percent;
        $this->items = $quotation->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'discount_percent' => (float) $item->discount_percent,
            'tax_percent' => (float) $item->tax_percent,
        ])->all();
    }

    protected function itemsAreBlank(): bool
    {
        if (count($this->items) !== 1) {
            return false;
        }

        $item = $this->items[0];

        return trim((string) ($item['description'] ?? '')) === '' && (float) ($item['unit_price'] ?? 0) === 0.0;
    }

    protected function assertLinkedRecords(): void
    {
        $errors = [];

        if ($this->form['project_id']) {
            $project = Project::query()->findOrFail($this->form['project_id']);
            if ($project->client_id !== $this->form['client_id']) {
                $errors['form.project_id'] = 'The project must belong to the selected client.';
            }
        }

        if ($this->form['quotation_id']) {
            $quotation = Quotation::query()->findOrFail($this->form['quotation_id']);
            if ($quotation->client_id !== $this->form['client_id']) {
                $errors['form.quotation_id'] = 'The quotation must belong to the selected client.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
