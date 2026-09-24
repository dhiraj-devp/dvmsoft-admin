<?php

namespace App\Livewire\Automations;

use App\Models\Automation;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public Automation $automation;

    public bool $inApp = true;

    public bool $email = true;

    public function mount(Automation $automation): void
    {
        $this->authorize('view', $automation);
        $this->automation = $automation;
        $channels = $automation->channels ?? ['database', 'mail'];
        $this->inApp = in_array('database', $channels, true);
        $this->email = in_array('mail', $channels, true);
    }

    public function toggle(): void
    {
        $this->authorize('update', $this->automation);
        $this->automation->update(['enabled' => ! $this->automation->enabled]);
        $this->automation->refresh();
        $this->dispatch('notify', type: 'success', message: $this->automation->enabled ? 'Automation enabled.' : 'Automation disabled.');
    }

    public function saveChannels(): void
    {
        $this->authorize('update', $this->automation);

        $channels = [];

        if ($this->inApp) {
            $channels[] = 'database';
        }

        if ($this->email) {
            $channels[] = 'mail';
        }

        $this->automation->update(['channels' => $channels === [] ? ['database'] : $channels]);
        $this->dispatch('notify', type: 'success', message: 'Channels saved.');
    }

    public function render(): View
    {
        $runs = null;

        if (auth()->user()?->can('viewHistory', $this->automation)) {
            $runs = $this->automation->runs()->paginate(15, pageName: 'runsPage');
        }

        return view('livewire.automations.show', [
            'runs' => $runs,
        ]);
    }
}
