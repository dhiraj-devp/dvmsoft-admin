<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectStageMessageAttachment extends Model
{
    use Auditable, HasUlids;

    protected string $auditModule = 'project_stages';

    protected $fillable = [
        'message_id',
        'uploaded_by_id',
        'uploaded_by_client_user_id',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (ProjectStageMessageAttachment $attachment): void {
            if ($attachment->path && $attachment->disk) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
        });
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ProjectStageMessage::class, 'message_id');
    }

    public function humanSize(): string
    {
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
