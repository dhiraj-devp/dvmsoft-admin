@extends('layouts.client')

@section('content')
    <x-page-header title="Submit a change request" :breadcrumbs="['Change requests' => route('client.change-requests.index'), 'New' => null]" />

    <form method="POST" action="{{ route('client.change-requests.store') }}" class="card max-w-2xl space-y-4 p-6">
        @csrf
        <div>
            <label class="label">Project</label>
            <select name="project_id" required class="input">
                <option value="">Select a project</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected(old('project_id') === $project->id)>{{ $project->number }} · {{ $project->name }}</option>
                @endforeach
            </select>
            @error('project_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Title</label>
            <input name="title" value="{{ old('title') }}" required class="input">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Description</label>
            <textarea name="description" rows="6" required class="input">{{ old('description') }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Estimated extra days (optional)</label>
            <input type="number" min="0" name="impact_on_timeline_days" value="{{ old('impact_on_timeline_days') }}" class="input">
        </div>
        <button class="btn-primary">Submit</button>
    </form>
@endsection
