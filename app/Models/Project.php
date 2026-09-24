<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\ChangeRequestStatus;
use App\Enums\ClientApprovalMode;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\StageStatus;
use App\Enums\TaskStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'projects';

    protected $fillable = [
        'number',
        'client_id',
        'quotation_id',
        'manager_id',
        'name',
        'description',
        'start_date',
        'expected_end_date',
        'actual_completion_date',
        'budget',
        'status',
        'health',
        'priority',
        'client_approval_mode',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expected_end_date' => 'date',
            'actual_completion_date' => 'date',
            'budget' => 'decimal:2',
            'status' => ProjectStatus::class,
            'health' => ProjectHealth::class,
            'priority' => ProjectPriority::class,
            'client_approval_mode' => ClientApprovalMode::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('due_date');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ProjectStage::class)->orderBy('sequence');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectAttachment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function companyDocuments(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'subject')->latest('created_at');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ProjectStatus::Planning->value,
            ProjectStatus::Active->value,
            ProjectStatus::OnHold->value,
        ]);
    }

    /**
     * When stages exist, progress is the average of stage progress
     * (each stage = completed tasks + completed deliverables / totals).
     * Otherwise progress stays task-first, then milestone average.
     */
    public function progressPercent(): int
    {
        $stageCount = $this->stages()->count();

        if ($stageCount > 0) {
            $stages = $this->stages()->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', TaskStatus::Completed->value),
                'deliverables',
                'deliverables as completed_deliverables_count' => fn ($query) => $query->where('status', 'completed'),
            ])->get();

            $sum = $stages->sum(function (ProjectStage $stage) {
                $total = $stage->tasks_count + $stage->deliverables_count;
                if ($total === 0) {
                    return in_array($stage->status, [StageStatus::Completed, StageStatus::Approved], true) ? 100 : 0;
                }

                return (int) round((($stage->completed_tasks_count + $stage->completed_deliverables_count) / $total) * 100);
            });

            return (int) round($sum / $stageCount);
        }

        $total = $this->tasks()->count();

        if ($total > 0) {
            $completed = $this->tasks()->where('status', TaskStatus::Completed->value)->count();

            return (int) round(($completed / $total) * 100);
        }

        $milestones = $this->milestones()->count();

        if ($milestones === 0) {
            return 0;
        }

        return (int) round($this->milestones()->avg('completion_percentage') ?? 0);
    }

    public function currentStage(): ?ProjectStage
    {
        $stages = $this->relationLoaded('stages') ? $this->stages : $this->stages()->ordered()->get();

        return $stages->first(fn (ProjectStage $stage) => $stage->status->isActiveWork())
            ?? $stages->first(fn (ProjectStage $stage) => $stage->status === StageStatus::NotStarted);
    }

    /**
     * @return array{label: string, tone: string}
     */
    public function deliveryHealth(): array
    {
        $stages = $this->relationLoaded('stages') ? $this->stages : $this->stages()->get();

        if ($stages->isEmpty()) {
            return ['label' => 'No stages', 'tone' => 'neutral'];
        }

        if ($stages->contains(fn (ProjectStage $stage) => $stage->status === StageStatus::Blocked)) {
            return ['label' => 'Blocked', 'tone' => 'danger'];
        }

        if ($stages->contains(fn (ProjectStage $stage) => $stage->isOverdue())) {
            return ['label' => 'Overdue', 'tone' => 'danger'];
        }

        if ($stages->contains(fn (ProjectStage $stage) => $stage->status === StageStatus::ChangesRequested)) {
            return ['label' => 'Changes requested', 'tone' => 'warning'];
        }

        if ($stages->contains(fn (ProjectStage $stage) => $stage->status === StageStatus::ReadyForReview)) {
            return ['label' => 'Awaiting review', 'tone' => 'warning'];
        }

        if ($stages->every(fn (ProjectStage $stage) => $stage->status === StageStatus::Completed)) {
            return ['label' => 'Complete', 'tone' => 'success'];
        }

        if ($stages->contains(fn (ProjectStage $stage) => $stage->status === StageStatus::InProgress)) {
            return ['label' => 'On track', 'tone' => 'success'];
        }

        return ['label' => 'Not started', 'tone' => 'neutral'];
    }

    public function openChangeRequestsCount(): int
    {
        return $this->changeRequests()->where('status', ChangeRequestStatus::Pending->value)->count();
    }
}
