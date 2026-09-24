@extends('layouts.app')

@section('content')
    <x-page-header title="New quotation" description="Build line items with tax and discount." :breadcrumbs="['Sales' => route('sales.dashboard'), 'Quotations' => route('quotations.index'), 'Create' => null]" />
    <div class="space-y-6">
        @can('ai.quotations.use')
            <livewire:ai.quotation-panel :lead-id="request('lead_id')" :client-id="request('client_id')" :can-apply-to-form="true" />
        @endcan
        <livewire:quotations.form />
    </div>
@endsection
