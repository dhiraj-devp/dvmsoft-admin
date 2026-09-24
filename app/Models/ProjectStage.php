<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\ClientApprovalMode;
use App\Enums\StageApprovalRequirement;
use App\Enums\StageDeliverableStatus;
use App\Enums\StageEvidenceVisibility;
use App\Enums\StageStatus;
use App\Enums\TaskStatus;
use Database\Factories\ProjectStageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectStage extends Model
{
    /** @use HasFactory<ProjectStageFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'project_stages';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'sequence',
        'status',
        'start_date',
        'due_date',
        'completed_at',
        'completion_percentage',
        'owner_id',
        'client_review_enabled',
        'approval_requirement',
        'requires_evidence',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'status' => StageStatus::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'completion_percentage' => 'integer',
            'client_review_enabled' => 'boolean',
            'approval_requirement' => StageApprovalRequirement::class,
            'requires_evidence' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_stage_members', 'stage_id', 'user_id')
            ->using(ProjectStageMember::class)
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'stage_id');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(ProjectStageDeliverable::class, 'stage_id')->orderBy('sequence');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(ProjectStageEvidence::class, 'stage_id')->latest();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ProjectStageSubmission::class, 'stage_id')->orderByDesc('version');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ProjectStageMessage::class, 'stage_id')->whereNull('parent_id')->latest();
    }

    public function allMessages(): HasMany
    {
        return $this->hasMany(ProjectStageMessage::class, 'stage_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sequence');
    }

    public function approvalIsRequired(): bool
    {
        return match ($this->approval_requirement) {
            StageApprovalRequirement::Required => true,
            StageApprovalRequirement::NotRequired => false,
            StageApprovalRequirement::Inherit => $this->project?->client_approval_mode === ClientApprovalMode::Strict,
        };
    }

    /**
     * Progress is (completed tasks + completed deliverables) / (tasks + deliverables).
     * Stages with neither use 100% when approved or completed, otherwise 0%.
     */
    public function calculateProgress(): int
    {
        $taskTotal = $this->tasks()->count();
        $taskDone = $this->tasks()->where('status', TaskStatus::Completed->value)->count();
        $deliverableTotal = $this->deliverables()->count();
        $deliverableDone = $this->deliverables()->where('status', StageDeliverableStatus::Completed->value)->count();

        $total = $taskTotal + $deliverableTotal;

        if ($total === 0) {
            return in_array($this->status, [StageStatus::Completed, StageStatus::Approved], true) ? 100 : 0;
        }

        return (int) round((($taskDone + $deliverableDone) / $total) * 100);
    }

    public function refreshProgress(): int
    {
        $percent = $this->calculateProgress();
        $this->forceFill(['completion_percentage' => $percent])->saveQuietly();

        return $percent;
    }

    public function progressPercent(): int
    {
        return $this->calculateProgress();
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen()
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    public function incompleteRequiredDeliverables()
    {
        return $this->deliverables()
            ->where('is_required', true)
            ->where('status', '!=', StageDeliverableStatus::Completed->value);
    }

    public function incompleteTasks()
    {
        return $this->tasks()->where('status', '!=', TaskStatus::Completed->value);
    }

    public function clientVisibleEvidence()
    {
        return $this->evidence()->where('visibility', StageEvidenceVisibility::Client->value);
    }

    public function latestSubmission(): ?ProjectStageSubmission
    {
        return $this->submissions()->orderByDesc('version')->first();
    }

    public function nextVersion(): int
    {
        return ((int) $this->submissions()->max('version')) + 1;
    }
}
