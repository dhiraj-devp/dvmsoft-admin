<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\AuditLog;
use App\Models\Document;

class DocumentMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $warning = (int) settings('documents.expiry_warning_days', 30);

        return [
            'metrics' => [
                ['label' => 'Total documents', 'value' => Document::query()->count(), 'hint' => 'Company registry', 'icon' => 'document'],
                ['label' => 'Draft', 'value' => Document::query()->where('status', DocumentStatus::Draft->value)->count(), 'hint' => 'Not yet submitted', 'icon' => 'document'],
                ['label' => 'Pending review', 'value' => Document::query()->where('status', DocumentStatus::Review->value)->count(), 'hint' => 'Awaiting approval', 'icon' => 'clock'],
                ['label' => 'Approved', 'value' => Document::query()->where('status', DocumentStatus::Approved->value)->count(), 'hint' => 'Ready to send', 'icon' => 'check'],
                ['label' => 'Expiring soon', 'value' => Document::query()->expiring($warning)->whereDate('expiry_date', '>=', now()->toDateString())->count(), 'hint' => 'Next '.$warning.' days', 'icon' => 'shield'],
            ],
            'recentUploads' => Document::query()
                ->with(['type', 'owner'])
                ->latest()
                ->limit(8)
                ->get(),
            'recentActivity' => AuditLog::query()
                ->with('user')
                ->where('module', 'documents')
                ->latest('created_at')
                ->limit(8)
                ->get(),
        ];
    }
}
