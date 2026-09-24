<?php

namespace App\Livewire\Payments;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public ?string $lockedInvoiceId = null;

    public array $form = [];

    public $receipt = null;

    public function mount(?string $invoiceId = null): void
    {
        $this->authorize('viewAny', Payment::class);
        $this->lockedInvoiceId = $invoiceId ?: request('invoice_id');
        $this->resetForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage('paymentsPage');
    }

    public function create(): void
    {
        $this->authorize('create', Payment::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $payment = Payment::query()->findOrFail($id);
        $this->authorize('update', $payment);
        $this->editingId = $payment->id;
        $this->form = [
            'invoice_id' => $payment->invoice_id,
            'amount' => (float) $payment->amount,
            'paid_on' => optional($payment->paid_on)?->format('Y-m-d'),
            'method' => $payment->method->value,
            'reference' => $payment->reference ?? '',
            'notes' => $payment->notes ?? '',
        ];
        $this->receipt = null;
        $this->showForm = true;
    }

    public function save(PaymentService $payments): void
    {
        $payment = $this->editingId ? Payment::query()->findOrFail($this->editingId) : null;
        $payment ? $this->authorize('update', $payment) : $this->authorize('create', Payment::class);

        $this->validate([
            'form.invoice_id' => ['required', 'ulid', 'exists:invoices,id'],
            'form.amount' => ['required', 'numeric', 'min:0.01'],
            'form.paid_on' => ['required', 'date'],
            'form.method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'form.reference' => ['nullable', 'string', 'max:100'],
            'form.notes' => ['nullable', 'string', 'max:2000'],
            'receipt' => ['nullable', 'file', 'max:10240'],
        ]);

        $invoice = Invoice::query()->findOrFail($this->form['invoice_id']);
        $file = $this->receipt instanceof TemporaryUploadedFile ? $this->receipt : null;

        if ($payment) {
            $payments->update($payment, $this->form, $file);
            $message = 'Payment updated.';
        } else {
            $payments->record($invoice, $this->form, request()->user(), $file);
            $message = 'Payment recorded.';
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: $message);
    }

    #[On('confirmed-delete-payment')]
    public function delete(string $id, PaymentService $payments): void
    {
        $payment = Payment::query()->findOrFail($id);
        $this->authorize('delete', $payment);
        $payments->delete($payment);
        $this->dispatch('notify', type: 'success', message: 'Payment deleted.');
    }

    public function render(): View
    {
        $payments = Payment::query()
            ->with(['invoice', 'client'])
            ->when($this->lockedInvoiceId, fn ($query) => $query->where('invoice_id', $this->lockedInvoiceId))
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('reference', 'like', '%'.$this->search.'%')
                        ->orWhereHas('invoice', fn ($invoices) => $invoices->where('number', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('client', fn ($clients) => $clients->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->latest('paid_on')
            ->paginate(12, pageName: 'paymentsPage');

        $invoiceOptions = Invoice::query()
            ->with('client')
            ->where(function ($query) {
                $query->where(function ($open) {
                    $open->whereIn('status', [
                        \App\Enums\InvoiceStatus::Sent->value,
                        \App\Enums\InvoiceStatus::PartiallyPaid->value,
                        \App\Enums\InvoiceStatus::Overdue->value,
                    ])->where('balance', '>', 0);
                });
                if ($this->form['invoice_id'] ?? false) {
                    $query->orWhere('id', $this->form['invoice_id']);
                }
            })
            ->orderByDesc('invoice_date')
            ->get();

        return view('livewire.payments.index', [
            'payments' => $payments,
            'invoiceOptions' => $invoiceOptions,
            'methods' => PaymentMethod::cases(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'invoice_id' => $this->lockedInvoiceId ?? '',
            'amount' => '',
            'paid_on' => now()->toDateString(),
            'method' => PaymentMethod::BankTransfer->value,
            'reference' => '',
            'notes' => '',
        ];
        $this->receipt = null;
    }
}
