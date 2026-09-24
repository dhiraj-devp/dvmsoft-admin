<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\ProjectStageEvidence;
use App\Models\ProjectStageMessage;
use App\Models\ProjectStageMessageAttachment;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectStageFileController extends Controller
{
    public function evidence(Project $project, ProjectStage $stage, ProjectStageEvidence $evidence): StreamedResponse
    {
        $this->authorize('view', $evidence);

        abort_unless($stage->project_id === $project->id, 404);
        abort_unless($evidence->stage_id === $stage->id, 404);
        abort_unless($evidence->hasFile(), 404);
        abort_unless(in_array($evidence->disk, ['stages', 'local'], true), 404);
        abort_unless(Storage::disk($evidence->disk)->exists($evidence->path), 404);

        return Storage::disk($evidence->disk)->download($evidence->path, $evidence->original_name);
    }

    public function attachment(
        Project $project,
        ProjectStage $stage,
        ProjectStageMessage $message,
        ProjectStageMessageAttachment $attachment,
        AuditLogger $audit,
    ): StreamedResponse {
        $this->authorize('view', $message);

        abort_unless($stage->project_id === $project->id, 404);
        abort_unless($message->stage_id === $stage->id, 404);
        abort_unless($attachment->message_id === $message->id, 404);
        abort_unless(in_array($attachment->disk, ['stages', 'local'], true), 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        $audit->record(
            action: 'downloaded',
            module: 'project_stages',
            auditable: $attachment,
            newValues: ['file' => $attachment->original_name, 'stage_id' => $stage->id],
        );

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
