<?php

return [
    'categories' => [
        ['name' => 'General', 'slug' => 'general'],
        ['name' => 'Billing', 'slug' => 'billing'],
        ['name' => 'Technical', 'slug' => 'technical'],
        ['name' => 'Project delivery', 'slug' => 'project-delivery'],
        ['name' => 'Access / accounts', 'slug' => 'access'],
    ],
    'sla' => [
        'low' => ['hours' => 72, 'warning_hours' => 12],
        'normal' => ['hours' => 24, 'warning_hours' => 4],
        'high' => ['hours' => 8, 'warning_hours' => 2],
        'urgent' => ['hours' => 2, 'warning_hours' => 1],
    ],
];
