<?php

namespace App\Services;

use App\Automations\ClientPortalNotifier;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentWorkflowService
{
    public function __construct(
        protected SequentialNumberGenerator $numbers,
        protected AuditLogger $audit,
        protected DocumentNotificationService $notifications,
        protected ClientPortalNotifier $portal,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, User $actor, UploadedFile $file): Document
    {
        return DB::transaction(function () use ($attributes, $actor, $file) {
            $payload = $this->payload($attributes, $actor->id);
            $payload['number'] = $this->numbers->nextDocument();
            $payload['status'] = DocumentStatus::Draft;
            $payload['current_version_number'] = 1;
            $payload['created_by_id'] = $actor->id;
            $payload['owner_id'] = $payload['owner_id'] ?: $actor->id;

            $document = Document::query()->create($payload);

            $this->storeVersion($document, $actor, $file, 1, $attributes['change_notes'] ?? 'Initial upload');

            $this->audit->record(
                action: 'uploaded',
                module: 'documents',
                auditable: $document,
                newValues: ['version' => 1, 'file' => $file->getClientOriginalName()],
                user: $actor,
            );

            return $document->fresh(['type', 'owner', 'versions']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Document $document, array $attributes): Document
    {
        $payload = $this->payload($attributes, $document->owner_id);
        $newExpiry = $payload['expiry_date'];

        if ($newExpiry !== $document->expiry_date?->toDateString()) {
            $payload['expiry_notified_at'] = null;
        }

        $document->update($payload);

        return $document->fresh();
    }

    public function uploadVersion(Document $document, User $actor, UploadedFile $file, ?string $changeNotes = null): DocumentVersion
    {
        if ($document->status === DocumentStatus::Archived) {
            throw ValidationException::withMessages([
                'status' => 'Archived documents cannot receive new versions.',
            ]);
        }

        return DB::transaction(function () use ($document, $actor, $file, $changeNotes) {
            $next = (int) $document->current_version_number + 1;
            $version = $this->storeVersion($document, $actor, $file, $next, $changeNotes ?: 'Version '.$next);

            $updates = ['current_version_number' => $next];

            if (! in_array($document->status, [DocumentStatus::Draft, DocumentStatus::Review], true)) {
                $updates['status'] = DocumentStatus::Draft;
                $updates['approved_by_id'] = null;
                $updates['approved_at'] = null;
            }

            $document->update($updates);

            $this->audit->record(
                action: 'version_created',
                module: 'documents',
                auditable: $document,
                newValues: ['version' => $next, 'file' => $file->getClientOriginalName()],
                user: $actor,
            );

            return $version;
        });
    }

    public function submit(Document $document, User $actor): Document
    {
        if ($document->status !== DocumentStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only draft documents can be submitted for review.',
            ]);
        }

        $document->update(['status' => DocumentStatus::Review]);
        $this->audit->record(action: 'submitted', module: 'documents', auditable: $document, user: $actor);
        $this->notifications->notify($document->fresh(['owner', 'type']), 'submitted', $actor);

        return $document->fresh();
    }

    public function approve(Document $document, User $actor, ?string $notes = null): Document
    {
        if ($document->status !== DocumentStatus::Review) {
            throw ValidationException::withMessages([
                'status' => 'Only documents in review can be approved.',
            ]);
        }

        $document->update([
            'status' => DocumentStatus::Approved,
            'approved_by_id' => $actor->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);

        $this->audit->record(action: 'approved', module: 'documents', auditable: $document, user: $actor);
        $this->notifications->notify($document->fresh(['owner', 'createdBy', 'type']), 'approved', $actor);

        $document = $document->fresh(['client']);

        if ($document->client_id) {
            $this->portal->notify(
                $document->client,
                'portal.activity',
                'document_approved',
                'Document approved',
                $document->title.' is available in your portal.',
                route('client.documents.index'),
                ['document_id' => $document->id],
                $document,
                'approved',
            );
        }

        return $document;
    }

    public function reject(Document $document, User $actor, string $notes): Document
    {
        if ($document->status !== DocumentStatus::Review) {
            throw ValidationException::withMessages([
                'status' => 'Only documents in review can be rejected.',
            ]);
        }

        $document->update([
            'status' => DocumentStatus::Draft,
            'approved_by_id' => null,
            'approved_at' => null,
            'approval_notes' => $notes,
        ]);

        $this->audit->record(action: 'rejected', module: 'documents', auditable: $document, user: $actor);
        $this->notifications->notify($document->fresh(['owner', 'createdBy', 'type']), 'rejected', $actor);

        return $document->fresh();
    }

    public function markSent(Document $document, User $actor): Document
    {
        if ($document->status !== DocumentStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => 'Only approved documents can be marked sent.',
            ]);
        }

        $document->update(['status' => DocumentStatus::Sent]);
        $this->audit->record(action: 'sent', module: 'documents', auditable: $document, user: $actor);

        return $document->fresh();
    }

    public function markSigned(Document $document, User $actor): Document
    {
        if ($document->status !== DocumentStatus::Sent) {
            throw ValidationException::withMessages([
                'status' => 'Only sent documents can be marked signed.',
            ]);
        }

        $document->update(['status' => DocumentStatus::Signed]);
        $this->audit->record(action: 'signed', module: 'documents', auditable: $document, user: $actor);

        return $document->fresh();
    }

    public function archive(Document $document, User $actor): Document
    {
        if ($document->status === DocumentStatus::Draft || $document->status === DocumentStatus::Review) {
            throw ValidationException::withMessages([
                'status' => 'Submit and approve the document before archiving.',
            ]);
        }

        $document->update(['status' => DocumentStatus::Archived]);
        $this->audit->record(action: 'archived', module: 'documents', auditable: $document, user: $actor);

        return $document->fresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function payload(array $attributes, ?string $fallbackOwnerId): array
    {
        return [
            'title' => $attributes['title'],
            'document_type_id' => $attributes['document_type_id'],
            'description' => ($attributes['description'] ?? '') ?: null,
            'owner_id' => ($attributes['owner_id'] ?? '') ?: $fallbackOwnerId,
            'expiry_date' => ($attributes['expiry_date'] ?? '') ?: null,
            'notes' => ($attributes['notes'] ?? '') ?: null,
            'client_id' => ($attributes['client_id'] ?? '') ?: null,
            'project_id' => ($attributes['project_id'] ?? '') ?: null,
            'employee_id' => ($attributes['employee_id'] ?? '') ?: null,
            'quotation_id' => ($attributes['quotation_id'] ?? '') ?: null,
            'invoice_id' => ($attributes['invoice_id'] ?? '') ?: null,
        ];
    }

    protected function storeVersion(Document $document, User $actor, UploadedFile $file, int $number, string $notes): DocumentVersion
    {
        $path = $file->store('documents/'.$document->id, 'documents');
        $format = (string) settings('documents.version_format', 'v{n}');

        return DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => $number,
            'version_label' => str_replace('{n}', (string) $number, $format),
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'documents',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by_id' => $actor->id,
            'change_notes' => $notes,
        ]);
    }
}
