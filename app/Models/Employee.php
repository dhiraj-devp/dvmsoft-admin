<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\EmployeeDocumentStatus;
use App\Enums\EmployeeDocumentType;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\HrChecklistType;
use App\Enums\ProbationStatus;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected string $auditModule = 'employees';

    protected $fillable = [
        'user_id',
        'employment_type',
        'employment_status',
        'probation_days',
        'probation_status',
        'probation_end_date',
        'confirmation_date',
        'exit_date',
        'exit_reason',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
        'photo_path',
        'photo_disk',
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'employment_status' => EmploymentStatus::class,
            'probation_status' => ProbationStatus::class,
            'probation_end_date' => 'date',
            'confirmation_date' => 'date',
            'exit_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function companyDocuments(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(EmployeeAsset::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(HrChecklist::class);
    }

    public function onboarding(): HasOne
    {
        return $this->hasOne(HrChecklist::class)->where('type', HrChecklistType::Onboarding->value);
    }

    public function offboarding(): HasOne
    {
        return $this->hasOne(HrChecklist::class)->where('type', HrChecklistType::Offboarding->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('employment_status', '!=', EmploymentStatus::Exited->value);
    }

    public function scopeOnProbation(Builder $query): Builder
    {
        return $query->where(function (Builder $nested): void {
            $nested->where('employment_status', EmploymentStatus::Probation->value)
                ->orWhere('probation_status', ProbationStatus::Ongoing->value);
        });
    }

    public function code(): ?string
    {
        return $this->user?->employee_code;
    }

    public function name(): string
    {
        return $this->user?->name ?? 'Employee';
    }

    public function missingDocumentTypes(): array
    {
        $required = config('hr.required_document_types', []);
        $present = $this->documents
            ->filter(function (EmployeeDocument $document): bool {
                if ($document->status === EmployeeDocumentStatus::Expired) {
                    return false;
                }

                if ($document->expiry_date && $document->expiry_date->lt(now()->startOfDay())) {
                    return false;
                }

                return $document->status->isSatisfied();
            })
            ->pluck('type')
            ->map(fn ($type) => $type instanceof EmployeeDocumentType ? $type->value : $type)
            ->unique()
            ->all();

        return array_values(array_diff($required, $present));
    }

    public function photoResponsePath(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        $disk = $this->photo_disk ?: 'hr';

        return Storage::disk($disk)->exists($this->photo_path) ? $this->photo_path : null;
    }
}
