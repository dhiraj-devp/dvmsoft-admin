<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Services\NavigationService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

#[Fillable([
    'department_id',
    'manager_id',
    'name',
    'email',
    'password',
    'phone',
    'employee_code',
    'job_title',
    'avatar_path',
    'date_of_joining',
    'is_active',
    'is_super_admin',
    'theme',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasUlids, Notifiable, SoftDeletes;

    protected string $auditModule = 'users';

    /**
     * @var list<string>
     */
    protected array $permissionCache = [];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_joining' => 'date',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function canViewWorkTeam(): bool
    {
        return $this->hasPermission('work.team.view')
            || $this->reports()->exists();
    }

    /**
     * @return Builder<User>|HasMany<User, $this>
     */
    public function workTeamMembers(): Builder|HasMany
    {
        if ($this->hasPermission('work.team.view')) {
            return static::query()->active();
        }

        return $this->reports()->active();
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->permissionNames()->contains($permission);
    }

    public function homeRoute(): string
    {
        if ($this->hasPermission('dashboard.view') && Route::has('dashboard')) {
            return 'dashboard';
        }

        foreach (app(NavigationService::class)->for($this) as $item) {
            if (! empty($item['route']) && Route::has($item['route'])) {
                return $item['route'];
            }

            foreach ($item['children'] ?? [] as $child) {
                if (! empty($child['route']) && Route::has($child['route'])) {
                    return $child['route'];
                }
            }
        }

        return Route::has('work.my') ? 'work.my' : 'login';
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->permissionNames()->intersect($permissions)->isNotEmpty();
    }

    /**
     * @return Collection<int, string>
     */
    public function permissionNames(): Collection
    {
        if ($this->permissionCache !== []) {
            return collect($this->permissionCache);
        }

        $this->permissionCache = $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();

        return collect($this->permissionCache);
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
