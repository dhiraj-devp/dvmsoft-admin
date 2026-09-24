<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\ProjectPriority;
use App\Enums\RequirementApprovalStatus;
use App\Enums\RequirementStatus;
use Database\Factories\RequirementFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Requirement extends Model
{
    /** @use HasFactory<RequirementFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'requirements';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'priority',
        'status',
        'client_approval_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'priority' => ProjectPriority::class,
            'status' => RequirementStatus::class,
            'client_approval_status' => RequirementApprovalStatus::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectAttachment::class);
    }
}
