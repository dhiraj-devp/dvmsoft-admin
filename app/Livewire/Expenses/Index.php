<?php

namespace App\Livewire\Expenses;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\Project;
use App\Services\SequentialNumberGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [];

    public $receipt = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Expense::class);
        $this->resetForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage('expensesPage');
    }

    public function create(): void
    {
        $this->authorize('create', Expense::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $expense = Expense::query()->findOrFail($id);
        $this->authorize('update', $expense);
        $this->editingId = $expense->id;
        $this->form = [
            'expense_date' => optional($expense->expense_date)?->format('Y-m-d'),
            'category' => $expense->category,
            'vendor' => $expense->vendor,
            'amount' => (float) $expense->amount,
            'tax_amount' => (float) $expense->tax_amount,
            'payment_method' => $expense->payment_method?->value ?? PaymentMethod::BankTransfer->value,
            'project_id' => $expense->project_id ?? '',
            'status' => $expense->status->value,
            'notes' => $expense->notes ?? '',
        ];
        $this->receipt = null;
        $this->showForm = true;
    }

    public function save(SequentialNumberGenerator $numbers): void
    {
        $expense = $this->editingId ? Expense::query()->findOrFail($this->editingId) : null;
        $expense ? $this->authorize('update', $expense) : $this->authorize('create', Expense::class);

        $this->validate([
            'form.expense_date' => ['required', 'date'],
            'form.category' => ['required', Rule::in(array_keys(config('finance.expense_categories')))],
            'form.vendor' => ['required', 'string', 'max:255'],
            'form.amount' => ['required', 'numeric', 'min:0.01'],
            'form.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'form.payment_method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'form.project_id' => ['nullable', 'ulid', 'exists:projects,id'],
            'form.status' => ['required', Rule::in(array_column(ExpenseStatus::cases(), 'value'))],
            'form.notes' => ['nullable', 'string', 'max:2000'],
            'receipt' => ['nullable', 'file', 'max:10240'],
        ]);

        $payload = [
            'expense_date' => $this->form['expense_date'],
            'category' => $this->form['category'],
            'vendor' => $this->form['vendor'],
            'amount' => $this->form['amount'],
            'tax_amount' => $this->form['tax_amount'] ?: 0,
            'payment_method' => $this->form['payment_method'],
            'project_id' => $this->form['project_id'] ?: null,
            'status' => $this->form['status'],
            'notes' => $this->form['notes'] ?: null,
            'receipt_disk' => 'local',
        ];

        if ($expense) {
            $expense->update($payload);
        } else {
            $payload['number'] = $numbers->nextExpense();
            $payload['recorded_by_id'] = auth()->id();
            $expense = Expense::query()->create($payload);
        }

        if ($this->receipt instanceof TemporaryUploadedFile) {
            if ($expense->receipt_path) {
                Storage::disk($expense->receipt_disk ?: 'local')->delete($expense->receipt_path);
            }
            $path = $this->receipt->store('expenses/'.$expense->id, 'local');
            $expense->forceFill([
                'receipt_path' => $path,
                'receipt_disk' => 'local',
            ])->save();
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Expense saved.');
    }

    #[On('confirmed-delete-expense')]
    public function delete(string $id): void
    {
        $expense = Expense::query()->findOrFail($id);
        $this->authorize('delete', $expense);
        if ($expense->receipt_path) {
            Storage::disk($expense->receipt_disk ?: 'local')->delete($expense->receipt_path);
        }
        $expense->delete();
        $this->dispatch('notify', type: 'success', message: 'Expense deleted.');
    }

    public function render(): View
    {
        $expenses = Expense::query()
            ->with('project')
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('number', 'like', '%'.$this->search.'%')
                        ->orWhere('vendor', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest('expense_date')
            ->paginate(12, pageName: 'expensesPage');

        return view('livewire.expenses.index', [
            'expenses' => $expenses,
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'number']),
            'categories' => config('finance.expense_categories'),
            'methods' => PaymentMethod::cases(),
            'statuses' => ExpenseStatus::cases(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'expense_date' => now()->toDateString(),
            'category' => 'software',
            'vendor' => '',
            'amount' => '',
            'tax_amount' => 0,
            'payment_method' => PaymentMethod::BankTransfer->value,
            'project_id' => '',
            'status' => ExpenseStatus::Approved->value,
            'notes' => '',
        ];
        $this->receipt = null;
    }
}
