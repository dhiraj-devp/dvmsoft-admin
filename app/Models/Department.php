<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Department extends Model
{
    use Auditable, HasUlids, SoftDeletes;

    protected string $auditModule = 'departments';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Department $department): void {
            if (blank($department->slug)) {
                $department->slug = Str::slug($department->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
