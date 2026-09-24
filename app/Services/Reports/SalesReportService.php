<?php

namespace App\Services\Reports;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\User;
use App\Support\ReportPeriod;

class SalesReportService
{
    /**
     * @return array<string, mixed>
     */
    public function build(ReportPeriod $period): array
    {
        $created = Lead::query();
        $period->apply($created, 'created_at');

        $leadsByStatus = collect(LeadStatus::cases())->map(fn (LeadStatus $status) => [
            'label' => $status->label(),
            'value' => (clone $created)->where('status', $status->value)->count(),
        ])->all();

        $createdCount = (clone $created)->count();
        $wonInPeriod = Lead::query()->where('status', LeadStatus::Won->value)->where(function ($query) use ($period) {
            $query->whereBetween('converted_at', [$period->from->toDateTimeString(), $period->to->toDateTimeString()])
                ->orWhere(function ($inner) use ($period) {
                    $inner->whereNull('converted_at')
                        ->whereBetween('created_at', [$period->from->toDateTimeString(), $period->to->toDateTimeString()]);
                });
        });
        $wonCount = (clone $wonInPeriod)->count();
        $wonRevenue = (float) (clone $wonInPeriod)->sum('estimated_value');

        $lostInPeriod = Lead::query()->where('status', LeadStatus::Lost->value);
        $period->apply($lostInPeriod, 'created_at');
        $lostCount = (clone $lostInPeriod)->count();
        $lostValue = (float) (clone $lostInPeriod)->sum('estimated_value');

        $convertedCount = Lead::query()->whereNotNull('converted_at');
        $period->apply($convertedCount, 'converted_at');
        $converted = $convertedCount->count();
        $conversionRate = $createdCount > 0 ? round(($converted / $createdCount) * 100, 1) : 0.0;

        $pipelineQuery = Lead::query()->open();
        $pipelineValue = (float) (clone $pipelineQuery)->sum('estimated_value');
        $pipelineCount = (clone $pipelineQuery)->count();

        $sources = collect(config('crm.sources', []))
            ->map(fn (string $label, string $key) => [
                'label' => $label,
                'value' => (clone $created)->where('source', $key)->count(),
                'amount' => (float) (clone $created)->where('source', $key)->sum('estimated_value'),
            ])
            ->values()
            ->all();

        $quotationsSent = Quotation::query()->whereNotNull('sent_at');
        $period->apply($quotationsSent, 'sent_at');
        $quotationsAccepted = Quotation::query()->whereNotNull('accepted_at');
        $period->apply($quotationsAccepted, 'accepted_at');
        $quotationsRejected = Quotation::query()->whereNotNull('rejected_at');
        $period->apply($quotationsRejected, 'rejected_at');

        $sentCount = $quotationsSent->count();
        $acceptedCount = $quotationsAccepted->count();
        $rejectedCount = $quotationsRejected->count();

        $performance = User::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (User $user) use ($period) {
                $assigned = Lead::query()->where('assigned_user_id', $user->id);
                $period->apply($assigned, 'created_at');
                $won = Lead::query()->where('assigned_user_id', $user->id)->where('status', LeadStatus::Won->value)->where(function ($query) use ($period) {
                    $query->whereBetween('converted_at', [$period->from->toDateTimeString(), $period->to->toDateTimeString()])
                        ->orWhere(function ($inner) use ($period) {
                            $inner->whereNull('converted_at')
                                ->whereBetween('created_at', [$period->from->toDateTimeString(), $period->to->toDateTimeString()]);
                        });
                });
                $quotes = Quotation::query()->where('created_by_id', $user->id)->whereNotNull('sent_at');
                $period->apply($quotes, 'sent_at');

                $leadsCreated = (clone $assigned)->count();
                $wonCount = (clone $won)->count();
                $wonValue = (float) (clone $won)->sum('estimated_value');
                $quotesSent = $quotes->count();

                if ($leadsCreated === 0 && $wonCount === 0 && $quotesSent === 0) {
                    return null;
                }

                return [
                    'user' => $user->name,
                    'leads' => $leadsCreated,
                    'won' => $wonCount,
                    'won_value' => $wonValue,
                    'quotations_sent' => $quotesSent,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'metrics' => [
                ['label' => 'Leads created', 'value' => $createdCount, 'hint' => 'In selected period', 'icon' => 'briefcase'],
                ['label' => 'Conversion rate', 'value' => $conversionRate.'%', 'hint' => 'Converted ÷ created', 'icon' => 'chart'],
                ['label' => 'Pipeline value', 'value' => money($pipelineValue), 'hint' => $pipelineCount.' open opportunities', 'icon' => 'currency'],
                ['label' => 'Won revenue', 'value' => money($wonRevenue), 'hint' => $wonCount.' won in period', 'icon' => 'check'],
                ['label' => 'Lost opportunities', 'value' => $lostCount, 'hint' => money($lostValue).' estimated', 'icon' => 'alert'],
                ['label' => 'Quotations sent', 'value' => $sentCount, 'hint' => 'Accepted '.$acceptedCount.' · rejected '.$rejectedCount, 'icon' => 'document'],
            ],
            'charts' => [
                ['title' => 'Leads by status', 'items' => $this->withDisplay($leadsByStatus)],
                ['title' => 'Sales by source', 'items' => collect($sources)->map(fn (array $row) => [
                    'label' => $row['label'],
                    'value' => $row['value'],
                    'display' => $row['value'].' · '.money($row['amount']),
                ])->all()],
                ['title' => 'Quotations', 'items' => $this->withDisplay([
                    ['label' => 'Sent', 'value' => $sentCount],
                    ['label' => 'Accepted', 'value' => $acceptedCount],
                    ['label' => 'Rejected', 'value' => $rejectedCount],
                ])],
            ],
            'tables' => [
                [
                    'title' => 'Sales performance by user',
                    'headers' => ['User', 'Leads created', 'Won', 'Won value', 'Quotations sent'],
                    'rows' => collect($performance)->map(fn (array $row) => [
                        $row['user'],
                        $row['leads'],
                        $row['won'],
                        money($row['won_value']),
                        $row['quotations_sent'],
                    ])->all(),
                    'export' => collect($performance)->map(fn (array $row) => [
                        $row['user'],
                        $row['leads'],
                        $row['won'],
                        $row['won_value'],
                        $row['quotations_sent'],
                    ])->all(),
                ],
                [
                    'title' => 'Sales by source',
                    'headers' => ['Source', 'Leads', 'Estimated value'],
                    'rows' => collect($sources)->map(fn (array $row) => [$row['label'], $row['value'], money($row['amount'])])->all(),
                    'export' => collect($sources)->map(fn (array $row) => [$row['label'], $row['value'], $row['amount']])->all(),
                ],
            ],
            'kpis' => [
                'leads' => $createdCount,
                'pipeline_value' => $pipelineValue,
                'won_revenue' => $wonRevenue,
                'conversion_rate' => $conversionRate,
            ],
        ];
    }

    /**
     * @param  list<array{label: string, value: int|float}>  $items
     * @return list<array{label: string, value: int|float, display: string}>
     */
    protected function withDisplay(array $items): array
    {
        return collect($items)->map(fn (array $item) => $item + ['display' => (string) $item['value']])->all();
    }
}
