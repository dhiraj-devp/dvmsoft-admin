@extends('layouts.app')

@section('content')
    <x-page-header title="{{ $quotation->number }}" description="{{ $quotation->title }}" :breadcrumbs="['Sales' => route('sales.dashboard'), 'Quotations' => route('quotations.index'), $quotation->number => null]">
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('quotations.pdf', $quotation) }}" class="btn-secondary">Download PDF</a>
                @can('update', $quotation)
                    <a href="{{ route('quotations.edit', $quotation) }}" class="btn-secondary">Edit</a>
                @endcan
                @can('send', $quotation)
                    <form method="POST" action="{{ route('quotations.send', $quotation) }}">@csrf<button class="btn-primary">Mark sent</button></form>
                @endcan
                @can('approve', $quotation)
                    <form method="POST" action="{{ route('quotations.accept', $quotation) }}">@csrf<button class="btn-primary">Accept</button></form>
                    <form method="POST" action="{{ route('quotations.reject', $quotation) }}">@csrf<button class="btn-danger">Reject</button></form>
                @endcan
                @can('convert', $quotation)
                    <form method="POST" action="{{ route('quotations.convert', $quotation) }}">@csrf<button class="btn-primary">Convert</button></form>
                @endcan
                @if ($quotation->project)
                    <a href="{{ route('projects.show', $quotation->project) }}" class="btn-secondary">Open project {{ $quotation->project->number }}</a>
                @elseif ($quotation->status === \App\Enums\QuotationStatus::Converted)
                    @can('createProject', $quotation)
                        <form method="POST" action="{{ route('quotations.project', $quotation) }}">@csrf<button class="btn-primary">Create project</button></form>
                    @endcan
                @endif
            </div>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                <div>
                    <p class="text-sm text-ink-500">Client</p>
                    <a href="{{ route('clients.show', $quotation->client) }}" class="font-semibold text-brand-700">{{ $quotation->client->name }}</a>
                </div>
                <x-badge :tone="$quotation->status->tone()">{{ $quotation->status->label() }}</x-badge>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-ink-50 text-xs uppercase text-ink-500 dark:bg-ink-950">
                        <tr>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Unit</th>
                            <th class="px-4 py-3">Disc %</th>
                            <th class="px-4 py-3">Tax %</th>
                            <th class="px-4 py-3 text-right">Line total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                        @foreach ($quotation->items as $item)
                            <tr>
                                <td class="px-4 py-3">{{ $item->description }}</td>
                                <td class="px-4 py-3">{{ $item->quantity }}</td>
                                <td class="px-4 py-3">{{ money($item->unit_price) }}</td>
                                <td class="px-4 py-3">{{ $item->discount_percent }}%</td>
                                <td class="px-4 py-3">{{ $item->tax_percent }}%</td>
                                <td class="px-4 py-3 text-right font-medium">{{ money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="space-y-2 border-t border-ink-100 px-5 py-4 text-sm dark:border-ink-800">
                <div class="flex justify-between"><span class="text-ink-500">Subtotal</span><span>{{ money($quotation->subtotal) }}</span></div>
                <div class="flex justify-between"><span class="text-ink-500">Discount</span><span>- {{ money($quotation->discount_amount) }}</span></div>
                <div class="flex justify-between"><span class="text-ink-500">Tax / GST</span><span>{{ money($quotation->tax_amount) }}</span></div>
                <div class="flex justify-between text-base font-semibold"><span>Total</span><span>{{ money($quotation->total) }}</span></div>
            </div>
        </section>
        <section class="card p-5 text-sm">
            <h2 class="font-semibold">Terms</h2>
            <dl class="mt-4 space-y-3">
                <div>
                    <dt class="text-ink-500">Payment terms</dt>
                    <dd class="font-medium">{{ config('crm.payment_terms.'.$quotation->payment_terms, $quotation->payment_terms ?: '—') }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500">Valid until</dt>
                    <dd class="font-medium">{{ $quotation->valid_until?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500">Prepared by</dt>
                    <dd class="font-medium">{{ $quotation->createdBy?->name ?: '—' }}</dd>
                </div>
            </dl>
            @if ($quotation->notes)
                <p class="mt-4 rounded-xl bg-ink-50 p-3 dark:bg-ink-950">{{ $quotation->notes }}</p>
            @endif
        </section>
        @can('ai.quotations.use')
            <div class="xl:col-span-3">
                <livewire:ai.quotation-panel :quotation-id="$quotation->id" :lead-id="$quotation->lead_id" :client-id="$quotation->client_id" :can-apply-to-form="false" :key="'quote-ai-show-'.$quotation->id" />
            </div>
        @endcan
    </div>
@endsection
