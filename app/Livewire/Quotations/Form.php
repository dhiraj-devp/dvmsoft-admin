<?php

namespace App\Livewire\Quotations;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Quotation;
use App\Services\QuotationCalculator;
use App\Services\QuotationNumberGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public ?string $quotationId = null;

    public array $form = [];

    public array $items = [];

    public array $totals = [
        'subtotal' => 0,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => 0,
    ];

    public function mount(?Quotation $quotation = null): void
    {
        if ($quotation?->exists) {
            $this->authorize('update', $quotation);
            $this->quotationId = $quotation->id;
            $quotation->load('items');
            $this->form = [
                'client_id' => $quotation->client_id,
                'lead_id' => $quotation->lead_id ?? '',
                'title' => $quotation->title,
                'discount_percent' => (float) $quotation->discount_percent,
                'tax_percent' => (float) $quotation->tax_percent,
                'payment_terms' => $quotation->payment_terms ?? 'net_15',
                'valid_until' => optional($quotation->valid_until)?->format('Y-m-d') ?? '',
                'notes' => $quotation->notes ?? '',
            ];
            $this->items = $quotation->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_percent' => (float) $item->discount_percent,
                'tax_percent' => (float) $item->tax_percent,
            ])->all();
        } else {
            $this->authorize('create', Quotation::class);
            $this->form = [
                'client_id' => request('client_id', ''),
                'lead_id' => request('lead_id', ''),
                'title' => '',
                'discount_percent' => 0,
                'tax_percent' => (float) config('crm.default_tax_percent', 18),
                'payment_terms' => 'net_15',
                'valid_until' => now()->addDays((int) config('crm.default_validity_days', 15))->format('Y-m-d'),
                'notes' => '',
            ];
            $this->items = [$this->blankItem()];
        }

        $this->recalculate();
    }

    #[On('ai-apply-quotation')]
    public function applyAiSuggestions(string $title = '', string $paymentTermsWording = '', array $descriptions = []): void
    {
        if ($title !== '') {
            $this->form['title'] = $title;
        }

        if ($paymentTermsWording !== '') {
            $notes = trim((string) ($this->form['notes'] ?? ''));
            $this->form['notes'] = $notes === ''
                ? $paymentTermsWording
                : $notes."\n\n".$paymentTermsWording;
        }

        foreach ($descriptions as $index => $description) {
            if (! is_string($description) || $description === '') {
                continue;
            }

            if (! isset($this->items[$index])) {
                continue;
            }

            $this->items[$index]['description'] = $description;
        }
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

    public function save(QuotationCalculator $calculator, QuotationNumberGenerator $numbers): mixed
    {
        $quotation = $this->quotationId ? Quotation::query()->findOrFail($this->quotationId) : null;
        $quotation ? $this->authorize('update', $quotation) : $this->authorize('create', Quotation::class);

        $this->validate([
            'form.client_id' => ['required', 'ulid', 'exists:clients,id'],
            'form.lead_id' => ['nullable', 'ulid', 'exists:leads,id'],
            'form.title' => ['required', 'string', 'max:255'],
            'form.discount_percent' => ['numeric', 'min:0', 'max:100'],
            'form.tax_percent' => ['numeric', 'min:0', 'max:100'],
            'form.payment_terms' => ['required', Rule::in(array_keys(config('crm.payment_terms')))],
            'form.valid_until' => ['nullable', 'date'],
            'form.notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['numeric', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['numeric', 'min:0', 'max:100'],
        ]);

        $totals = $calculator->calculate($this->items, (float) $this->form['discount_percent']);

        $quotation = DB::transaction(function () use ($quotation, $totals, $numbers) {
            $payload = [
                'client_id' => $this->form['client_id'],
                'lead_id' => $this->form['lead_id'] ?: null,
                'title' => $this->form['title'],
                'discount_percent' => $this->form['discount_percent'],
                'tax_percent' => $this->form['tax_percent'],
                'payment_terms' => $this->form['payment_terms'],
                'valid_until' => $this->form['valid_until'] ?: null,
                'notes' => $this->form['notes'] ?: null,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
            ];

            if ($quotation) {
                $quotation->update($payload);
            } else {
                $payload['number'] = $numbers->next();
                $payload['created_by_id'] = auth()->id();
                $quotation = Quotation::query()->create($payload);
            }

            $quotation->items()->delete();
            foreach ($totals['items'] as $item) {
                $quotation->items()->create($item);
            }

            return $quotation;
        });

        session()->flash('status', 'Quotation saved.');

        return redirect()->route('quotations.show', $quotation);
    }

    public function render(): View
    {
        return view('livewire.quotations.form', [
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'leads' => Lead::query()->orderBy('name')->limit(200)->get(['id', 'name', 'company']),
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
            'tax_percent' => (float) ($this->form['tax_percent'] ?? config('crm.default_tax_percent', 18)),
        ];
    }
}
