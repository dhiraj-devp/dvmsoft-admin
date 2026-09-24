@extends('layouts.client')

@section('content')
    <x-page-header title="{{ $ticket->number }}" :description="$ticket->subject" :breadcrumbs="['Tickets' => route('client.tickets.index'), $ticket->number => null]" />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4 text-sm"><p class="text-ink-500">Status</p><x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge></div>
        <div class="card p-4 text-sm"><p class="text-ink-500">Priority</p><x-badge :tone="$ticket->priority->tone()">{{ $ticket->priority->label() }}</x-badge></div>
        <div class="card p-4 text-sm">
            <p class="text-ink-500">SLA</p>
            <x-badge :tone="$ticket->slaTone()">{{ $ticket->slaLabel() }}</x-badge>
            <p class="mt-1 text-xs text-ink-500">{{ $ticket->sla_due_at?->format(settings('company.date_format', 'd M Y').' H:i') }}</p>
        </div>
        <div class="card p-4 text-sm"><p class="text-ink-500">Project</p><p class="font-medium">{{ $ticket->project?->number ?: 'None' }}</p></div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card space-y-4 p-5 xl:col-span-2">
            <h2 class="font-semibold">Conversation</h2>
            @forelse ($messages as $message)
                <article class="rounded-2xl border border-ink-200 px-4 py-3 dark:border-ink-700">
                    <div class="flex items-center justify-between text-xs text-ink-500">
                        <span class="font-medium text-ink-800 dark:text-ink-100">{{ $message->clientUser?->name ?: 'Dvmsoft team' }}</span>
                        <span>{{ $message->created_at?->format('d M Y H:i') }}</span>
                    </div>
                    <p class="mt-2 whitespace-pre-wrap text-sm">{{ $message->body }}</p>
                    @foreach ($message->attachments as $attachment)
                        <a href="{{ route('client.tickets.attachments.download', [$ticket, $message, $attachment]) }}" class="mt-2 inline-block text-sm font-medium text-brand-700">{{ $attachment->original_name }} ({{ $attachment->humanSize() }})</a>
                    @endforeach
                </article>
            @empty
                <x-empty-state title="No replies yet" />
            @endforelse

            @if ($ticket->status !== \App\Enums\TicketStatus::Closed)
                <form method="POST" action="{{ route('client.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <label class="label">Reply</label>
                    <textarea name="body" rows="4" required class="input">{{ old('body') }}</textarea>
                    @error('body') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('status') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <input type="file" name="attachment" class="input">
                    <button class="btn-primary">Send reply</button>
                </form>
            @endif
        </section>
        <section class="card p-5 text-sm">
            <h2 class="font-semibold">Details</h2>
            <p class="mt-3 whitespace-pre-wrap text-ink-600">{{ $ticket->description }}</p>
            @if ($ticket->resolution)
                <h3 class="mt-5 font-semibold">Resolution</h3>
                <p class="mt-2 whitespace-pre-wrap">{{ $ticket->resolution }}</p>
            @endif
        </section>
    </div>
@endsection
