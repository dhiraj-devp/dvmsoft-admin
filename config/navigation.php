<?php

/**
 * Permission-aware application navigation.
 *
 * Future modules stay in this file with enabled=false so the sidebar
 * architecture is ready without exposing unfinished features.
 */
return [
    [
        'label' => 'Dashboard',
        'icon' => 'home',
        'route' => 'dashboard',
        'permission' => 'dashboard.view',
        'enabled' => true,
    ],
    [
        'label' => 'Sales',
        'icon' => 'briefcase',
        'enabled' => true,
        'children' => [
            ['label' => 'Overview', 'route' => 'sales.dashboard', 'permission' => 'sales.view', 'enabled' => true],
            ['label' => 'Leads', 'route' => 'leads.index', 'permission' => 'leads.view', 'enabled' => true],
            ['label' => 'Clients', 'route' => 'clients.index', 'permission' => 'clients.view', 'enabled' => true],
            ['label' => 'Contacts', 'route' => 'contacts.index', 'permission' => 'contacts.view', 'enabled' => true],
            ['label' => 'Follow-ups', 'route' => 'follow-ups.index', 'permission' => 'follow_ups.view', 'enabled' => true],
            ['label' => 'Quotations', 'route' => 'quotations.index', 'permission' => 'quotations.view', 'enabled' => true],
        ],
    ],
    [
        'label' => 'Projects',
        'icon' => 'folder',
        'enabled' => true,
        'children' => [
            ['label' => 'Overview', 'route' => 'projects.dashboard', 'permission' => 'projects.view', 'enabled' => true],
            ['label' => 'All Projects', 'route' => 'projects.index', 'permission' => 'projects.view', 'enabled' => true],
            ['label' => 'My Tasks', 'route' => 'tasks.index', 'permission' => 'tasks.view', 'enabled' => true],
            ['label' => 'Milestones', 'route' => 'milestones.index', 'permission' => 'milestones.view', 'enabled' => true],
        ],
    ],
    [
        'label' => 'Finance',
        'icon' => 'currency',
        'enabled' => true,
        'children' => [
            ['label' => 'Overview', 'route' => 'finance.dashboard', 'permission' => 'finance.dashboard.view', 'enabled' => true],
            ['label' => 'Invoices', 'route' => 'invoices.index', 'permission' => 'invoices.view', 'enabled' => true],
            ['label' => 'Payments', 'route' => 'payments.index', 'permission' => 'payments.view', 'enabled' => true],
            ['label' => 'Expenses', 'route' => 'expenses.index', 'permission' => 'expenses.view', 'enabled' => true],
            ['label' => 'Outstanding', 'route' => 'finance.outstanding', 'permission' => 'invoices.view', 'enabled' => true],
            ['label' => 'Reports', 'route' => 'finance.reports', 'permission' => 'finance.reports.view', 'enabled' => true],
        ],
    ],
    [
        'label' => 'Work',
        'icon' => 'clipboard',
        'enabled' => true,
        'children' => [
            ['label' => 'Daily progress', 'route' => 'work.my', 'permission' => 'work.my.view', 'enabled' => true],
            ['label' => 'Team progress', 'route' => 'work.team', 'permission' => 'work.team.view', 'enabled' => true],
        ],
    ],
    [
        'label' => 'HR',
        'icon' => 'users',
        'enabled' => true,
        'children' => [
            ['label' => 'Overview', 'route' => 'hr.dashboard', 'permission' => 'hr.dashboard.view', 'enabled' => true],
            ['label' => 'Employees', 'route' => 'employees.index', 'permission' => 'employees.view', 'enabled' => true],
            ['label' => 'Leave', 'route' => 'leave.index', 'permission' => 'leave.view', 'enabled' => true],
            ['label' => 'Assets', 'route' => 'assets.index', 'permission' => 'assets.view', 'enabled' => true],
            ['label' => 'Onboarding', 'route' => 'onboarding.index', 'permission' => 'onboarding.view', 'enabled' => true],
            ['label' => 'Offboarding', 'route' => 'offboarding.index', 'permission' => 'offboarding.view', 'enabled' => true],
        ],
    ],
    [
        'label' => 'Support',
        'icon' => 'lifebuoy',
        'enabled' => true,
        'children' => [
            ['label' => 'Overview', 'route' => 'support.dashboard', 'permission' => 'support.dashboard.view', 'enabled' => true],
            ['label' => 'Tickets', 'route' => 'tickets.index', 'permission' => 'tickets.view', 'enabled' => true],
            ['label' => 'Categories', 'route' => 'ticket-categories.index', 'permission' => 'tickets.manage_categories', 'enabled' => true],
            ['label' => 'SLA', 'route' => 'ticket-sla.index', 'permission' => 'tickets.manage_sla', 'enabled' => true],
        ],
    ],
    [
        'label' => 'Documents',
        'icon' => 'document',
        'enabled' => true,
        'children' => [
            ['label' => 'Overview', 'route' => 'documents.dashboard', 'permission' => 'documents.view', 'enabled' => true],
            ['label' => 'All Documents', 'route' => 'documents.index', 'permission' => 'documents.view', 'enabled' => true],
            ['label' => 'Document Types', 'route' => 'document-types.index', 'permission' => 'documents.manage_types', 'enabled' => true],
            ['label' => 'Expiring', 'route' => 'documents.expiring', 'permission' => 'documents.view', 'enabled' => true],
        ],
    ],
    [
        'label' => 'Reports',
        'icon' => 'chart',
        'enabled' => true,
        'children' => [
            ['label' => 'Overview', 'route' => 'reports.overview', 'permission' => 'reports.view', 'enabled' => true],
            ['label' => 'Sales', 'route' => 'reports.sales', 'permission' => 'reports.sales.view', 'enabled' => true],
            ['label' => 'Projects', 'route' => 'reports.projects', 'permission' => 'reports.projects.view', 'enabled' => true],
            ['label' => 'Finance', 'route' => 'reports.finance', 'permission' => 'reports.finance.view', 'enabled' => true],
            ['label' => 'HR', 'route' => 'reports.hr', 'permission' => 'reports.hr.view', 'enabled' => true],
            ['label' => 'Support', 'route' => 'reports.support', 'permission' => 'reports.support.view', 'enabled' => true],
        ],
    ],
    [
        'label' => 'AI',
        'icon' => 'sparkles',
        'enabled' => true,
        'children' => [
            ['label' => 'Company risks', 'route' => 'ai.overview', 'permission' => 'ai.overview.view', 'enabled' => true],
            ['label' => 'Finance insights', 'route' => 'ai.finance', 'permission' => 'ai.finance.use', 'enabled' => true],
        ],
    ],
    [
        'label' => 'Administration',
        'icon' => 'cog',
        'enabled' => true,
        'children' => [
            ['label' => 'Users', 'route' => 'users.index', 'permission' => 'users.view', 'enabled' => true],
            ['label' => 'Roles & Permissions', 'route' => 'roles.index', 'permission' => 'roles.view', 'enabled' => true],
            ['label' => 'Permissions', 'route' => 'permissions.index', 'permission' => 'permissions.view', 'enabled' => true],
            ['label' => 'Audit Logs', 'route' => 'audit-logs.index', 'permission' => 'audit_logs.view', 'enabled' => true],
            ['label' => 'Automations', 'route' => 'automations.index', 'permission' => 'automations.view', 'enabled' => true],
            ['label' => 'Settings', 'route' => 'settings.index', 'permission' => 'settings.view', 'enabled' => true],
            ['label' => 'Office calendar', 'route' => 'office.calendar', 'enabled' => true],
        ],
    ],
];
