@extends('layouts.client')

@section('content')
    <x-page-header title="Documents" description="Files shared with your account." />

    <form method="GET" class="mb-4">
        <input type="search" name="q" value="{{ $search }}" placeholder="Search documents" class="input max-w-xs">
        <button class="btn-secondary ml-2">Search</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 text-xs uppercase text-ink-500 dark:border-ink-800">
                <tr>
                    <th class="px-5 py-3">Document</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr class="border-b border-ink-50 dark:border-ink-800">
                        <td class="px-5 py-3">
                            <p class="font-medium">{{ $document->title }}</p>
                            <p class="text-xs text-ink-500">{{ $document->number }} · {{ $document->versionLabel() }}</p>
                        </td>
                        <td class="px-5 py-3">{{ $document->type?->name ?: '—' }}</td>
                        <td class="px-5 py-3"><x-badge :tone="$document->status->tone()">{{ $document->status->label() }}</x-badge></td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('client.documents.download', $document) }}" class="text-brand-700">Download</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty-state title="No documents" description="Approved documents linked to your account will appear here." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $documents->links() }}</div>
@endsection
