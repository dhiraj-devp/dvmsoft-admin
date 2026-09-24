<?php

namespace App\Livewire\TicketCategories;

use App\Models\TicketCategory;
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

    public ?string $categoryId = null;

    public array $form = [];

    public function mount(): void
    {
        $this->authorize('viewAny', TicketCategory::class);
        $this->resetForm();
    }

    public function create(): void
    {
        $this->authorize('create', TicketCategory::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $category = TicketCategory::query()->findOrFail($id);
        $this->authorize('update', $category);
        $this->categoryId = $category->id;
        $this->form = [
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description ?? '',
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $category = $this->categoryId ? TicketCategory::query()->findOrFail($this->categoryId) : null;
        $category ? $this->authorize('update', $category) : $this->authorize('create', TicketCategory::class);

        $this->validate([
            'form.name' => ['required', 'string', 'max:120'],
            'form.slug' => ['nullable', 'string', 'max:120', Rule::unique('ticket_categories', 'slug')->ignore($this->categoryId)],
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

        if ($category) {
            $category->update($payload);
        } else {
            TicketCategory::query()->create($payload);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Category saved.');
    }

    #[On('confirmed-delete-ticket-category')]
    public function delete(string $id): void
    {
        $category = TicketCategory::query()->findOrFail($id);
        $this->authorize('delete', $category);
        $category->delete();
        $this->dispatch('notify', type: 'success', message: 'Category removed.');
    }

    public function render(): View
    {
        return view('livewire.ticket-categories.index', [
            'categories' => TicketCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->categoryId = null;
        $this->form = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
