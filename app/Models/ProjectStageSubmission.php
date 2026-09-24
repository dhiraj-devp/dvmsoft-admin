<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\StageSubmissionStatus;
use Database\Factories\ProjectStageSubmissionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStageSubmission extends Model
{
    /** @use HasFactory<ProjectStageSubmissionFactory> */
    use Auditable, HasFactory, HasUlids;

    protected string $auditModule = 'project_stages';

    protected $fillable = [
        'stage_id',
        'version',
        'status',
        'submitted_by_id',
        'submitted_at',
        'comment',
        'override_incomplete',
        'override_by_id',
        'override_reason',
        'decided_by_id',
        'decided_by_client_user_id',
        'decided_at',
        'decision_comment',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'status' => StageSubmissionStatus::class,
            'submitted_at' => 'datetime',
            'override_incomplete' => 'boolean',
            'decided_at' => 'datetime',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStage::class, 'stage_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function overrideBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'override_by_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    public function decidedByClientUser(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class, 'decided_by_client_user_id');
    }

    public function reviewerName(): string
    {
        return $this->decidedByClientUser?->name
            ?: $this->decidedBy?->name
            ?: '—';
    }

    public function isPending(): bool
    {
        return $this->status === StageSubmissionStatus::Submitted;
    }
}
