<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\StageEvidenceType;
use App\Enums\StageEvidenceVisibility;
use Database\Factories\ProjectStageEvidenceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ProjectStageEvidence extends Model
{
    /** @use HasFactory<ProjectStageEvidenceFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'project_stages';

    protected $table = 'project_stage_evidence';

    protected $fillable = [
        'stage_id',
        'uploaded_by_id',
        'type',
        'title',
        'description',
        'url',
        'path',
        'disk',
        'original_name',
        'mime_type',
        'size',
        'version',
        'visibility',
    ];

    protected function casts(): array
    {
        return [
            'type' => StageEvidenceType::class,
            'visibility' => StageEvidenceVisibility::class,
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (ProjectStageEvidence $evidence): void {
            if ($evidence->path && $evidence->disk) {
                Storage::disk($evidence->disk)->delete($evidence->path);
            }
        });
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStage::class, 'stage_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function isClientVisible(): bool
    {
        return $this->visibility === StageEvidenceVisibility::Client;
    }

    public function hasFile(): bool
    {
        return filled($this->path) && filled($this->disk);
    }

    public function humanSize(): string
    {
        if (! $this->size) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = (float) $this->size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }
}
