<?php

namespace App\Livewire\Finance;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Outstanding extends Component
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
        $this->resetPage('outstandingPage');
    }

    public function render(): View
    {
        $invoices = Invoice::query()
            ->with(['client', 'project'])
            ->outstanding()
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('client', fn ($clients) => $clients->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->orderBy('due_date')
            ->paginate(15, pageName: 'outstandingPage');

        return view('livewire.finance.outstanding', [
            'invoices' => $invoices,
            'statuses' => [
                InvoiceStatus::Sent,
                InvoiceStatus::PartiallyPaid,
                InvoiceStatus::Overdue,
            ],
        ]);
    }
}
