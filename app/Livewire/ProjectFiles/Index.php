<?php

namespace App\Livewire\ProjectFiles;

use App\Models\Project;
use App\Models\ProjectAttachment;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Index extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public string $projectId = '';

    public $upload = null;

    public function mount(string $projectId): void
    {
        $this->authorize('viewAny', ProjectAttachment::class);
        $this->projectId = $projectId;
    }

    public function save(): void
    {
        $this->authorize('create', ProjectAttachment::class);
        $this->validate([
            'upload' => ['required', 'file', 'max:20480'],
        ]);

        if (! $this->upload instanceof TemporaryUploadedFile) {
            return;
        }

        $path = $this->upload->store('projects/'.$this->projectId, 'local');

        ProjectAttachment::query()->create([
            'project_id' => $this->projectId,
            'uploaded_by_id' => auth()->id(),
            'original_name' => $this->upload->getClientOriginalName(),
            'path' => $path,
            'disk' => 'local',
            'mime_type' => $this->upload->getMimeType(),
            'size' => $this->upload->getSize(),
        ]);

        $this->upload = null;
        $this->dispatch('notify', type: 'success', message: 'File uploaded.');
    }

    #[On('confirmed-delete-file')]
    public function delete(string $id): void
    {
        $file = ProjectAttachment::query()->findOrFail($id);
        $this->authorize('delete', $file);
        $file->delete();
        $this->dispatch('notify', type: 'success', message: 'File removed.');
    }

    public function render(): View
    {
        return view('livewire.project-files.index', [
            'files' => ProjectAttachment::query()
                ->with('uploadedBy')
                ->where('project_id', $this->projectId)
                ->whereNull('requirement_id')
                ->latest()
                ->get(),
            'project' => Project::query()->findOrFail($this->projectId),
        ]);
    }
}
