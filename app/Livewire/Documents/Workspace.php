<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use App\Services\DocumentWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Workspace extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public string $documentId = '';

    public string $approvalNotes = '';

    public string $changeNotes = '';

    public $upload = null;

    public function mount(string $documentId): void
    {
        $document = Document::query()->findOrFail($documentId);
        $this->authorize('view', $document);
        $this->documentId = $documentId;
        $this->approvalNotes = $document->approval_notes ?? '';
    }

    public function submit(DocumentWorkflowService $workflow): void
    {
        $document = $this->document();
        $this->authorize('submit', $document);
        $workflow->submit($document, request()->user());
        $this->dispatch('notify', type: 'success', message: 'Document submitted for review.');
    }

    public function approve(DocumentWorkflowService $workflow): void
    {
        $document = $this->document();
        $this->authorize('approve', $document);
        $workflow->approve($document, request()->user(), $this->approvalNotes ?: null);
        $this->dispatch('notify', type: 'success', message: 'Document approved.');
    }

    public function reject(DocumentWorkflowService $workflow): void
    {
        $document = $this->document();
        $this->authorize('approve', $document);
        $this->validate([
            'approvalNotes' => ['required', 'string', 'max:2000'],
        ]);
        $workflow->reject($document, request()->user(), $this->approvalNotes);
        $this->dispatch('notify', type: 'success', message: 'Document returned to draft.');
    }

    public function markSent(DocumentWorkflowService $workflow): void
    {
        $document = $this->document();
        $this->authorize('update', $document);
        $workflow->markSent($document, request()->user());
        $this->dispatch('notify', type: 'success', message: 'Document marked sent.');
    }

    public function markSigned(DocumentWorkflowService $workflow): void
    {
        $document = $this->document();
        $this->authorize('update', $document);
        $workflow->markSigned($document, request()->user());
        $this->dispatch('notify', type: 'success', message: 'Document marked signed.');
    }

    public function archive(DocumentWorkflowService $workflow): void
    {
        $document = $this->document();
        $this->authorize('archive', $document);
        $workflow->archive($document, request()->user());
        $this->dispatch('notify', type: 'success', message: 'Document archived.');
    }

    public function uploadVersion(DocumentWorkflowService $workflow): void
    {
        $document = $this->document();
        $this->authorize('upload', $document);

        $this->validate([
            'upload' => ['required', 'file', 'max:20480'],
            'changeNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $this->upload instanceof TemporaryUploadedFile ? $this->upload : null;
        $workflow->uploadVersion($document, request()->user(), $file, $this->changeNotes ?: null);
        $this->reset('upload', 'changeNotes');
        $this->dispatch('notify', type: 'success', message: 'New version uploaded.');
    }

    #[On('confirmed-delete-document')]
    public function delete(string $id): mixed
    {
        $document = Document::query()->findOrFail($id);
        $this->authorize('delete', $document);
        $document->delete();
        session()->flash('status', 'Document removed.');

        return redirect()->route('documents.index');
    }

    public function render(): View
    {
        $document = Document::query()
            ->with(['versions.uploadedBy', 'type', 'owner', 'createdBy', 'approvedBy'])
            ->findOrFail($this->documentId);

        return view('livewire.documents.workspace', [
            'document' => $document,
        ]);
    }

    protected function document(): Document
    {
        return Document::query()->findOrFail($this->documentId);
    }
}
