<?php

return [
    'employment_types' => [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'contract' => 'Contract',
        'intern' => 'Intern',
    ],
    'document_types' => [
        'offer_letter' => 'Offer letter',
        'employment_agreement' => 'Employment agreement',
        'nda' => 'NDA',
        'ip_assignment' => 'IP assignment',
        'id_document' => 'ID documents',
        'other' => 'Other documents',
    ],
    'required_document_types' => [
        'offer_letter',
        'employment_agreement',
        'nda',
        'ip_assignment',
        'id_document',
    ],
    'leave_types' => [
        ['name' => 'Annual leave', 'code' => 'annual', 'days_per_year' => 18],
        ['name' => 'Casual leave', 'code' => 'casual', 'days_per_year' => 6],
        ['name' => 'Sick leave', 'code' => 'sick', 'days_per_year' => 6],
        ['name' => 'Unpaid leave', 'code' => 'unpaid', 'days_per_year' => 0],
    ],
    'onboarding_items' => [
        ['key' => 'account_created', 'label' => 'Account created'],
        ['key' => 'documents_collected', 'label' => 'Documents collected'],
        ['key' => 'nda_signed', 'label' => 'NDA signed'],
        ['key' => 'employment_agreement_signed', 'label' => 'Employment agreement signed'],
        ['key' => 'assets_assigned', 'label' => 'Assets assigned'],
        ['key' => 'access_granted', 'label' => 'Access granted'],
        ['key' => 'orientation_completed', 'label' => 'Orientation completed'],
    ],
    'offboarding_items' => [
        ['key' => 'exit_recorded', 'label' => 'Resignation/exit recorded'],
        ['key' => 'assets_returned', 'label' => 'Assets returned'],
        ['key' => 'documents_completed', 'label' => 'Documents completed'],
        ['key' => 'access_revoked', 'label' => 'Access revoked'],
        ['key' => 'final_clearance', 'label' => 'Final clearance'],
        ['key' => 'exit_completed', 'label' => 'Exit completed'],
    ],
    'asset_conditions' => [
        'new' => 'New',
        'good' => 'Good',
        'fair' => 'Fair',
        'poor' => 'Poor',
        'damaged' => 'Damaged',
    ],
];
