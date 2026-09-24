@extends('layouts.client')

@section('content')
    <x-page-header title="Change requests" description="Requested changes on your projects.">
        <x-slot:actions>
            <a href="{{ route('client.change-requests.create') }}" class="btn-primary">Submit request</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4">
        <select name="status" class="input max-w-xs">
            <option value="">All statuses</option>
            @foreach (\App\Enums\ChangeRequestStatus::cases() as $item)
                <option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>
            @endforeach
        </select>
        <button class="btn-secondary ml-2">Filter</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 text-xs uppercase text-ink-500 dark:border-ink-800">
                <tr>
                    <th class="px-5 py-3">Request</th>
                    <th class="px-5 py-3">Project</th>
                    <th class="px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($changeRequests as $changeRequest)
                    <tr class="border-b border-ink-50 dark:border-ink-800">
                        <td class="px-5 py-3">
                            <a href="{{ route('client.change-requests.show', $changeRequest) }}" class="font-medium text-brand-700">{{ $changeRequest->number }}</a>
                            <p class="text-xs text-ink-500">{{ $changeRequest->title }}</p>
                        </td>
                        <td class="px-5 py-3">{{ $changeRequest->project?->number }}</td>
                        <td class="px-5 py-3"><x-badge :tone="$changeRequest->status->tone()">{{ $changeRequest->status->label() }}</x-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="3"><x-empty-state title="No change requests" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $changeRequests->links() }}</div>
@endsection
