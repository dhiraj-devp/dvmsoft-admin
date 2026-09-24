@extends('layouts.client')

@section('content')
    <x-page-header title="{{ $quotation->number }}" :description="$quotation->title" :breadcrumbs="['Quotations' => route('client.quotations.index'), $quotation->number => null]">
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('client.quotations.pdf', $quotation) }}" class="btn-secondary">Download PDF</a>
                @if ($canDecide)
                    <form id="accept-quotation" method="POST" action="{{ route('client.quotations.accept', $quotation) }}">@csrf</form>
                    <form id="reject-quotation" method="POST" action="{{ route('client.quotations.reject', $quotation) }}">@csrf</form>
                    <button type="button" class="btn-primary" @click="openConfirm({ title: 'Accept quotation?', message: 'This records your acceptance in the quotation workflow.', form: 'accept-quotation' })">Accept</button>
                    <button type="button" class="btn-danger" @click="openConfirm({ title: 'Decline quotation?', message: 'This records your rejection in the quotation workflow.', form: 'reject-quotation' })">Decline</button>
                @endif
            </div>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                <p class="text-sm text-ink-500">{{ $quotation->payment_terms }}</p>
                <x-badge :tone="$quotation->status->tone()">{{ $quotation->status->label() }}</x-badge>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-ink-500">
                    <tr>
                        <th class="px-5 py-3">Item</th>
                        <th class="px-5 py-3">Qty</th>
                        <th class="px-5 py-3">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotation->items as $item)
                        <tr class="border-t border-ink-50 dark:border-ink-800">
                            <td class="px-5 py-3">{{ $item->description }}</td>
                            <td class="px-5 py-3">{{ $item->quantity }}</td>
                            <td class="px-5 py-3">{{ money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        <section class="card p-5 text-sm">
            <h2 class="font-semibold">Summary</h2>
            <dl class="mt-4 space-y-2">
                <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ money($quotation->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt>Discount</dt><dd>{{ money($quotation->discount_amount) }}</dd></div>
                <div class="flex justify-between"><dt>Tax</dt><dd>{{ money($quotation->tax_amount) }}</dd></div>
                <div class="flex justify-between font-semibold"><dt>Total</dt><dd>{{ money($quotation->total) }}</dd></div>
                <div class="flex justify-between"><dt>Valid until</dt><dd>{{ $quotation->valid_until?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</dd></div>
            </dl>
            @if ($quotation->notes)
                <p class="mt-4 whitespace-pre-wrap text-ink-600">{{ $quotation->notes }}</p>
            @endif
        </section>
    </div>
@endsection
