<?php

namespace App\Livewire\EmployeeDocuments;

use App\Enums\EmployeeDocumentStatus;
use App\Enums\EmployeeDocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Index extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public string $employeeId = '';

    public array $form = [];

    public $upload = null;

    public function mount(string $employeeId): void
    {
        $this->authorize('viewAny', EmployeeDocument::class);
        $this->employeeId = $employeeId;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->authorize('create', EmployeeDocument::class);

        $this->validate([
            'form.type' => ['required', Rule::enum(EmployeeDocumentType::class)],
            'form.title' => ['required', 'string', 'max:255'],
            'form.status' => ['required', Rule::enum(EmployeeDocumentStatus::class)],
            'form.expiry_date' => ['nullable', 'date'],
            'form.notes' => ['nullable', 'string', 'max:2000'],
            'upload' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        if (! $this->upload instanceof TemporaryUploadedFile) {
            return;
        }

        $path = $this->upload->store('employees/'.$this->employeeId.'/documents', 'hr');

        EmployeeDocument::query()->create([
            'employee_id' => $this->employeeId,
            'uploaded_by_id' => auth()->id(),
            'type' => $this->form['type'],
            'title' => $this->form['title'],
            'status' => $this->form['status'],
            'expiry_date' => $this->form['expiry_date'] ?: null,
            'original_name' => $this->upload->getClientOriginalName(),
            'path' => $path,
            'disk' => 'hr',
            'mime_type' => $this->upload->getMimeType(),
            'size' => $this->upload->getSize(),
            'notes' => $this->form['notes'] ?: null,
        ]);

        $this->upload = null;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Document stored privately.');
    }

    #[On('confirmed-delete-employee-document')]
    public function delete(string $id): void
    {
        $document = EmployeeDocument::query()->findOrFail($id);
        $this->authorize('delete', $document);
        abort_unless($document->employee_id === $this->employeeId, 404);
        $document->delete();
        $this->dispatch('notify', type: 'success', message: 'Document removed.');
    }

    public function render(): View
    {
        $employee = Employee::query()->findOrFail($this->employeeId);

        return view('livewire.employee-documents.index', [
            'employee' => $employee,
            'documents' => EmployeeDocument::query()
                ->with('uploadedBy')
                ->where('employee_id', $this->employeeId)
                ->latest()
                ->get(),
            'types' => EmployeeDocumentType::cases(),
            'statuses' => EmployeeDocumentStatus::cases(),
            'missing' => $employee->loadMissing('documents')->missingDocumentTypes(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'type' => EmployeeDocumentType::Nda->value,
            'title' => '',
            'status' => EmployeeDocumentStatus::Uploaded->value,
            'expiry_date' => '',
            'notes' => '',
        ];
    }
}
