<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $permissions = Permission::query()->get()->keyBy('name');
        $allPermissionIds = $permissions->pluck('id');

        foreach (config('dvmsoft.roles') as $name) {
            $role = Role::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $name.' access level',
                    'is_default' => $name === 'Employee',
                ]
            );

            $assigned = match ($name) {
                'Super Admin' => $allPermissionIds,
                'Admin' => $allPermissionIds,
                'Sales Manager' => $this->ids($permissions, [
                    'dashboard.view', 'sales.view',
                    'leads.view', 'leads.create', 'leads.edit', 'leads.delete', 'leads.convert',
                    'clients.view', 'clients.create', 'clients.edit', 'clients.delete', 'clients.manage_portal',
                    'contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete',
                    'follow_ups.view', 'follow_ups.create', 'follow_ups.edit', 'follow_ups.delete', 'follow_ups.complete',
                    'quotations.view', 'quotations.create', 'quotations.edit', 'quotations.delete', 'quotations.send', 'quotations.approve', 'quotations.convert',
                    'projects.view', 'projects.create', 'projects.stages.view',
                    'invoices.view',
                    'documents.view', 'documents.create', 'documents.edit', 'documents.upload', 'documents.download', 'documents.submit',
                    'reports.view', 'reports.sales.view', 'reports.export',
                    'ai.overview.view', 'ai.leads.use', 'ai.quotations.use',
                    ...$this->workManagePermissions(),
                ]),
                'Sales Executive' => $this->ids($permissions, [
                    'dashboard.view', 'sales.view',
                    'leads.view', 'leads.create', 'leads.edit', 'leads.convert',
                    'clients.view', 'clients.create', 'clients.edit',
                    'contacts.view', 'contacts.create', 'contacts.edit',
                    'follow_ups.view', 'follow_ups.create', 'follow_ups.edit', 'follow_ups.complete',
                    'quotations.view', 'quotations.create', 'quotations.edit', 'quotations.send',
                    'projects.view',
                    'reports.sales.view',
                    'ai.leads.use', 'ai.quotations.use',
                    ...$this->workOwnPermissions(),
                ]),
                'Project Manager' => $this->ids($permissions, [
                    'dashboard.view',
                    'clients.view', 'contacts.view', 'quotations.view',
                    'projects.view', 'projects.create', 'projects.edit', 'projects.delete', 'projects.manage_team', 'projects.upload',
                    'projects.stages.view', 'projects.stages.manage', 'projects.stages.assign', 'projects.stages.submit_review', 'projects.stages.complete',
                    'projects.stage_evidence.view', 'projects.stage_evidence.manage',
                    'projects.stage_deliverables.view', 'projects.stage_deliverables.manage',
                    'projects.stage_reviews.view', 'projects.stage_reviews.manage',
                    'projects.stage_discussion.view', 'projects.stage_discussion.create',
                    'projects.stage_approval.override',
                    'milestones.view', 'milestones.create', 'milestones.edit', 'milestones.delete',
                    'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
                    'requirements.view', 'requirements.create', 'requirements.edit', 'requirements.delete', 'requirements.approve',
                    'change_requests.view', 'change_requests.create', 'change_requests.edit', 'change_requests.delete', 'change_requests.approve',
                    'invoices.view', 'invoices.create',
                    'tickets.view', 'tickets.create', 'tickets.reply',
                    'documents.view', 'documents.create', 'documents.edit', 'documents.upload', 'documents.download', 'documents.submit', 'documents.manage_versions',
                    'reports.view', 'reports.projects.view', 'reports.export',
                    'ai.overview.view', 'ai.requirements.use', 'ai.projects.use', 'ai.quotations.use',
                    ...$this->workManagePermissions(),
                ]),
                'Developer' => $this->ids($permissions, [
                    'dashboard.view',
                    'projects.view', 'projects.edit',
                    'projects.stages.view', 'projects.stages.submit_review',
                    'projects.stage_evidence.view', 'projects.stage_evidence.manage',
                    'projects.stage_deliverables.view',
                    'projects.stage_reviews.view',
                    'projects.stage_discussion.view', 'projects.stage_discussion.create',
                    'milestones.view',
                    'tasks.view', 'tasks.create', 'tasks.edit',
                    'requirements.view',
                    'change_requests.view', 'change_requests.create',
                    'ai.requirements.use', 'ai.projects.use',
                    ...$this->workOwnPermissions(),
                ]),
                'Designer' => $this->ids($permissions, [
                    'dashboard.view',
                    'projects.view',
                    'projects.stages.view',
                    'projects.stage_evidence.view', 'projects.stage_evidence.manage',
                    'projects.stage_deliverables.view',
                    'projects.stage_discussion.view', 'projects.stage_discussion.create',
                    'milestones.view',
                    'tasks.view', 'tasks.create', 'tasks.edit',
                    'requirements.view',
                    'change_requests.view',
                    ...$this->workOwnPermissions(),
                ]),
                'QA' => $this->ids($permissions, [
                    'dashboard.view',
                    'projects.view',
                    'projects.stages.view',
                    'projects.stage_evidence.view',
                    'projects.stage_deliverables.view',
                    'projects.stage_reviews.view',
                    'projects.stage_discussion.view', 'projects.stage_discussion.create',
                    'milestones.view',
                    'tasks.view', 'tasks.edit',
                    'requirements.view',
                    'change_requests.view',
                    ...$this->workOwnPermissions(),
                ]),
                'Support Executive' => $this->ids($permissions, [
                    'dashboard.view', 'clients.view', 'contacts.view', 'projects.view',
                    'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.delete',
                    'tickets.assign', 'tickets.reply', 'tickets.internal_notes',
                    'tickets.resolve', 'tickets.close',
                    'tickets.manage_categories', 'tickets.manage_sla',
                    'support.dashboard.view',
                    'reports.view', 'reports.support.view', 'reports.export',
                    'ai.tickets.use',
                    ...$this->workOwnPermissions(),
                ]),
                'HR Manager' => $this->ids($permissions, [
                    'dashboard.view',
                    'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
                    'employee_documents.view', 'employee_documents.upload', 'employee_documents.delete',
                    'leave.view', 'leave.create', 'leave.approve', 'leave.reject',
                    'assets.view', 'assets.create', 'assets.assign', 'assets.return',
                    'onboarding.view', 'onboarding.manage',
                    'offboarding.view', 'offboarding.manage',
                    'hr.dashboard.view',
                    'users.view', 'users.create', 'users.edit',
                    'documents.view', 'documents.create', 'documents.edit', 'documents.upload', 'documents.download', 'documents.submit', 'documents.approve', 'documents.archive', 'documents.manage_versions',
                    'reports.view', 'reports.hr.view', 'reports.export',
                    ...$this->workManagePermissions(),
                ]),
                'Accountant' => $this->ids($permissions, [
                    'dashboard.view',
                    'clients.view', 'contacts.view', 'quotations.view', 'projects.view',
                    'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete', 'invoices.send',
                    'payments.view', 'payments.create', 'payments.edit', 'payments.delete',
                    'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
                    'finance.dashboard.view', 'finance.reports.view',
                    'documents.view', 'documents.create', 'documents.edit', 'documents.upload', 'documents.download', 'documents.submit',
                    'reports.view', 'reports.finance.view', 'reports.export',
                    'ai.overview.view', 'ai.finance.use',
                    ...$this->workOwnPermissions(),
                ]),
                default => $this->ids($permissions, $this->workOwnPermissions()),
            };

            $role->permissions()->sync($assigned);
        }
    }

    /**
     * @return list<string>
     */
    protected function workOwnPermissions(): array
    {
        return [
            'work.my.view',
            'work.my.manage',
        ];
    }

    /**
     * @return list<string>
     */
    protected function workManagePermissions(): array
    {
        return array_merge($this->workOwnPermissions(), [
            'work.goals.manage',
            'work.team.view',
            'work.reviews.view',
            'work.reviews.manage',
            'work.reports.view',
        ]);
    }

    /**
     * @param  Collection<string, Permission>  $permissions
     * @param  list<string>  $names
     * @return Collection<int, string>
     */
    protected function ids($permissions, array $names)
    {
        return collect($names)
            ->map(fn (string $name) => $permissions->get($name)?->id)
            ->filter()
            ->values();
    }
}
