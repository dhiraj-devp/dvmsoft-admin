<?php

namespace App\Livewire\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Invoice::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage('invoicesPage');
    }

    #[On('confirmed-delete-invoice')]
    public function delete(string $id): void
    {
        $invoice = Invoice::query()->findOrFail($id);
        $this->authorize('delete', $invoice);
        $invoice->delete();
        $this->dispatch('notify', type: 'success', message: 'Invoice deleted.');
    }

    public function render(): View
    {
        $invoices = Invoice::query()
            ->with(['client', 'project'])
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('number', 'like', '%'.$this->search.'%')
                        ->orWhere('title', 'like', '%'.$this->search.'%')
                        ->orWhereHas('client', fn ($clients) => $clients->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest('invoice_date')
            ->paginate(12, pageName: 'invoicesPage');

        return view('livewire.invoices.index', [
            'invoices' => $invoices,
            'statuses' => InvoiceStatus::cases(),
        ]);
    }
}
