<div class="card p-6">
    <form wire:submit="save" class="space-y-5">
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
                <label class="label">Converted quotation</label>
                <select wire:model="form.quotation_id" class="input">
                    <option value="">None</option>
                    @foreach ($quotations as $quotation)
                        <option value="{{ $quotation->id }}">{{ $quotation->number }} · {{ $quotation->title }}</option>
                    @endforeach
                </select>
                @error('form.quotation_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="label">Project name</label>
            <input type="text" wire:model="form.name" class="input">
            @error('form.name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Description</label>
            <textarea wire:model="form.description" rows="4" class="input"></textarea>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label">Project manager</label>
                <select wire:model="form.manager_id" class="input">
                    <option value="">Unassigned</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Budget</label>
                <input type="number" step="0.01" wire:model="form.budget" class="input">
            </div>
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="label">Status</label>
                <select wire:model="form.status" class="input">
                    @foreach ($statuses as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Health</label>
                <select wire:model="form.health" class="input">
                    @foreach ($healths as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Priority</label>
                <select wire:model="form.priority" class="input">
                    @foreach ($priorities as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="label">Client approval mode</label>
            <select wire:model="form.client_approval_mode" class="input">
                @foreach ($approvalModes as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-400">Strict gates required stages until the client approves. Flexible still allows review without blocking later stages unless a stage explicitly requires approval.</p>
            @error('form.client_approval_mode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="label">Start date</label><input type="date" wire:model="form.start_date" class="input"></div>
            <div><label class="label">Expected end</label><input type="date" wire:model="form.expected_end_date" class="input"></div>
            <div><label class="label">Actual completion</label><input type="date" wire:model="form.actual_completion_date" class="input"></div>
        </div>
        <div>
            <label class="label">Notes</label>
            <textarea wire:model="form.notes" rows="3" class="input"></textarea>
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('projects.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save project</button>
        </div>
    </form>
</div>
