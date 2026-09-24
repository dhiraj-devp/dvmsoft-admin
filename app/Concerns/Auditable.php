<?php

namespace App\Concerns;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            app(AuditLogger::class)->record(
                action: 'created',
                module: $model->auditModule(),
                auditable: $model,
                newValues: $model->auditValues(),
            );
        });

        static::updated(function (Model $model): void {
            $old = $model->auditValues($model->getOriginal());
            $new = $model->auditValues($model->getChanges());

            if ($old === [] && $new === []) {
                return;
            }

            app(AuditLogger::class)->record(
                action: 'updated',
                module: $model->auditModule(),
                auditable: $model,
                oldValues: $old,
                newValues: $new,
            );
        });

        static::deleted(function (Model $model): void {
            $forceDeleting = method_exists($model, 'isForceDeleting') && $model->isForceDeleting();

            app(AuditLogger::class)->record(
                action: $forceDeleting ? 'force_deleted' : 'deleted',
                module: $model->auditModule(),
                auditable: $model,
                oldValues: $model->auditValues(),
            );
        });
    }

    public function auditModule(): string
    {
        return property_exists($this, 'auditModule')
            ? $this->auditModule
            : strtolower(class_basename($this));
    }

    /**
     * @param  array<string, mixed>|null  $source
     * @return array<string, mixed>
     */
    public function auditValues(?array $source = null): array
    {
        $values = $source ?? $this->attributesToArray();
        $hidden = array_merge($this->getHidden(), config('dvmsoft.audit_hidden', []));

        return collect($values)
            ->except($hidden)
            ->all();
    }
}
