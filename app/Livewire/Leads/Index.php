<?php

namespace App\Livewire\Leads;

use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use App\Services\CrmActivityLogger;
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

    public string $priority = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Lead::class);
        $this->resetForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage('leadsPage');
    }

    public function create(): void
    {
        $this->authorize('create', Lead::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $lead = Lead::query()->findOrFail($id);
        $this->authorize('update', $lead);
        $this->editingId = $lead->id;
        $this->form = [
            'name' => $lead->name,
            'company' => $lead->company ?? '',
            'email' => $lead->email ?? '',
            'phone' => $lead->phone ?? '',
            'source' => $lead->source ?? '',
            'requirement' => $lead->requirement ?? '',
            'estimated_value' => $lead->estimated_value,
            'status' => $lead->status->value,
            'priority' => $lead->priority->value,
            'assigned_user_id' => $lead->assigned_user_id ?? '',
            'next_follow_up_at' => optional($lead->next_follow_up_at)?->format('Y-m-d\TH:i') ?? '',
            'notes' => $lead->notes ?? '',
            'lost_reason' => $lead->lost_reason ?? '',
        ];
        $this->showForm = true;
    }

    public function save(CrmActivityLogger $activities): void
    {
        $lead = $this->editingId ? Lead::query()->findOrFail($this->editingId) : null;
        $lead ? $this->authorize('update', $lead) : $this->authorize('create', Lead::class);

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.company' => ['nullable', 'string', 'max:255'],
            'form.email' => ['nullable', 'email', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.source' => ['nullable', Rule::in(array_keys(config('crm.sources')))],
            'form.requirement' => ['nullable', 'string', 'max:5000'],
            'form.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'form.status' => ['required', Rule::enum(LeadStatus::class)],
            'form.priority' => ['required', Rule::enum(LeadPriority::class)],
            'form.assigned_user_id' => ['nullable', 'ulid', 'exists:users,id'],
            'form.next_follow_up_at' => ['nullable', 'date'],
            'form.notes' => ['nullable', 'string', 'max:5000'],
            'form.lost_reason' => ['required_if:form.status,lost', 'nullable', 'string', 'max:255'],
        ]);

        $payload = $validated['form'];
        $payload['assigned_user_id'] = $payload['assigned_user_id'] ?: null;
        $payload['next_follow_up_at'] = $payload['next_follow_up_at'] ?: null;
        $payload['estimated_value'] = $payload['estimated_value'] === '' ? null : $payload['estimated_value'];
        $previousStatus = $lead?->status->value;

        if ($lead) {
            $lead->update($payload);
        } else {
            $lead = Lead::query()->create($payload);
            $activities->log($lead, 'created', 'Lead created', $lead->name);
        }

        if ($previousStatus && $previousStatus !== $lead->status->value) {
            $activities->log($lead, 'status', 'Status changed', $previousStatus.' → '.$lead->status->label());
        }

        $this->syncFollowUp($lead);
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Lead saved.');
    }

    #[On('confirmed-delete-lead')]
    public function delete(string $id): void
    {
        $lead = Lead::query()->findOrFail($id);
        $this->authorize('delete', $lead);
        $lead->delete();
        $this->dispatch('notify', type: 'success', message: 'Lead deleted.');
    }

    public function render(): View
    {
        $leads = Lead::query()
            ->with('assignedUser')
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('company', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->priority, fn ($query) => $query->where('priority', $this->priority))
            ->latest()
            ->paginate(12, pageName: 'leadsPage');

        return view('livewire.leads.index', [
            'leads' => $leads,
            'users' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'statuses' => LeadStatus::cases(),
            'priorities' => LeadPriority::cases(),
            'sources' => config('crm.sources'),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'company' => '',
            'email' => '',
            'phone' => '',
            'source' => '',
            'requirement' => '',
            'estimated_value' => '',
            'status' => LeadStatus::New->value,
            'priority' => LeadPriority::Medium->value,
            'assigned_user_id' => auth()->id() ?? '',
            'next_follow_up_at' => '',
            'notes' => '',
            'lost_reason' => '',
        ];
        $this->resetValidation();
    }

    protected function syncFollowUp(Lead $lead): void
    {
        if (! $lead->next_follow_up_at) {
            return;
        }

        $exists = $lead->followUps()
            ->where('status', FollowUpStatus::Pending)
            ->where('scheduled_at', $lead->next_follow_up_at)
            ->exists();

        if ($exists) {
            return;
        }

        FollowUp::query()->create([
            'followable_type' => Lead::class,
            'followable_id' => $lead->id,
            'assigned_user_id' => $lead->assigned_user_id,
            'scheduled_at' => $lead->next_follow_up_at,
            'reminder_at' => $lead->next_follow_up_at->copy()->subHour(),
            'type' => FollowUpType::Call,
            'status' => FollowUpStatus::Pending,
            'notes' => 'Scheduled from lead follow-up date.',
        ]);
    }
}
