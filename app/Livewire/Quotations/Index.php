<?php

namespace App\Livewire\Quotations;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
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
        $this->authorize('viewAny', Quotation::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage('quotationsPage');
    }

    #[On('confirmed-delete-quotation')]
    public function delete(string $id): void
    {
        $quotation = Quotation::query()->findOrFail($id);
        $this->authorize('delete', $quotation);
        $quotation->delete();
        $this->dispatch('notify', type: 'success', message: 'Quotation deleted.');
    }

    public function render(): View
    {
        $quotations = Quotation::query()
            ->with('client')
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('number', 'like', '%'.$this->search.'%')
                        ->orWhere('title', 'like', '%'.$this->search.'%')
                        ->orWhereHas('client', fn ($clients) => $clients->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(12, pageName: 'quotationsPage');

        return view('livewire.quotations.index', [
            'quotations' => $quotations,
            'statuses' => QuotationStatus::cases(),
        ]);
    }
}
