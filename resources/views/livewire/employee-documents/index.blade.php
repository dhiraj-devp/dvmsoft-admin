<div class="space-y-6">
    @if ($missing !== [])
        <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100">
            Missing required documents:
            {{ collect($missing)->map(fn ($type) => config('hr.document_types.'.$type, $type))->join(', ') }}
        </div>
    @endif

    @can('create', App\Models\EmployeeDocument::class)
        <form wire:submit="save" class="card grid gap-4 p-5 md:grid-cols-2">
            <div>
                <label class="label">Type</label>
                <select wire:model="form.type" class="input">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Title</label>
                <input type="text" wire:model="form.title" class="input">
                @error('form.title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Status</label>
                <select wire:model="form.status" class="input">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Expiry date</label>
                <input type="date" wire:model="form.expiry_date" class="input">
            </div>
            <div class="md:col-span-2">
                <label class="label">Notes</label>
                <textarea wire:model="form.notes" rows="2" class="input"></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="label">File (private storage)</label>
                <input type="file" wire:model="upload" class="input">
                @error('upload')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="btn-primary">Upload document</button>
            </div>
        </form>
    @endcan

    <div class="table-wrap">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-ink-50 text-xs uppercase text-ink-500 dark:bg-ink-950">
                <tr>
                    <th class="px-4 py-3">Document</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Expiry</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                @forelse ($documents as $document)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $document->title }}</div>
                            <div class="text-xs text-ink-500">{{ $document->original_name }} · {{ $document->humanSize() }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $document->type->label() }}</td>
                        <td class="px-4 py-3"><x-badge :tone="$document->status->tone()">{{ $document->status->label() }}</x-badge></td>
                        <td class="px-4 py-3">{{ $document->expiry_date?->format('d M Y') ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('employees.documents.download', [$employee, $document]) }}" class="text-sm font-medium text-brand-700">Download</a>
                            @can('delete', $document)
                                <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $document->id }}', event: 'confirmed-delete-employee-document', title: 'Delete document?', message: 'The private file will remain until permanently purged.' })">Delete</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="No documents uploaded" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
