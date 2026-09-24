@extends('layouts.app')

@section('content')
    <x-page-header title="{{ $client->name }}" description="{{ $client->type->label() }}" :breadcrumbs="['Sales' => route('sales.dashboard'), 'Clients' => route('clients.index'), $client->name => null]">
        <x-slot:actions>
            @can('quotations.create')
                <a href="{{ route('quotations.create', ['client_id' => $client->id]) }}" class="btn-primary">New quotation</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-ink-500">{{ $client->email ?: 'No email' }} · {{ $client->phone ?: 'No phone' }}</p>
                        <p class="mt-2 text-sm">{{ $client->address ?: 'No address on file.' }}</p>
                    </div>
                    <x-badge :tone="$client->status->tone()">{{ $client->status->label() }}</x-badge>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-3 text-sm">
                    <div>
                        <dt class="text-ink-500">Website</dt>
                        <dd class="font-medium">{{ $client->website ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">GST</dt>
                        <dd class="font-medium">{{ $client->gst_number ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">PAN</dt>
                        <dd class="font-medium">{{ $client->pan_number ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Account manager</dt>
                        <dd class="font-medium">{{ $client->accountManager?->name ?: 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">From lead</dt>
                        <dd class="font-medium">
                            @if ($client->convertedFromLead)
                                <a class="text-brand-700" href="{{ route('leads.show', $client->convertedFromLead) }}">{{ $client->convertedFromLead->name }}</a>
                            @else
                                Direct
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="card p-5">
                <h2 class="mb-4 font-semibold">Contacts</h2>
                <livewire:contacts.index :client-id="$client->id" :key="'client-contacts-'.$client->id" />
            </section>

            <section class="card p-5">
                <h2 class="mb-4 font-semibold">Follow-ups</h2>
                <livewire:follow-ups.index :subject-type="App\Models\Client::class" :subject-id="$client->id" :key="'client-followups-'.$client->id" />
            </section>
        </div>

        <div class="space-y-6">
            <section class="card p-5">
                <h2 class="mb-4 font-semibold">Portal users</h2>
                @can('managePortal', $client)
                    <livewire:client-portal-users.index :client-id="$client->id" :key="'client-portal-users-'.$client->id" />
                @else
                    <p class="text-sm text-ink-500">You do not have permission to manage portal users.</p>
                @endcan
            </section>
            <section class="card p-5">
                <h2 class="mb-4 font-semibold">Projects</h2>
                @can('viewAny', App\Models\Project::class)
                    @forelse ($client->projects as $project)
                        <a href="{{ route('projects.show', $project) }}" class="mb-3 flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 text-sm dark:border-ink-800">
                            <span>{{ $project->number }}</span>
                            <x-badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-badge>
                        </a>
                    @empty
                        <p class="text-sm text-ink-500">No projects yet.</p>
                    @endforelse
                @else
                    <p class="text-sm text-ink-500">You do not have access to projects.</p>
                @endcan
            </section>
            <section class="card p-5">
                <h2 class="mb-4 font-semibold">Quotations</h2>
                @forelse ($client->quotations as $quotation)
                    <a href="{{ route('quotations.show', $quotation) }}" class="mb-3 flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 text-sm dark:border-ink-800">
                        <span>{{ $quotation->number }}</span>
                        <x-badge :tone="$quotation->status->tone()">{{ $quotation->status->label() }}</x-badge>
                    </a>
                @empty
                    <p class="text-sm text-ink-500">No quotations yet.</p>
                @endforelse
            </section>
            <section class="card p-5">
                <h2 class="mb-4 font-semibold">Activity</h2>
                <livewire:crm.timeline :subject-type="App\Models\Client::class" :subject-id="$client->id" :key="'client-timeline-'.$client->id" />
            </section>
        </div>
    </div>
@endsection
