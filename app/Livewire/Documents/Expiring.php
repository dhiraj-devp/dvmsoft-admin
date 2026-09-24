<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Expiring extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Document::class);
    }

    public function render(): View
    {
        $warning = (int) settings('documents.expiry_warning_days', 30);

        $documents = Document::query()
            ->with(['type', 'owner'])
            ->expiring($warning)
            ->orderBy('expiry_date')
            ->paginate(12, pageName: 'expiringPage');

        return view('livewire.documents.expiring', [
            'documents' => $documents,
            'warning' => $warning,
        ]);
    }
}
