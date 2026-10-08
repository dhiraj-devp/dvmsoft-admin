<?php

namespace App\Http\Controllers;

use App\Models\WorkDailyUpdateEvidence;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkEvidenceController extends Controller
{
    public function download(WorkDailyUpdateEvidence $evidence): StreamedResponse
    {
        $this->authorize('view', $evidence->updateEntry);

        abort_unless($evidence->isFile(), 404);
        abort_unless(in_array($evidence->disk, ['work', 'local'], true), 404);
        abort_unless(Storage::disk($evidence->disk)->exists($evidence->path), 404);

        return Storage::disk($evidence->disk)->download($evidence->path, $evidence->original_name ?: 'evidence');
    }
}
