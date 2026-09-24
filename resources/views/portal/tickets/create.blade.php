@extends('layouts.client')

@section('content')
    <x-page-header title="New ticket" description="Tell us what you need help with." :breadcrumbs="['Tickets' => route('client.tickets.index'), 'New ticket' => null]" />

    <form method="POST" action="{{ route('client.tickets.store') }}" enctype="multipart/form-data" class="card max-w-2xl space-y-4 p-6" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        <div>
            <label class="label">Subject</label>
            <input name="subject" value="{{ old('subject') }}" required class="input">
            @error('subject') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Project (optional)</label>
            <select name="project_id" class="input">
                <option value="">None</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected(old('project_id') === $project->id)>{{ $project->number }} · {{ $project->name }}</option>
                @endforeach
            </select>
            @error('project_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Category</label>
            <select name="category_id" class="input">
                <option value="">None</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id') === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Priority</label>
            <select name="priority" class="input">
                @foreach ($priorities as $priority)
                    <option value="{{ $priority->value }}" @selected(old('priority', 'normal') === $priority->value)>{{ $priority->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Description</label>
            <textarea name="description" rows="6" required class="input">{{ old('description') }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Attachment</label>
            <input type="file" name="attachment" class="input">
        </div>
        <button class="btn-primary" :disabled="loading">Submit ticket</button>
    </form>
@endsection
