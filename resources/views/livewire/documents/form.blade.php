<div class="card p-6">
    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="label">Title</label>
                <input type="text" wire:model="form.title" class="input">
                @error('form.title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Document type</label>
                <select wire:model="form.document_type_id" class="input">
                    <option value="">Select type</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
                @error('form.document_type_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Owner</label>
                <select wire:model="form.owner_id" class="input">
                    <option value="">Me / unassigned</option>
                    @foreach ($owners as $owner)
                        <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Expiry date</label>
                <input type="date" wire:model="form.expiry_date" class="input">
            </div>
            @unless ($document)
                <div>
                    <label class="label">File</label>
                    <input type="file" wire:model="upload" class="input">
                    <p class="mt-1 text-xs text-ink-400">Stored privately. No public URL is created.</p>
                    @error('upload')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            @endunless
            <div>
                <label class="label">Client (optional)</label>
                <select wire:model.live="form.client_id" class="input">
                    <option value="">None</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
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
                <label class="label">Employee (optional)</label>
                <select wire:model="form.employee_id" class="input">
                    <option value="">None</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Quotation (optional)</label>
                <select wire:model="form.quotation_id" class="input">
                    <option value="">None</option>
                    @foreach ($quotations as $quotation)
                        <option value="{{ $quotation->id }}">{{ $quotation->number }} · {{ $quotation->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Invoice (optional)</label>
                <select wire:model="form.invoice_id" class="input">
                    <option value="">None</option>
                    @foreach ($invoices as $invoice)
                        <option value="{{ $invoice->id }}">{{ $invoice->number }} · {{ $invoice->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="label">Description</label>
            <textarea wire:model="form.description" rows="4" class="input"></textarea>
        </div>
        <div>
            <label class="label">Notes</label>
            <textarea wire:model="form.notes" rows="3" class="input"></textarea>
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('documents.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save document</button>
        </div>
    </form>
</div>
