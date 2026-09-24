<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\DocumentVersionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class DocumentVersion extends Model
{
    /** @use HasFactory<DocumentVersionFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected $table = 'company_document_versions';

    protected string $auditModule = 'documents';

    protected $fillable = [
        'document_id',
        'version_number',
        'version_label',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'uploaded_by_id',
        'change_notes',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
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
        static::deleted(function (DocumentVersion $version): void {
            if ($version->isForceDeleting() && $version->path) {
                Storage::disk($version->disk ?: 'documents')->delete($version->path);
            }
        });
    }
}
