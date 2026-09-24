<?php

namespace App\Services;

use App\Automations\DispatchLog;
use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentEventNotification;
use Illuminate\Support\Collection;

class DocumentNotificationService
{
    public function notify(Document $document, string $event, ?User $actor = null): void
    {
        $document->loadMissing(['owner', 'createdBy']);

        foreach ($this->recipients($document, $event, $actor) as $user) {
            $user->notify(new DocumentEventNotification($document, $event));
        }
    }

    /**
     * @return Collection<int, User>
     */
    protected function recipients(Document $document, string $event, ?User $actor): Collection
    {
        $ids = collect([$document->owner_id, $document->created_by_id]);

        if (in_array($event, ['submitted', 'expiring', 'expired'], true)) {
            $ids = $ids->merge(
                User::query()
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->where('is_super_admin', true)
                            ->orWhereHas('roles.permissions', fn ($permissions) => $permissions->where('name', 'documents.approve'));
                    })
                    ->pluck('id')
            );
        }

        return User::query()
            ->where('is_active', true)
            ->whereIn('id', $ids->filter()->unique())
            ->when($actor, fn ($query) => $query->where('id', '!=', $actor->id))
            ->get();
    }

    /**
     * @return array{processed: int, notified: int}
     */
    public function notifyExpiring(): array
    {
        $days = (int) settings('documents.expiry_warning_days', 30);
        $notified = 0;

        $documents = Document::query()
            ->with(['owner', 'createdBy', 'type'])
            ->expiring($days)
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->whereNull('expiry_notified_at')
            ->get();

        foreach ($documents as $document) {
            $this->notify($document, 'expiring');
            $document->forceFill(['expiry_notified_at' => now()])->saveQuietly();
            $notified++;
        }

        return [
            'processed' => $documents->count(),
            'notified' => $notified,
        ];
    }

    /**
     * @return array{processed: int, notified: int}
     */
    public function notifyExpired(): array
    {
        $documents = Document::query()
            ->with(['owner', 'createdBy', 'type'])
            ->expired()
            ->get();

        $processed = 0;
        $notified = 0;
        $dispatches = app(DispatchLog::class);

        foreach ($documents as $document) {
            $processed++;

            if (! $dispatches->claimed('documents.expired', $document, 'expired')) {
                continue;
            }

            $this->notify($document, 'expired');
            $notified++;
        }

        return [
            'processed' => $processed,
            'notified' => $notified,
        ];
    }
}
