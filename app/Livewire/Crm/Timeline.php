<?php

namespace App\Livewire\Crm;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Services\CrmActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Timeline extends Component
{
    use AuthorizesRequests;

    public string $subjectType;

    public string $subjectId;

    public string $note = '';

    public function mount(): void
    {
        $this->authorizeView($this->subject());
    }

    public function addNote(CrmActivityLogger $logger): void
    {
        $this->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $subject = $this->subject();
        $this->authorizeView($subject);
        $logger->log($subject, 'note', 'Note added', $this->note);
        $this->note = '';
        $this->dispatch('notify', type: 'success', message: 'Note added to activity history.');
    }

    public function render(): View
    {
        $subject = $this->subject();

        return view('livewire.crm.timeline', [
            'activities' => $subject->activities()->with('user')->limit(40)->get(),
        ]);
    }

    protected function subject(): Model
    {
        return $this->subjectType::query()->findOrFail($this->subjectId);
    }

    protected function authorizeView(Model $subject): void
    {
        if ($subject instanceof Lead) {
            $this->authorize('view', $subject);
        }

        if ($subject instanceof Client) {
            $this->authorize('view', $subject);
        }

        if ($subject instanceof Project) {
            $this->authorize('view', $subject);
        }
    }
}
