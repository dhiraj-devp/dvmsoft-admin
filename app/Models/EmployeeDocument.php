<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\EmployeeDocumentStatus;
use App\Enums\EmployeeDocumentType;
use Database\Factories\EmployeeDocumentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class EmployeeDocument extends Model
{
    /** @use HasFactory<EmployeeDocumentFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'employee_documents';

    protected $fillable = [
        'employee_id',
        'uploaded_by_id',
        'type',
        'title',
        'status',
        'expiry_date',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => EmployeeDocumentType::class,
            'status' => EmployeeDocumentStatus::class,
            'expiry_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function isExpired(): bool
    {
        if ($this->status === EmployeeDocumentStatus::Expired) {
            return true;
        }

        return $this->expiry_date !== null && $this->expiry_date->lt(now()->startOfDay());
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
        static::deleted(function (EmployeeDocument $document): void {
            if ($document->isForceDeleting() && $document->path) {
                Storage::disk($document->disk ?: 'hr')->delete($document->path);
            }
        });
    }
}
