@extends('layouts.app')

@section('content')
    <x-page-header title="Edit {{ $quotation->number }}" :breadcrumbs="['Sales' => route('sales.dashboard'), 'Quotations' => route('quotations.index'), $quotation->number => route('quotations.show', $quotation), 'Edit' => null]" />
    <div class="space-y-6">
        @can('ai.quotations.use')
            <livewire:ai.quotation-panel :quotation-id="$quotation->id" :lead-id="$quotation->lead_id" :client-id="$quotation->client_id" :can-apply-to-form="true" :key="'quote-ai-'.$quotation->id" />
        @endcan
        <livewire:quotations.form :quotation="$quotation" :key="$quotation->id" />
    </div>
@endsection
