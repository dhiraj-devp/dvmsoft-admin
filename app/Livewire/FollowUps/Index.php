<?php

namespace App\Livewire\FollowUps;

use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $type = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public ?string $subjectType = null;

    public ?string $subjectId = null;

    public bool $lockedToSubject = false;

    public array $form = [];

    public function mount(?string $subjectType = null, ?string $subjectId = null): void
    {
        $this->authorize('viewAny', FollowUp::class);
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
        $this->lockedToSubject = filled($subjectType) && filled($subjectId);
        $this->resetForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage('followUpsPage');
    }

    public function create(): void
    {
        $this->authorize('create', FollowUp::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $followUp = FollowUp::query()->findOrFail($id);
        $this->authorize('update', $followUp);
        $this->editingId = $followUp->id;
        $this->form = [
            'followable_type' => $followUp->followable_type,
            'followable_id' => $followUp->followable_id,
            'assigned_user_id' => $followUp->assigned_user_id ?? '',
            'scheduled_at' => optional($followUp->scheduled_at)?->format('Y-m-d\TH:i') ?? '',
            'type' => $followUp->type->value,
            'notes' => $followUp->notes ?? '',
            'status' => $followUp->status->value,
            'reminder_at' => optional($followUp->reminder_at)?->format('Y-m-d\TH:i') ?? '',
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $followUp = $this->editingId ? FollowUp::query()->findOrFail($this->editingId) : null;
        $followUp ? $this->authorize('update', $followUp) : $this->authorize('create', FollowUp::class);

        $validated = $this->validate([
            'form.followable_type' => ['required', Rule::in([Lead::class, Client::class])],
            'form.followable_id' => ['required', 'ulid'],
            'form.assigned_user_id' => ['nullable', 'ulid', 'exists:users,id'],
            'form.scheduled_at' => ['required', 'date'],
            'form.type' => ['required', Rule::enum(FollowUpType::class)],
            'form.notes' => ['nullable', 'string', 'max:2000'],
            'form.status' => ['required', Rule::enum(FollowUpStatus::class)],
            'form.reminder_at' => ['nullable', 'date'],
        ]);

        $type = $validated['form']['followable_type'];
        $exists = $type === Lead::class
            ? Lead::query()->whereKey($validated['form']['followable_id'])->exists()
            : Client::query()->whereKey($validated['form']['followable_id'])->exists();

        if (! $exists) {
            $this->addError('form.followable_id', 'Select a valid lead or client.');

            return;
        }

        $payload = $validated['form'];
        if ($this->lockedToSubject) {
            $payload['followable_type'] = $this->subjectType;
            $payload['followable_id'] = $this->subjectId;
        }
        $payload['assigned_user_id'] = $payload['assigned_user_id'] ?: null;
        $payload['reminder_at'] = $payload['reminder_at'] ?: $payload['scheduled_at'];
        if ($payload['status'] === FollowUpStatus::Completed->value) {
            $payload['completed_at'] = now();
        }

        if ($followUp) {
            $followUp->update($payload);
        } else {
            FollowUp::query()->create($payload);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Follow-up saved.');
    }

    public function complete(string $id): void
    {
        $followUp = FollowUp::query()->findOrFail($id);
        $this->authorize('complete', $followUp);
        $followUp->update([
            'status' => FollowUpStatus::Completed,
            'completed_at' => now(),
        ]);
        $this->dispatch('notify', type: 'success', message: 'Follow-up completed.');
    }

    #[On('confirmed-delete-follow-up')]
    public function delete(string $id): void
    {
        $followUp = FollowUp::query()->findOrFail($id);
        $this->authorize('delete', $followUp);
        $followUp->delete();
        $this->dispatch('notify', type: 'success', message: 'Follow-up deleted.');
    }

    public function render(): View
    {
        $followUps = FollowUp::query()
            ->with(['assignedUser', 'followable'])
            ->when($this->search, fn ($query) => $query->where('notes', 'like', '%'.$this->search.'%'))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->type, fn ($query) => $query->where('type', $this->type))
            ->when($this->subjectType && $this->subjectId, function ($query) {
                $query->where('followable_type', $this->subjectType)->where('followable_id', $this->subjectId);
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_at')
            ->paginate(12, pageName: 'followUpsPage');

        return view('livewire.follow-ups.index', [
            'followUps' => $followUps,
            'users' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'leads' => Lead::query()->orderBy('name')->limit(200)->get(['id', 'name', 'company']),
            'clients' => Client::query()->orderBy('name')->limit(200)->get(['id', 'name']),
            'types' => FollowUpType::cases(),
            'statuses' => FollowUpStatus::cases(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'followable_type' => $this->subjectType ?: Lead::class,
            'followable_id' => $this->subjectId ?: '',
            'assigned_user_id' => auth()->id() ?? '',
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'type' => FollowUpType::Call->value,
            'notes' => '',
            'status' => FollowUpStatus::Pending->value,
            'reminder_at' => now()->addDay()->subHour()->format('Y-m-d\TH:i'),
        ];
        $this->resetValidation();
    }
}
