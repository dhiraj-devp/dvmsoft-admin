<?php

namespace App\Livewire\Documents;

use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Services\DocumentWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public ?string $documentId = null;

    public array $form = [];

    public $upload = null;

    public function mount(?Document $document = null): void
    {
        if ($document?->exists) {
            $this->authorize('update', $document);
            $this->documentId = $document->id;
            $this->form = [
                'title' => $document->title,
                'document_type_id' => $document->document_type_id,
                'description' => $document->description ?? '',
                'owner_id' => $document->owner_id ?? '',
                'expiry_date' => $document->expiry_date?->toDateString() ?? '',
                'notes' => $document->notes ?? '',
                'client_id' => $document->client_id ?? '',
                'project_id' => $document->project_id ?? '',
                'employee_id' => $document->employee_id ?? '',
                'quotation_id' => $document->quotation_id ?? '',
                'invoice_id' => $document->invoice_id ?? '',
                'change_notes' => '',
            ];
        } else {
            $this->authorize('create', Document::class);
            $this->form = [
                'title' => '',
                'document_type_id' => '',
                'description' => '',
                'owner_id' => auth()->id() ?? '',
                'expiry_date' => '',
                'notes' => '',
                'client_id' => request('client_id', ''),
                'project_id' => request('project_id', ''),
                'employee_id' => request('employee_id', ''),
                'quotation_id' => request('quotation_id', ''),
                'invoice_id' => request('invoice_id', ''),
                'change_notes' => 'Initial upload',
            ];
        }
    }

    public function updatedFormClientId(): void
    {
        $this->form['project_id'] = '';
        $this->form['quotation_id'] = '';
        $this->form['invoice_id'] = '';
    }

    public function save(DocumentWorkflowService $workflow): mixed
    {
        $document = $this->documentId ? Document::query()->findOrFail($this->documentId) : null;
        $document ? $this->authorize('update', $document) : $this->authorize('create', Document::class);

        $clientId = $this->form['client_id'] ?: null;

        $projectRule = $clientId
            ? Rule::exists('projects', 'id')->where('client_id', $clientId)
            : Rule::exists('projects', 'id');
        $quotationRule = $clientId
            ? Rule::exists('quotations', 'id')->where('client_id', $clientId)
            : Rule::exists('quotations', 'id');
        $invoiceRule = $clientId
            ? Rule::exists('invoices', 'id')->where('client_id', $clientId)
            : Rule::exists('invoices', 'id');

        $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.document_type_id' => ['required', 'ulid', 'exists:company_document_types,id'],
            'form.description' => ['nullable', 'string', 'max:10000'],
            'form.owner_id' => ['nullable', 'ulid', 'exists:users,id'],
            'form.expiry_date' => ['nullable', 'date'],
            'form.notes' => ['nullable', 'string', 'max:5000'],
            'form.client_id' => ['nullable', 'ulid', 'exists:clients,id'],
            'form.project_id' => ['nullable', 'ulid', $projectRule],
            'form.employee_id' => ['nullable', 'ulid', 'exists:employees,id'],
            'form.quotation_id' => ['nullable', 'ulid', $quotationRule],
            'form.invoice_id' => ['nullable', 'ulid', $invoiceRule],
            'form.change_notes' => ['nullable', 'string', 'max:1000'],
            'upload' => [$document ? 'nullable' : 'required', 'file', 'max:20480'],
        ]);

        if ($document) {
            $document = $workflow->update($document, $this->form);
            session()->flash('status', 'Document updated.');
        } else {
            $file = $this->upload instanceof TemporaryUploadedFile ? $this->upload : null;
            $document = $workflow->create($this->form, request()->user(), $file);
            session()->flash('status', 'Document created.');
        }

        return redirect()->route('documents.show', $document);
    }

    public function render(): View
    {
        $clientId = $this->form['client_id'] ?? '';

        return view('livewire.documents.form', [
            'types' => DocumentType::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'owners' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'projects' => $clientId
                ? Project::query()->where('client_id', $clientId)->orderBy('name')->get(['id', 'name', 'number', 'client_id'])
                : Project::query()->orderBy('name')->limit(50)->get(['id', 'name', 'number', 'client_id']),
            'employees' => Employee::query()->with('user')->latest()->limit(100)->get(),
            'quotations' => $clientId
                ? Quotation::query()->where('client_id', $clientId)->latest()->get(['id', 'number', 'title', 'client_id'])
                : Quotation::query()->latest()->limit(50)->get(['id', 'number', 'title', 'client_id']),
            'invoices' => $clientId
                ? Invoice::query()->where('client_id', $clientId)->latest()->get(['id', 'number', 'title', 'client_id'])
                : Invoice::query()->latest()->limit(50)->get(['id', 'number', 'title', 'client_id']),
            'document' => $this->documentId ? Document::query()->find($this->documentId) : null,
        ]);
    }
}
