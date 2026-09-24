@extends('layouts.app')

@section('content')
    <x-page-header title="{{ $lead->name }}" description="{{ $lead->company ?: 'Individual lead' }}" :breadcrumbs="['Sales' => route('sales.dashboard'), 'Leads' => route('leads.index'), $lead->name => null]">
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                @can('convert', $lead)
                    @if (! $lead->isConverted() && $lead->status !== \App\Enums\LeadStatus::Lost)
                        <form method="POST" action="{{ route('leads.convert', $lead) }}">
                            @csrf
                            <button type="submit" class="btn-primary">Convert to client</button>
                        </form>
                    @endif
                @endcan
                @can('quotations.create')
                    @if ($lead->converted_client_id)
                        <a href="{{ route('quotations.create', ['client_id' => $lead->converted_client_id, 'lead_id' => $lead->id]) }}" class="btn-secondary">New quotation</a>
                    @endif
                @endcan
            </div>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-ink-500">{{ $lead->email ?: 'No email' }} · {{ $lead->phone ?: 'No phone' }}</p>
                        <p class="mt-2 text-sm">{{ $lead->requirement ?: 'No requirement captured yet.' }}</p>
                    </div>
                    <div class="flex gap-2">
                        <x-badge :tone="$lead->status->tone()">{{ $lead->status->label() }}</x-badge>
                        <x-badge :tone="$lead->priority->tone()">{{ $lead->priority->label() }}</x-badge>
                    </div>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-3 text-sm">
                    <div>
                        <dt class="text-ink-500">Source</dt>
                        <dd class="font-medium">{{ config('crm.sources.'.$lead->source, $lead->source ?: '—') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Estimated value</dt>
                        <dd class="font-medium">{{ $lead->estimated_value ? money($lead->estimated_value) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Owner</dt>
                        <dd class="font-medium">{{ $lead->assignedUser?->name ?: 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Next follow-up</dt>
                        <dd class="font-medium">{{ $lead->next_follow_up_at?->format(settings('company.date_format', 'd M Y').' H:i') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Client</dt>
                        <dd class="font-medium">
                            @if ($lead->convertedClient)
                                <a class="text-brand-700" href="{{ route('clients.show', $lead->convertedClient) }}">{{ $lead->convertedClient->name }}</a>
                            @else
                                Not converted
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Lost reason</dt>
                        <dd class="font-medium">{{ $lead->lost_reason ?: '—' }}</dd>
                    </div>
                </dl>
                @if ($lead->notes)
                    <p class="mt-4 rounded-xl bg-ink-50 p-3 text-sm dark:bg-ink-950">{{ $lead->notes }}</p>
                @endif
            </section>

            <section class="card p-5">
                <h2 class="mb-4 font-semibold">Follow-ups</h2>
                <livewire:follow-ups.index :subject-type="App\Models\Lead::class" :subject-id="$lead->id" :key="'lead-followups-'.$lead->id" />
            </section>
        </div>

        <div class="space-y-6">
            @can('ai.leads.use')
                <livewire:ai.lead-panel :lead-id="$lead->id" :key="'lead-ai-'.$lead->id" />
            @endcan
            <section class="card p-5">
                <h2 class="mb-4 font-semibold">Activity history</h2>
                <livewire:crm.timeline :subject-type="App\Models\Lead::class" :subject-id="$lead->id" :key="'lead-timeline-'.$lead->id" />
            </section>
        </div>
    </div>
@endsection
