<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentVersionController extends Controller
{
    public function download(Document $document, DocumentVersion $version, AuditLogger $audit): StreamedResponse
    {
        $this->authorize('download', $document);
        $this->authorize('view', $version);

        abort_unless($version->document_id === $document->id, 404);
        abort_unless($version->disk === 'documents' || $version->disk === 'local', 404);

        $disk = $version->disk ?: 'documents';

        abort_unless(Storage::disk($disk)->exists($version->path), 404);

        $audit->record(
            action: 'downloaded',
            module: 'documents',
            auditable: $document,
            newValues: [
                'version' => $version->version_number,
                'file' => $version->original_name,
            ],
        );

        return Storage::disk($disk)->download($version->path, $version->original_name);
    }
}
