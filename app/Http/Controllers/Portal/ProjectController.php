<?php

namespace App\Http\Controllers\Portal;

use App\Enums\RequirementApprovalStatus;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends PortalController
{
    public function index(Request $request): View
    {
        $user = $this->portalUser();
        $status = $request->string('status')->toString();
        $search = $request->string('q')->toString();

        $projects = $this->access()->projects($user)
            ->with('milestones')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', '%'.$search.'%')
                    ->orWhere('number', 'like', '%'.$search.'%');
            }))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('portal.projects.index', compact('projects', 'status', 'search'));
    }

    public function show(string $project): View
    {
        $user = $this->portalUser();
        $project = $this->access()->project($user, $project);

        $project->load([
            'milestones',
            'stages',
            'requirements' => fn ($query) => $query->where('client_approval_status', RequirementApprovalStatus::Approved->value),
        ]);

        $files = $this->access()->projectFiles($user, $project)->latest()->get();

        return view('portal.projects.show', [
            'project' => $project,
            'progress' => $project->progressPercent(),
            'files' => $files,
        ]);
    }

    public function downloadFile(string $project, string $attachment, AuditLogger $audit): StreamedResponse
    {
        $user = $this->portalUser();
        $project = $this->access()->project($user, $project);
        $file = $this->access()->projectFile($user, $project, $attachment);

        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        $audit->record(
            action: 'downloaded',
            module: 'client_portal',
            auditable: $file,
            newValues: array_merge($user->auditActorValues(), [
                'project_id' => $project->id,
                'file' => $file->original_name,
            ]),
            user: $user,
        );

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function downloadEvidence(string $project, string $stage, string $evidence, AuditLogger $audit): StreamedResponse
    {
        $user = $this->portalUser();
        $project = $this->access()->project($user, $project);
        $stage = $this->access()->stage($user, $project, $stage);
        $file = $this->access()->stageEvidenceItem($user, $project, $stage, $evidence);

        abort_unless($file->hasFile(), 404);
        abort_unless(in_array($file->disk, ['stages', 'local'], true), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        $audit->record(
            action: 'downloaded',
            module: 'client_portal',
            auditable: $file,
            newValues: array_merge($user->auditActorValues(), [
                'project_id' => $project->id,
                'stage_id' => $stage->id,
                'file' => $file->original_name,
            ]),
            user: $user,
        );

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function downloadAttachment(string $project, string $stage, string $message, string $attachment, AuditLogger $audit): StreamedResponse
    {
        $user = $this->portalUser();
        $project = $this->access()->project($user, $project);
        $stage = $this->access()->stage($user, $project, $stage);
        $file = $this->access()->stageMessageAttachment($user, $project, $stage, $message, $attachment);

        abort_unless(in_array($file->disk, ['stages', 'local'], true), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        $audit->record(
            action: 'downloaded',
            module: 'client_portal',
            auditable: $file,
            newValues: array_merge($user->auditActorValues(), [
                'project_id' => $project->id,
                'stage_id' => $stage->id,
                'file' => $file->original_name,
            ]),
            user: $user,
        );

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}
