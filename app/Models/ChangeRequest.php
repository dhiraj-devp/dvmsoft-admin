<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\ChangeRequestStatus;
use Database\Factories\ChangeRequestFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChangeRequest extends Model
{
    /** @use HasFactory<ChangeRequestFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'change_requests';

    protected $fillable = [
        'number',
        'project_id',
        'requested_by_id',
        'client_user_id',
        'decided_by_id',
        'title',
        'description',
        'impact_on_cost',
        'impact_on_timeline_days',
        'status',
        'notes',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'impact_on_cost' => 'decimal:2',
            'impact_on_timeline_days' => 'integer',
            'status' => ChangeRequestStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class, 'client_user_id');
    }

    public function requesterName(): string
    {
        return $this->requestedBy?->name
            ?: $this->clientUser?->name
            ?: 'Client';
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }
}
