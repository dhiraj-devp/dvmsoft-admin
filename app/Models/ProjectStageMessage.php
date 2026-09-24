<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\ProjectStageMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectStageMessage extends Model
{
    /** @use HasFactory<ProjectStageMessageFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'project_stages';

    protected $fillable = [
        'stage_id',
        'parent_id',
        'author_id',
        'client_user_id',
        'body',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStage::class, 'stage_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class, 'client_user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectStageMessageAttachment::class, 'message_id');
    }

    public function displayAuthorName(bool $forClient = false): string
    {
        if ($forClient && $this->author_id) {
            return company_name();
        }

        return $this->author?->name
            ?: $this->clientUser?->name
            ?: ($this->is_internal ? 'Staff' : 'Client');
    }

    public function scopeVisibleToClient(Builder $query): Builder
    {
        return $query->where('is_internal', false);
    }
}
