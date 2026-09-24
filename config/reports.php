<?php

return [
    'sections' => [
        'overview' => [
            'label' => 'Overview',
            'permission' => 'reports.view',
            'route' => 'reports.overview',
        ],
        'sales' => [
            'label' => 'Sales',
            'permission' => 'reports.sales.view',
            'route' => 'reports.sales',
        ],
        'projects' => [
            'label' => 'Projects',
            'permission' => 'reports.projects.view',
            'route' => 'reports.projects',
        ],
        'finance' => [
            'label' => 'Finance',
            'permission' => 'reports.finance.view',
            'route' => 'reports.finance',
        ],
        'hr' => [
            'label' => 'HR',
            'permission' => 'reports.hr.view',
            'route' => 'reports.hr',
        ],
        'support' => [
            'label' => 'Support',
            'permission' => 'reports.support.view',
            'route' => 'reports.support',
        ],
    ],
];
