<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class NavigationService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return collect(config('navigation', []))
            ->map(fn (array $item) => $this->filterItem($item, $user))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    protected function filterItem(array $item, User $user): ?array
    {
        if (($item['enabled'] ?? true) === false) {
            return null;
        }

        if (isset($item['children'])) {
            $children = collect($item['children'])
                ->map(fn (array $child) => $this->filterItem($child, $user))
                ->filter()
                ->values()
                ->all();

            if ($children === []) {
                return null;
            }

            $item['children'] = $children;
            $item['active'] = collect($children)->contains(fn (array $child) => $child['active'] ?? false);

            return $item;
        }

        $permission = $item['permission'] ?? null;
        $route = $item['route'] ?? null;

        if ($route === 'work.team') {
            if (! $user->canViewWorkTeam()) {
                return null;
            }
        } elseif ($route === 'office.calendar') {
            if (! $user->is_super_admin) {
                return null;
            }
        } elseif ($permission && ! $user->hasPermission($permission)) {
            return null;
        }

        if ($route && ! Route::has($route)) {
            return null;
        }

        $item['active'] = match ($route) {
            'settings.index' => request()->routeIs('settings.*'),
            'office.calendar' => request()->routeIs('office.calendar'),
            'roles.index' => request()->routeIs('roles.*'),
            'sales.dashboard' => request()->routeIs('sales.*'),
            'leads.index' => request()->routeIs('leads.*'),
            'clients.index' => request()->routeIs('clients.*'),
            'contacts.index' => request()->routeIs('contacts.*'),
            'follow-ups.index' => request()->routeIs('follow-ups.*'),
            'quotations.index' => request()->routeIs('quotations.*'),
            'projects.dashboard' => request()->routeIs('projects.dashboard'),
            'projects.index' => request()->routeIs('projects.*') && ! request()->routeIs('projects.dashboard'),
            'tasks.index' => request()->routeIs('tasks.*'),
            'milestones.index' => request()->routeIs('milestones.*'),
            'finance.dashboard' => request()->routeIs('finance.dashboard'),
            'invoices.index' => request()->routeIs('invoices.*'),
            'payments.index' => request()->routeIs('payments.*'),
            'expenses.index' => request()->routeIs('expenses.*'),
            'hr.dashboard' => request()->routeIs('hr.dashboard'),
            'employees.index' => request()->routeIs('employees.*'),
            'leave.index' => request()->routeIs('leave.*'),
            'assets.index' => request()->routeIs('assets.*'),
            'onboarding.index' => request()->routeIs('onboarding.*'),
            'offboarding.index' => request()->routeIs('offboarding.*'),
            'support.dashboard' => request()->routeIs('support.dashboard'),
            'tickets.index' => request()->routeIs('tickets.*'),
            'ticket-categories.index' => request()->routeIs('ticket-categories.*'),
            'ticket-sla.index' => request()->routeIs('ticket-sla.*'),
            'documents.dashboard' => request()->routeIs('documents.dashboard'),
            'documents.index' => request()->routeIs('documents.*') && ! request()->routeIs('documents.dashboard', 'documents.expiring'),
            'documents.expiring' => request()->routeIs('documents.expiring'),
            'document-types.index' => request()->routeIs('document-types.*'),
            'reports.overview' => request()->routeIs('reports.overview'),
            'reports.sales' => request()->routeIs('reports.sales'),
            'reports.projects' => request()->routeIs('reports.projects'),
            'reports.finance' => request()->routeIs('reports.finance'),
            'reports.hr' => request()->routeIs('reports.hr'),
            'reports.support' => request()->routeIs('reports.support'),
            default => $route ? request()->routeIs($route, $route.'.*') : false,
        };
        $item['url'] = $route ? route($route) : ($item['url'] ?? '#');

        return $item;
    }
}
