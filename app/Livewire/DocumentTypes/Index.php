<?php

namespace App\Livewire\DocumentTypes;

use App\Models\DocumentType;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public bool $showForm = false;

    public ?string $typeId = null;

    public array $form = [];

    public function mount(): void
    {
        $this->authorize('viewAny', DocumentType::class);
        $this->resetForm();
    }

    public function create(): void
    {
        $this->authorize('create', DocumentType::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $type = DocumentType::query()->findOrFail($id);
        $this->authorize('update', $type);
        $this->typeId = $type->id;
        $this->form = [
            'name' => $type->name,
            'slug' => $type->slug,
            'description' => $type->description ?? '',
            'is_active' => $type->is_active,
            'sort_order' => $type->sort_order,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $type = $this->typeId ? DocumentType::query()->findOrFail($this->typeId) : null;
        $type ? $this->authorize('update', $type) : $this->authorize('create', DocumentType::class);

        $this->validate([
            'form.name' => ['required', 'string', 'max:120'],
            'form.slug' => ['nullable', 'string', 'max:120', Rule::unique('company_document_types', 'slug')->ignore($this->typeId)],
            'form.description' => ['nullable', 'string', 'max:1000'],
            'form.is_active' => ['boolean'],
            'form.sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $payload = [
            'name' => $this->form['name'],
            'slug' => $this->form['slug'] ?: Str::slug($this->form['name']),
            'description' => $this->form['description'] ?: null,
            'is_active' => (bool) $this->form['is_active'],
            'sort_order' => (int) $this->form['sort_order'],
        ];

        if ($type) {
            $type->update($payload);
        } else {
            DocumentType::query()->create($payload);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Document type saved.');
    }

    #[On('confirmed-delete-document-type')]
    public function delete(string $id): void
    {
        $type = DocumentType::query()->findOrFail($id);
        $this->authorize('delete', $type);

        if ($type->documents()->exists()) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete a type that is in use.');

            return;
        }

        $type->delete();
        $this->dispatch('notify', type: 'success', message: 'Document type removed.');
    }

    public function render(): View
    {
        return view('livewire.document-types.index', [
            'types' => DocumentType::query()->withCount('documents')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->typeId = null;
        $this->form = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
