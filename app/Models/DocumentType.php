<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DocumentType extends Model
{
    /** @use HasFactory<DocumentTypeFactory> */
    use Auditable, HasFactory, HasUlids;

    protected $table = 'company_document_types';

    protected string $auditModule = 'documents';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (DocumentType $type): void {
            if (blank($type->slug)) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'document_type_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
