<?php

namespace App\Livewire\Projects;

use App\Enums\ClientApprovalMode;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\QuotationStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Services\CrmActivityLogger;
use App\Services\SequentialNumberGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public ?string $projectId = null;

    public array $form = [];

    public function mount(?Project $project = null): void
    {
        if ($project?->exists) {
            $this->authorize('update', $project);
            $this->projectId = $project->id;
            $this->form = [
                'client_id' => $project->client_id,
                'quotation_id' => $project->quotation_id ?? '',
                'manager_id' => $project->manager_id ?? '',
                'name' => $project->name,
                'description' => $project->description ?? '',
                'start_date' => optional($project->start_date)?->format('Y-m-d') ?? '',
                'expected_end_date' => optional($project->expected_end_date)?->format('Y-m-d') ?? '',
                'actual_completion_date' => optional($project->actual_completion_date)?->format('Y-m-d') ?? '',
                'budget' => $project->budget,
                'status' => $project->status->value,
                'health' => $project->health->value,
                'priority' => $project->priority->value,
                'client_approval_mode' => $project->client_approval_mode->value,
                'notes' => $project->notes ?? '',
            ];
        } else {
            $this->authorize('create', Project::class);
            $this->form = [
                'client_id' => request('client_id', ''),
                'quotation_id' => request('quotation_id', ''),
                'manager_id' => auth()->id() ?? '',
                'name' => '',
                'description' => '',
                'start_date' => now()->format('Y-m-d'),
                'expected_end_date' => now()->addMonth()->format('Y-m-d'),
                'actual_completion_date' => '',
                'budget' => '',
                'status' => ProjectStatus::Planning->value,
                'health' => ProjectHealth::Green->value,
                'priority' => ProjectPriority::Medium->value,
                'client_approval_mode' => (string) settings('projects.default_client_approval_mode', ClientApprovalMode::Flexible->value),
                'notes' => '',
            ];
        }
    }

    public function save(SequentialNumberGenerator $numbers, CrmActivityLogger $activities): mixed
    {
        $project = $this->projectId ? Project::query()->findOrFail($this->projectId) : null;
        $project ? $this->authorize('update', $project) : $this->authorize('create', Project::class);

        $validated = $this->validate([
            'form.client_id' => ['required', 'ulid', 'exists:clients,id'],
            'form.quotation_id' => ['nullable', 'ulid', 'exists:quotations,id'],
            'form.manager_id' => ['nullable', 'ulid', 'exists:users,id'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string', 'max:8000'],
            'form.start_date' => ['nullable', 'date'],
            'form.expected_end_date' => ['nullable', 'date'],
            'form.actual_completion_date' => ['nullable', 'date'],
            'form.budget' => ['nullable', 'numeric', 'min:0'],
            'form.status' => ['required', Rule::enum(ProjectStatus::class)],
            'form.health' => ['required', Rule::enum(ProjectHealth::class)],
            'form.priority' => ['required', Rule::enum(ProjectPriority::class)],
            'form.client_approval_mode' => ['required', Rule::enum(ClientApprovalMode::class)],
            'form.notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $payload = $validated['form'];
        $payload['quotation_id'] = $payload['quotation_id'] ?: null;
        $payload['manager_id'] = $payload['manager_id'] ?: null;
        $payload['budget'] = $payload['budget'] === '' ? null : $payload['budget'];
        $payload['start_date'] = $payload['start_date'] ?: null;
        $payload['expected_end_date'] = $payload['expected_end_date'] ?: null;
        $payload['actual_completion_date'] = $payload['actual_completion_date'] ?: null;

        if ($payload['status'] === ProjectStatus::Completed->value && blank($payload['actual_completion_date'])) {
            $payload['actual_completion_date'] = now()->toDateString();
        }

        if ($payload['quotation_id']) {
            $taken = Project::query()
                ->where('quotation_id', $payload['quotation_id'])
                ->when($project, fn ($query) => $query->where('id', '!=', $project->id))
                ->exists();

            if ($taken) {
                $this->addError('form.quotation_id', 'That quotation is already linked to a project.');

                return null;
            }
        }

        if ($project) {
            $previous = $project->status->value;
            $project->update($payload);
            if ($previous !== $project->status->value) {
                $activities->log($project, 'status', 'Status changed', $previous.' → '.$project->status->label());
            }
        } else {
            $payload['number'] = $numbers->nextProject();
            $project = Project::query()->create($payload);
            if ($project->manager_id) {
                $project->members()->firstOrCreate(
                    ['user_id' => $project->manager_id],
                    ['role' => 'project_manager'],
                );
            }
            $activities->log($project, 'created', 'Project created', $project->name);
        }

        session()->flash('status', 'Project saved.');

        return redirect()->route('projects.show', $project);
    }

    public function render(): View
    {
        return view('livewire.projects.form', [
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'quotations' => Quotation::query()
                ->where('status', QuotationStatus::Converted)
                ->when($this->form['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
                ->orderByDesc('created_at')
                ->limit(100)
                ->get(['id', 'number', 'title', 'client_id']),
            'statuses' => ProjectStatus::cases(),
            'healths' => ProjectHealth::cases(),
            'priorities' => ProjectPriority::cases(),
            'approvalModes' => ClientApprovalMode::cases(),
        ]);
    }
}
