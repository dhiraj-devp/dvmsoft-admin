<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    use Auditable, HasUlids, SoftDeletes;

    protected string $auditModule = 'tickets';

    protected $fillable = [
        'ticket_message_id',
        'uploaded_by_id',
        'client_user_id',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class, 'client_user_id');
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }

    protected static function booted(): void
    {
        static::deleted(function (TicketAttachment $attachment): void {
            if ($attachment->isForceDeleting() && $attachment->path) {
                Storage::disk($attachment->disk ?: 'support')->delete($attachment->path);
            }
        });
    }
}
