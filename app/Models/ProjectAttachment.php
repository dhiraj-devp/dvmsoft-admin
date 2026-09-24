<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\ProjectAttachmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ProjectAttachment extends Model
{
    /** @use HasFactory<ProjectAttachmentFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'project_attachments';

    protected $fillable = [
        'project_id',
        'requirement_id',
        'uploaded_by_id',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
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
        static::deleted(function (ProjectAttachment $attachment): void {
            if ($attachment->isForceDeleting()) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
        });
    }
}
