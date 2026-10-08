<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\OfficeHolidayFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeHoliday extends Model
{
    /** @use HasFactory<OfficeHolidayFactory> */
    use Auditable, HasFactory, HasUlids;

    protected string $auditModule = 'office_holidays';

    protected $fillable = [
        'holiday_date',
        'title',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
