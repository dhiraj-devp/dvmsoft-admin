<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkDailyUpdateEvidence extends Model
{
    use HasUlids;

    protected $fillable = [
        'work_daily_update_id',
        'type',
        'label',
        'url',
        'disk',
        'path',
        'original_name',
    ];

    public function updateEntry(): BelongsTo
    {
        return $this->belongsTo(WorkDailyUpdate::class, 'work_daily_update_id');
    }

    public function isFile(): bool
    {
        return $this->type === 'file' && filled($this->path);
    }
}
