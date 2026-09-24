@extends('layouts.client')

@section('content')
    <x-page-header title="Support tickets" description="Raise and follow issues for your account.">
        <x-slot:actions>
            <a href="{{ route('client.tickets.create') }}" class="btn-primary">New ticket</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input type="search" name="q" value="{{ $search }}" placeholder="Search tickets" class="input max-w-xs">
        <select name="status" class="input max-w-xs">
            <option value="">All statuses</option>
            @foreach (\App\Enums\TicketStatus::cases() as $item)
                <option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>
            @endforeach
        </select>
        <button class="btn-secondary">Filter</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 text-xs uppercase text-ink-500 dark:border-ink-800">
                <tr>
                    <th class="px-5 py-3">Ticket</th>
                    <th class="px-5 py-3">Priority</th>
                    <th class="px-5 py-3">SLA</th>
                    <th class="px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr class="border-b border-ink-50 dark:border-ink-800">
                        <td class="px-5 py-3">
                            <a href="{{ route('client.tickets.show', $ticket) }}" class="font-medium text-brand-700">{{ $ticket->number }}</a>
                            <p class="text-xs text-ink-500">{{ $ticket->subject }}</p>
                        </td>
                        <td class="px-5 py-3"><x-badge :tone="$ticket->priority->tone()">{{ $ticket->priority->label() }}</x-badge></td>
                        <td class="px-5 py-3"><x-badge :tone="$ticket->slaTone()">{{ $ticket->slaLabel() }}</x-badge></td>
                        <td class="px-5 py-3"><x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty-state title="No tickets" description="Open a ticket if you need help." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $tickets->links() }}</div>
@endsection
