@extends('layouts.client')

@section('content')
    <x-page-header title="{{ $changeRequest->number }}" :description="$changeRequest->title" :breadcrumbs="['Change requests' => route('client.change-requests.index'), $changeRequest->number => null]" />

    <div class="card max-w-3xl space-y-4 p-6 text-sm">
        <div class="flex items-center justify-between">
            <p>{{ $changeRequest->project?->number }} · {{ $changeRequest->project?->name }}</p>
            <x-badge :tone="$changeRequest->status->tone()">{{ $changeRequest->status->label() }}</x-badge>
        </div>
        <p class="whitespace-pre-wrap text-ink-600">{{ $changeRequest->description }}</p>
        @if ($changeRequest->impact_on_timeline_days)
            <p>Estimated timeline impact: {{ $changeRequest->impact_on_timeline_days }} days</p>
        @endif
        <p class="text-ink-500">Submitted {{ $changeRequest->created_at?->format(settings('company.date_format', 'd M Y').' H:i') }}</p>
    </div>
@endsection
