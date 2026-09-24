<?php

namespace App\Livewire\AuditLogs;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $module = '';

    public string $action = '';

    public function mount(): void
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('action', 'like', '%'.$this->search.'%')
                        ->orWhere('module', 'like', '%'.$this->search.'%')
                        ->orWhere('auditable_id', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', fn ($users) => $users->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->module, fn ($query) => $query->where('module', $this->module))
            ->when($this->action, fn ($query) => $query->where('action', $this->action))
            ->latest('created_at')
            ->paginate(20);

        return view('livewire.audit-logs.index', [
            'logs' => $logs,
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
