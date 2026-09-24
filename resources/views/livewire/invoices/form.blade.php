<div class="card p-6">
    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3">
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
                <label class="label">Project</label>
                <select wire:model.live="form.project_id" class="input">
                    <option value="">None</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->number }} · {{ $project->name }}</option>
                    @endforeach
                </select>
                @error('form.project_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Quotation</label>
                <select wire:model.live="form.quotation_id" class="input">
                    <option value="">None</option>
                    @foreach ($quotations as $quotation)
                        <option value="{{ $quotation->id }}">{{ $quotation->number }}</option>
                    @endforeach
                </select>
                @error('form.quotation_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="label">Title</label>
            <input type="text" wire:model="form.title" class="input">
            @error('form.title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="label">Invoice date</label>
                <input type="date" wire:model="form.invoice_date" class="input">
            </div>
            <div>
                <label class="label">Due date</label>
                <input type="date" wire:model="form.due_date" class="input">
            </div>
            <div>
                <label class="label">Payment terms</label>
                <select wire:model="form.payment_terms" class="input">
                    @foreach ($paymentTerms as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Header discount %</label>
                <input type="number" step="0.01" wire:model.live="form.discount_percent" class="input">
            </div>
        </div>

        <div>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold">Line items</h3>
                <button type="button" class="btn-secondary" wire:click="addItem">Add line</button>
            </div>
            <div class="space-y-3">
                @foreach ($items as $index => $item)
                    <div wire:key="item-{{ $index }}" class="grid gap-3 rounded-2xl border border-ink-200 p-4 dark:border-ink-700 md:grid-cols-12">
                        <div class="md:col-span-4">
                            <label class="label">Description</label>
                            <input type="text" wire:model="items.{{ $index }}.description" class="input">
                            @error('items.'.$index.'.description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="label">Qty</label>
                            <input type="number" step="0.01" wire:model.live="items.{{ $index }}.quantity" class="input">
                        </div>
                        <div class="md:col-span-2">
                            <label class="label">Unit price</label>
                            <input type="number" step="0.01" wire:model.live="items.{{ $index }}.unit_price" class="input">
                        </div>
                        <div class="md:col-span-1">
                            <label class="label">Disc %</label>
                            <input type="number" step="0.01" wire:model.live="items.{{ $index }}.discount_percent" class="input">
                        </div>
                        <div class="md:col-span-2">
                            <label class="label">Tax %</label>
                            <input type="number" step="0.01" wire:model.live="items.{{ $index }}.tax_percent" class="input">
                        </div>
                        <div class="flex items-end md:col-span-1">
                            <button type="button" class="btn-secondary w-full" wire:click="removeItem({{ $index }})">×</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <label class="label">Notes</label>
            <textarea wire:model="form.notes" rows="3" class="input"></textarea>
        </div>

        <div class="flex flex-col gap-2 rounded-2xl bg-ink-50 p-4 text-sm dark:bg-ink-950 sm:ml-auto sm:w-80">
            <div class="flex justify-between"><span>Subtotal</span><span>{{ money($totals['subtotal']) }}</span></div>
            <div class="flex justify-between"><span>Discount</span><span>- {{ money($totals['discount_amount']) }}</span></div>
            <div class="flex justify-between"><span>Tax / GST</span><span>{{ money($totals['tax_amount']) }}</span></div>
            <div class="flex justify-between text-base font-semibold"><span>Total</span><span>{{ money($totals['total']) }}</span></div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('invoices.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save invoice</button>
        </div>
    </form>
</div>
