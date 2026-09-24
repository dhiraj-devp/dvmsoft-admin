<?php

namespace App\Http\Controllers\Portal;

use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends PortalController
{
    public function index(Request $request): View
    {
        $user = $this->portalUser();
        $search = $request->string('q')->toString();

        $documents = $this->access()->documents($user)
            ->with(['type', 'versions'])
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search): void {
                $inner->where('title', 'like', '%'.$search.'%')
                    ->orWhere('number', 'like', '%'.$search.'%');
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('portal.documents.index', compact('documents', 'search'));
    }

    public function download(string $document, AuditLogger $audit): StreamedResponse
    {
        $user = $this->portalUser();
        $document = $this->access()->document($user, $document);
        $version = $document->currentVersion();

        abort_unless($version, 404);

        $disk = $version->disk ?: 'documents';

        abort_unless(in_array($disk, ['documents', 'local'], true), 404);
        abort_unless(Storage::disk($disk)->exists($version->path), 404);

        $audit->record(
            action: 'downloaded',
            module: 'client_portal',
            auditable: $document,
            newValues: array_merge($user->auditActorValues(), [
                'version' => $version->version_number,
                'file' => $version->original_name,
            ]),
            user: $user,
        );

        return Storage::disk($disk)->download($version->path, $version->original_name);
    }
}
