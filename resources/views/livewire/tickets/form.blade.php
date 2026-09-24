<div class="card p-6">
    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label">Client</label>
                <select wire:model.live="form.client_id" class="input">
                    <option value="">Select client</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
                @error('form.client_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Project (optional)</label>
                <select wire:model="form.project_id" class="input">
                    <option value="">None</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->number }} · {{ $project->name }}</option>
                    @endforeach
                </select>
                @error('form.project_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Category</label>
                <select wire:model="form.category_id" class="input">
                    <option value="">None</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Priority</label>
                <select wire:model="form.priority" class="input">
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Assign to</label>
                <select wire:model="form.assigned_to_id" class="input">
                    <option value="">Unassigned</option>
                    @foreach ($assignees as $assignee)
                        <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                    @endforeach
                </select>
            </div>
            @unless ($ticket)
                <div>
                    <label class="label">Attachment</label>
                    <input type="file" wire:model="upload" class="input">
                    @error('upload')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            @endunless
        </div>
        <div>
            <label class="label">Subject</label>
            <input type="text" wire:model="form.subject" class="input">
            @error('form.subject')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Description</label>
            <textarea wire:model="form.description" rows="6" class="input"></textarea>
            @error('form.description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('tickets.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save ticket</button>
        </div>
    </form>
</div>
