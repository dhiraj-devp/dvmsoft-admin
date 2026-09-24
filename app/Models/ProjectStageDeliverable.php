<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\StageDeliverableStatus;
use Database\Factories\ProjectStageDeliverableFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectStageDeliverable extends Model
{
    /** @use HasFactory<ProjectStageDeliverableFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'project_stages';

    protected $fillable = [
        'stage_id',
        'name',
        'description',
        'is_required',
        'status',
        'completed_by_id',
        'completed_at',
        'evidence_id',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'status' => StageDeliverableStatus::class,
            'completed_at' => 'datetime',
            'sequence' => 'integer',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStage::class, 'stage_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_id');
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(ProjectStageEvidence::class, 'evidence_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === StageDeliverableStatus::Completed;
    }
}
